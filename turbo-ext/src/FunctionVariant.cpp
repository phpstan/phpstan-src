/*
 * PHPStanTurbo\FunctionVariant — native implementation of
 * PHPStan\Reflection\FunctionVariant.
 *
 * A non-final @api value class over the variant's template type maps,
 * parameters, variadicness, return type and call-site variance map (the
 * declared $callSiteVarianceMap slot first, then the promoted ones, in the
 * twin's order). Its getters read `$this`'s own slots, the same slots for
 * ExtendedFunctionVariant, ExtendedCallableFunctionVariant and any PHP
 * subclass. The constructor body is exported for the subclasses'
 * parent::__construct().
 *
 * This file also holds the slow half of the ParametersAcceptor dispatch
 * (AcceptorValues.h): the native bodies of the variant classes behind
 * pt_parameters_acceptor_call_slow(), and one cached method site per member
 * for every other acceptor.
 */

#include "support.h"
#include "generated/FunctionVariant.h"

namespace slots = ptdecl::FunctionVariant::slot;
namespace sigs = ptdecl::FunctionVariant::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AcceptorValues.h"

zend_class_entry *pt_ce_function_variant = NULL;

namespace phpstanturbo {

/* Mirrors PHPStan\Reflection\FunctionVariant; UNDEF = pending exception. */
class FunctionVariant
{
public:
	explicit FunctionVariant(zend_object *self) : self(self) {}

	/* the promoted slots in parameter order, then $this->callSiteVarianceMap =
	 * $callSiteVarianceMap ?? TemplateTypeVarianceMap::createEmpty()
	 * ($resolvedTemplateTypeMap / $callSiteVarianceMap NULL for null);
	 * false = pending exception */
	[[nodiscard]] bool construct(zval *templateTypeMap, zval *resolvedTemplateTypeMap, zval *parameters, bool isVariadic, zval *returnType, zval *callSiteVarianceMap) const
	{
		zval value;
		pt_write_slot(self, slots::templateTypeMap, templateTypeMap);
		if (resolvedTemplateTypeMap != NULL) {
			pt_write_slot(self, slots::resolvedTemplateTypeMap, resolvedTemplateTypeMap);
		} else {
			ZVAL_NULL(&value);
			pt_write_slot(self, slots::resolvedTemplateTypeMap, &value);
		}
		pt_write_slot(self, slots::parameters, parameters);
		ZVAL_BOOL(&value, isVariadic);
		pt_write_slot(self, slots::isVariadic, &value);
		pt_write_slot(self, slots::returnType, returnType);
		if (callSiteVarianceMap != NULL) {
			pt_write_slot(self, slots::callSiteVarianceMap, callSiteVarianceMap);
			return true;
		}
		if (UNEXPECTED(!pt_template_type_variance_map_empty(&value))) return false;
		pt_write_slot(self, slots::callSiteVarianceMap, &value);
		zval_ptr_dtor(&value);
		return true;
	}

	zv::Val getTemplateTypeMap() const { return copyOf(slots::templateTypeMap, "templateTypeMap"); }

	/* $this->resolvedTemplateTypeMap ?? TemplateTypeMap::createEmpty() — `??`
	 * reads an uninitialized property as null */
	zv::Val getResolvedTemplateTypeMap() const
	{
		zval *map = OBJ_PROP_NUM(self, slots::resolvedTemplateTypeMap);
		if (Z_TYPE_P(map) > IS_NULL) return zv::Val::copyOf(zv::Ref(map));
		zval empty;
		if (UNEXPECTED(!pt_template_type_map_empty(&empty))) return zv::Val();
		return zv::Val::adopt(empty);
	}

	zv::Val getCallSiteVarianceMap() const { return copyOf(slots::callSiteVarianceMap, "callSiteVarianceMap"); }
	zv::Val getParameters() const { return copyOf(slots::parameters, "parameters"); }
	zv::Val isVariadic() const { return copyOf(slots::isVariadic, "isVariadic"); }
	zv::Val getReturnType() const { return copyOf(slots::returnType, "returnType"); }

private:
	zend_object *self;

	/* a slot of FunctionVariant's own (borrowed); NULL with the engine's Error
	 * pending before the constructor initialized it */
	zval *slot(uint32_t index, const char *propertyName) const
	{
		return pt_typed_slot(self, index, pt_ce_function_variant, propertyName);
	}

	zv::Val copyOf(uint32_t index, const char *propertyName) const
	{
		zval *value = slot(index, propertyName);
		return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
	}
};

} // namespace phpstanturbo

using phpstanturbo::FunctionVariant;

