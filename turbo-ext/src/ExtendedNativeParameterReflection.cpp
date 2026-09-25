/*
 * PHPStanTurbo\ExtendedNativeParameterReflection — native implementation of
 * PHPStan\Reflection\Native\ExtendedNativeParameterReflection.
 *
 * A final value class over its fourteen promoted slots in the twin's order:
 * every getter returns its slot, hasNativeType() asks the native type
 * whether it is an explicit mixed, checkAllowedConstants() delegates to the
 * PHP ParameterAllowedConstants. Native callers read the getters through
 * pt_parameter_reflection_call() (ParameterValues.h) without a frame and
 * create instances with pt_extended_native_parameter_reflection_new().
 */

#include "support.h"
#include "generated/ExtendedNativeParameterReflection.h"

namespace slots = ptdecl::ExtendedNativeParameterReflection::slot;
namespace sigs = ptdecl::ExtendedNativeParameterReflection::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "ParameterValues.h"

zend_class_entry *pt_ce_extended_native_parameter_reflection = NULL;

namespace {

/* $type instanceof MixedType (an object): the native class — and, under the
 * prefixed activation of the differential tests, where the native class is
 * PHPStanTurbo\MixedType, the PHP twin a PHP collaborator created too (the
 * real-name check is decided once per class entry) */
bool isMixedType(zval *type)
{
	zend_class_entry *ce = Z_OBJCE_P(type);
	if (instanceof_function(ce, pt_ce_mixed_type)) return true;
	static zend_class_entry *decidedFor = NULL;
	static bool realName = true;
	if (UNEXPECTED(decidedFor != pt_ce_mixed_type)) {
		decidedFor = pt_ce_mixed_type;
		realName = pt_ce_mixed_type == NULL || zend_string_equals_literal(pt_ce_mixed_type->name, "PHPStan\\Type\\MixedType");
	}
	if (EXPECTED(realName)) return false;
	zend_string *name = zend_string_init(ZEND_STRL("PHPStan\\Type\\MixedType"), 0);
	zend_class_entry *twin = zend_lookup_class_ex(name, NULL, ZEND_FETCH_CLASS_NO_AUTOLOAD);
	zend_string_release(name);
	return twin != NULL && instanceof_function(ce, twin);
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Reflection\Native\ExtendedNativeParameterReflection. */
class ExtendedNativeParameterReflection
{
public:
	explicit ExtendedNativeParameterReflection(zend_object *self) : self(self) {}

	/* the promoted slots in parameter order (borrowed; NULL for a nullable
	 * null) */
	void construct(zend_string *name, bool optional, zval *type, zval *phpDocType, zval *nativeType, zval *passedByReference, bool variadic, zval *defaultValue, zval *outType, zval *immediatelyInvokedCallable, zval *closureThisType, zval *attributes, zval *allowedConstants, zval *pureUnlessCallableIsImpureParameter) const
	{
		zval value;
		ZVAL_STR(&value, name);
		pt_write_slot(self, slots::name, &value);
		ZVAL_BOOL(&value, optional);
		pt_write_slot(self, slots::optional, &value);
		pt_write_slot(self, slots::type, type);
		pt_write_slot(self, slots::phpDocType, phpDocType);
		pt_write_slot(self, slots::nativeType, nativeType);
		pt_write_slot(self, slots::passedByReference, passedByReference);
		ZVAL_BOOL(&value, variadic);
		pt_write_slot(self, slots::variadic, &value);
		writeNullable(slots::defaultValue, defaultValue);
		writeNullable(slots::outType, outType);
		pt_write_slot(self, slots::immediatelyInvokedCallable, immediatelyInvokedCallable);
		writeNullable(slots::closureThisType, closureThisType);
		pt_write_slot(self, slots::attributes, attributes);
		writeNullable(slots::allowedConstants, allowedConstants);
		pt_write_slot(self, slots::pureUnlessCallableIsImpureParameter, pureUnlessCallableIsImpureParameter);
	}

	zv::Val getName() const { return copyOf(slots::name, "name"); }
	zv::Val isOptional() const { return copyOf(slots::optional, "optional"); }
	zv::Val getType() const { return copyOf(slots::type, "type"); }
	zv::Val getPhpDocType() const { return copyOf(slots::phpDocType, "phpDocType"); }

	/* !$this->nativeType instanceof MixedType || $this->nativeType->isExplicitMixed();
	 * false = pending exception */
	[[nodiscard]] bool hasNativeType(bool &out) const
	{
		zval *nativeType = slot(slots::nativeType, "nativeType");
		if (UNEXPECTED(nativeType == NULL)) return false;
		if (Z_TYPE_P(nativeType) != IS_OBJECT || !isMixedType(nativeType)) {
			out = true;
			return true;
		}
		zv::Val explicitMixed = pt_type_call(Z_OBJ_P(nativeType), PT_LC("isexplicitmixed"), 0, NULL);
		if (UNEXPECTED(explicitMixed.isUndef())) return false;
		out = zend_is_true(explicitMixed.raw());
		return true;
	}

	zv::Val getNativeType() const { return copyOf(slots::nativeType, "nativeType"); }
	zv::Val passedByReference() const { return copyOf(slots::passedByReference, "passedByReference"); }
	zv::Val isVariadic() const { return copyOf(slots::variadic, "variadic"); }
	zv::Val getDefaultValue() const { return copyOf(slots::defaultValue, "defaultValue"); }
	zv::Val getOutType() const { return copyOf(slots::outType, "outType"); }
	zv::Val isImmediatelyInvokedCallable() const { return copyOf(slots::immediatelyInvokedCallable, "immediatelyInvokedCallable"); }
	zv::Val getClosureThisType() const { return copyOf(slots::closureThisType, "closureThisType"); }
	zv::Val getAttributes() const { return copyOf(slots::attributes, "attributes"); }
	zv::Val getAllowedConstants() const { return copyOf(slots::allowedConstants, "allowedConstants"); }

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
		return pt_type_call(Z_OBJ_P(allowedConstants), PT_LC("check"), 1, constants);
	}

	zv::Val isPureUnlessCallableIsImpureParameter() const { return copyOf(slots::pureUnlessCallableIsImpureParameter, "pureUnlessCallableIsImpureParameter"); }

private:
	zend_object *self;

	void writeNullable(uint32_t index, zval *value) const
	{
		zval null;
		ZVAL_NULL(&null);
		pt_write_slot(self, index, value != NULL ? value : &null);
	}

	/* a slot (borrowed); NULL with the engine's Error pending before the
	 * constructor initialized it */
	zval *slot(uint32_t index, const char *propertyName) const
	{
		return pt_typed_slot(self, index, pt_ce_extended_native_parameter_reflection, propertyName);
	}

	zv::Val copyOf(uint32_t index, const char *propertyName) const
	{
		zval *value = slot(index, propertyName);
		return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
	}
};

} // namespace phpstanturbo

