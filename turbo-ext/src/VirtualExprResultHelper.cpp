/*
 * PHPStanTurbo\VirtualExprResultHelper — native implementation of
 * PHPStan\Analyser\ExprHandler\Helper\VirtualExprResultHelper.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. createTypeExprResult() and
 * createUnsetOffsetExprResult() — which AssignHandler, the virtual node
 * handlers and UnsetHandler call — are exported as
 * pt_virtual_expr_result_helper_create_type_expr_result() /
 * _create_unset_offset_expr_result() (Engine.h conventions). The twin's
 * closures are native closures capturing what the PHP closures capture: the
 * TypeExpr / NativeTypeExpr typeCallbacks ($expr), the specifyTypesCallback
 * ($this, $expr) and the unset-offset typeCallback ($varResult, $dimResult).
 *
 * ExpressionResult, SpecifiedTypes, DefaultNarrowingHelper and the Type
 * kernel are called through their direct entries; the virtual nodes stay
 * PHP and are read in their slots (VirtualExprHandlers.h).
 */

#include "support.h"
#include "generated/VirtualExprResultHelper.h"

namespace slots = ptdecl::VirtualExprResultHelper::slot;
namespace sigs = ptdecl::VirtualExprResultHelper::sig;
#include "VirtualExprHandlers.h"

zend_class_entry *pt_ce_virtual_expr_result_helper = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Helper\VirtualExprResultHelper;
 * UNDEF = pending exception. */
class VirtualExprResultHelper
{
public:
	explicit VirtualExprResultHelper(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *expressionResultFactory, zval *defaultNarrowingHelper) const
	{
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
	}

