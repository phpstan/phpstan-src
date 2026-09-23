/*
 * PHPStanTurbo\WhileHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\WhileHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo (the #[AutowiredParameter] bools pair by name) so Nette autowires
 * it. processStmt() is registered as the class's statement-handler entry
 * (Engine.h).
 *
 * The convergence passes re-walk the condition and the body through
 * NodeScopeResolver's direct entries (processExprNode() /
 * processStmtNodesInternal() keep the fresh-stack guard on the recursion);
 * ExpressionResult, MutatingScope, ExpressionResultStorage, the contexts,
 * VariableFlow, the statement results and RecordingNodeCallback through
 * theirs. The twin's try/finally storage pushes are pt_finally() blocks.
 */

#include "support.h"
#include "generated/WhileHandler.h"

namespace slots = ptdecl::WhileHandler::slot;
namespace sigs = ptdecl::WhileHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"
#include "LoopHandlerCalls.h"

zend_class_entry *pt_ce_while_handler = nullptr;

namespace {

pt_property_site pt_wh_cond_site;
pt_property_site pt_wh_stmts_site;

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\WhileHandler; UNDEF = pending
 * exception. */
class WhileHandler
{
public:
	explicit WhileHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(bool polluteScopeWithLoopInitialAssignments, bool treatPhpDocTypesAsCertain)
	{
		zv::ObjRef object(self);
		object.propAtWrite(slots::polluteScopeWithLoopInitialAssignments, zv::Val::boolean(polluteScopeWithLoopInitialAssignments));
		object.propAtWrite(slots::treatPhpDocTypesAsCertain, zv::Val::boolean(treatPhpDocTypesAsCertain));
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *stmt, bool &out) const
	{
		bool error = false;
		out = ptsh::isInstanceOf(stmt, PT_CLASS_WHILE_STMT, error);
		return !error;
	}

	/* Mirrors processStmt(). */
	zv::Val processStmt(zval *nodeScopeResolver, zval *stmt, zval *scopeArg, zval *storageArg, zval *nodeCallback, zval *context) const
	{
		bool treatPhpDocTypesAsCertain = ptlh::boolSlot(self, slots::treatPhpDocTypesAsCertain);
		bool polluteScopeWithLoopInitialAssignments = ptlh::boolSlot(self, slots::polluteScopeWithLoopInitialAssignments);
		zval *originalStorage = storageArg;
		zv::Val scope = zv::Val::copyOf(zv::Ref(scopeArg));
		bool isTopLevel;
		if (UNEXPECTED(!pt_statement_context_is_top_level(context, isTopLevel))) return zv::Val();

		zv::Val storage = pt_expression_result_storage_duplicate(originalStorage);
		if (UNEXPECTED(storage.isUndef())) return zv::Val();
		// pass-local storages are pushed for the duration of each pass so
		// in-pass asks (applySpecifiedTypes pricing, branch-scope derivation)
		// read the pass's own results instead of re-pricing on demand
		if (UNEXPECTED(!pt_mutating_scope_push_expression_result_storage(Z_OBJ_P(scope.raw()), storage.raw()))) return zv::Val();
		zv::Val condResult;
		ptlh::ConditionBoolean beforeCondBooleanType;
		zv::Val condScope;
		zv::Val bodyScope;
		zv::Val earlyResult;
		[&]() {
			zval *cond = ptsh::readNodeProperty(pt_wh_cond_site, stmt, PT_LC("cond"));
			if (UNEXPECTED(cond == NULL)) return;
			zv::Val condHold = zv::Val::copyOf(zv::Ref(cond));
			zv::Val noop = ptlh::newNoopNodeCallback();
			if (UNEXPECTED(noop.isUndef())) return;
			zv::Val expressionContext = pt_expression_context_create_deep(false);
			if (UNEXPECTED(expressionContext.isUndef())) return;
			condResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, condHold.raw(), scope.raw(), storage.raw(), noop.raw(), expressionContext.raw());
			if (UNEXPECTED(condResult.isUndef())) return;
			if (UNEXPECTED(!ptlh::conditionBoolean(condResult.raw(), treatPhpDocTypesAsCertain, beforeCondBooleanType))) return;
			condScope = pt_expression_result_get_falsey_scope(condResult.raw());
			if (UNEXPECTED(condScope.isUndef())) return;
			if (!isTopLevel && beforeCondBooleanType.isFalse == PT_TRI_YES) {
				if (!polluteScopeWithLoopInitialAssignments) {
					zv::Val merged = pt_mutating_scope_merge_with(Z_OBJ_P(condScope.raw()), scope.raw());
					if (UNEXPECTED(merged.isUndef())) return;
					scope = std::move(merged);
				}
				bool hasYield;
				if (UNEXPECTED(!pt_expression_result_has_yield(condResult.raw(), hasYield))) return;
				zv::Val throwPointsHold, impurePointsHold;
				zval *throwPoints = pt_expression_result_throw_points(condResult.raw(), throwPointsHold);
				if (UNEXPECTED(throwPoints == NULL)) return;
				zval *impurePoints = pt_expression_result_impure_points(condResult.raw(), impurePointsHold);
				if (UNEXPECTED(impurePoints == NULL)) return;
				zv::Val variableFlow = pt_expression_result_variable_flow(condResult.raw());
				if (UNEXPECTED(variableFlow.isUndef())) return;
				zval emptyArray;
				ZVAL_EMPTY_ARRAY(&emptyArray);
				earlyResult = pt_internal_statement_result_new(scope.raw(), hasYield, false, &emptyArray, throwPoints, impurePoints, NULL, variableFlow.raw());
				return;
			}
			bodyScope = pt_expression_result_get_truthy_scope(condResult.raw());
		}();
		pt_finally([&]() { (void) pt_mutating_scope_pop_expression_result_storage(Z_OBJ_P(scope.raw())); });
		if (UNEXPECTED(EG(exception))) return zv::Val();
		if (!earlyResult.isUndef()) return earlyResult;

