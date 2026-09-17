/*
 * PHPStanTurbo\EvalHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\EvalHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback (nothing) and the
 * specifyTypesCallback ($this, $expr).
 *
 * NodeScopeResolver, ExpressionResult, ExpressionContext, MutatingScope,
 * InternalThrowPoint, ImpurePoint, VariableFlow, DefaultNarrowingHelper and
 * the Type kernel are called through their direct entries.
 */

#include "support.h"
#include "generated/EvalHandler.h"

namespace slots = ptdecl::EvalHandler::slot;
namespace sigs = ptdecl::EvalHandler::sig;
#include "SimpleExprHandlers.h"

zend_class_entry *pt_ce_eval_handler = nullptr;

namespace {

using phpstanturbo::visitors::NodeProp;

NodeProp pt_evh_expr = PT_NODE_PROP(PT_CLASS_EVAL_EXPR, "expr");

/* the twin's literal, a permanent interned string (module startup) */
zend_string *pt_evh_eval = nullptr;

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\EvalHandler; UNDEF = pending
 * exception. */
class EvalHandler
{
public:
	static constexpr const char *closureName = "PHPStan\\Analyser\\ExprHandler\\EvalHandler::{closure}";
	static constexpr uint32_t defaultNarrowingHelperSlot = slots::defaultNarrowingHelper;

	explicit EvalHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *expressionResultFactory, zval *defaultNarrowingHelper) const
	{
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		int is = ptoh::isInstance(expr, PT_CLASS_EVAL_EXPR);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *beforeScope = scope;
		zval *inner = ptoh::operand(pt_evh_expr, expr);
		if (UNEXPECTED(inner == NULL)) return zv::Val();
		zv::Val innerContext = pt_expression_context_enter_deep(context);
		if (UNEXPECTED(innerContext.isUndef())) return zv::Val();
		zv::Val exprResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, inner, scope, storage, nodeCallback, innerContext.raw());
		if (UNEXPECTED(exprResult.isUndef())) return zv::Val();
		ptse::ChildResult child;
		if (UNEXPECTED(!child.read(exprResult.raw()))) return zv::Val();
		// the evaluated code may read any variable
		zv::Val resultScope = pt_mutating_scope_invalidate_volatile_expressions(Z_OBJ_P(child.scope));
		if (UNEXPECTED(resultScope.isUndef())) return zv::Val();

		zv::Val throwPoint = pt_internal_throw_point_create_implicit(resultScope.raw(), expr);
		if (UNEXPECTED(throwPoint.isUndef())) return zv::Val();

		zv::Val variableFlow;
		{
			zv::Val readAll = pt_variable_flow_all_read_all();
			if (UNEXPECTED(readAll.isUndef())) return zv::Val();
			zv::Val typeHold;
			zval *throwPointType = pt_internal_throw_point_type(throwPoint.raw(), typeHold);
			if (UNEXPECTED(throwPointType == NULL)) return zv::Val();
			zv::Val throwing = pt_variable_flow_throwing(throwPointType, true, false);
			if (UNEXPECTED(throwing.isUndef())) return zv::Val();
			zv::Args flows{child.variableFlow.raw(), readAll.raw(), throwing.raw()};
			variableFlow = pt_variable_flow_sequence(3, flows);
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		}
		zv::Val throwPoints = ptse::mergeOne(child.throwPoints, std::move(throwPoint));
		if (UNEXPECTED(throwPoints.isUndef())) return zv::Val();
		zv::Val impurePoint = pt_impure_point_new(resultScope.raw(), expr, pt_evh_eval, pt_evh_eval, true);
		if (UNEXPECTED(impurePoint.isUndef())) return zv::Val();
		zv::Val impurePoints = ptse::mergeOne(child.impurePoints, std::move(impurePoint));
		if (UNEXPECTED(impurePoints.isUndef())) return zv::Val();

		zv::Val typeCallback = pt_native_closure(&ptse::mixedTypeBody<EvalHandler>);
		zv::Val specifyTypesCallback = pt_native_closure(&ptse::specifyDefaultTypesBody<EvalHandler>, self, expr);
		pt_expression_result_args args(resultScope.raw(), beforeScope, expr, child.hasYield, child.isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return EvalHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::EvalHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_eval_handler()
{
	pt_evh_eval = zend_string_init_interned(PT_LC("eval"), 1);

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\EvalHandler");
	ptdecl::EvalHandler::declareClass(cls);
	ptdecl::EvalHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expressionResultFactory, *defaultNarrowingHelper;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, expressionResultFactory, defaultNarrowingHelper)) RETURN_THROWS();
		EvalHandler(Z_OBJ_P(ZEND_THIS)).construct(expressionResultFactory, defaultNarrowingHelper);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!EvalHandler::supports(expr, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(EvalHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_eval_handler);
	pt_expr_handler_entry_register(&pt_ce_eval_handler, &EvalHandler::processExprEntry);
}

/* }}} */
