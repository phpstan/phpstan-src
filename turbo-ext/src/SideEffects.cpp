/*
 * PHPStanTurbo\SideEffects — native implementation of
 * PHPStan\Reflection\SideEffects.
 *
 * A final @api value class: function and method reflections create one in
 * getSideEffects() and the handlers ask it what a call does. The twin's
 * constructor is private; instances come from none() / all() / unknown() /
 * create(). State lives in the twin's four promoted property slots, in its
 * order; native callers ask through pt_side_effects_*().
 */

#include "support.h"
#include "generated/SideEffects.h"
#include "Engine.h"

namespace slots = ptdecl::SideEffects::slot;
namespace sigs = ptdecl::SideEffects::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_side_effects = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Reflection\SideEffects. */
class SideEffects
{
public:
	explicit SideEffects(zend_object *self) : self(self) {}

	/* the private constructor's body */
	void construct(zval *effects, zval *others, zval *calledParameters, zval *writtenParameters) const
	{
		pt_write_slot(self, slots::effects, effects);
		pt_write_slot(self, slots::others, others);
		pt_write_slot(self, slots::calledParameters, calledParameters);
		pt_write_slot(self, slots::writtenParameters, writtenParameters);
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val newSelf(zval *effects, zval *others, zval *calledParameters, zval *writtenParameters)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_side_effects) != SUCCESS)) return zv::Val();
		SideEffects(Z_OBJ(object)).construct(effects, others, calledParameters, writtenParameters);
		return zv::Val::adopt(object);
	}

	/* new self([], <others>, [], []) */
	static zv::Val withOthers(zend_long others)
	{
		zval empty;
		ZVAL_EMPTY_ARRAY(&empty);
		return newSelf(&empty, pt_trinary_singleton(others), &empty, &empty);
	}

	static zv::Val none() { return withOthers(PT_TRI_NO); }
	static zv::Val all() { return withOthers(PT_TRI_YES); }
	static zv::Val unknown() { return withOthers(PT_TRI_MAYBE); }

	/* create($effects, $others, $calledParameters = [], $writtenParameters = []); the arrays NULL when omitted */
	static zv::Val create(zval *effects, zval *others, zval *calledParameters, zval *writtenParameters)
	{
		zval empty;
		ZVAL_EMPTY_ARRAY(&empty);
		return newSelf(effects, others, calledParameters != NULL ? calledParameters : &empty, writtenParameters != NULL ? writtenParameters : &empty);
	}

	/* effects !== [] ? Yes : (others is No and called/written parameters !== [] ? Maybe : others) */
	zv::Val hasAny() const
	{
		zval *effects = pt_typed_slot(self, slots::effects, self->ce, "effects");
		if (UNEXPECTED(effects == NULL)) return zv::Val();
		if (zend_hash_num_elements(Z_ARRVAL_P(effects)) > 0) return zv::Val::copyOf(zv::Ref(pt_trinary_singleton(PT_TRI_YES)));
		zv::Val others = read(slots::others, "others");
		if (UNEXPECTED(others.isUndef())) return zv::Val();
		if (pt_trinary_value(Z_OBJ_P(others.raw())) == PT_TRI_NO) {
			zval *calledParameters = pt_typed_slot(self, slots::calledParameters, self->ce, "calledParameters");
			if (UNEXPECTED(calledParameters == NULL)) return zv::Val();
			zval *writtenParameters = pt_typed_slot(self, slots::writtenParameters, self->ce, "writtenParameters");
			if (UNEXPECTED(writtenParameters == NULL)) return zv::Val();
			if (zend_hash_num_elements(Z_ARRVAL_P(calledParameters)) > 0 || zend_hash_num_elements(Z_ARRVAL_P(writtenParameters)) > 0) {
				return zv::Val::copyOf(zv::Ref(pt_trinary_singleton(PT_TRI_MAYBE)));
			}
		}
		return others;
	}

	/* any effect "nondeterministic" or "nondeterministic.*" ? No : others->negate() */
	zv::Val isDeterministic() const
	{
		zend_long value = isDeterministicValue();
		if (UNEXPECTED(value < 0)) return zv::Val();
		return zv::Val::copyOf(zv::Ref(pt_trinary_singleton(value)));
	}

	/* isDeterministic() as PT_TRI_*; -1 = pending exception */
	zend_long isDeterministicValue() const
	{
		zval *effects = pt_typed_slot(self, slots::effects, self->ce, "effects");
		if (UNEXPECTED(effects == NULL)) return -1;
		zval *effect;
		ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(effects), effect) {
			ZVAL_DEREF(effect);
			if (Z_TYPE_P(effect) != IS_STRING) {
				zend_type_error("str_starts_with(): Argument #1 ($haystack) must be of type string, %s given", zend_zval_value_name(effect));
				return -1;
			}
			if (zend_string_equals_literal(Z_STR_P(effect), "nondeterministic")
				|| (Z_STRLEN_P(effect) >= sizeof("nondeterministic.") - 1 && memcmp(Z_STRVAL_P(effect), "nondeterministic.", sizeof("nondeterministic.") - 1) == 0)) {
				return PT_TRI_NO;
			}
		} ZEND_HASH_FOREACH_END();
		zval *others = pt_typed_slot(self, slots::others, self->ce, "others");
		if (UNEXPECTED(others == NULL)) return -1;
		zend_long othersValue = pt_trinary_value(Z_OBJ_P(others));
		return othersValue == PT_TRI_YES ? PT_TRI_NO : (othersValue == PT_TRI_NO ? PT_TRI_YES : PT_TRI_MAYBE);
	}

	zv::Val getEffectNames() const { return read(slots::effects, "effects"); }
	zv::Val getCalledParameters() const { return read(slots::calledParameters, "calledParameters"); }
	zv::Val getWrittenParameters() const { return read(slots::writtenParameters, "writtenParameters"); }

	/* $this->writtenParameters[$parameterName] ?? $this->others */
	zv::Val writesParameter(zend_string *parameterName) const
	{
		zval *writtenParameters = pt_typed_slot(self, slots::writtenParameters, self->ce, "writtenParameters");
		if (UNEXPECTED(writtenParameters == NULL)) return zv::Val();
		zval *written = zend_symtable_find(Z_ARRVAL_P(writtenParameters), parameterName);
		if (written != NULL) {
			ZVAL_DEREF(written);
			if (Z_TYPE_P(written) != IS_NULL) {
				/* the twin's TrinaryLogic return type */
				if (UNEXPECTED(Z_TYPE_P(written) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(written), pt_ce_trinary))) {
					zend_type_error("PHPStan\\Reflection\\SideEffects::writesParameter(): Return value must be of type PHPStan\\TrinaryLogic, %s returned", zend_zval_value_name(written));
					return zv::Val();
				}
				return zv::Val::copyOf(zv::Ref(written));
			}
		}
		return read(slots::others, "others");
	}

