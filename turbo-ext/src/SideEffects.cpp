/*
 * PHPStanTurbo\SideEffects — native implementation of
 * PHPStan\Reflection\SideEffects.
 *
 * A final @api value class: function and method reflections create one in
 * getSideEffects() and the handlers ask it what a call does. The twin's
 * constructor is private; instances come from none() / all() / unknown() /
 * create(). State lives in the twin's five promoted property slots, in its
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

/* the built-in effect names the analysis asks about */
static constexpr char pt_se_nondeterministic[] = "nondeterministic";
static constexpr char pt_se_writes_this[] = "writes-this";
static constexpr char pt_se_writes_global[] = "writes-global";
static constexpr char pt_se_writes_args[] = "writes-args";
static constexpr char pt_se_reads_global[] = "reads-global";

namespace phpstanturbo {

/* Mirrors PHPStan\Reflection\SideEffects. */
class SideEffects
{
public:
	explicit SideEffects(zend_object *self) : self(self) {}

	/* the private constructor's body */
	void construct(zval *effects, zval *others, zval *calledParameters, zval *writtenParameters, bool readsWrittenArguments) const
	{
		pt_write_slot(self, slots::effects, effects);
		pt_write_slot(self, slots::others, others);
		pt_write_slot(self, slots::calledParameters, calledParameters);
		pt_write_slot(self, slots::writtenParameters, writtenParameters);
		zval reads;
		ZVAL_BOOL(&reads, readsWrittenArguments);
		pt_write_slot(self, slots::readsWrittenArguments, &reads);
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val newSelf(zval *effects, zval *others, zval *calledParameters, zval *writtenParameters, bool readsWrittenArguments)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_side_effects) != SUCCESS)) return zv::Val();
		SideEffects(Z_OBJ(object)).construct(effects, others, calledParameters, writtenParameters, readsWrittenArguments);
		return zv::Val::adopt(object);
	}

	/* new self([], <others>, [], []) */
	static zv::Val withOthers(zend_long others)
	{
		zval empty;
		ZVAL_EMPTY_ARRAY(&empty);
		return newSelf(&empty, pt_trinary_singleton(others), &empty, &empty, false);
	}

	static zv::Val none() { return withOthers(PT_TRI_NO); }
	static zv::Val all() { return withOthers(PT_TRI_YES); }
	static zv::Val unknown() { return withOthers(PT_TRI_MAYBE); }

	/* create($effects, $others, $calledParameters = [], $writtenParameters = [], $readsWrittenArguments = false); the arrays NULL when omitted */
	static zv::Val create(zval *effects, zval *others, zval *calledParameters, zval *writtenParameters, bool readsWrittenArguments)
	{
		zval empty;
		ZVAL_EMPTY_ARRAY(&empty);
		return newSelf(effects, others, calledParameters != NULL ? calledParameters : &empty, writtenParameters != NULL ? writtenParameters : &empty, readsWrittenArguments);
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

	/* has($effect): Yes when $effect or one of its sub-effects is listed,
	 * else $this->others, or-ed with Maybe for each listed parent of $effect */
	zv::Val has(zend_string *effect) const
	{
		zend_long value = hasValue(ZSTR_VAL(effect), ZSTR_LEN(effect));
		if (UNEXPECTED(value < 0)) return zv::Val();
		return zv::Val::copyOf(zv::Ref(pt_trinary_singleton(value)));
	}

	/* has() as PT_TRI_*; -1 = pending exception */
	zend_long hasValue(const char *effect, size_t len) const
	{
		zval *others = pt_typed_slot(self, slots::others, self->ce, "others");
		if (UNEXPECTED(others == NULL)) return -1;
		zend_long result = pt_trinary_value(Z_OBJ_P(others));
		zval *effects = pt_typed_slot(self, slots::effects, self->ce, "effects");
		if (UNEXPECTED(effects == NULL)) return -1;
		zval *listedZv;
		ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(effects), listedZv) {
			ZVAL_DEREF(listedZv);
			if (Z_TYPE_P(listedZv) != IS_STRING) {
				zend_type_error("str_starts_with(): Argument #1 ($haystack) must be of type string, %s given", zend_zval_value_name(listedZv));
				return -1;
			}
			const char *listed = Z_STRVAL_P(listedZv);
			size_t listedLen = baseNameLength(listed, Z_STRLEN_P(listedZv));
			/* $listed === $effect || str_starts_with($listed, $effect . '.') */
			if (listedLen >= len && memcmp(listed, effect, len) == 0 && (listedLen == len || listed[len] == '.')) return PT_TRI_YES;
			/* str_starts_with($effect, $listed . '.'): $result->or(Maybe) */
			if (len > listedLen && memcmp(effect, listed, listedLen) == 0 && effect[listedLen] == '.' && result == PT_TRI_NO) result = PT_TRI_MAYBE;
		} ZEND_HASH_FOREACH_END();
		return result;
	}

	/* $this->has('nondeterministic')->negate() */
	zv::Val isDeterministic() const
	{
		zend_long value = isDeterministicValue();
		if (UNEXPECTED(value < 0)) return zv::Val();
		return zv::Val::copyOf(zv::Ref(pt_trinary_singleton(value)));
	}

	/* isDeterministic() as PT_TRI_*; -1 = pending exception */
	zend_long isDeterministicValue() const
	{
		zval *readsWrittenArguments = pt_typed_slot(self, slots::readsWrittenArguments, self->ce, "readsWrittenArguments");
		if (UNEXPECTED(readsWrittenArguments == NULL)) return -1;
		if (Z_TYPE_P(readsWrittenArguments) == IS_TRUE) return PT_TRI_NO;
		zend_long has = hasValue(pt_se_nondeterministic, sizeof(pt_se_nondeterministic) - 1);
		if (UNEXPECTED(has < 0)) return -1;
		return has == PT_TRI_YES ? PT_TRI_NO : (has == PT_TRI_NO ? PT_TRI_YES : PT_TRI_MAYBE);
	}

	zv::Val getEffectNames() const { return read(slots::effects, "effects"); }

	/* in_array($effect, $this->getEffectNames(), true) as 1 / 0; -1 = pending exception */
	int listsValue(const char *effect, size_t len) const
	{
		zval *effects = pt_typed_slot(self, slots::effects, self->ce, "effects");
		if (UNEXPECTED(effects == NULL)) return -1;
		zval *listed;
		ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(effects), listed) {
			ZVAL_DEREF(listed);
			if (Z_TYPE_P(listed) == IS_STRING && Z_STRLEN_P(listed) == len && memcmp(Z_STRVAL_P(listed), effect, len) == 0) return 1;
		} ZEND_HASH_FOREACH_END();
		return 0;
	}
	zv::Val getCalledParameters() const { return read(slots::calledParameters, "calledParameters"); }
	zv::Val getWrittenParameters() const { return read(slots::writtenParameters, "writtenParameters"); }

	/* $this->writtenParameters[$parameterName], else Yes for a listed bare
	 * writes-args, else $this->others */
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
		int listsWritesArgs = listsValue(pt_se_writes_args, sizeof(pt_se_writes_args) - 1);
		if (UNEXPECTED(listsWritesArgs < 0)) return zv::Val();
		if (listsWritesArgs == 1) return zv::Val::copyOf(zv::Ref(pt_trinary_singleton(PT_TRI_YES)));
		return read(slots::others, "others");
	}

	/* strlen(baseName($listed)): the name without its parenthesised parameters */
	static size_t baseNameLength(const char *listed, size_t len)
	{
		const char *paren = static_cast<const char *>(memchr(listed, '(', len));
		return paren == NULL ? len : static_cast<size_t>(paren - listed);
	}

	/* hasOnly($effects) */
	zv::Val hasOnly(HashTable *allowed) const
	{
		zval *others = pt_typed_slot(self, slots::others, self->ce, "others");
		if (UNEXPECTED(others == NULL)) return zv::Val();
		zend_long othersValue = pt_trinary_value(Z_OBJ_P(others));
		if (othersValue == PT_TRI_YES) return trinary(PT_TRI_NO);
		/* $this->others->negate() */
		zend_long result = othersValue == PT_TRI_NO ? PT_TRI_YES : PT_TRI_MAYBE;

		zval *effects = pt_typed_slot(self, slots::effects, self->ce, "effects");
		if (UNEXPECTED(effects == NULL)) return zv::Val();
		zval *listedZv;
		ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(effects), listedZv) {
			ZVAL_DEREF(listedZv);
			if (Z_TYPE_P(listedZv) != IS_STRING) {
				zend_type_error("PHPStan\\Reflection\\SideEffects::baseName(): Argument #1 ($listed) must be of type string, %s given", zend_zval_value_name(listedZv));
				return zv::Val();
			}
			const char *listed = Z_STRVAL_P(listedZv);
			size_t listedLen = baseNameLength(listed, Z_STRLEN_P(listedZv));
			zend_long listedResult = PT_TRI_NO;
			zval *effectZv;
			ZEND_HASH_FOREACH_VAL(allowed, effectZv) {
				ZVAL_DEREF(effectZv);
				if (Z_TYPE_P(effectZv) != IS_STRING) {
					zend_type_error("str_starts_with(): Argument #2 ($needle) must be of type string, %s given", zend_zval_value_name(effectZv));
					return zv::Val();
				}
				const char *effect = Z_STRVAL_P(effectZv);
				size_t len = Z_STRLEN_P(effectZv);
				/* $listed === $effect || str_starts_with($listed, $effect . '.') */
				if (listedLen >= len && memcmp(listed, effect, len) == 0 && (listedLen == len || listed[len] == '.')) {
					listedResult = PT_TRI_YES;
					break;
				}
				/* str_starts_with($effect, $listed . '.') */
				if (len > listedLen && memcmp(effect, listed, listedLen) == 0 && effect[listedLen] == '.') listedResult = PT_TRI_MAYBE;
			} ZEND_HASH_FOREACH_END();
			result = trinaryAnd(result, listedResult);
		} ZEND_HASH_FOREACH_END();

		bool allowsWritesArgs = false;
		zval *effectZv;
		ZEND_HASH_FOREACH_VAL(allowed, effectZv) {
			if (Z_TYPE_P(effectZv) == IS_STRING && Z_STRLEN_P(effectZv) == sizeof(pt_se_writes_args) - 1 && memcmp(Z_STRVAL_P(effectZv), pt_se_writes_args, sizeof(pt_se_writes_args) - 1) == 0) {
				allowsWritesArgs = true;
				break;
			}
		} ZEND_HASH_FOREACH_END();
		if (!allowsWritesArgs) {
			zval *writtenParameters = pt_typed_slot(self, slots::writtenParameters, self->ce, "writtenParameters");
			if (UNEXPECTED(writtenParameters == NULL)) return zv::Val();
			zval *written;
			ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(writtenParameters), written) {
				ZVAL_DEREF(written);
				if (UNEXPECTED(Z_TYPE_P(written) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(written), pt_ce_trinary))) {
					zend_throw_error(NULL, "Call to a member function negate() on %s", zend_zval_value_name(written));
					return zv::Val();
				}
				zend_long writtenValue = pt_trinary_value(Z_OBJ_P(written));
				result = trinaryAnd(result, writtenValue == PT_TRI_YES ? PT_TRI_NO : (writtenValue == PT_TRI_NO ? PT_TRI_YES : PT_TRI_MAYBE));
			} ZEND_HASH_FOREACH_END();
		}

		return trinary(result);
	}

