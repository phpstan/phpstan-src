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

	/* $this->effects !== [] ? TrinaryLogic::createYes() : $this->others */
	zv::Val hasAny() const
	{
		zval *effects = pt_typed_slot(self, slots::effects, self->ce, "effects");
		if (UNEXPECTED(effects == NULL)) return zv::Val();
		if (zend_hash_num_elements(Z_ARRVAL_P(effects)) > 0) return zv::Val::copyOf(zv::Ref(pt_trinary_singleton(PT_TRI_YES)));
		return read(slots::others, "others");
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
	cls.method<&SideEffects::getEffectNames>(sigs::getEffectNames);
	cls.method<&SideEffects::getCalledParameters>(sigs::getCalledParameters);
	cls.method<&SideEffects::getWrittenParameters>(sigs::getWrittenParameters);
	cls.method<&SideEffects::writesParameter, zp::Str>(sigs::writesParameter);

	cls.shadow(&pt_ce_side_effects);
}

/* }}} */