private:
	zend_object *self;

	/* a copy of the typed slot; UNDEF with the uninitialized-read Error pending */
	zv::Val read(uint32_t index, const char *name) const
	{
		zval *value = pt_typed_slot(self, index, self->ce, name);
		return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
	}
};

} // namespace phpstanturbo

using phpstanturbo::SideEffects;

/* {{{ exported helpers: the shadowing class for native callers */

/* the PT_TRI_* value of a TrinaryLogic answer; -1 = pending exception */
static zend_long pt_side_effects_trinary(zv::Val answer)
{
	if (UNEXPECTED(answer.isUndef())) return -1;
	return pt_trinary_value(Z_OBJ_P(answer.raw()));
}

zend_long pt_side_effects_has_any(zend_object *sideEffects)
{
	return pt_side_effects_trinary(SideEffects(sideEffects).hasAny());
}

/* $reflection->getSideEffects()->isDeterministic()->negate() for a
 * FunctionReflection or an ExtendedMethodReflection; -1 = pending exception */
static pt_method_site pt_se_function_get_side_effects_site;

static zend_long pt_side_effects_result_varies_of(zv::Val sideEffects)
{
	if (UNEXPECTED(sideEffects.isUndef())) return -1;
	/* getSideEffects(): SideEffects, a final class only the native one implements */
	if (UNEXPECTED(Z_TYPE_P(sideEffects.raw()) != IS_OBJECT || Z_OBJCE_P(sideEffects.raw()) != pt_ce_side_effects)) {
		zend_throw_error(NULL, "Call to a member function isDeterministic() on %s", zend_zval_value_name(sideEffects.raw()));
		return -1;
	}
	zend_long deterministic = SideEffects(Z_OBJ_P(sideEffects.raw())).isDeterministicValue();
	if (UNEXPECTED(deterministic < 0)) return -1;
	return deterministic == PT_TRI_YES ? PT_TRI_NO : (deterministic == PT_TRI_NO ? PT_TRI_YES : PT_TRI_MAYBE);
}

