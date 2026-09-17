/*
 * PHPStanTurbo\PhpParameterReflection — native implementation of
 * PHPStan\Reflection\Php\PhpParameterReflection.
 *
 * The parameter reflection of a userland function or method: a final class
 * over the BetterReflection adapter parameter, the PHPDoc-derived types and
 * two memo slots ($type, $nativeType) in the twin's order. The name,
 * optionality, variadicness, by-reference mode and the native / default
 * types are the adapter's answers (still PHP, one cached method site per
 * adapter method); InitializerExprContext stays PHP too,
 * InitializerExprTypeResolver is called through its direct entry. Native callers reach the getters through
 * pt_parameter_reflection_call() (ParameterValues.h) without a frame.
 */

#include "support.h"
#include "generated/PhpParameterReflection.h"

namespace slots = ptdecl::PhpParameterReflection::slot;
namespace sigs = ptdecl::PhpParameterReflection::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "ParameterValues.h"

zend_class_entry *pt_ce_php_parameter_reflection = NULL;

namespace {

/* {{{ the PHP collaborators (one site each) */

pt_method_site pt_ppr_is_optional_site;
pt_method_site pt_ppr_get_name_site;
pt_method_site pt_ppr_is_default_value_available_site;
pt_method_site pt_ppr_get_default_value_expression_site;
pt_method_site pt_ppr_get_type_site;
pt_method_site pt_ppr_is_passed_by_reference_site;
pt_method_site pt_ppr_is_variadic_site;

/* $reflection->method() of the adapter parameter */
inline zv::Val adapterCall(pt_method_site &site, zval *reflection, const char *lcname, size_t len)
{
	return pt_call_method_cached(site, Z_OBJ_P(reflection), lcname, len, 0, NULL);
}

/* a bool answer of the adapter; false = pending exception */
inline bool adapterBool(pt_method_site &site, zval *reflection, const char *lcname, size_t len, bool &out)
{
	zv::Val result = adapterCall(site, reflection, lcname, len);
	if (UNEXPECTED(result.isUndef())) return false;
	out = Z_TYPE_P(result.raw()) == IS_TRUE;
	return true;
}

/* $initializerExprTypeResolver->getType($expr, $context) */
inline zv::Val initializerGetType(zval *resolver, zval *expr, zval *context)
{
	return pt_initializer_expr_type_resolver_get_type(resolver, expr, context);
}

/* InitializerExprContext::fromReflectionParameter($reflection) */
inline zv::Val contextFromReflectionParameter(zval *reflection)
{
	return pt_initializer_expr_context_from_reflection_parameter(reflection);
}

/* }}} */

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Reflection\Php\PhpParameterReflection; UNDEF = pending
 * exception. */
class PhpParameterReflection
{
public:
	explicit PhpParameterReflection(zend_object *self) : self(self) {}

	/* the promoted slots in parameter order (borrowed; NULL for a nullable
	 * null) */
	void construct(zval *initializerExprTypeResolver, zval *reflection, zval *phpDocType, zval *declaringClass, zval *outType, zval *immediatelyInvokedCallable, zval *closureThisType, zval *attributes, zval *allowedConstants, zval *pureUnlessCallableIsImpureParameter) const
	{
		pt_write_slot(self, slots::initializerExprTypeResolver, initializerExprTypeResolver);
		pt_write_slot(self, slots::reflection, reflection);
		writeNullable(slots::phpDocType, phpDocType);
		writeNullable(slots::declaringClass, declaringClass);
		writeNullable(slots::outType, outType);
		pt_write_slot(self, slots::immediatelyInvokedCallable, immediatelyInvokedCallable);
		writeNullable(slots::closureThisType, closureThisType);
		pt_write_slot(self, slots::attributes, attributes);
		writeNullable(slots::allowedConstants, allowedConstants);
		pt_write_slot(self, slots::pureUnlessCallableIsImpureParameter, pureUnlessCallableIsImpureParameter);
	}