	/* Mirrors createTypeExprResult(). */
	zv::Val createTypeExprResult(zval *scope, zval *expr) const
	{
		int isTypeExpr = ptveh::isInstance(expr, PT_CLASS_TYPE_EXPR);
		if (UNEXPECTED(isTypeExpr < 0)) return zv::Val();
		zv::Val typeCallback = isTypeExpr
			? pt_native_closure(&typeExprTypeCallbackBody, expr)
			: pt_native_closure(&nativeTypeExprTypeCallbackBody, expr);
		zv::Val specifyTypesCallback = pt_native_closure(&specifyTypesCallbackBody, self, expr);
		pt_expression_result_args args(scope, scope, expr, false, false, NULL, NULL, typeCallback.raw(), specifyTypesCallback.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* Mirrors createUnsetOffsetExprResult(). */
	zv::Val createUnsetOffsetExprResult(zval *scope, zval *expr, zval *varResult, zval *dimResult) const
	{
		zv::Val typeCallback = pt_native_closure(&unsetOffsetTypeCallbackBody, varResult, dimResult);
		zv::Val specifyTypesCallback = pt_specified_types_empty_specify_callback();
		if (UNEXPECTED(specifyTypesCallback.isUndef())) return zv::Val();
		pt_expression_result_args args(scope, scope, expr, false, false, NULL, NULL, typeCallback.raw(), specifyTypesCallback.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	static constexpr char closureName[] = "PHPStan\\Analyser\\ExprHandler\\Helper\\VirtualExprResultHelper::{closure}";

private:
	zend_object *self;

	/* static fn (bool $nativeTypesPromoted): Type => $expr->getExprType() —
	 * captures: $expr */
	static void typeExprTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) argv;
		if (UNEXPECTED(!ptveh::requireArgs(argc, 1, closureName))) return;
		zv::Val hold;
		zval *type = ptveh::getterRead(ptveh::typeExprExprType, &captures[0], PT_LC("getexprtype"), hold);
		if (UNEXPECTED(type == NULL)) return;
		ZVAL_COPY(return_value, type);
	}

	/* static fn (bool $nativeTypesPromoted): Type => $nativeTypesPromoted ?
	 * $expr->getNativeType() : $expr->getPhpDocType() — captures: $expr */
	static void nativeTypeExprTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptveh::requireArgs(argc, 1, closureName))) return;
		zv::Val hold;
		zval *type = zend_is_true(&argv[0])
			? ptveh::getterRead(ptveh::nativeTypeExprNativeType, &captures[0], PT_LC("getnativetype"), hold)
			: ptveh::getterRead(ptveh::nativeTypeExprPhpdocType, &captures[0], PT_LC("getphpdoctype"), hold);
		if (UNEXPECTED(type == NULL)) return;
		ZVAL_COPY(return_value, type);
	}

	/* fn (TypeSpecifierContext $context, bool $nativeTypesPromoted) =>
	 * $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context) —
	 * captures: $this, $expr */
	static void specifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		ptveh::specifyDefaultTypesBody<slots::defaultNarrowingHelper, closureName>(captures, argc, argv, return_value);
	}

	/* static fn (bool $nativeTypesPromoted): Type => ($nativeTypesPromoted ?
	 * $varResult->getNativeType() : $varResult->getType())->unsetOffset(
	 * $nativeTypesPromoted ? $dimResult->getNativeType() : $dimResult->getType())
	 * — captures: $varResult, $dimResult */
	static void unsetOffsetTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptveh::requireArgs(argc, 1, closureName))) return;
		bool nativeTypesPromoted = zend_is_true(&argv[0]);
		zv::Val type;
		pt_engine_with_stack([&]() {
			zv::Val varType = ptveh::resultType(&captures[0], nativeTypesPromoted);
			if (UNEXPECTED(varType.isUndef())) return;
			zv::Val dimType = ptveh::resultType(&captures[1], nativeTypesPromoted);
			if (UNEXPECTED(dimType.isUndef())) return;
			if (UNEXPECTED(Z_TYPE_P(varType.raw()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function unsetOffset() on %s", zend_zval_value_name(varType.raw()));
				return;
			}
			type = pt_type_call(Z_OBJ_P(varType.raw()), PT_LC("unsetoffset"), 1, dimType.raw());
		});
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::VirtualExprResultHelper;

zv::Val pt_virtual_expr_result_helper_create_type_expr_result(zval *helper, zval *scope, zval *expr)
{
	if (EXPECTED(Z_OBJCE_P(helper) == pt_ce_virtual_expr_result_helper)) return VirtualExprResultHelper(Z_OBJ_P(helper)).createTypeExprResult(scope, expr);
	zv::Args argv{scope, expr};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("createtypeexprresult"), 2, argv);
}

zv::Val pt_virtual_expr_result_helper_create_unset_offset_expr_result(zval *helper, zval *scope, zval *expr, zval *varResult, zval *dimResult)
{
	if (EXPECTED(Z_OBJCE_P(helper) == pt_ce_virtual_expr_result_helper)) return VirtualExprResultHelper(Z_OBJ_P(helper)).createUnsetOffsetExprResult(scope, expr, varResult, dimResult);
	zv::Args argv{scope, expr, varResult, dimResult};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("createunsetoffsetexprresult"), 4, argv);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_virtual_expr_result_helper)
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Helper\\VirtualExprResultHelper");
	ptdecl::VirtualExprResultHelper::declareClass(cls);
	ptdecl::VirtualExprResultHelper::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expressionResultFactory, *defaultNarrowingHelper;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, expressionResultFactory, defaultNarrowingHelper)) RETURN_THROWS();
		VirtualExprResultHelper(Z_OBJ_P(ZEND_THIS)).construct(expressionResultFactory, defaultNarrowingHelper);
	});

	cls.method(sigs::createTypeExprResult, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, scope, expr)) RETURN_THROWS();
		PT_RETURN_VAL(VirtualExprResultHelper(Z_OBJ_P(ZEND_THIS)).createTypeExprResult(scope, expr));
	});

	cls.method(sigs::createUnsetOffsetExprResult, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr, *varResult, *dimResult;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, scope, expr, varResult, dimResult)) RETURN_THROWS();
		PT_RETURN_VAL(VirtualExprResultHelper(Z_OBJ_P(ZEND_THIS)).createUnsetOffsetExprResult(scope, expr, varResult, dimResult));
	});

	cls.shadow(&pt_ce_virtual_expr_result_helper);
}

/* }}} */