		zv::Val replayCondRecording, replayBodyRecording, replayCondResult, replayPassStorage, replayPassResult;
		zv::Val prevEntryScope;
		if (isTopLevel) {
			zend_long count = 0;
			zval *stmts = ptsh::readNodeProperty(pt_wh_stmts_site, stmt, PT_LC("stmts"));
			if (UNEXPECTED(stmts == NULL)) return zv::Val();
			bool bodyIsReplayable;
			if (UNEXPECTED(!pt_node_scope_resolver_is_replayable_convergence_body(nodeScopeResolver, stmt, stmts, bodyIsReplayable))) return zv::Val();
			do {
				zv::Val prevScope = zv::Val::copyOf(bodyScope.ref());
				bodyScope = pt_mutating_scope_merge_with(Z_OBJ_P(bodyScope.raw()), scope.raw());
				if (UNEXPECTED(bodyScope.isUndef())) return zv::Val();
				if (!prevEntryScope.isNull()) {
					bool equal;
					if (UNEXPECTED(!ptlh::scopesEqual(bodyScope.raw(), prevEntryScope.raw(), equal))) return zv::Val();
					if (equal) {
						// walking is deterministic in the entry scope - an unchanged entry
						// reproduces the previous pass's exit, so the verification walk is skipped
						bodyScope = std::move(prevScope);
						break;
					}
				}
				prevEntryScope = zv::Val::copyOf(bodyScope.ref());
				storage = pt_expression_result_storage_duplicate(originalStorage);
				if (UNEXPECTED(storage.isUndef())) return zv::Val();
				zv::Val condRecording = ptlh::newPassNodeCallback(bodyIsReplayable);
				if (UNEXPECTED(condRecording.isUndef())) return zv::Val();
				zv::Val bodyRecording = ptlh::newPassNodeCallback(bodyIsReplayable);
				if (UNEXPECTED(bodyRecording.isUndef())) return zv::Val();
				if (UNEXPECTED(!pt_mutating_scope_push_expression_result_storage(Z_OBJ_P(scope.raw()), storage.raw()))) return zv::Val();
				zv::Val passCondResult, bodyScopeResult, backEdgeScope;
				[&]() {
					zval *cond = ptsh::readNodeProperty(pt_wh_cond_site, stmt, PT_LC("cond"));
					if (UNEXPECTED(cond == NULL)) return;
					zv::Val condHold = zv::Val::copyOf(zv::Ref(cond));
					zv::Val expressionContext = pt_expression_context_create_deep(false);
					if (UNEXPECTED(expressionContext.isUndef())) return;
					passCondResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, condHold.raw(), bodyScope.raw(), storage.raw(), condRecording.raw(), expressionContext.raw());
					if (UNEXPECTED(passCondResult.isUndef())) return;
					zv::Val truthyScope = pt_expression_result_get_truthy_scope(passCondResult.raw());
					if (UNEXPECTED(truthyScope.isUndef())) return;
					bodyScope = std::move(truthyScope);
					zval *passStmts = ptsh::readNodeProperty(pt_wh_stmts_site, stmt, PT_LC("stmts"));
					if (UNEXPECTED(passStmts == NULL)) return;
					zv::Val stmtsHold = zv::Val::copyOf(zv::Ref(passStmts));
					zv::Val deepContext = pt_statement_context_enter_deep(context);
					if (UNEXPECTED(deepContext.isUndef())) return;
					zv::Val passContext = pt_statement_context_without_template_argument_resolution(deepContext.raw());
					if (UNEXPECTED(passContext.isUndef())) return;
					zv::Val walked = pt_node_scope_resolver_process_stmt_nodes_internal(nodeScopeResolver, stmt, stmtsHold.raw(), bodyScope.raw(), storage.raw(), bodyRecording.raw(), passContext.raw());
					if (UNEXPECTED(walked.isUndef())) return;
					bodyScopeResult = pt_internal_statement_result_filter_out_loop_exit_points(walked.raw());
					if (UNEXPECTED(bodyScopeResult.isUndef())) return;
					backEdgeScope = pt_internal_statement_result_loop_back_edge_scope(bodyScopeResult.raw());
				}();
				pt_finally([&]() { (void) pt_mutating_scope_pop_expression_result_storage(Z_OBJ_P(scope.raw())); });
				if (UNEXPECTED(EG(exception))) return zv::Val();
				if (backEdgeScope.isNull()) {
					bodyScope = std::move(prevScope);
					break;
				}
				bodyScope = std::move(backEdgeScope);
				// the candidate to replace the final walk when this pass's
				// entry turns out to be the fixpoint
				if (bodyIsReplayable) {
					replayCondRecording = std::move(condRecording);
					replayBodyRecording = std::move(bodyRecording);
					replayPassStorage = zv::Val::copyOf(storage.ref());
					replayPassResult = zv::Val::copyOf(bodyScopeResult.ref());
					replayCondResult = zv::Val::copyOf(passCondResult.ref());
				}
				bool equal;
				if (UNEXPECTED(!ptlh::scopesEqual(bodyScope.raw(), prevScope.raw(), equal))) return zv::Val();
				if (equal) break;

				if (count >= PT_LH_GENERALIZE_AFTER_ITERATION_LIMIT) {
					zv::Val condFlow = pt_expression_result_variable_flow(passCondResult.raw());
					if (UNEXPECTED(condFlow.isUndef())) return zv::Val();
					zv::Val bodyFlowHold;
					zval *bodyFlow = pt_internal_statement_result_variable_flow(bodyScopeResult.raw(), bodyFlowHold);
					if (UNEXPECTED(bodyFlow == NULL)) return zv::Val();
					zv::Args flows{condFlow.raw(), bodyFlow};
					zv::Val passFlow = pt_variable_flow_sequence(2, flows);
					if (UNEXPECTED(passFlow.isUndef())) return zv::Val();
					bodyScope = ptlh::generalizeWithWrittenNames(prevScope.raw(), bodyScope.raw(), stmt, passFlow.raw());
					if (UNEXPECTED(bodyScope.isUndef())) return zv::Val();
				}
				count++;
			} while (count < PT_LH_LOOP_SCOPE_ITERATIONS_LIMIT);
		}