/* {{{ direct entries (support.h) */

namespace {

/* the ParametersAcceptor / ExtendedParametersAcceptor / ResolvedFunctionVariant
 * / CallableParametersAcceptor methods by member */
struct MemberName
{
	const char *lcname;
	size_t len;
	const char *name;
};

#define PT_FV_MEMBER(lc, name) { lc, sizeof(lc) - 1, name }

const MemberName memberNames[PT_PA_MEMBER_COUNT] = {
	/* PT_PA_GET_TEMPLATE_TYPE_MAP */ PT_FV_MEMBER("gettemplatetypemap", "getTemplateTypeMap"),
	/* PT_PA_GET_RESOLVED_TEMPLATE_TYPE_MAP */ PT_FV_MEMBER("getresolvedtemplatetypemap", "getResolvedTemplateTypeMap"),
	/* PT_PA_GET_PARAMETERS */ PT_FV_MEMBER("getparameters", "getParameters"),
	/* PT_PA_IS_VARIADIC */ PT_FV_MEMBER("isvariadic", "isVariadic"),
	/* PT_PA_GET_RETURN_TYPE */ PT_FV_MEMBER("getreturntype", "getReturnType"),
	/* PT_PA_GET_PHPDOC_RETURN_TYPE */ PT_FV_MEMBER("getphpdocreturntype", "getPhpDocReturnType"),
	/* PT_PA_GET_NATIVE_RETURN_TYPE */ PT_FV_MEMBER("getnativereturntype", "getNativeReturnType"),
	/* PT_PA_GET_CALL_SITE_VARIANCE_MAP */ PT_FV_MEMBER("getcallsitevariancemap", "getCallSiteVarianceMap"),
	/* PT_PA_GET_ORIGINAL_PARAMETERS_ACCEPTOR */ PT_FV_MEMBER("getoriginalparametersacceptor", "getOriginalParametersAcceptor"),
	/* PT_PA_GET_RETURN_TYPE_WITH_UNRESOLVABLE_TEMPLATE_TYPES */ PT_FV_MEMBER("getreturntypewithunresolvabletemplatetypes", "getReturnTypeWithUnresolvableTemplateTypes"),
	/* PT_PA_GET_THROW_POINTS */ PT_FV_MEMBER("getthrowpoints", "getThrowPoints"),
	/* PT_PA_IS_PURE */ PT_FV_MEMBER("ispure", "isPure"),
	/* PT_PA_GET_IMPURE_POINTS */ PT_FV_MEMBER("getimpurepoints", "getImpurePoints"),
	/* PT_PA_GET_INVALIDATE_EXPRESSIONS */ PT_FV_MEMBER("getinvalidateexpressions", "getInvalidateExpressions"),
	/* PT_PA_GET_USED_VARIABLES */ PT_FV_MEMBER("getusedvariables", "getUsedVariables"),
	/* PT_PA_ACCEPTS_NAMED_ARGUMENTS */ PT_FV_MEMBER("acceptsnamedarguments", "acceptsNamedArguments"),
	/* PT_PA_MUST_USE_RETURN_VALUE */ PT_FV_MEMBER("mustusereturnvalue", "mustUseReturnValue"),
	/* PT_PA_GET_ASSERTS */ PT_FV_MEMBER("getasserts", "getAsserts"),
	/* PT_PA_IS_STATIC_CLOSURE */ PT_FV_MEMBER("isstaticclosure", "isStaticClosure"),
};

#undef PT_FV_MEMBER

pt_method_site memberSites[PT_PA_MEMBER_COUNT];

/* a declared slot of a native variant for its getter (the property's
 * declaring class names the uninitialized-read Error) */
zv::Val variantSlot(zend_object *variant, uint32_t index, zend_class_entry *declaringClass, const char *propertyName)
{
	zval *value = pt_typed_slot(variant, index, declaringClass, propertyName);
	return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
}

} // namespace

bool pt_function_variant_construct(zend_object *variant, zval *templateTypeMap, zval *resolvedTemplateTypeMap, zval *parameters, bool isVariadic, zval *returnType, zval *callSiteVarianceMap)
{
	return FunctionVariant(variant).construct(templateTypeMap, resolvedTemplateTypeMap, parameters, isVariadic, returnType, callSiteVarianceMap);
}

zv::Val pt_parameters_acceptor_call_method(zend_object *acceptor, pt_parameters_acceptor_member member)
{
	const MemberName &name = memberNames[member];
	return pt_call_method_cached(memberSites[member], acceptor, name.lcname, name.len, 0, NULL);
}