zend_long pt_function_reflection_result_varies(zval *functionReflection)
{
	if (UNEXPECTED(Z_TYPE_P(functionReflection) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function getSideEffects() on %s", zend_zval_value_name(functionReflection));
		return -1;
	}
	return pt_side_effects_result_varies_of(pt_call_method_cached(pt_se_function_get_side_effects_site, Z_OBJ_P(functionReflection), PT_LC("getsideeffects"), 0, NULL));
}

zend_long pt_method_reflection_result_varies(zval *methodReflection)
{
	if (UNEXPECTED(Z_TYPE_P(methodReflection) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function getSideEffects() on %s", zend_zval_value_name(methodReflection));
		return -1;
	}
	return pt_side_effects_result_varies_of(pt_extended_method_reflection_call(methodReflection, PT_MR_GET_SIDE_EFFECTS));
}

static pt_method_site pt_se_is_deterministic_site;

zend_long pt_side_effects_is_deterministic(zend_object *sideEffects)
{
	if (EXPECTED(sideEffects->ce == pt_ce_side_effects)) return SideEffects(sideEffects).isDeterministicValue();
	/* a twin from a test that mixes both implementations */
	return pt_side_effects_trinary(pt_call_method_cached(pt_se_is_deterministic_site, sideEffects, PT_LC("isdeterministic"), 0, NULL));
}

zend_long pt_side_effects_writes_parameter(zend_object *sideEffects, zend_string *parameterName)
{
	return pt_side_effects_trinary(SideEffects(sideEffects).writesParameter(parameterName));
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_side_effects)
{
	reg::Class cls("PHPStan\\Reflection\\SideEffects");
	ptdecl::SideEffects::declareClass(cls);
	ptdecl::SideEffects::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *effects, *others, *calledParameters, *writtenParameters;
		ZEND_PARSE_PARAMETERS_START(4, 4)
			Z_PARAM_ARRAY(effects)
			Z_PARAM_OBJECT_OF_CLASS(others, pt_ce_trinary)
			Z_PARAM_ARRAY(calledParameters)
			Z_PARAM_ARRAY(writtenParameters)
		ZEND_PARSE_PARAMETERS_END();
		SideEffects(Z_OBJ_P(ZEND_THIS)).construct(effects, others, calledParameters, writtenParameters);
	});

	cls.method<&SideEffects::none>(sigs::none);
	cls.method<&SideEffects::all>(sigs::all);
	cls.method<&SideEffects::unknown>(sigs::unknown);

	cls.method(sigs::create, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *effects, *others, *calledParameters = NULL, *writtenParameters = NULL;
		ZEND_PARSE_PARAMETERS_START(2, 4)
			Z_PARAM_ARRAY(effects)
			Z_PARAM_OBJECT_OF_CLASS(others, pt_ce_trinary)
			Z_PARAM_OPTIONAL
			Z_PARAM_ARRAY(calledParameters)
			Z_PARAM_ARRAY(writtenParameters)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(SideEffects::create(effects, others, calledParameters, writtenParameters));
	});

	cls.method<&SideEffects::hasAny>(sigs::hasAny);
	cls.method<&SideEffects::isDeterministic>(sigs::isDeterministic);
	cls.method<&SideEffects::getEffectNames>(sigs::getEffectNames);
	cls.method<&SideEffects::getCalledParameters>(sigs::getCalledParameters);
	cls.method<&SideEffects::getWrittenParameters>(sigs::getWrittenParameters);
	cls.method<&SideEffects::writesParameter, zp::Str>(sigs::writesParameter);

	cls.shadow(&pt_ce_side_effects);
}

/* }}} */