	/* $this->reflection->isOptional() */
	zv::Val isOptional() const
	{
		zval *reflection = slot(slots::reflection, "reflection");
		if (UNEXPECTED(reflection == NULL)) return zv::Val();
		return adapterCall(pt_ppr_is_optional_site, reflection, PT_LC("isoptional"));
	}

	/* $this->reflection->getName() */
	zv::Val getName() const
	{
		zval *reflection = slot(slots::reflection, "reflection");
		if (UNEXPECTED(reflection == NULL)) return zv::Val();
		return adapterCall(pt_ppr_get_name_site, reflection, PT_LC("getname"));
	}

	/* Mirrors getType(): memoized in $type. */
	zv::Val getType() const
	{
		zval *memo = OBJ_PROP_NUM(self, slots::type);
		if (EXPECTED(Z_TYPE_P(memo) == IS_OBJECT)) return zv::Val::copyOf(zv::Ref(memo));

		zval *phpDocTypeSlot = slot(slots::phpDocType, "phpDocType");
		if (UNEXPECTED(phpDocTypeSlot == NULL)) return zv::Val();
		zv::Val phpDocType = zv::Val::copyOf(zv::Ref(phpDocTypeSlot));
		if (!phpDocType.isNull()) {
			zval *reflection = slot(slots::reflection, "reflection");
			if (UNEXPECTED(reflection == NULL)) return zv::Val();
			bool defaultValueAvailable;
			if (UNEXPECTED(!adapterBool(pt_ppr_is_default_value_available_site, reflection, PT_LC("isdefaultvalueavailable"), defaultValueAvailable))) return zv::Val();
			if (defaultValueAvailable) {
				zv::Val defaultValueType = defaultValueTypeOf();
				if (UNEXPECTED(defaultValueType.isUndef())) return zv::Val();
				zend_long isNull = pt_type_op_trinary(Z_OBJ_P(defaultValueType.raw()), PT_OP_IS_NULL, 0, NULL);
				if (UNEXPECTED(isNull < 0)) return zv::Val();
				if (isNull == PT_TRI_YES) {
					phpDocType = pt_type_combinator_add_null(phpDocType.raw());
					if (UNEXPECTED(phpDocType.isUndef())) return zv::Val();
				}
			}
		}

		zval *reflection = slot(slots::reflection, "reflection");
		if (UNEXPECTED(reflection == NULL)) return zv::Val();
		zv::Val reflectionType = adapterCall(pt_ppr_get_type_site, reflection, PT_LC("gettype"));
		if (UNEXPECTED(reflectionType.isUndef())) return zv::Val();
		zval *declaringClass = slot(slots::declaringClass, "declaringClass");
		if (UNEXPECTED(declaringClass == NULL)) return zv::Val();
		zv::Val variadic = isVariadic();
		if (UNEXPECTED(variadic.isUndef())) return zv::Val();
		zv::Val type = pt_typehint_helper_decide_type_from_reflection(nullOrValue(reflectionType.raw()), nullOrValue(phpDocType.raw()), nullOrValue(declaringClass), Z_TYPE_P(variadic.raw()) == IS_TRUE);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		pt_write_slot(self, slots::type, type.raw());
		return type;
	}

	/* $this->reflection->isPassedByReference() ?
	 * PassedByReference::createCreatesNewVariable() : PassedByReference::createNo() */
	zv::Val passedByReference() const
	{
		zval *reflection = slot(slots::reflection, "reflection");
		if (UNEXPECTED(reflection == NULL)) return zv::Val();
		bool byReference;
		if (UNEXPECTED(!adapterBool(pt_ppr_is_passed_by_reference_site, reflection, PT_LC("ispassedbyreference"), byReference))) return zv::Val();
		zend_object *mode = byReference ? pt_passed_by_reference_create_creates_new_variable() : pt_passed_by_reference_create_no();
		if (UNEXPECTED(mode == NULL)) return zv::Val();
		zval value;
		ZVAL_OBJ_COPY(&value, mode);
		return zv::Val::adopt(value);
	}

	/* $this->reflection->isVariadic() */
	zv::Val isVariadic() const
	{
		zval *reflection = slot(slots::reflection, "reflection");
		if (UNEXPECTED(reflection == NULL)) return zv::Val();
		return adapterCall(pt_ppr_is_variadic_site, reflection, PT_LC("isvariadic"));
	}