zv::Val pt_function_variant_call(zend_object *variant, pt_parameters_acceptor_member member)
{
	namespace efv = ptdecl::ExtendedFunctionVariant::slot;
	namespace ecfv = ptdecl::ExtendedCallableFunctionVariant::slot;
	zend_class_entry *ce = variant->ce;
	bool extended = ce == pt_ce_extended_function_variant || ce == pt_ce_extended_callable_function_variant;
	bool callable = ce == pt_ce_extended_callable_function_variant;
	switch (member) {
		case PT_PA_GET_TEMPLATE_TYPE_MAP: return FunctionVariant(variant).getTemplateTypeMap();
		case PT_PA_GET_RESOLVED_TEMPLATE_TYPE_MAP: return FunctionVariant(variant).getResolvedTemplateTypeMap();
		case PT_PA_GET_PARAMETERS: return FunctionVariant(variant).getParameters();
		case PT_PA_IS_VARIADIC: return FunctionVariant(variant).isVariadic();
		case PT_PA_GET_RETURN_TYPE: return FunctionVariant(variant).getReturnType();
		case PT_PA_GET_CALL_SITE_VARIANCE_MAP: return FunctionVariant(variant).getCallSiteVarianceMap();
		case PT_PA_GET_PHPDOC_RETURN_TYPE:
			if (!extended) break;
			return variantSlot(variant, efv::phpDocReturnType, pt_ce_extended_function_variant, "phpDocReturnType");
		case PT_PA_GET_NATIVE_RETURN_TYPE:
			if (!extended) break;
			return variantSlot(variant, efv::nativeReturnType, pt_ce_extended_function_variant, "nativeReturnType");
		case PT_PA_GET_THROW_POINTS:
			if (!callable) break;
			return variantSlot(variant, ecfv::throwPoints, pt_ce_extended_callable_function_variant, "throwPoints");
		case PT_PA_IS_PURE:
			if (!callable) break;
			return variantSlot(variant, ecfv::isPure, pt_ce_extended_callable_function_variant, "isPure");
		case PT_PA_GET_IMPURE_POINTS:
			if (!callable) break;
			return variantSlot(variant, ecfv::impurePoints, pt_ce_extended_callable_function_variant, "impurePoints");
		case PT_PA_GET_INVALIDATE_EXPRESSIONS:
			if (!callable) break;
			return variantSlot(variant, ecfv::invalidateExpressions, pt_ce_extended_callable_function_variant, "invalidateExpressions");
		case PT_PA_GET_USED_VARIABLES:
			if (!callable) break;
			return variantSlot(variant, ecfv::usedVariables, pt_ce_extended_callable_function_variant, "usedVariables");
		case PT_PA_ACCEPTS_NAMED_ARGUMENTS:
			if (!callable) break;
			return variantSlot(variant, ecfv::acceptsNamedArguments, pt_ce_extended_callable_function_variant, "acceptsNamedArguments");
		case PT_PA_MUST_USE_RETURN_VALUE:
			if (!callable) break;
			return variantSlot(variant, ecfv::mustUseReturnValue, pt_ce_extended_callable_function_variant, "mustUseReturnValue");
		case PT_PA_GET_ASSERTS: {
			if (!callable) break;
			/* $this->assertions ?? Assertions::createEmpty() (`??` reads an
			 * uninitialized property as null) */
			zval *assertions = OBJ_PROP_NUM(variant, ecfv::assertions);
			if (Z_TYPE_P(assertions) > IS_NULL) return zv::Val::copyOf(zv::Ref(assertions));
			return pt_assertions_create_empty();
		}
		case PT_PA_IS_STATIC_CLOSURE: {
			if (!callable) break;
			/* $this->isStatic ?? TrinaryLogic::createMaybe() */
			zval *isStatic = OBJ_PROP_NUM(variant, ecfv::isStatic);
			return zv::Val::copyOf(zv::Ref(Z_TYPE_P(isStatic) > IS_NULL ? isStatic : pt_trinary_singleton(PT_TRI_MAYBE)));
		}
		case PT_PA_GET_ORIGINAL_PARAMETERS_ACCEPTOR:
		case PT_PA_GET_RETURN_TYPE_WITH_UNRESOLVABLE_TEMPLATE_TYPES:
			break;
		case PT_PA_MEMBER_COUNT:
			ZEND_UNREACHABLE();
			break;
	}
	/* a method the class does not declare: the engine's Error */
	return pt_parameters_acceptor_call_method(variant, member);
}