private:
	zend_object *self;

	static zv::Val trinary(zend_long value) { return zv::Val::copyOf(zv::Ref(pt_trinary_singleton(value))); }

	/* TrinaryLogic::and() */
	static zend_long trinaryAnd(zend_long a, zend_long b)
	{
		if (a == PT_TRI_NO || b == PT_TRI_NO) return PT_TRI_NO;
		if (a == PT_TRI_MAYBE || b == PT_TRI_MAYBE) return PT_TRI_MAYBE;
		return PT_TRI_YES;
	}

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

zend_long pt_method_reflection_writes_receiver(zval *methodReflection)
{
	if (UNEXPECTED(Z_TYPE_P(methodReflection) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function getSideEffects() on %s", zend_zval_value_name(methodReflection));
		return -1;
	}
	zv::Val sideEffects = pt_extended_method_reflection_call(methodReflection, PT_MR_GET_SIDE_EFFECTS);
	if (UNEXPECTED(sideEffects.isUndef())) return -1;
	if (UNEXPECTED(Z_TYPE_P(sideEffects.raw()) != IS_OBJECT || Z_OBJCE_P(sideEffects.raw()) != pt_ce_side_effects)) {
		zend_throw_error(NULL, "Call to a member function has() on %s", zend_zval_value_name(sideEffects.raw()));
		return -1;
	}
	SideEffects view(Z_OBJ_P(sideEffects.raw()));
	zend_long writesThis = view.hasValue(pt_se_writes_this, sizeof(pt_se_writes_this) - 1);
	if (UNEXPECTED(writesThis < 0)) return -1;
	zend_long writesGlobal = view.hasValue(pt_se_writes_global, sizeof(pt_se_writes_global) - 1);
	if (UNEXPECTED(writesGlobal < 0)) return -1;
	/* TrinaryLogic::or() */
	if (writesThis == PT_TRI_YES || writesGlobal == PT_TRI_YES) return PT_TRI_YES;
	if (writesThis == PT_TRI_MAYBE || writesGlobal == PT_TRI_MAYBE) return PT_TRI_MAYBE;
	return PT_TRI_NO;
}

/* the object a getSideEffects() call answered, NULL with an Error pending
 * when it is not an object; a twin from a test that mixes both
 * implementations is asked through its methods */
static zend_object *pt_se_side_effects_of(zv::Val &sideEffects, const char *nextCall)
{
	if (UNEXPECTED(sideEffects.isUndef())) return NULL;
	if (UNEXPECTED(Z_TYPE_P(sideEffects.raw()) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", nextCall, zend_zval_value_name(sideEffects.raw()));
		return NULL;
	}
	return Z_OBJ_P(sideEffects.raw());
}

static pt_method_site pt_se_has_site;
static pt_method_site pt_se_get_effect_names_site;

/* in_array('writes-global', $sideEffects->getEffectNames(), true) as 1 / 0; -1 = pending exception */
static int pt_se_lists_writes_global(zend_object *object)
{
	if (EXPECTED(object->ce == pt_ce_side_effects)) return SideEffects(object).listsValue(pt_se_writes_global, sizeof(pt_se_writes_global) - 1);
	zv::Val names = pt_call_method_cached(pt_se_get_effect_names_site, object, PT_LC("geteffectnames"), 0, NULL);
	if (UNEXPECTED(names.isUndef())) return -1;
	if (Z_TYPE_P(names.raw()) != IS_ARRAY) return 0;
	zval *listed;
	ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(names.raw()), listed) {
		ZVAL_DEREF(listed);
		if (Z_TYPE_P(listed) == IS_STRING && Z_STRLEN_P(listed) == sizeof(pt_se_writes_global) - 1 && memcmp(Z_STRVAL_P(listed), pt_se_writes_global, sizeof(pt_se_writes_global) - 1) == 0) return 1;
	} ZEND_HASH_FOREACH_END();
	return 0;
}

zend_long pt_function_reflection_reads_global(zval *functionReflection)
{
	if (UNEXPECTED(Z_TYPE_P(functionReflection) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function getSideEffects() on %s", zend_zval_value_name(functionReflection));
		return -1;
	}
	zv::Val sideEffects = pt_call_method_cached(pt_se_function_get_side_effects_site, Z_OBJ_P(functionReflection), PT_LC("getsideeffects"), 0, NULL);
	zend_object *object = pt_se_side_effects_of(sideEffects, "has");
	if (UNEXPECTED(object == NULL)) return -1;
	if (EXPECTED(object->ce == pt_ce_side_effects)) return SideEffects(object).hasValue(pt_se_reads_global, sizeof(pt_se_reads_global) - 1);
	zval effect;
	ZVAL_STRINGL(&effect, pt_se_reads_global, sizeof(pt_se_reads_global) - 1);
	zend_long result = pt_side_effects_trinary(pt_call_method_cached(pt_se_has_site, object, PT_LC("has"), 1, &effect));
	zval_ptr_dtor(&effect);
	return result;
}

int pt_function_reflection_lists_writes_global(zval *functionReflection)
{
	if (UNEXPECTED(Z_TYPE_P(functionReflection) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function getSideEffects() on %s", zend_zval_value_name(functionReflection));
		return -1;
	}
	zv::Val sideEffects = pt_call_method_cached(pt_se_function_get_side_effects_site, Z_OBJ_P(functionReflection), PT_LC("getsideeffects"), 0, NULL);
	zend_object *object = pt_se_side_effects_of(sideEffects, "getEffectNames");
	if (UNEXPECTED(object == NULL)) return -1;
	return pt_se_lists_writes_global(object);
}

int pt_method_reflection_lists_writes_global(zval *methodReflection)
{
	if (UNEXPECTED(Z_TYPE_P(methodReflection) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function getSideEffects() on %s", zend_zval_value_name(methodReflection));
		return -1;
	}
	zv::Val sideEffects = pt_extended_method_reflection_call(methodReflection, PT_MR_GET_SIDE_EFFECTS);
	zend_object *object = pt_se_side_effects_of(sideEffects, "getEffectNames");
	if (UNEXPECTED(object == NULL)) return -1;
	return pt_se_lists_writes_global(object);
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
		bool readsWrittenArguments;
		ZEND_PARSE_PARAMETERS_START(5, 5)
			Z_PARAM_ARRAY(effects)
			Z_PARAM_OBJECT_OF_CLASS(others, pt_ce_trinary)
			Z_PARAM_ARRAY(calledParameters)
			Z_PARAM_ARRAY(writtenParameters)
			Z_PARAM_BOOL(readsWrittenArguments)
		ZEND_PARSE_PARAMETERS_END();
		SideEffects(Z_OBJ_P(ZEND_THIS)).construct(effects, others, calledParameters, writtenParameters, readsWrittenArguments);
	});

	cls.method<&SideEffects::none>(sigs::none);
	cls.method<&SideEffects::all>(sigs::all);
	cls.method<&SideEffects::unknown>(sigs::unknown);

	cls.method(sigs::create, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *effects, *others, *calledParameters = NULL, *writtenParameters = NULL;
		bool readsWrittenArguments = false;
		ZEND_PARSE_PARAMETERS_START(2, 5)
			Z_PARAM_ARRAY(effects)
			Z_PARAM_OBJECT_OF_CLASS(others, pt_ce_trinary)
			Z_PARAM_OPTIONAL
			Z_PARAM_ARRAY(calledParameters)
			Z_PARAM_ARRAY(writtenParameters)
			Z_PARAM_BOOL(readsWrittenArguments)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(SideEffects::create(effects, others, calledParameters, writtenParameters, readsWrittenArguments));
	});

	cls.method<&SideEffects::hasAny>(sigs::hasAny);
	cls.method<&SideEffects::has, zp::Str>(sigs::has);
	cls.method<&SideEffects::isDeterministic>(sigs::isDeterministic);
	cls.method<&SideEffects::hasOnly, zp::Ht>(sigs::hasOnly);
	cls.method(sigs::baseName, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *listed;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_STR(listed)
		ZEND_PARSE_PARAMETERS_END();
		RETURN_STRINGL(ZSTR_VAL(listed), SideEffects::baseNameLength(ZSTR_VAL(listed), ZSTR_LEN(listed)));
	});
	cls.method<&SideEffects::getEffectNames>(sigs::getEffectNames);
	cls.method<&SideEffects::getCalledParameters>(sigs::getCalledParameters);
	cls.method<&SideEffects::getWrittenParameters>(sigs::getWrittenParameters);
	cls.method<&SideEffects::writesParameter, zp::Str>(sigs::writesParameter);

	cls.shadow(&pt_ce_side_effects);
}

/* }}} */