using phpstanturbo::ExtendedNativeParameterReflection;

/* {{{ direct entries (support.h) */

zv::Val pt_extended_native_parameter_reflection_new(uint32_t argc, zval *argv)
{
	if (EXPECTED(argc == 14)) {
		auto isBool = [](zval *value) { return Z_TYPE_P(value) == IS_TRUE || Z_TYPE_P(value) == IS_FALSE; };
		auto isObjectOrNull = [](zval *value) { return Z_TYPE_P(value) == IS_OBJECT || Z_TYPE_P(value) == IS_NULL; };
		if (EXPECTED(Z_TYPE(argv[0]) == IS_STRING && isBool(&argv[1]) && Z_TYPE(argv[2]) == IS_OBJECT && Z_TYPE(argv[3]) == IS_OBJECT && Z_TYPE(argv[4]) == IS_OBJECT
			&& Z_TYPE(argv[5]) == IS_OBJECT && isBool(&argv[6]) && isObjectOrNull(&argv[7]) && isObjectOrNull(&argv[8]) && Z_TYPE(argv[9]) == IS_OBJECT
			&& isObjectOrNull(&argv[10]) && Z_TYPE(argv[11]) == IS_ARRAY && isObjectOrNull(&argv[12]) && Z_TYPE(argv[13]) == IS_OBJECT)) {
			zval object;
			if (UNEXPECTED(object_init_ex(&object, pt_ce_extended_native_parameter_reflection) != SUCCESS)) return zv::Val();
			auto orNull = [](zval *value) { return Z_TYPE_P(value) == IS_NULL ? NULL : value; };
			ExtendedNativeParameterReflection(Z_OBJ(object)).construct(Z_STR(argv[0]), Z_TYPE(argv[1]) == IS_TRUE, &argv[2], &argv[3], &argv[4], &argv[5], Z_TYPE(argv[6]) == IS_TRUE, orNull(&argv[7]), orNull(&argv[8]), &argv[9], orNull(&argv[10]), &argv[11], orNull(&argv[12]), &argv[13]);
			return zv::Val::adopt(object);
		}
	}
	/* anything else through the constructor's parameter parsing (its
	 * coercions and TypeErrors) */
	return pt_type_new_ce(pt_ce_extended_native_parameter_reflection, argc, argv);
}