zv::Val pt_parameters_acceptor_call_slow(zval *acceptor, pt_parameters_acceptor_member member)
{
	if (UNEXPECTED(Z_TYPE_P(acceptor) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", memberNames[member].name, zend_zval_value_name(acceptor));
		return zv::Val();
	}
	zend_object *object = Z_OBJ_P(acceptor);
	zend_class_entry *ce = object->ce;
	if (EXPECTED(ce == pt_ce_resolved_function_variant_with_original)) return pt_resolved_function_variant_with_original_call(object, member);
	if (ce == pt_ce_extended_function_variant || ce == pt_ce_function_variant || ce == pt_ce_extended_callable_function_variant) return pt_function_variant_call(object, member);
	if (ce == pt_ce_trivial_parameters_acceptor) return pt_trivial_parameters_acceptor_call(object, member);
	return pt_parameters_acceptor_call_method(object, member);
}

zv::Val pt_function_variant_new(uint32_t argc, zval *argv)
{
	if (EXPECTED(argc >= 5 && argc <= 6)) {
		zval *callSiteVarianceMap = argc == 6 ? &argv[5] : NULL;
		if (EXPECTED(Z_TYPE(argv[0]) == IS_OBJECT && (Z_TYPE(argv[1]) == IS_OBJECT || Z_TYPE(argv[1]) == IS_NULL) && Z_TYPE(argv[2]) == IS_ARRAY
			&& (Z_TYPE(argv[3]) == IS_TRUE || Z_TYPE(argv[3]) == IS_FALSE) && Z_TYPE(argv[4]) == IS_OBJECT
			&& (callSiteVarianceMap == NULL || Z_TYPE_P(callSiteVarianceMap) == IS_OBJECT || Z_TYPE_P(callSiteVarianceMap) == IS_NULL))) {
			zval object;
			if (UNEXPECTED(object_init_ex(&object, pt_ce_function_variant) != SUCCESS)) return zv::Val();
			zv::Val result = zv::Val::adopt(object);
			if (callSiteVarianceMap != NULL && Z_TYPE_P(callSiteVarianceMap) == IS_NULL) callSiteVarianceMap = NULL;
			if (UNEXPECTED(!FunctionVariant(Z_OBJ_P(result.raw())).construct(&argv[0], Z_TYPE(argv[1]) == IS_NULL ? NULL : &argv[1], &argv[2], Z_TYPE(argv[3]) == IS_TRUE, &argv[4], callSiteVarianceMap))) return zv::Val();
			return result;
		}
	}
	return pt_type_new_ce(pt_ce_function_variant, argc, argv);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_FV_THIS FunctionVariant(Z_OBJ_P(ZEND_THIS))

void pt_register_function_variant()
{
	reg::Class cls("PHPStan\\Reflection\\FunctionVariant");
	ptdecl::FunctionVariant::declareClass(cls);
	ptdecl::FunctionVariant::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *templateTypeMap = NULL, *resolvedTemplateTypeMap = NULL, *parameters = NULL, *returnType = NULL, *callSiteVarianceMap = NULL;
		bool isVariadic;
		ZEND_PARSE_PARAMETERS_START(5, 6)
			Z_PARAM_OBJECT_OF_CLASS(templateTypeMap, pt_ce_template_type_map)
			Z_PARAM_OBJECT_OF_CLASS_OR_NULL(resolvedTemplateTypeMap, pt_ce_template_type_map)
			Z_PARAM_ARRAY(parameters)
			Z_PARAM_BOOL(isVariadic)
			Z_PARAM_OBJECT_OF_CLASS(returnType, pt_class(PT_CLASS_TYPE))
			Z_PARAM_OPTIONAL
			Z_PARAM_OBJECT_OF_CLASS_OR_NULL(callSiteVarianceMap, pt_ce_template_type_variance_map)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!PT_FV_THIS.construct(templateTypeMap, resolvedTemplateTypeMap, parameters, isVariadic, returnType, callSiteVarianceMap))) RETURN_THROWS();
	});

	cls.method<&FunctionVariant::getTemplateTypeMap>(sigs::getTemplateTypeMap);
	cls.method<&FunctionVariant::getResolvedTemplateTypeMap>(sigs::getResolvedTemplateTypeMap);
	cls.method<&FunctionVariant::getCallSiteVarianceMap>(sigs::getCallSiteVarianceMap);
	cls.method<&FunctionVariant::getParameters>(sigs::getParameters);
	cls.method<&FunctionVariant::isVariadic>(sigs::isVariadic);
	cls.method<&FunctionVariant::getReturnType>(sigs::getReturnType);

	cls.shadow(&pt_ce_function_variant);
}

/* }}} */
