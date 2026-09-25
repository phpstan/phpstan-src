/*
 * PHPStanTurbo\BreakContinueHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\BreakContinueHandler.
 *
 * A DI service (#[AutowiredService]) without a constructor. processStmt()
 * is registered as the class's statement-handler entry (Engine.h).
 * ExpressionResult, the contexts, VariableFlow, the statement results and
 * NodeScopeResolver are called through their direct entries.
 */

#include "support.h"
#include "generated/BreakContinueHandler.h"

namespace sigs = ptdecl::BreakContinueHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"

zend_class_entry *pt_ce_break_continue_handler = nullptr;

namespace {

pt_property_site pt_bch_num_site;
pt_property_site pt_bch_int_value_site;

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\BreakContinueHandler; UNDEF = pending
 * exception. */
class BreakContinueHandler
{
public:
	explicit BreakContinueHandler(zend_object *self) : self(self) {}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *stmt, bool &out) const
	{
		bool error = false;
		out = ptsh::isInstanceOf(stmt, PT_CLASS_BREAK_STMT, error) || ptsh::isInstanceOf(stmt, PT_CLASS_CONTINUE_STMT, error);
		return !error;
	}

	/* Mirrors processStmt(). */
	zv::Val processStmt(zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		(void) self;
		zval emptyArray;
		ZVAL_EMPTY_ARRAY(&emptyArray);
		zval *resultScope = scope;
		bool hasYield = false;
		zval *throwPoints = &emptyArray;
		zval *impurePoints = &emptyArray;
		zv::Val result, scopeHold, throwPointsHold, impurePointsHold;

		zval *num = ptsh::readNodeProperty(pt_bch_num_site, stmt, PT_LC("num"));
		if (UNEXPECTED(num == NULL)) return zv::Val();
		if (Z_TYPE_P(num) != IS_NULL) {
			zv::Val numHold = zv::Val::copyOf(zv::Ref(num));
			bool resolveTemplateArguments;
			if (UNEXPECTED(!pt_statement_context_should_resolve_template_arguments(context, resolveTemplateArguments))) return zv::Val();
			zv::Val expressionContext = pt_expression_context_create_deep(resolveTemplateArguments);
			if (UNEXPECTED(expressionContext.isUndef())) return zv::Val();
			result = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, numHold.raw(), scope, storage, nodeCallback, expressionContext.raw());
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			resultScope = pt_expression_result_scope(result.raw(), scopeHold);
			if (UNEXPECTED(resultScope == NULL)) return zv::Val();
			if (UNEXPECTED(!pt_expression_result_has_yield(result.raw(), hasYield))) return zv::Val();
			throwPoints = pt_expression_result_throw_points(result.raw(), throwPointsHold);
			if (UNEXPECTED(throwPoints == NULL)) return zv::Val();
			impurePoints = pt_expression_result_impure_points(result.raw(), impurePointsHold);
			if (UNEXPECTED(impurePoints == NULL)) return zv::Val();
		}

		zv::Val exitPoint = pt_internal_statement_exit_point_new(stmt, resultScope);
		if (UNEXPECTED(exitPoint.isUndef())) return zv::Val();
		zv::Arr exitPoints = zv::Arr::create(1);
		exitPoints.push(std::move(exitPoint));

		bool error = false;
		bool isBreak = ptsh::isInstanceOf(stmt, PT_CLASS_BREAK_STMT, error);
		if (UNEXPECTED(error)) return zv::Val();
		num = ptsh::readNodeProperty(pt_bch_num_site, stmt, PT_LC("num"));
		if (UNEXPECTED(num == NULL)) return zv::Val();
		zend_long level = 1;
		if (ptsh::isInstanceOf(num, PT_CLASS_SCALAR_INT, error)) {
			zval *value = ptsh::readNodeProperty(pt_bch_int_value_site, num, PT_LC("value"));
			if (UNEXPECTED(value == NULL)) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(value) != IS_LONG)) {
				zend_type_error("PHPStan\\Analyser\\VariableFlow::exit(): Argument #2 ($level) must be of type int, %s given", zend_zval_value_name(value));
				return zv::Val();
			}
			level = Z_LVAL_P(value);
		}
		if (UNEXPECTED(error)) return zv::Val();
		zv::Val variableFlow = pt_variable_flow_exit(isBreak ? PT_VARIABLE_FLOW_EXIT_BREAK : PT_VARIABLE_FLOW_EXIT_CONTINUE, level);
		if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();

		return pt_internal_statement_result_new(resultScope, hasYield, true, exitPoints.raw(), throwPoints, impurePoints, NULL, variableFlow.raw());
	}

	/* the statement-handler entry (Engine.h) */
	static zv::Val processStmtEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return BreakContinueHandler(handler).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::BreakContinueHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_break_continue_handler)
{
	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\BreakContinueHandler");
	ptdecl::BreakContinueHandler::declareClass(cls);
	ptdecl::BreakContinueHandler::declareProperties(cls);

	cls.method<&BreakContinueHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(BreakContinueHandler(Z_OBJ_P(ZEND_THIS)).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_break_continue_handler);
	pt_stmt_handler_entry_register(&pt_ce_break_continue_handler, &BreakContinueHandler::processStmtEntry);
}

/* }}} */