zv::Val pt_extended_native_parameter_reflection_call(zend_object *parameter, pt_parameter_reflection_member member)
{
	ExtendedNativeParameterReflection reflection(parameter);
	switch (member) {
		case PT_PR_GET_NAME: return reflection.getName();
		case PT_PR_IS_OPTIONAL: return reflection.isOptional();
		case PT_PR_GET_TYPE: return reflection.getType();
		case PT_PR_PASSED_BY_REFERENCE: return reflection.passedByReference();
		case PT_PR_IS_VARIADIC: return reflection.isVariadic();
		case PT_PR_GET_DEFAULT_VALUE: return reflection.getDefaultValue();
		case PT_PR_GET_PHPDOC_TYPE: return reflection.getPhpDocType();
		case PT_PR_HAS_NATIVE_TYPE: {
			bool out;
			if (UNEXPECTED(!reflection.hasNativeType(out))) return zv::Val();
			return zv::Val::boolean(out);
		}
		case PT_PR_GET_NATIVE_TYPE: return reflection.getNativeType();
		case PT_PR_GET_OUT_TYPE: return reflection.getOutType();
		case PT_PR_IS_IMMEDIATELY_INVOKED_CALLABLE: return reflection.isImmediatelyInvokedCallable();
		case PT_PR_GET_CLOSURE_THIS_TYPE: return reflection.getClosureThisType();
		case PT_PR_GET_ATTRIBUTES: return reflection.getAttributes();
		case PT_PR_GET_ALLOWED_CONSTANTS: return reflection.getAllowedConstants();
		case PT_PR_IS_PURE_UNLESS_CALLABLE_IS_IMPURE_PARAMETER: return reflection.isPureUnlessCallableIsImpureParameter();
		case PT_PR_MEMBER_COUNT: break;
	}
	ZEND_UNREACHABLE();
	return zv::Val();
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_ENPR_THIS ExtendedNativeParameterReflection(Z_OBJ_P(ZEND_THIS))

PT_MINIT_REGISTRATION(pt_register_extended_native_parameter_reflection)
{
	reg::Class cls("PHPStan\\Reflection\\Native\\ExtendedNativeParameterReflection");
	ptdecl::ExtendedNativeParameterReflection::declareClass(cls);
	ptdecl::ExtendedNativeParameterReflection::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *name;
		bool optional, variadic;
		zval *type, *phpDocType, *nativeType, *passedByReference, *defaultValue = NULL, *outType = NULL, *immediatelyInvokedCallable, *closureThisType = NULL, *attributes, *allowedConstants = NULL, *pureUnlessCallableIsImpureParameter;
		ZEND_PARSE_PARAMETERS_START(14, 14)
			Z_PARAM_STR(name)
			Z_PARAM_BOOL(optional)
			Z_PARAM_OBJECT(type)
			Z_PARAM_OBJECT(phpDocType)
			Z_PARAM_OBJECT(nativeType)
			Z_PARAM_OBJECT(passedByReference)
			Z_PARAM_BOOL(variadic)
			Z_PARAM_OBJECT_OR_NULL(defaultValue)
			Z_PARAM_OBJECT_OR_NULL(outType)
			Z_PARAM_OBJECT(immediatelyInvokedCallable)
			Z_PARAM_OBJECT_OR_NULL(closureThisType)
			Z_PARAM_ARRAY(attributes)
			Z_PARAM_OBJECT_OR_NULL(allowedConstants)
			Z_PARAM_OBJECT(pureUnlessCallableIsImpureParameter)
		ZEND_PARSE_PARAMETERS_END();
		PT_ENPR_THIS.construct(name, optional, type, phpDocType, nativeType, passedByReference, variadic, defaultValue, outType, immediatelyInvokedCallable, closureThisType, attributes, allowedConstants, pureUnlessCallableIsImpureParameter);
	});

	cls.method<&ExtendedNativeParameterReflection::getName>(sigs::getName);
	cls.op<PT_OP_GET_NAME, &ExtendedNativeParameterReflection::getName>();
	cls.method<&ExtendedNativeParameterReflection::isOptional>(sigs::isOptional);
	cls.op<PT_OP_IS_OPTIONAL, &ExtendedNativeParameterReflection::isOptional>();
	cls.method<&ExtendedNativeParameterReflection::getType>(sigs::getType);
	cls.op<PT_OP_GET_TYPE, &ExtendedNativeParameterReflection::getType>();
	cls.method<&ExtendedNativeParameterReflection::getPhpDocType>(sigs::getPhpDocType);
	cls.method<&ExtendedNativeParameterReflection::hasNativeType>(sigs::hasNativeType);
	cls.method<&ExtendedNativeParameterReflection::getNativeType>(sigs::getNativeType);
	cls.method<&ExtendedNativeParameterReflection::passedByReference>(sigs::passedByReference);
	cls.op<PT_OP_PASSED_BY_REFERENCE, &ExtendedNativeParameterReflection::passedByReference>();
	cls.method<&ExtendedNativeParameterReflection::isVariadic>(sigs::isVariadic);
	cls.op<PT_OP_IS_VARIADIC, &ExtendedNativeParameterReflection::isVariadic>();
	cls.method<&ExtendedNativeParameterReflection::getDefaultValue>(sigs::getDefaultValue);
	cls.op<PT_OP_GET_DEFAULT_VALUE, &ExtendedNativeParameterReflection::getDefaultValue>();
	cls.method<&ExtendedNativeParameterReflection::getOutType>(sigs::getOutType);
	cls.method<&ExtendedNativeParameterReflection::isImmediatelyInvokedCallable>(sigs::isImmediatelyInvokedCallable);
	cls.method<&ExtendedNativeParameterReflection::getClosureThisType>(sigs::getClosureThisType);
	cls.method<&ExtendedNativeParameterReflection::getAttributes>(sigs::getAttributes);
	cls.method<&ExtendedNativeParameterReflection::getAllowedConstants>(sigs::getAllowedConstants);

	cls.method(sigs::checkAllowedConstants, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *constants;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_ARRAY(constants)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_ENPR_THIS.checkAllowedConstants(constants));
	});

	cls.method<&ExtendedNativeParameterReflection::isPureUnlessCallableIsImpureParameter>(sigs::isPureUnlessCallableIsImpureParameter);

	cls.shadow(&pt_ce_extended_native_parameter_reflection);
}

/* }}} */
