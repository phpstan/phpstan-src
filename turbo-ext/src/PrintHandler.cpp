/*
 * PHPStanTurbo\PrintHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\PrintHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback (nothing) and the
 * specifyTypesCallback ($this, $expr).
 *
 * NodeScopeResolver, ExpressionResult, ExpressionContext,
 * ImplicitToStringCallHelper, ImpurePoint, VariableFlow(Builder),
 * DefaultNarrowingHelper and the Type kernel are called through their direct
 * entries.
 */

#include "support.h"
#include "generated/PrintHandler.h"

namespace slots = ptdecl::PrintHandler::slot;
namespace sigs = ptdecl::PrintHandler::sig;
#include "SimpleExprHandlers.h"

zend_class_entry *pt_ce_print_handler = nullptr;

namespace {

using phpstanturbo::visitors::NodeProp;

NodeProp pt_prh_expr = PT_NODE_PROP(PT_CLASS_PRINT_EXPR, "expr");

/* the twin's literal, a permanent interned string (module startup) */
zend_string *pt_prh_print = nullptr;

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\PrintHandler; UNDEF = pending
 * exception. */
class PrintHandler
{
public:
	static constexpr const char *closureName = "PHPStan\\Analyser\\ExprHandler\\PrintHandler::{closure}";
	static constexpr uint32_t defaultNarrowingHelperSlot = slots::defaultNarrowingHelper;

	explicit PrintHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *implicitToStringCallHelper, zval *expressionResultFactory, zval *defaultNarrowingHelper) const
	{
		pt_write_slot(self, slots::implicitToStringCallHelper, implicitToStringCallHelper);
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		int is = ptoh::isInstance(expr, PT_CLASS_PRINT_EXPR);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *beforeScope = scope;
		zval *inner = ptoh::operand(pt_prh_expr, expr);
		if (UNEXPECTED(inner == NULL)) return zv::Val();
		zv::Val innerContext = pt_expression_context_enter_deep(context);
		if (UNEXPECTED(innerContext.isUndef())) return zv::Val();
		zv::Val exprResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, inner, scope, storage, nodeCallback, innerContext.raw());
		if (UNEXPECTED(exprResult.isUndef())) return zv::Val();
		zv::Val throwPoints = ptse::throwPointsOf(exprResult.raw());
		if (UNEXPECTED(throwPoints.isUndef())) return zv::Val();
		zv::Val impurePoints = ptse::impurePointsOf(exprResult.raw());
		if (UNEXPECTED(impurePoints.isUndef())) return zv::Val();

		zv::Val toStringResult = pt_implicit_to_string_call_helper_process_implicit_to_string_call(OBJ_PROP_NUM(self, slots::implicitToStringCallHelper), inner, scope, exprResult.raw());
		if (UNEXPECTED(toStringResult.isUndef())) return zv::Val();
		{
			zv::Val hold;
			zval *points = pt_expression_result_throw_points(toStringResult.raw(), hold);
			if (UNEXPECTED(points == NULL || !ptse::mergeInto(throwPoints, points))) return zv::Val();
		}
		{
			zv::Val hold;
			zval *points = pt_expression_result_impure_points(toStringResult.raw(), hold);
			if (UNEXPECTED(points == NULL || !ptse::mergeInto(impurePoints, points))) return zv::Val();
		}

		zv::Val resultScope = ptse::scopeOf(exprResult.raw());
		if (UNEXPECTED(resultScope.isUndef())) return zv::Val();

		zv::Val variableFlow;
		{
			zv::Val exprFlow = pt_expression_result_variable_flow(exprResult.raw());
			if (UNEXPECTED(exprFlow.isUndef())) return zv::Val();
			zv::Val throwsFlow = pt_variable_flow_builder_throws(expr, Z_ARRVAL_P(throwPoints.raw()));
			if (UNEXPECTED(throwsFlow.isUndef())) return zv::Val();
			zv::Args flows{exprFlow.raw(), throwsFlow.raw()};
			variableFlow = pt_variable_flow_sequence(2, flows);
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		}
		bool hasYield, isAlwaysTerminating;
		if (UNEXPECTED(!pt_expression_result_has_yield(exprResult.raw(), hasYield))) return zv::Val();
		if (UNEXPECTED(!pt_expression_result_is_always_terminating(exprResult.raw(), isAlwaysTerminating))) return zv::Val();
		zv::Val impurePoint = pt_impure_point_new(resultScope.raw(), expr, pt_prh_print, pt_prh_print, true);
		if (UNEXPECTED(impurePoint.isUndef())) return zv::Val();
		zv::Val allImpurePoints = ptse::mergeOne(impurePoints.raw(), std::move(impurePoint));
		if (UNEXPECTED(allImpurePoints.isUndef())) return zv::Val();

		zv::Val typeCallback = pt_native_closure(&typeCallbackBody);
		zv::Val specifyTypesCallback = pt_native_closure(&ptse::specifyDefaultTypesBody<PrintHandler>, self, expr);
		pt_expression_result_args args(resultScope.raw(), beforeScope, expr, hasYield, isAlwaysTerminating, throwPoints.raw(), allImpurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return PrintHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* static fn (bool $nativeTypesPromoted): Type => new ConstantIntegerType(1)
	 * — captures nothing */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) captures;
		(void) argv;
		if (UNEXPECTED(!ptse::requireArgs(argc, 1, closureName))) return;
		if (UNEXPECTED(!pt_constant_integer_type_new(return_value, 1))) ZVAL_NULL(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::PrintHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_print_handler)
{
	pt_prh_print = zend_string_init_interned(PT_LC("print"), 1);

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\PrintHandler");
	ptdecl::PrintHandler::declareClass(cls);
	ptdecl::PrintHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *implicitToStringCallHelper, *expressionResultFactory, *defaultNarrowingHelper;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, implicitToStringCallHelper, expressionResultFactory, defaultNarrowingHelper)) RETURN_THROWS();
		PrintHandler(Z_OBJ_P(ZEND_THIS)).construct(implicitToStringCallHelper, expressionResultFactory, defaultNarrowingHelper);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!PrintHandler::supports(expr, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(PrintHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_print_handler);
	pt_expr_handler_entry_register(&pt_ce_print_handler, &PrintHandler::processExprEntry);
}

/* }}} */