	/* $this->phpDocType ?? new MixedType() */
	zv::Val getPhpDocType() const
	{
		zval *phpDocType = slot(slots::phpDocType, "phpDocType");
		if (UNEXPECTED(phpDocType == NULL)) return zv::Val();
		if (Z_TYPE_P(phpDocType) != IS_NULL) return zv::Val::copyOf(zv::Ref(phpDocType));
		zval mixed;
		if (UNEXPECTED(!pt_mixed_type_new(&mixed))) return zv::Val();
		return zv::Val::adopt(mixed);
	}

	/* $this->reflection->getType() !== null; false = pending exception */
	[[nodiscard]] bool hasNativeType(bool &out) const
	{
		zval *reflection = slot(slots::reflection, "reflection");
		if (UNEXPECTED(reflection == NULL)) return false;
		zv::Val reflectionType = adapterCall(pt_ppr_get_type_site, reflection, PT_LC("gettype"));
		if (UNEXPECTED(reflectionType.isUndef())) return false;
		out = !reflectionType.isNull();
		return true;
	}

	/* Mirrors getNativeType(): $this->nativeType ??=
	 * TypehintHelper::decideTypeFromReflection($this->reflection->getType(),
	 * selfClass: $this->declaringClass, isVariadic: $this->isVariadic()). */
	zv::Val getNativeType() const
	{
		zval *memo = OBJ_PROP_NUM(self, slots::nativeType);
		if (EXPECTED(Z_TYPE_P(memo) == IS_OBJECT)) return zv::Val::copyOf(zv::Ref(memo));
		zval *reflection = slot(slots::reflection, "reflection");
		if (UNEXPECTED(reflection == NULL)) return zv::Val();
		zv::Val reflectionType = adapterCall(pt_ppr_get_type_site, reflection, PT_LC("gettype"));
		if (UNEXPECTED(reflectionType.isUndef())) return zv::Val();
		zval *declaringClass = slot(slots::declaringClass, "declaringClass");
		if (UNEXPECTED(declaringClass == NULL)) return zv::Val();
		zv::Val variadic = isVariadic();
		if (UNEXPECTED(variadic.isUndef())) return zv::Val();
		zv::Val type = pt_typehint_helper_decide_type_from_reflection(nullOrValue(reflectionType.raw()), NULL, nullOrValue(declaringClass), Z_TYPE_P(variadic.raw()) == IS_TRUE);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		pt_write_slot(self, slots::nativeType, type.raw());
		return type;
	}

	/* Mirrors getDefaultValue(). */
	zv::Val getDefaultValue() const
	{
		zval *reflection = slot(slots::reflection, "reflection");
		if (UNEXPECTED(reflection == NULL)) return zv::Val();
		bool defaultValueAvailable;
		if (UNEXPECTED(!adapterBool(pt_ppr_is_default_value_available_site, reflection, PT_LC("isdefaultvalueavailable"), defaultValueAvailable))) return zv::Val();
		if (!defaultValueAvailable) return zv::Val::null();
		return defaultValueTypeOf();
	}

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

	static zval *nullOrValue(zval *value)
	{
		return Z_TYPE_P(value) == IS_NULL ? NULL : value;
	}

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
		return pt_typed_slot(self, index, pt_ce_php_parameter_reflection, propertyName);
	}

	zv::Val copyOf(uint32_t index, const char *propertyName) const
	{
		zval *value = slot(index, propertyName);
		return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
	}

	/* $this->initializerExprTypeResolver->getType($this->reflection->getDefaultValueExpression(),
	 * InitializerExprContext::fromReflectionParameter($this->reflection)) —
	 * the resolver read first, then the arguments left to right */
	zv::Val defaultValueTypeOf() const
	{
		zval *resolver = slot(slots::initializerExprTypeResolver, "initializerExprTypeResolver");
		if (UNEXPECTED(resolver == NULL)) return zv::Val();
		zval *reflection = slot(slots::reflection, "reflection");
		if (UNEXPECTED(reflection == NULL)) return zv::Val();
		zv::Val expr = adapterCall(pt_ppr_get_default_value_expression_site, reflection, PT_LC("getdefaultvalueexpression"));
		if (UNEXPECTED(expr.isUndef())) return zv::Val();
		zv::Val context = contextFromReflectionParameter(reflection);
		if (UNEXPECTED(context.isUndef())) return zv::Val();
		return initializerGetType(resolver, expr.raw(), context.raw());
	}
};

} // namespace phpstanturbo

