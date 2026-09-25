/*
 * PHPStanTurbo\BlockHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\BlockHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo (the #[AutowiredParameter] bool pairs by name) so Nette autowires
 * it. processStmt() is registered as the class's statement-handler entry
 * (Engine.h).
 *
 * MutatingScope, InternalStatementResult and NodeScopeResolver are called
 * through their direct entries.
 */

#include "support.h"
#include "generated/BlockHandler.h"

namespace slots = ptdecl::BlockHandler::slot;
namespace sigs = ptdecl::BlockHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"

zend_class_entry *pt_ce_block_handler = nullptr;

namespace {

pt_property_site pt_bh_stmts_site;

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\BlockHandler; UNDEF = pending
 * exception. */
class BlockHandler
{
public:
	explicit BlockHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted property */
	void construct(bool polluteScopeWithBlock)
	{
		zv::ObjRef(self).propAtWrite(slots::polluteScopeWithBlock, zv::Val::boolean(polluteScopeWithBlock));
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *stmt, bool &out) const
	{
		zend_class_entry *blockCe = pt_class(PT_CLASS_BLOCK_STMT);
		if (UNEXPECTED(blockCe == NULL)) return false;
		out = instanceof_function(Z_OBJCE_P(stmt), blockCe);
		return true;
	}

	/* Mirrors processStmt(). */
	zv::Val processStmt(zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *stmts = ptsh::readNodeProperty(pt_bh_stmts_site, stmt, PT_LC("stmts"));
		if (UNEXPECTED(stmts == NULL)) return zv::Val();
		zv::Val result = pt_node_scope_resolver_process_stmt_nodes_internal(nodeScopeResolver, stmt, stmts, scope, storage, nodeCallback, context);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		// like a loop body, the variable flow keeps the block optional whatever
		// polluteScopeWithBlock says: a write inside it does not make an
		// earlier write of the variable dead
		zv::Val variableFlow;
		{
			zv::Val flowHold;
			zval *blockFlow = pt_internal_statement_result_variable_flow(result.raw(), flowHold);
			if (UNEXPECTED(blockFlow == NULL)) return zv::Val();
			zv::Args branches{blockFlow, zv::null};
			variableFlow = pt_variable_flow_choice(2, branches);
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		}
		if (Z_TYPE_P(OBJ_PROP_NUM(self, slots::polluteScopeWithBlock)) == IS_TRUE) {
			return pt_internal_statement_result_with_variable_flow(result.raw(), variableFlow.raw());
		}

		zval *resultValue = result.raw();
		zv::Val resultScopeHold;
		zval *resultScope = pt_internal_statement_result_scope(resultValue, resultScopeHold);
		if (UNEXPECTED(resultScope == NULL)) return zv::Val();
		zv::Val mergedScope = pt_mutating_scope_merge_with(Z_OBJ_P(scope), resultScope);
		if (UNEXPECTED(mergedScope.isUndef())) return zv::Val();
		bool hasYield;
		if (UNEXPECTED(!pt_internal_statement_result_has_yield(resultValue, hasYield))) return zv::Val();
		bool isAlwaysTerminating;
		if (UNEXPECTED(!pt_internal_statement_result_is_always_terminating(resultValue, isAlwaysTerminating))) return zv::Val();
		zv::Val exitPointsHold, throwPointsHold, impurePointsHold, endStatementsHold;
		zval *exitPoints = pt_internal_statement_result_exit_points(resultValue, exitPointsHold);
		if (UNEXPECTED(exitPoints == NULL)) return zv::Val();
		zval *throwPoints = pt_internal_statement_result_throw_points(resultValue, throwPointsHold);
		if (UNEXPECTED(throwPoints == NULL)) return zv::Val();
		zval *impurePoints = pt_internal_statement_result_impure_points(resultValue, impurePointsHold);
		if (UNEXPECTED(impurePoints == NULL)) return zv::Val();
		zval *endStatements = pt_internal_statement_result_end_statements(resultValue, endStatementsHold);
		if (UNEXPECTED(endStatements == NULL)) return zv::Val();

		// the twin passes no endReachable here
		return pt_internal_statement_result_new(mergedScope.raw(), hasYield, isAlwaysTerminating, exitPoints, throwPoints, impurePoints, endStatements, Z_TYPE_P(variableFlow.raw()) == IS_NULL ? NULL : variableFlow.raw());
	}

	/* the statement-handler entry (Engine.h) */
	static zv::Val processStmtEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return BlockHandler(handler).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::BlockHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_block_handler)
{
	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\BlockHandler");
	ptdecl::BlockHandler::declareClass(cls);
	ptdecl::BlockHandler::declareProperties(cls);

	/* the real parameter names: the DI container pairs the
	 * #[AutowiredParameter] by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool polluteScopeWithBlock;
		if (!zp::parse<zp::Bool>(execute_data, polluteScopeWithBlock)) RETURN_THROWS();
		BlockHandler(Z_OBJ_P(ZEND_THIS)).construct(polluteScopeWithBlock);
	});

	cls.method<&BlockHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(BlockHandler(Z_OBJ_P(ZEND_THIS)).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_block_handler);
	pt_stmt_handler_entry_register(&pt_ce_block_handler, &BlockHandler::processStmtEntry);
}

/* }}} */
