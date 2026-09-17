/*
 * PHPStanTurbo\ExtendedCallableFunctionVariant — native implementation of
 * PHPStan\Reflection\ExtendedCallableFunctionVariant.
 *
 * The final ExtendedFunctionVariant of a closure or callable type, with the
 * CallableParametersAcceptor answers in nine more promoted slots (after the
 * parent's eight); the constructor writes them and runs the parent's
 * constructor body directly. getAsserts() / isStaticClosure() default a null
 * slot to Assertions::createEmpty() / TrinaryLogic::createMaybe(). Native
 * creators use pt_extended_callable_function_variant_new(); native callers
 * read the getters through pt_parameters_acceptor_call() (AcceptorValues.h).
 */

#include "support.h"
#include "generated/ExtendedCallableFunctionVariant.h"

namespace slots = ptdecl::ExtendedCallableFunctionVariant::slot;
namespace sigs = ptdecl::ExtendedCallableFunctionVariant::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "AcceptorValues.h"

zend_class_entry *pt_ce_extended_callable_function_variant = NULL;

namespace phpstanturbo {

/* Mirrors PHPStan\Reflection\ExtendedCallableFunctionVariant; UNDEF =
 * pending exception. */
class ExtendedCallableFunctionVariant
{
public:
	explicit ExtendedCallableFunctionVariant(zend_object *self) : self(self) {}

	/* the promoted slots, then parent::__construct(...) (NULL for the
	 * nullable nulls); false = pending exception */
	[[nodiscard]] bool construct(zval *templateTypeMap, zval *resolvedTemplateTypeMap, zval *parameters, bool isVariadic, zval *returnType, zval *phpDocReturnType, zval *nativeReturnType, zval *callSiteVarianceMap, zval *throwPoints, zval *isPure, zval *impurePoints, zval *invalidateExpressions, zval *usedVariables, zval *acceptsNamedArguments, zval *mustUseReturnValue, zval *assertions, zval *isStatic) const
	{
		zval null;
		ZVAL_NULL(&null);
		pt_write_slot(self, slots::throwPoints, throwPoints);
		pt_write_slot(self, slots::isPure, isPure);
		pt_write_slot(self, slots::impurePoints, impurePoints);
		pt_write_slot(self, slots::invalidateExpressions, invalidateExpressions);
		pt_write_slot(self, slots::usedVariables, usedVariables);
		pt_write_slot(self, slots::acceptsNamedArguments, acceptsNamedArguments);
		pt_write_slot(self, slots::mustUseReturnValue, mustUseReturnValue);
		pt_write_slot(self, slots::assertions, assertions != NULL ? assertions : &null);
		pt_write_slot(self, slots::isStatic, isStatic != NULL ? isStatic : &null);
		return pt_extended_function_variant_construct(self, templateTypeMap, resolvedTemplateTypeMap, parameters, isVariadic, returnType, phpDocReturnType, nativeReturnType, callSiteVarianceMap);
	}

	zv::Val getThrowPoints() const { return call(PT_PA_GET_THROW_POINTS); }
	zv::Val isPure() const { return call(PT_PA_IS_PURE); }
	zv::Val getImpurePoints() const { return call(PT_PA_GET_IMPURE_POINTS); }
	zv::Val getInvalidateExpressions() const { return call(PT_PA_GET_INVALIDATE_EXPRESSIONS); }
	zv::Val getUsedVariables() const { return call(PT_PA_GET_USED_VARIABLES); }
	zv::Val acceptsNamedArguments() const { return call(PT_PA_ACCEPTS_NAMED_ARGUMENTS); }
	zv::Val mustUseReturnValue() const { return call(PT_PA_MUST_USE_RETURN_VALUE); }
	zv::Val getAsserts() const { return call(PT_PA_GET_ASSERTS); }
	zv::Val isStaticClosure() const { return call(PT_PA_IS_STATIC_CLOSURE); }

private:
	zend_object *self;

	/* the shared variant bodies (FunctionVariant.cpp) */
	zv::Val call(pt_parameters_acceptor_member member) const
	{
		return pt_function_variant_call(self, member);
	}
};

} // namespace phpstanturbo

using phpstanturbo::ExtendedCallableFunctionVariant;

/* {{{ direct entries (support.h) */

