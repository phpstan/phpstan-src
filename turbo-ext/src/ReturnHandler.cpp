/*
 * PHPStanTurbo\ReturnHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\ReturnHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processStmt() is registered as the class's
 * statement-handler entry (Engine.h).
 *
 * ExpressionResult, MutatingScope, the contexts, VariableFlow, the
 * statement results, NodeScopeResolver and StatementsHandler are called
 * through their direct entries.
 */

#include "support.h"
#include "generated/ReturnHandler.h"

namespace slots = ptdecl::ReturnHandler::slot;
namespace sigs = ptdecl::ReturnHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"

zend_class_entry *pt_ce_return_handler = nullptr;

namespace {

pt_property_site pt_rh_return_expr_site;
pt_property_site pt_rh_variable_name_site;

/* $stmt->expr of the Return_ statement (NULL = pending exception) */
zval *returnedExpr(zval *stmt)
{
	return ptsh::readNodeProperty(pt_rh_return_expr_site, stmt, PT_LC("expr"));
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\ReturnHandler; UNDEF = pending
 * exception. */
class ReturnHandler
{
public:
	explicit ReturnHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted property */
	void construct(zval *statementsHandler)
	{
		zv::ObjRef(self).propAtWrite(slots::statementsHandler, zv::Val::copyOf(zv::Ref(statementsHandler)));
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *stmt, bool &out) const
	{
		zend_class_entry *returnCe = pt_class(PT_CLASS_RETURN_STMT);
		if (UNEXPECTED(returnCe == NULL)) return false;
		out = instanceof_function(Z_OBJCE_P(stmt), returnCe);
		return true;
	}

	/* Mirrors processStmt(). */
	zv::Val processStmt(zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *statementsHandler = OBJ_PROP_NUM(self, slots::statementsHandler);
		zval *expr = returnedExpr(stmt);
		if (UNEXPECTED(expr == NULL)) return zv::Val();
		zv::Val stmtScope = pt_statements_handler_process_stmt_var_annotation(statementsHandler, nodeScopeResolver, scope, storage, stmt, expr, nodeCallback);
		if (UNEXPECTED(stmtScope.isUndef())) return zv::Val();

		zv::Val resultScope;
		zval *finalScope = scope;
		bool hasYield = false;
		zv::Val throwPointsHold, impurePointsHold;
		zval emptyArray;
		ZVAL_EMPTY_ARRAY(&emptyArray);
		zval *throwPoints = &emptyArray;
		zval *impurePoints = &emptyArray;
		zv::Val variableFlow;
		expr = returnedExpr(stmt);
		if (UNEXPECTED(expr == NULL)) return zv::Val();
		if (Z_TYPE_P(expr) != IS_NULL) {
			bool resolveTemplateArguments;
			if (UNEXPECTED(!pt_statement_context_should_resolve_template_arguments(context, resolveTemplateArguments))) return zv::Val();
			zv::Val expressionContext = pt_expression_context_create_deep(resolveTemplateArguments);
			if (UNEXPECTED(expressionContext.isUndef())) return zv::Val();
			zv::Val result = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, expr, stmtScope.raw(), storage, nodeCallback, expressionContext.raw());
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			// the @var-changed-type node fires now that the expression is stored
			// on the scope BEFORE the @var tag re-typed the expression, so the rule
			// compares the tag against the expression's walked type
			expr = returnedExpr(stmt);
			if (UNEXPECTED(expr == NULL)) return zv::Val();
			zv::Val varConstraints = pt_statements_handler_emit_var_tag_changed_node(statementsHandler, nodeScopeResolver, scope, storage, stmt, expr, nodeCallback);
			if (UNEXPECTED(varConstraints.isUndef())) return zv::Val();
			throwPoints = pt_expression_result_throw_points(result.raw(), throwPointsHold);
			if (UNEXPECTED(throwPoints == NULL)) return zv::Val();
			impurePoints = pt_expression_result_impure_points(result.raw(), impurePointsHold);
			if (UNEXPECTED(impurePoints == NULL)) return zv::Val();
			zv::Val walkedScopeHold;
			zval *walkedScope = pt_expression_result_scope(result.raw(), walkedScopeHold);
			if (UNEXPECTED(walkedScope == NULL)) return zv::Val();
			zv::Val varConstrainedScope = pt_mutating_scope_add_template_argument_constraints(Z_OBJ_P(walkedScope), varConstraints.raw());
			if (UNEXPECTED(varConstrainedScope.isUndef())) return zv::Val();
			zv::Val sendConstraints = pt_node_scope_resolver_collect_return_send(nodeScopeResolver, stmtScope.raw(), result.raw());
			if (UNEXPECTED(sendConstraints.isUndef())) return zv::Val();
			resultScope = pt_mutating_scope_add_template_argument_constraints(Z_OBJ_P(varConstrainedScope.raw()), sendConstraints.raw());
			if (UNEXPECTED(resultScope.isUndef())) return zv::Val();
			finalScope = resultScope.raw();
			if (UNEXPECTED(!pt_expression_result_has_yield(result.raw(), hasYield))) return zv::Val();
			variableFlow = pt_expression_result_variable_flow(result.raw());
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		}

		if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, stmt, stmtScope.raw(), storage))) return zv::Val();

		zv::Val exitPoint = pt_internal_statement_exit_point_new(stmt, finalScope);
		if (UNEXPECTED(exitPoint.isUndef())) return zv::Val();
		zv::Arr exitPoints = zv::Arr::create(1);
		exitPoints.push(std::move(exitPoint));

		expr = returnedExpr(stmt);
		if (UNEXPECTED(expr == NULL)) return zv::Val();
		zend_string *returnedName = NULL;
		zend_class_entry *variableCe = pt_class(PT_CLASS_VARIABLE);
		if (UNEXPECTED(variableCe == NULL)) return zv::Val();
		if (Z_TYPE_P(expr) == IS_OBJECT && instanceof_function(Z_OBJCE_P(expr), variableCe)) {
			zval *name = ptsh::readNodeProperty(pt_rh_variable_name_site, expr, PT_LC("name"));
			if (UNEXPECTED(name == NULL)) return zv::Val();
			if (Z_TYPE_P(name) == IS_STRING) {
				returnedName = Z_STR_P(name);
			}
		}
		zv::Val exitFlow = pt_variable_flow_exit(PT_VARIABLE_FLOW_EXIT_RETURN, 1, returnedName);
		if (UNEXPECTED(exitFlow.isUndef())) return zv::Val();
		zval nullFlow = {};
		ZVAL_NULL(&nullFlow);
		zv::Args flows{variableFlow.isUndef() ? &nullFlow : variableFlow.raw(), exitFlow.raw()};
		zv::Val sequence = pt_variable_flow_sequence(2, flows);
		if (UNEXPECTED(sequence.isUndef())) return zv::Val();

		return pt_internal_statement_result_new(finalScope, hasYield, true, exitPoints.raw(), throwPoints, impurePoints, NULL, sequence.raw());
	}

	/* the statement-handler entry (Engine.h) */
	static zv::Val processStmtEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return ReturnHandler(handler).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::ReturnHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_return_handler)
{
	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\ReturnHandler");
	ptdecl::ReturnHandler::declareClass(cls);
	ptdecl::ReturnHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *statementsHandler;
		if (!zp::parse<zp::Obj>(execute_data, statementsHandler)) RETURN_THROWS();
		ReturnHandler(Z_OBJ_P(ZEND_THIS)).construct(statementsHandler);
	});

	cls.method<&ReturnHandler::supports, zp::Obj>(sigs::supports);

	cls.method(sigs::processStmt, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *stmt, *scope, *storage, *nodeCallback, *context;
		ZEND_PARSE_PARAMETERS_START(6, 6)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(ReturnHandler(Z_OBJ_P(ZEND_THIS)).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_return_handler);
	pt_stmt_handler_entry_register(&pt_ce_return_handler, &ReturnHandler::processStmtEntry);
}

/* }}} */
