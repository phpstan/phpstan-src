/*
 * PHPStanTurbo\ExtendedFunctionVariant — native implementation of
 * PHPStan\Reflection\ExtendedFunctionVariant.
 *
 * The non-final @api FunctionVariant with separate PHPDoc and native return
 * types: its two promoted slots follow the parent's six, the constructor
 * writes them and runs the parent's constructor body directly
 * (pt_function_variant_construct() — the parent method is native, nothing
 * can sit in between). getParameters() is parent::getParameters(). Native
 * creators use pt_extended_function_variant_new(); native callers read the
 * getters through pt_parameters_acceptor_call() (AcceptorValues.h).
 */

#include "support.h"
#include "generated/ExtendedFunctionVariant.h"
#include "generated/FunctionVariant.h"

namespace slots = ptdecl::ExtendedFunctionVariant::slot;
namespace sigs = ptdecl::ExtendedFunctionVariant::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "AcceptorValues.h"

zend_class_entry *pt_ce_extended_function_variant = NULL;

namespace phpstanturbo {

/* Mirrors PHPStan\Reflection\ExtendedFunctionVariant; UNDEF = pending
 * exception. */
class ExtendedFunctionVariant
{
public:
	explicit ExtendedFunctionVariant(zend_object *self) : self(self) {}

	/* the promoted slots, then parent::__construct(...) (NULL for the
	 * nullable nulls); false = pending exception */
	[[nodiscard]] bool construct(zval *templateTypeMap, zval *resolvedTemplateTypeMap, zval *parameters, bool isVariadic, zval *returnType, zval *phpDocReturnType, zval *nativeReturnType, zval *callSiteVarianceMap) const
	{
		pt_write_slot(self, slots::phpDocReturnType, phpDocReturnType);
		pt_write_slot(self, slots::nativeReturnType, nativeReturnType);
		return pt_function_variant_construct(self, templateTypeMap, resolvedTemplateTypeMap, parameters, isVariadic, returnType, callSiteVarianceMap);
	}

	/* parent::getParameters() */
	zv::Val getParameters() const
	{
		zval *parameters = pt_typed_slot(self, ptdecl::FunctionVariant::slot::parameters, pt_ce_function_variant, "parameters");
		return parameters != NULL ? zv::Val::copyOf(zv::Ref(parameters)) : zv::Val();
	}

	zv::Val getPhpDocReturnType() const { return copyOf(slots::phpDocReturnType, "phpDocReturnType"); }
	zv::Val getNativeReturnType() const { return copyOf(slots::nativeReturnType, "nativeReturnType"); }

private:
	zend_object *self;

	zv::Val copyOf(uint32_t index, const char *propertyName) const
	{
		zval *value = pt_typed_slot(self, index, pt_ce_extended_function_variant, propertyName);
		return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
	}
};

} // namespace phpstanturbo

using phpstanturbo::ExtendedFunctionVariant;

/* {{{ direct entries (support.h) */

bool pt_extended_function_variant_construct(zend_object *variant, zval *templateTypeMap, zval *resolvedTemplateTypeMap, zval *parameters, bool isVariadic, zval *returnType, zval *phpDocReturnType, zval *nativeReturnType, zval *callSiteVarianceMap)
{
	return ExtendedFunctionVariant(variant).construct(templateTypeMap, resolvedTemplateTypeMap, parameters, isVariadic, returnType, phpDocReturnType, nativeReturnType, callSiteVarianceMap);
}

zv::Val pt_extended_function_variant_new(uint32_t argc, zval *argv)
{
	if (EXPECTED(argc >= 7 && argc <= 8)) {
		zval *callSiteVarianceMap = argc == 8 ? &argv[7] : NULL;
		if (EXPECTED(Z_TYPE(argv[0]) == IS_OBJECT && (Z_TYPE(argv[1]) == IS_OBJECT || Z_TYPE(argv[1]) == IS_NULL) && Z_TYPE(argv[2]) == IS_ARRAY
			&& (Z_TYPE(argv[3]) == IS_TRUE || Z_TYPE(argv[3]) == IS_FALSE) && Z_TYPE(argv[4]) == IS_OBJECT && Z_TYPE(argv[5]) == IS_OBJECT && Z_TYPE(argv[6]) == IS_OBJECT
			&& (callSiteVarianceMap == NULL || Z_TYPE_P(callSiteVarianceMap) == IS_OBJECT || Z_TYPE_P(callSiteVarianceMap) == IS_NULL))) {
			zval object;
			if (UNEXPECTED(object_init_ex(&object, pt_ce_extended_function_variant) != SUCCESS)) return zv::Val();
			zv::Val result = zv::Val::adopt(object);
			if (callSiteVarianceMap != NULL && Z_TYPE_P(callSiteVarianceMap) == IS_NULL) callSiteVarianceMap = NULL;
			if (UNEXPECTED(!ExtendedFunctionVariant(Z_OBJ_P(result.raw())).construct(&argv[0], Z_TYPE(argv[1]) == IS_NULL ? NULL : &argv[1], &argv[2], Z_TYPE(argv[3]) == IS_TRUE, &argv[4], &argv[5], &argv[6], callSiteVarianceMap))) return zv::Val();
			return result;
		}
	}
	return pt_type_new_ce(pt_ce_extended_function_variant, argc, argv);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_EFV_THIS ExtendedFunctionVariant(Z_OBJ_P(ZEND_THIS))

void pt_register_extended_function_variant()
{
	reg::Class cls("PHPStan\\Reflection\\ExtendedFunctionVariant");
	ptdecl::ExtendedFunctionVariant::declareClass(cls);
	ptdecl::ExtendedFunctionVariant::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *templateTypeMap, *resolvedTemplateTypeMap, *parameters, *returnType, *phpDocReturnType, *nativeReturnType, *callSiteVarianceMap = NULL;
		bool isVariadic;
		ZEND_PARSE_PARAMETERS_START(7, 8)
			Z_PARAM_OBJECT_OF_CLASS(templateTypeMap, pt_ce_template_type_map)
			Z_PARAM_OBJECT_OF_CLASS_OR_NULL(resolvedTemplateTypeMap, pt_ce_template_type_map)
			Z_PARAM_ARRAY(parameters)
			Z_PARAM_BOOL(isVariadic)
			Z_PARAM_OBJECT_OF_CLASS(returnType, pt_class(PT_CLASS_TYPE))
			Z_PARAM_OBJECT_OF_CLASS(phpDocReturnType, pt_class(PT_CLASS_TYPE))
			Z_PARAM_OBJECT_OF_CLASS(nativeReturnType, pt_class(PT_CLASS_TYPE))
			Z_PARAM_OPTIONAL
			Z_PARAM_OBJECT_OF_CLASS_OR_NULL(callSiteVarianceMap, pt_ce_template_type_variance_map)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!PT_EFV_THIS.construct(templateTypeMap, resolvedTemplateTypeMap, parameters, isVariadic, returnType, phpDocReturnType, nativeReturnType, callSiteVarianceMap))) RETURN_THROWS();
	});

	cls.method<&ExtendedFunctionVariant::getParameters>(sigs::getParameters);
	cls.method<&ExtendedFunctionVariant::getPhpDocReturnType>(sigs::getPhpDocReturnType);
	cls.method<&ExtendedFunctionVariant::getNativeReturnType>(sigs::getNativeReturnType);

	cls.shadow(&pt_ce_extended_function_variant);
}

/* }}} */