zv::Val pt_extended_callable_function_variant_new(uint32_t argc, zval *argv)
{
	if (EXPECTED(argc >= 15 && argc <= 17)) {
		auto isObjectOrNull = [](zval *value) { return Z_TYPE_P(value) == IS_OBJECT || Z_TYPE_P(value) == IS_NULL; };
		zval *assertions = argc >= 16 ? &argv[15] : NULL;
		zval *isStatic = argc >= 17 ? &argv[16] : NULL;
		if (EXPECTED(Z_TYPE(argv[0]) == IS_OBJECT && isObjectOrNull(&argv[1]) && Z_TYPE(argv[2]) == IS_ARRAY && (Z_TYPE(argv[3]) == IS_TRUE || Z_TYPE(argv[3]) == IS_FALSE)
			&& Z_TYPE(argv[4]) == IS_OBJECT && Z_TYPE(argv[5]) == IS_OBJECT && Z_TYPE(argv[6]) == IS_OBJECT && isObjectOrNull(&argv[7])
			&& Z_TYPE(argv[8]) == IS_ARRAY && Z_TYPE(argv[9]) == IS_OBJECT && Z_TYPE(argv[10]) == IS_ARRAY && Z_TYPE(argv[11]) == IS_ARRAY && Z_TYPE(argv[12]) == IS_ARRAY
			&& Z_TYPE(argv[13]) == IS_OBJECT && Z_TYPE(argv[14]) == IS_OBJECT && (assertions == NULL || isObjectOrNull(assertions)) && (isStatic == NULL || isObjectOrNull(isStatic)))) {
			zval object;
			if (UNEXPECTED(object_init_ex(&object, pt_ce_extended_callable_function_variant) != SUCCESS)) return zv::Val();
			zv::Val result = zv::Val::adopt(object);
			auto orNull = [](zval *value) { return value == NULL || Z_TYPE_P(value) == IS_NULL ? NULL : value; };
			if (UNEXPECTED(!ExtendedCallableFunctionVariant(Z_OBJ_P(result.raw())).construct(&argv[0], orNull(&argv[1]), &argv[2], Z_TYPE(argv[3]) == IS_TRUE, &argv[4], &argv[5], &argv[6], orNull(&argv[7]), &argv[8], &argv[9], &argv[10], &argv[11], &argv[12], &argv[13], &argv[14], orNull(assertions), orNull(isStatic)))) return zv::Val();
			return result;
		}
	}
	return pt_type_new_ce(pt_ce_extended_callable_function_variant, argc, argv);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_ECFV_THIS ExtendedCallableFunctionVariant(Z_OBJ_P(ZEND_THIS))

void pt_register_extended_callable_function_variant()
{
	reg::Class cls("PHPStan\\Reflection\\ExtendedCallableFunctionVariant");
	ptdecl::ExtendedCallableFunctionVariant::declareClass(cls);
	ptdecl::ExtendedCallableFunctionVariant::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *templateTypeMap, *resolvedTemplateTypeMap, *parameters, *returnType, *phpDocReturnType, *nativeReturnType, *callSiteVarianceMap;
		zval *throwPoints, *isPure, *impurePoints, *invalidateExpressions, *usedVariables, *acceptsNamedArguments, *mustUseReturnValue, *assertions = NULL, *isStatic = NULL;
		bool isVariadic;
		ZEND_PARSE_PARAMETERS_START(15, 17)
			Z_PARAM_OBJECT_OF_CLASS(templateTypeMap, pt_ce_template_type_map)
			Z_PARAM_OBJECT_OF_CLASS_OR_NULL(resolvedTemplateTypeMap, pt_ce_template_type_map)
			Z_PARAM_ARRAY(parameters)
			Z_PARAM_BOOL(isVariadic)
			Z_PARAM_OBJECT_OF_CLASS(returnType, pt_class(PT_CLASS_TYPE))
			Z_PARAM_OBJECT_OF_CLASS(phpDocReturnType, pt_class(PT_CLASS_TYPE))
			Z_PARAM_OBJECT_OF_CLASS(nativeReturnType, pt_class(PT_CLASS_TYPE))
			Z_PARAM_OBJECT_OF_CLASS_OR_NULL(callSiteVarianceMap, pt_ce_template_type_variance_map)
			Z_PARAM_ARRAY(throwPoints)
			Z_PARAM_OBJECT_OF_CLASS(isPure, pt_ce_trinary)
			Z_PARAM_ARRAY(impurePoints)
			Z_PARAM_ARRAY(invalidateExpressions)
			Z_PARAM_ARRAY(usedVariables)
			Z_PARAM_OBJECT_OF_CLASS(acceptsNamedArguments, pt_ce_trinary)
			Z_PARAM_OBJECT_OF_CLASS(mustUseReturnValue, pt_ce_trinary)
			Z_PARAM_OPTIONAL
			Z_PARAM_OBJECT_OF_CLASS_OR_NULL(assertions, pt_class(PT_CLASS_ASSERTIONS))
			Z_PARAM_OBJECT_OF_CLASS_OR_NULL(isStatic, pt_ce_trinary)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!PT_ECFV_THIS.construct(templateTypeMap, resolvedTemplateTypeMap, parameters, isVariadic, returnType, phpDocReturnType, nativeReturnType, callSiteVarianceMap, throwPoints, isPure, impurePoints, invalidateExpressions, usedVariables, acceptsNamedArguments, mustUseReturnValue, assertions, isStatic))) RETURN_THROWS();
	});

	cls.method<&ExtendedCallableFunctionVariant::getThrowPoints>(sigs::getThrowPoints);
	cls.method<&ExtendedCallableFunctionVariant::isPure>(sigs::isPure);
	cls.method<&ExtendedCallableFunctionVariant::getImpurePoints>(sigs::getImpurePoints);
	cls.method<&ExtendedCallableFunctionVariant::getInvalidateExpressions>(sigs::getInvalidateExpressions);
	cls.method<&ExtendedCallableFunctionVariant::getUsedVariables>(sigs::getUsedVariables);
	cls.method<&ExtendedCallableFunctionVariant::acceptsNamedArguments>(sigs::acceptsNamedArguments);
	cls.method<&ExtendedCallableFunctionVariant::mustUseReturnValue>(sigs::mustUseReturnValue);
	cls.method<&ExtendedCallableFunctionVariant::getAsserts>(sigs::getAsserts);
	cls.method<&ExtendedCallableFunctionVariant::isStaticClosure>(sigs::isStaticClosure);

	cls.shadow(&pt_ce_extended_callable_function_variant);
}

/* }}} */
