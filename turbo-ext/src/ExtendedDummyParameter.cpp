/*
 * PHPStanTurbo\ExtendedDummyParameter — native implementation of
 * PHPStan\Reflection\Php\ExtendedDummyParameter.
 *
 * Final, extending the shadowing DummyParameter: its eight promoted slots
 * follow the parent's six (generated declarations), the constructor writes
 * them and runs the parent's constructor body directly
 * (pt_dummy_parameter_construct() — the parent method is native, nothing can
 * sit in between). ParameterAllowedConstants and AllowedConstantsResult stay
 * PHP; checkAllowedConstants() calls the former by name and instantiates the
 * latter through the class map.
 */

#include "support.h"
#include "generated/ExtendedDummyParameter.h"

namespace slots = ptdecl::ExtendedDummyParameter::slot;
namespace sigs = ptdecl::ExtendedDummyParameter::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_extended_dummy_parameter = NULL;

namespace phpstanturbo {

/* Mirrors PHPStan\Reflection\Php\ExtendedDummyParameter. */
class ExtendedDummyParameter
{
public:
	explicit ExtendedDummyParameter(zend_object *self) : self(self) {}

	/* the promoted slots in parameter order, then parent::__construct()
	 * (argv: the constructor's fourteen arguments, NULL / IS_NULL for the
	 * nullable nulls); false = pending exception */
	[[nodiscard]] bool construct(zend_string *name, zval *type, bool optional, zval *passedByReference, bool variadic, zval *defaultValue, zval *nativeType, zval *phpDocType, zval *outType, zval *immediatelyInvokedCallable, zval *closureThisType, zval *attributes, zval *allowedConstants, zval *pureUnlessCallableIsImpureParameter) const
	{
		write(slots::nativeType, nativeType);
		write(slots::phpDocType, phpDocType);
		write(slots::outType, outType);
		write(slots::immediatelyInvokedCallable, immediatelyInvokedCallable);
		write(slots::closureThisType, closureThisType);
		write(slots::attributes, attributes);
		write(slots::allowedConstants, allowedConstants);
		write(slots::pureUnlessCallableIsImpureParameter, pureUnlessCallableIsImpureParameter);
		return pt_dummy_parameter_construct(self, name, type, optional, passedByReference, variadic, defaultValue);
	}

	zv::Val getPhpDocType() const { return copyOf(slots::phpDocType, "phpDocType"); }
	zv::Val getNativeType() const { return copyOf(slots::nativeType, "nativeType"); }
	zv::Val getOutType() const { return copyOf(slots::outType, "outType"); }
	zv::Val isImmediatelyInvokedCallable() const { return copyOf(slots::immediatelyInvokedCallable, "immediatelyInvokedCallable"); }
	zv::Val getClosureThisType() const { return copyOf(slots::closureThisType, "closureThisType"); }
	zv::Val getAttributes() const { return copyOf(slots::attributes, "attributes"); }
	zv::Val getAllowedConstants() const { return copyOf(slots::allowedConstants, "allowedConstants"); }
	zv::Val isPureUnlessCallableIsImpureParameter() const { return copyOf(slots::pureUnlessCallableIsImpureParameter, "pureUnlessCallableIsImpureParameter"); }

	/* !$this->nativeType instanceof MixedType || $this->nativeType->isExplicitMixed();
	 * false = pending exception */
	[[nodiscard]] bool hasNativeType(bool &out) const
	{
		zval *nativeType = slot(slots::nativeType, "nativeType");
		if (UNEXPECTED(nativeType == NULL)) return false;
		if (Z_TYPE_P(nativeType) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(nativeType), pt_ce_mixed_type)) {
			out = true;
			return true;
		}
		zv::Val explicitMixed = pt_type_call(Z_OBJ_P(nativeType), PT_LC("isexplicitmixed"), 0, NULL);
		if (UNEXPECTED(explicitMixed.isUndef())) return false;
		out = zend_is_true(explicitMixed.raw());
		return true;
	}