using phpstanturbo::PhpParameterReflection;

/* {{{ direct entries (support.h) */

namespace {

/* the ParameterReflection / ExtendedParameterReflection methods by member,
 * for the cached sites of any other implementation */
struct MemberName
{
	const char *lcname;
	size_t len;
	const char *name;
};

#define PT_PPR_MEMBER(lc, name) { lc, sizeof(lc) - 1, name }

const MemberName memberNames[PT_PR_MEMBER_COUNT] = {
	/* PT_PR_GET_NAME */ PT_PPR_MEMBER("getname", "getName"),
	/* PT_PR_IS_OPTIONAL */ PT_PPR_MEMBER("isoptional", "isOptional"),
	/* PT_PR_GET_TYPE */ PT_PPR_MEMBER("gettype", "getType"),
	/* PT_PR_PASSED_BY_REFERENCE */ PT_PPR_MEMBER("passedbyreference", "passedByReference"),
	/* PT_PR_IS_VARIADIC */ PT_PPR_MEMBER("isvariadic", "isVariadic"),
	/* PT_PR_GET_DEFAULT_VALUE */ PT_PPR_MEMBER("getdefaultvalue", "getDefaultValue"),
	/* PT_PR_GET_PHPDOC_TYPE */ PT_PPR_MEMBER("getphpdoctype", "getPhpDocType"),
	/* PT_PR_HAS_NATIVE_TYPE */ PT_PPR_MEMBER("hasnativetype", "hasNativeType"),
	/* PT_PR_GET_NATIVE_TYPE */ PT_PPR_MEMBER("getnativetype", "getNativeType"),
	/* PT_PR_GET_OUT_TYPE */ PT_PPR_MEMBER("getouttype", "getOutType"),
	/* PT_PR_IS_IMMEDIATELY_INVOKED_CALLABLE */ PT_PPR_MEMBER("isimmediatelyinvokedcallable", "isImmediatelyInvokedCallable"),
	/* PT_PR_GET_CLOSURE_THIS_TYPE */ PT_PPR_MEMBER("getclosurethistype", "getClosureThisType"),
	/* PT_PR_GET_ATTRIBUTES */ PT_PPR_MEMBER("getattributes", "getAttributes"),
	/* PT_PR_GET_ALLOWED_CONSTANTS */ PT_PPR_MEMBER("getallowedconstants", "getAllowedConstants"),
	/* PT_PR_IS_PURE_UNLESS_CALLABLE_IS_IMPURE_PARAMETER */ PT_PPR_MEMBER("ispureunlesscallableisimpureparameter", "isPureUnlessCallableIsImpureParameter"),
};

#undef PT_PPR_MEMBER

pt_method_site memberSites[PT_PR_MEMBER_COUNT];

} // namespace

