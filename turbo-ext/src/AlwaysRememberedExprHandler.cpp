/*
 * PHPStanTurbo\AlwaysRememberedExprHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\Virtual\AlwaysRememberedExprHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback ($expr), the
 * specifyTypesCallback ($this, $expr, $innerResult) and the
 * createTypesCallback ($this, $expr, $innerExpr, $innerResult, $beforeScope).
 *
 * NodeScopeResolver, ExpressionResult, MutatingScope, SpecifiedTypes and
 * DefaultNarrowingHelper are called through their direct entries; the
 * virtual node stays PHP and is read in its slot (VirtualExprHandlers.h).
 */

#include "support.h"
#include "generated/AlwaysRememberedExprHandler.h"

namespace slots = ptdecl::AlwaysRememberedExprHandler::slot;
namespace sigs = ptdecl::AlwaysRememberedExprHandler::sig;
#include "VirtualExprHandlers.h"

zend_class_entry *pt_ce_always_remembered_expr_handler = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Virtual\AlwaysRememberedExprHandler;
 * UNDEF = pending exception. */
class AlwaysRememberedExprHandler
{
public:
	explicit AlwaysRememberedExprHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *expressionResultFactory, zval *defaultNarrowingHelper) const
	{
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		return ptveh::supportsInstance(expr, PT_CLASS_ALWAYS_REMEMBERED_EXPR, out);
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *beforeScope = scope;
		zv::Val innerExprHold;
		zval *innerExpr = ptveh::getterRead(ptveh::alwaysRememberedExprExpr, expr, PT_LC("getexpr"), innerExprHold);
		if (UNEXPECTED(innerExpr == NULL)) return zv::Val();
		zv::Val innerResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, innerExpr, scope, storage, nodeCallback, context);
		if (UNEXPECTED(innerResult.isUndef())) return zv::Val();
		zv::Val scopeHold, throwPointsHold, impurePointsHold;
		zval *resultScope = pt_expression_result_scope(innerResult.raw(), scopeHold);
		if (UNEXPECTED(resultScope == NULL)) return zv::Val();

		zv::Val variableFlow = pt_expression_result_variable_flow(innerResult.raw());
		if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		bool hasYield, isAlwaysTerminating;
		if (UNEXPECTED(!pt_expression_result_has_yield(innerResult.raw(), hasYield))) return zv::Val();
		if (UNEXPECTED(!pt_expression_result_is_always_terminating(innerResult.raw(), isAlwaysTerminating))) return zv::Val();
		zval *throwPoints = pt_expression_result_throw_points(innerResult.raw(), throwPointsHold);
		if (UNEXPECTED(throwPoints == NULL)) return zv::Val();
		zval *impurePoints = pt_expression_result_impure_points(innerResult.raw(), impurePointsHold);
		if (UNEXPECTED(impurePoints == NULL)) return zv::Val();

		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, expr);
		// Narrowing by the remembered wrapper is narrowing by the inner
		// expression (TypeSpecifier unwrapped it and specified both keys);
		// the wrapper node itself keeps the default truthy/falsey entry.
		zv::Val specifyTypesCallback = pt_native_closure(&specifyTypesCallbackBody, self, expr, innerResult.raw());
		// A type constraint on the remembered wrapper constrains both the wrapper
		// node (under its __phpstanRemembered(...) key) and the inner expression -
		// what TypeSpecifier::create() recovered by fanning the AlwaysRememberedExpr
		// out into wrapper + inner. The inner composes through its own child result;
		// raw-Expr callers still go through create()->createForExpr.
		zv::Val createTypesCallback = pt_native_closure(&createTypesCallbackBody, self, expr, innerExpr, innerResult.raw(), beforeScope);
		pt_expression_result_args args(resultScope, beforeScope, expr, hasYield, isAlwaysTerminating, throwPoints, impurePoints, typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw()).withCreateTypesCallback(createTypesCallback.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return AlwaysRememberedExprHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

	static constexpr char closureName[] = "PHPStan\\Analyser\\ExprHandler\\Virtual\\AlwaysRememberedExprHandler::{closure}";

private:
	zend_object *self;

	/* static fn (bool $nativeTypesPromoted): Type => $nativeTypesPromoted ?
	 * $expr->getNativeExprType() : $expr->getExprType() — captures: $expr */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptveh::requireArgs(argc, 1, closureName))) return;
		zv::Val hold;
		zval *type = zend_is_true(&argv[0])
			? ptveh::getterRead(ptveh::alwaysRememberedExprNativeType, &captures[0], PT_LC("getnativeexprtype"), hold)
			: ptveh::getterRead(ptveh::alwaysRememberedExprType, &captures[0], PT_LC("getexprtype"), hold);
		if (UNEXPECTED(type == NULL)) return;
		ZVAL_COPY(return_value, type);
	}

	/* fn (TypeSpecifierContext $context, bool $nativeTypesPromoted) =>
	 * $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context)->unionWith(
	 * $innerResult->getSpecifiedTypes($context, $nativeTypesPromoted)) —
	 * captures: $this, $expr, $innerResult */
	static void specifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptveh::requireArgs(argc, 2, closureName))) return;
		zval *context = &argv[0];
		bool nativeTypesPromoted = zend_is_true(&argv[1]);
		zv::Val specifiedTypes;
		pt_engine_with_stack([&]() {
			zv::Val defaultTypes = pt_default_narrowing_helper_specify_default_types(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::defaultNarrowingHelper), &captures[1], context);
			if (UNEXPECTED(defaultTypes.isUndef())) return;
			zv::Val innerTypes = pt_expression_result_get_specified_types(&captures[2], context, nativeTypesPromoted);
			if (UNEXPECTED(innerTypes.isUndef())) return;
			specifiedTypes = pt_specified_types_union_with(Z_OBJ_P(defaultTypes.raw()), innerTypes.raw());
		});
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}

	/* function (Type $type, TypeSpecifierContext $context, bool
	 * $nativeTypesPromoted) use ($expr, $innerExpr, $innerResult, $beforeScope):
	 * SpecifiedTypes — captures: $this, $expr, $innerExpr, $innerResult,
	 * $beforeScope */
	static void createTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptveh::requireArgs(argc, 3, closureName))) return;
		zval *type = &argv[0];
		zval *context = &argv[1];
		bool nativeTypesPromoted = zend_is_true(&argv[2]);
		zv::Val specifiedTypes;
		pt_engine_with_stack([&]() {
			zv::Val scopeHold;
			zval *s = ptveh::promotedScope(&captures[4], nativeTypesPromoted, scopeHold);
			if (UNEXPECTED(s == NULL)) return;

			zval *defaultNarrowingHelper = OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::defaultNarrowingHelper);
			zv::Val wrapperTypes = pt_default_narrowing_helper_create_subject_types(defaultNarrowingHelper, s, &captures[1], NULL, type, context);
			if (UNEXPECTED(wrapperTypes.isUndef())) return;
			zv::Val innerTypes = pt_default_narrowing_helper_create_subject_types(defaultNarrowingHelper, s, &captures[2], &captures[3], type, context);
			if (UNEXPECTED(innerTypes.isUndef())) return;
			specifiedTypes = pt_specified_types_union_with(Z_OBJ_P(wrapperTypes.raw()), innerTypes.raw());
		});
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::AlwaysRememberedExprHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_always_remembered_expr_handler)
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Virtual\\AlwaysRememberedExprHandler");
	ptdecl::AlwaysRememberedExprHandler::declareClass(cls);
	ptdecl::AlwaysRememberedExprHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expressionResultFactory, *defaultNarrowingHelper;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, expressionResultFactory, defaultNarrowingHelper)) RETURN_THROWS();
		AlwaysRememberedExprHandler(Z_OBJ_P(ZEND_THIS)).construct(expressionResultFactory, defaultNarrowingHelper);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!AlwaysRememberedExprHandler::supports(expr, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.method(sigs::processExpr, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *stmt, *expr, *scope, *storage, *nodeCallback, *context;
		ZEND_PARSE_PARAMETERS_START(7, 7)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(AlwaysRememberedExprHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_always_remembered_expr_handler);
	pt_expr_handler_entry_register(&pt_ce_always_remembered_expr_handler, &AlwaysRememberedExprHandler::processExprEntry);
}

/* }}} */