	/* $this->allowedConstants === null ? new AllowedConstantsResult([], [],
	 * false) : $this->allowedConstants->check($constants) */
	zv::Val checkAllowedConstants(zval *constants) const
	{
		zval *allowedConstants = slot(slots::allowedConstants, "allowedConstants");
		if (UNEXPECTED(allowedConstants == NULL)) return zv::Val();
		if (Z_TYPE_P(allowedConstants) == IS_NULL) {
			zval argv[3];
			ZVAL_EMPTY_ARRAY(&argv[0]);
			ZVAL_EMPTY_ARRAY(&argv[1]);
			ZVAL_FALSE(&argv[2]);
			return pt_type_new(PT_CLASS_ALLOWED_CONSTANTS_RESULT, 3, argv);
		}
		if (UNEXPECTED(Z_TYPE_P(allowedConstants) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function check() on %s", zend_zval_value_name(allowedConstants));
			return zv::Val();
		}
		return pt_type_call(Z_OBJ_P(allowedConstants), PT_LC("check"), 1, constants);
	}

private:
	zend_object *self;

	void write(uint32_t index, zval *value) const
	{
		zval *slotValue = OBJ_PROP_NUM(self, index);
		zval previous;
		ZVAL_COPY_VALUE(&previous, slotValue);
		if (value == NULL) {
			ZVAL_NULL(slotValue);
		} else {
			ZVAL_COPY(slotValue, value);
		}
		Z_PROP_FLAG_P(slotValue) = 0; /* no longer IS_PROP_UNINIT */
		if (Z_TYPE(previous) != IS_UNDEF) {
			zval_ptr_dtor(&previous);
		}
	}

	/* a slot (borrowed); NULL with the engine's Error pending before the
	 * constructor initialized it */
	zval *slot(uint32_t index, const char *propertyName) const
	{
		zval *value = OBJ_PROP_NUM(self, index);
		if (UNEXPECTED(Z_TYPE_P(value) == IS_UNDEF)) {
			zend_throw_error(NULL, "Typed property PHPStan\\Reflection\\Php\\ExtendedDummyParameter::$%s must not be accessed before initialization", propertyName);
			return NULL;
		}
		return value;
	}

	zv::Val copyOf(uint32_t index, const char *propertyName) const
	{
		zval *value = slot(index, propertyName);
		return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
	}
};

} // namespace phpstanturbo

using phpstanturbo::ExtendedDummyParameter;

/* {{{ direct entries (support.h) */