zv::Val pt_parameter_reflection_call_slow(zval *parameter, pt_parameter_reflection_member member)
{
	const MemberName &name = memberNames[member];
	if (UNEXPECTED(Z_TYPE_P(parameter) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", name.name, zend_zval_value_name(parameter));
		return zv::Val();
	}
	zend_object *object = Z_OBJ_P(parameter);
	if (EXPECTED(object->ce == pt_ce_php_parameter_reflection)) return pt_php_parameter_reflection_call(object, member);
	if (object->ce == pt_ce_extended_native_parameter_reflection) return pt_extended_native_parameter_reflection_call(object, member);
	return pt_call_method_cached(memberSites[member], object, name.lcname, name.len, 0, NULL);
}

zv::Val pt_php_parameter_reflection_call(zend_object *parameter, pt_parameter_reflection_member member)
{
	PhpParameterReflection reflection(parameter);
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

#define PT_PPR_THIS PhpParameterReflection(Z_OBJ_P(ZEND_THIS))

void pt_register_php_parameter_reflection()
{
	reg::Class cls("PHPStan\\Reflection\\Php\\PhpParameterReflection");
	ptdecl::PhpParameterReflection::declareClass(cls);
	ptdecl::PhpParameterReflection::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *initializerExprTypeResolver, *reflection, *phpDocType = NULL, *declaringClass = NULL, *outType = NULL, *immediatelyInvokedCallable, *closureThisType = NULL, *attributes, *allowedConstants = NULL, *pureUnlessCallableIsImpureParameter;
		ZEND_PARSE_PARAMETERS_START(10, 10)
			Z_PARAM_OBJECT(initializerExprTypeResolver)
			Z_PARAM_OBJECT(reflection)
			Z_PARAM_OBJECT_OR_NULL(phpDocType)
			Z_PARAM_OBJECT_OR_NULL(declaringClass)
			Z_PARAM_OBJECT_OR_NULL(outType)
			Z_PARAM_OBJECT(immediatelyInvokedCallable)
			Z_PARAM_OBJECT_OR_NULL(closureThisType)
			Z_PARAM_ARRAY(attributes)
			Z_PARAM_OBJECT_OR_NULL(allowedConstants)
			Z_PARAM_OBJECT(pureUnlessCallableIsImpureParameter)
		ZEND_PARSE_PARAMETERS_END();
		PT_PPR_THIS.construct(initializerExprTypeResolver, reflection, phpDocType, declaringClass, outType, immediatelyInvokedCallable, closureThisType, attributes, allowedConstants, pureUnlessCallableIsImpureParameter);
	});

	cls.method<&PhpParameterReflection::isOptional>(sigs::isOptional);
	cls.op<PT_OP_IS_OPTIONAL, &PhpParameterReflection::isOptional>();
	cls.method<&PhpParameterReflection::getName>(sigs::getName);
	cls.op<PT_OP_GET_NAME, &PhpParameterReflection::getName>();
	cls.method<&PhpParameterReflection::getType>(sigs::getType);
	cls.op<PT_OP_GET_TYPE, &PhpParameterReflection::getType>();
	cls.method<&PhpParameterReflection::passedByReference>(sigs::passedByReference);
	cls.op<PT_OP_PASSED_BY_REFERENCE, &PhpParameterReflection::passedByReference>();
	cls.method<&PhpParameterReflection::isVariadic>(sigs::isVariadic);
	cls.op<PT_OP_IS_VARIADIC, &PhpParameterReflection::isVariadic>();
	cls.method<&PhpParameterReflection::getPhpDocType>(sigs::getPhpDocType);
	cls.method<&PhpParameterReflection::hasNativeType>(sigs::hasNativeType);
	cls.method<&PhpParameterReflection::getNativeType>(sigs::getNativeType);
	cls.method<&PhpParameterReflection::getDefaultValue>(sigs::getDefaultValue);
	cls.op<PT_OP_GET_DEFAULT_VALUE, &PhpParameterReflection::getDefaultValue>();
	cls.method<&PhpParameterReflection::getOutType>(sigs::getOutType);
	cls.method<&PhpParameterReflection::isImmediatelyInvokedCallable>(sigs::isImmediatelyInvokedCallable);
	cls.method<&PhpParameterReflection::getClosureThisType>(sigs::getClosureThisType);
	cls.method<&PhpParameterReflection::getAttributes>(sigs::getAttributes);
	cls.method<&PhpParameterReflection::getAllowedConstants>(sigs::getAllowedConstants);

	cls.method(sigs::checkAllowedConstants, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *constants;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_ARRAY(constants)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_PPR_THIS.checkAllowedConstants(constants));
	});

	cls.method<&PhpParameterReflection::isPureUnlessCallableIsImpureParameter>(sigs::isPureUnlessCallableIsImpureParameter);

	cls.shadow(&pt_ce_php_parameter_reflection);
}

/* }}} */