		bodyScope = pt_mutating_scope_merge_with(Z_OBJ_P(bodyScope.raw()), scope.raw());
		if (UNEXPECTED(bodyScope.isUndef())) return zv::Val();
		zv::Val bodyScopeMaybeRan = zv::Val::copyOf(bodyScope.ref());
		zval *finalStorage = originalStorage;
		bool replay = !replayCondRecording.isNull() && !replayBodyRecording.isNull() && !replayPassStorage.isNull() && !replayPassResult.isNull() && !replayCondResult.isNull();
		if (replay) {
			if (UNEXPECTED(!ptlh::scopesEqual(bodyScope.raw(), prevEntryScope.raw(), replay))) return zv::Val();
		}
		zv::Val bodyCondResult, finalScopeResult;
		if (replay) {
			// the final walk would repeat the recorded fixpoint pass exactly
			// (same entry scope, deterministic walk) - adopt the pass's results
			// and replay its emissions through the real callback instead
			if (UNEXPECTED(!pt_expression_result_storage_merge_results(originalStorage, replayPassStorage.raw()))) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_replay_recording(nodeScopeResolver, replayCondRecording.raw(), nodeCallback, originalStorage, scope.raw()))) return zv::Val();
			// the While_ callback is deferred from processStmtNode(): it fires
			// after the condition's result is available, with the entry scope
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, stmt, scope.raw(), finalStorage))) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_replay_recording(nodeScopeResolver, replayBodyRecording.raw(), nodeCallback, originalStorage, scope.raw()))) return zv::Val();
			bodyCondResult = std::move(replayCondResult);
			finalScopeResult = std::move(replayPassResult);
		} else {
			zval *cond = ptsh::readNodeProperty(pt_wh_cond_site, stmt, PT_LC("cond"));
			if (UNEXPECTED(cond == NULL)) return zv::Val();
			zv::Val condHold = zv::Val::copyOf(zv::Ref(cond));
			bool resolveTemplateArguments;
			if (UNEXPECTED(!pt_statement_context_should_resolve_template_arguments(context, resolveTemplateArguments))) return zv::Val();
			zv::Val expressionContext = pt_expression_context_create_deep(resolveTemplateArguments);
			if (UNEXPECTED(expressionContext.isUndef())) return zv::Val();
			bodyCondResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, condHold.raw(), bodyScope.raw(), finalStorage, nodeCallback, expressionContext.raw());
			if (UNEXPECTED(bodyCondResult.isUndef())) return zv::Val();
			// the While_ callback is deferred from processStmtNode(): it fires after
			// the condition's real walk stored its result, with the entry scope
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, stmt, scope.raw(), finalStorage))) return zv::Val();
			bodyScope = pt_expression_result_get_truthy_scope(bodyCondResult.raw());
			if (UNEXPECTED(bodyScope.isUndef())) return zv::Val();
			zval *stmts = ptsh::readNodeProperty(pt_wh_stmts_site, stmt, PT_LC("stmts"));
			if (UNEXPECTED(stmts == NULL)) return zv::Val();
			zv::Val stmtsHold = zv::Val::copyOf(zv::Ref(stmts));
			zv::Val walked = pt_node_scope_resolver_process_stmt_nodes_internal(nodeScopeResolver, stmt, stmtsHold.raw(), bodyScope.raw(), finalStorage, nodeCallback, context);
			if (UNEXPECTED(walked.isUndef())) return zv::Val();
			finalScopeResult = pt_internal_statement_result_filter_out_loop_exit_points(walked.raw());
			if (UNEXPECTED(finalScopeResult.isUndef())) return zv::Val();
		}
		zval *result = finalScopeResult.raw();
		zv::Val backEdgeScope = pt_internal_statement_result_loop_back_edge_scope(result);
		if (UNEXPECTED(backEdgeScope.isUndef())) return zv::Val();
		bool backEdgeDead = backEdgeScope.isNull();
		zv::Val finalScope;
		if (backEdgeDead) {
			zv::Val hold;
			zval *resultScope = pt_internal_statement_result_scope(result, hold);
			if (UNEXPECTED(resultScope == NULL)) return zv::Val();
			finalScope = zv::Val::copyOf(zv::Ref(resultScope));
		} else {
			finalScope = std::move(backEdgeScope);
		}
		// the loop condition narrows the post-loop scope to its falsey branch;
		// $finalScope (after the body ran) is a different scope than the condition's
		// own, so reprocess the condition there rather than re-running its result.
		// The duplicate lets subresults whose state did not change in the body
		// answer from the final pass instead of being re-priced.
		{
			zval *cond = ptsh::readNodeProperty(pt_wh_cond_site, stmt, PT_LC("cond"));
			if (UNEXPECTED(cond == NULL)) return zv::Val();
			zv::Val condHold = zv::Val::copyOf(zv::Ref(cond));
			zv::Val duplicate = pt_expression_result_storage_duplicate(finalStorage);
			if (UNEXPECTED(duplicate.isUndef())) return zv::Val();
			zv::Val onDemand = pt_node_scope_resolver_process_expr_on_demand(nodeScopeResolver, condHold.raw(), finalScope.raw(), duplicate.raw());
			if (UNEXPECTED(onDemand.isUndef())) return zv::Val();
			zend_object *falsey = pt_type_specifier_context_create_falsey();
			if (UNEXPECTED(falsey == NULL)) return zv::Val();
			zval falseyContext;
			ZVAL_OBJ(&falseyContext, falsey);
			zv::Val specifiedTypes = pt_expression_result_get_specified_types_for_scope(onDemand.raw(), finalScope.raw(), &falseyContext);
			if (UNEXPECTED(specifiedTypes.isUndef())) return zv::Val();
			finalScope = pt_mutating_scope_apply_specified_types(Z_OBJ_P(finalScope.raw()), specifiedTypes.raw());
			if (UNEXPECTED(finalScope.isUndef())) return zv::Val();
		}

		bool alwaysIterates = false;
		bool neverIterates = false;
		if (isTopLevel) {
			ptlh::ConditionBoolean condBooleanType;
			if (UNEXPECTED(!ptlh::conditionBoolean(bodyCondResult.raw(), treatPhpDocTypesAsCertain, condBooleanType))) return zv::Val();
			alwaysIterates = condBooleanType.isTrue == PT_TRI_YES;
			neverIterates = condBooleanType.isFalse == PT_TRI_YES;
		}
		zv::Val breakExitPoints = ptlh::breakExitPoints(result);
		if (UNEXPECTED(breakExitPoints.isUndef())) return zv::Val();
		uint32_t breakCount = ptlh::countOf(breakExitPoints.raw());
		if (breakCount > 0) {
			zv::Val breakScope = alwaysIterates || backEdgeDead ? zv::Val::null() : std::move(finalScope);
			for (auto entry : zv::ArrRef(breakExitPoints.raw())) {
				zval *breakExitPoint = entry.value().deref().raw();
				zv::Val hold;
				zval *breakExitScope = pt_internal_statement_exit_point_scope(breakExitPoint, hold);
				if (UNEXPECTED(breakExitScope == NULL)) return zv::Val();
				if (UNEXPECTED(!ptlh::mergeOrTake(breakScope, breakExitScope))) return zv::Val();
			}
			finalScope = std::move(breakScope);
		}

		bool isIterableAtLeastOnce = beforeCondBooleanType.isTrue == PT_TRI_YES;
		{
			zv::Val publicResult = pt_internal_statement_result_to_public(result);
			if (UNEXPECTED(publicResult.isUndef())) return zv::Val();
			zv::Val exitPointsHold;
			zval *publicExitPoints = pt_statement_result_exit_points(publicResult.raw(), exitPointsHold);
			if (UNEXPECTED(publicExitPoints == NULL)) return zv::Val();
			bool resultHasYield;
			if (UNEXPECTED(!pt_internal_statement_result_has_yield(result, resultHasYield))) return zv::Val();
			zv::Args nodeArgv{stmt, publicExitPoints, resultHasYield};
			zv::Val breaklessNode = pt_type_new(PT_CLASS_BREAKLESS_WHILE_LOOP_NODE, 3, nodeArgv);
			if (UNEXPECTED(breaklessNode.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, breaklessNode.raw(), bodyScopeMaybeRan.raw(), finalStorage))) return zv::Val();
		}

		bool isAlwaysTerminating;
		if (alwaysIterates) {
			zv::Val againBreakExitPoints = ptlh::breakExitPoints(result);
			if (UNEXPECTED(againBreakExitPoints.isUndef())) return zv::Val();
			isAlwaysTerminating = ptlh::countOf(againBreakExitPoints.raw()) == 0;
		} else if (isIterableAtLeastOnce) {
			if (UNEXPECTED(!pt_internal_statement_result_is_always_terminating(result, isAlwaysTerminating))) return zv::Val();
		} else {
			isAlwaysTerminating = false;
		}
		if (backEdgeDead && breakCount == 0) {
			finalScope = zv::Val::null();
		}
		if (!isIterableAtLeastOnce) {
			if (!polluteScopeWithLoopInitialAssignments) {
				condScope = pt_mutating_scope_merge_with(Z_OBJ_P(condScope.raw()), scope.raw());
				if (UNEXPECTED(condScope.isUndef())) return zv::Val();
			}
			if (finalScope.isNull()) {
				finalScope = zv::Val::copyOf(condScope.ref());
			} else {
				finalScope = pt_mutating_scope_merge_with(Z_OBJ_P(finalScope.raw()), condScope.raw());
				if (UNEXPECTED(finalScope.isUndef())) return zv::Val();
			}
		}
		if (finalScope.isNull()) {
			zv::Val hold;
			zval *resultScope = pt_internal_statement_result_scope(result, hold);
			if (UNEXPECTED(resultScope == NULL)) return zv::Val();
			finalScope = zv::Val::copyOf(zv::Ref(resultScope));
		}

		zv::Arr throwPoints, impurePoints;
		{
			zv::Val hold;
			if (UNEXPECTED(!ptlh::arrayOf(pt_expression_result_throw_points(condResult.raw(), hold), throwPoints))) return zv::Val();
		}
		{
			zv::Val hold;
			if (UNEXPECTED(!ptlh::arrayOf(pt_expression_result_impure_points(condResult.raw(), hold), impurePoints))) return zv::Val();
		}
		if (!neverIterates) {
			{
				zv::Val hold;
				zval *more = pt_internal_statement_result_throw_points(result, hold);
				if (UNEXPECTED(more == NULL || !ptlh::mergeInto(throwPoints, more))) return zv::Val();
			}
			zv::Val hold;
			zval *more = pt_internal_statement_result_impure_points(result, hold);
			if (UNEXPECTED(more == NULL || !ptlh::mergeInto(impurePoints, more))) return zv::Val();
		}

		bool hasYield;
		if (UNEXPECTED(!pt_internal_statement_result_has_yield(result, hasYield))) return zv::Val();
		if (!hasYield) {
			if (UNEXPECTED(!pt_expression_result_has_yield(condResult.raw(), hasYield))) return zv::Val();
		}
		zv::Val exitPoints = pt_internal_statement_result_exit_points_for_outer_loop(result);
		if (UNEXPECTED(exitPoints.isUndef())) return zv::Val();
		zv::Val variableFlow;
		{
			zv::Val bodyFlowHold;
			zval *bodyFlow = pt_internal_statement_result_variable_flow(result, bodyFlowHold);
			if (UNEXPECTED(bodyFlow == NULL)) return zv::Val();
			zv::Val condFlow = pt_expression_result_variable_flow(bodyCondResult.raw());
			if (UNEXPECTED(condFlow.isUndef())) return zv::Val();
			// the body may run or not whatever the condition says: a write in
			// it does not make an earlier write dead, and a usage in it counts
			variableFlow = pt_variable_flow_loop(condFlow.raw(), bodyFlow, NULL, false, true);
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		}

		return pt_internal_statement_result_new(finalScope.raw(), hasYield, isAlwaysTerminating, exitPoints.raw(), throwPoints.raw(), impurePoints.raw(), NULL, variableFlow.raw());
	}

	/* the statement-handler entry (Engine.h) */
	static zv::Val processStmtEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return WhileHandler(handler).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::WhileHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_while_handler()
{
	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\WhileHandler");
	ptdecl::WhileHandler::declareClass(cls);
	ptdecl::WhileHandler::declareProperties(cls);

	/* the real parameter names: the DI container pairs the
	 * #[AutowiredParameter] bools by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool polluteScopeWithLoopInitialAssignments, treatPhpDocTypesAsCertain;
		if (!zp::parse<zp::Bool, zp::Bool>(execute_data, polluteScopeWithLoopInitialAssignments, treatPhpDocTypesAsCertain)) RETURN_THROWS();
		WhileHandler(Z_OBJ_P(ZEND_THIS)).construct(polluteScopeWithLoopInitialAssignments, treatPhpDocTypesAsCertain);
	});

	cls.method<&WhileHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(WhileHandler(Z_OBJ_P(ZEND_THIS)).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_while_handler);
	pt_stmt_handler_entry_register(&pt_ce_while_handler, &WhileHandler::processStmtEntry);
}

/* }}} */