zv::Val pt_extended_dummy_parameter_new(uint32_t argc, zval *argv)
{
	if (EXPECTED(argc == 14)) {
		zval *name = &argv[0], *optional = &argv[2], *passedByReference = &argv[3], *variadic = &argv[4], *defaultValue = &argv[5];
		zval *outType = &argv[8], *closureThisType = &argv[10], *attributes = &argv[11], *allowedConstants = &argv[12];
		auto isBool = [](zval *value) { return Z_TYPE_P(value) == IS_TRUE || Z_TYPE_P(value) == IS_FALSE; };
		auto isObjectOrNull = [](zval *value) { return Z_TYPE_P(value) == IS_OBJECT || Z_TYPE_P(value) == IS_NULL; };
		if (EXPECTED(Z_TYPE_P(name) == IS_STRING && Z_TYPE(argv[1]) == IS_OBJECT && isBool(optional) && isObjectOrNull(passedByReference) && isBool(variadic) && isObjectOrNull(defaultValue)
			&& Z_TYPE(argv[6]) == IS_OBJECT && Z_TYPE(argv[7]) == IS_OBJECT && isObjectOrNull(outType) && Z_TYPE(argv[9]) == IS_OBJECT && isObjectOrNull(closureThisType)
			&& Z_TYPE_P(attributes) == IS_ARRAY && isObjectOrNull(allowedConstants) && Z_TYPE(argv[13]) == IS_OBJECT)) {
			zval object;
			if (UNEXPECTED(object_init_ex(&object, pt_ce_extended_dummy_parameter) != SUCCESS)) return zv::Val();
			zv::Val result = zv::Val::adopt(object);
			if (UNEXPECTED(!ExtendedDummyParameter(Z_OBJ_P(result.raw())).construct(Z_STR_P(name), &argv[1], Z_TYPE_P(optional) == IS_TRUE, Z_TYPE_P(passedByReference) == IS_NULL ? NULL : passedByReference, Z_TYPE_P(variadic) == IS_TRUE, Z_TYPE_P(defaultValue) == IS_NULL ? NULL : defaultValue, &argv[6], &argv[7], outType, &argv[9], closureThisType, attributes, allowedConstants, &argv[13]))) return zv::Val();
			return result;
		}
	}
	/* anything else through the constructor's parameter parsing (its
	 * coercions and TypeErrors) */
	return pt_type_new_ce(pt_ce_extended_dummy_parameter, argc, argv);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_EDP_THIS ExtendedDummyParameter(Z_OBJ_P(ZEND_THIS))

void pt_register_extended_dummy_parameter()
{
	reg::Class cls("PHPStan\\Reflection\\Php\\ExtendedDummyParameter");
	ptdecl::ExtendedDummyParameter::declareClass(cls);
	ptdecl::ExtendedDummyParameter::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *name;
		zval *type, *passedByReference = NULL, *defaultValue = NULL, *nativeType, *phpDocType, *outType = NULL, *immediatelyInvokedCallable, *closureThisType = NULL, *attributes, *allowedConstants = NULL, *pureUnlessCallableIsImpureParameter;
		bool optional, variadic;
		ZEND_PARSE_PARAMETERS_START(14, 14)
			Z_PARAM_STR(name)
			Z_PARAM_OBJECT(type)
			Z_PARAM_BOOL(optional)
			Z_PARAM_OBJECT_OR_NULL(passedByReference)
			Z_PARAM_BOOL(variadic)
			Z_PARAM_OBJECT_OR_NULL(defaultValue)
			Z_PARAM_OBJECT(nativeType)
			Z_PARAM_OBJECT(phpDocType)
			Z_PARAM_OBJECT_OR_NULL(outType)
			Z_PARAM_OBJECT(immediatelyInvokedCallable)
			Z_PARAM_OBJECT_OR_NULL(closureThisType)
			Z_PARAM_ARRAY(attributes)
			Z_PARAM_OBJECT_OR_NULL(allowedConstants)
			Z_PARAM_OBJECT(pureUnlessCallableIsImpureParameter)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!PT_EDP_THIS.construct(name, type, optional, passedByReference, variadic, defaultValue, nativeType, phpDocType, outType, immediatelyInvokedCallable, closureThisType, attributes, allowedConstants, pureUnlessCallableIsImpureParameter))) RETURN_THROWS();
	});

	cls.method<&ExtendedDummyParameter::getPhpDocType>(sigs::getPhpDocType);
	cls.method<&ExtendedDummyParameter::hasNativeType>(sigs::hasNativeType);
	cls.method<&ExtendedDummyParameter::getNativeType>(sigs::getNativeType);
	cls.method<&ExtendedDummyParameter::getOutType>(sigs::getOutType);
	cls.method<&ExtendedDummyParameter::isImmediatelyInvokedCallable>(sigs::isImmediatelyInvokedCallable);
	cls.method<&ExtendedDummyParameter::getClosureThisType>(sigs::getClosureThisType);
	cls.method<&ExtendedDummyParameter::getAttributes>(sigs::getAttributes);
	cls.method<&ExtendedDummyParameter::getAllowedConstants>(sigs::getAllowedConstants);

	cls.method(sigs::checkAllowedConstants, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *constants;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_ARRAY(constants)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_EDP_THIS.checkAllowedConstants(constants));
	});

	cls.method<&ExtendedDummyParameter::isPureUnlessCallableIsImpureParameter>(sigs::isPureUnlessCallableIsImpureParameter);

	cls.shadow(&pt_ce_extended_dummy_parameter);
}

/* }}} */
