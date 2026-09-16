/*
 * PHPStanTurbo\DoWhileHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\DoWhileHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processStmt() is registered as the class's
 * statement-handler entry (Engine.h).
 *
 * The convergence passes re-walk the body and the condition through
 * NodeScopeResolver's direct entries (the fresh-stack guard stays on the
 * recursion); ExpressionResult, MutatingScope, ExpressionResultStorage, the
 * contexts, VariableFlow, the statement results and RecordingNodeCallback
 * through theirs. The twin's try/finally storage pushes are pt_finally()
 * blocks.
 */

#include "support.h"
#include "generated/DoWhileHandler.h"

namespace slots = ptdecl::DoWhileHandler::slot;
namespace sigs = ptdecl::DoWhileHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"
#include "LoopHandlerCalls.h"

zend_class_entry *pt_ce_do_while_handler = nullptr;

namespace {

pt_property_site pt_dwh_cond_site;
pt_property_site pt_dwh_stmts_site;

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\DoWhileHandler; UNDEF = pending
 * exception. */
class DoWhileHandler
{
public:
	explicit DoWhileHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted property */
	void construct(bool treatPhpDocTypesAsCertain)
	{
		zv::ObjRef(self).propAtWrite(slots::treatPhpDocTypesAsCertain, zv::Val::boolean(treatPhpDocTypesAsCertain));
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *stmt, bool &out) const
	{
		bool error = false;
		out = ptsh::isInstanceOf(stmt, PT_CLASS_DO_STMT, error);
		return !error;
	}

	/* Mirrors processStmt(). */
	zv::Val processStmt(zval *nodeScopeResolver, zval *stmt, zval *scope, zval *originalStorage, zval *nodeCallback, zval *context) const
	{
		bool treatPhpDocTypesAsCertain = ptlh::boolSlot(self, slots::treatPhpDocTypesAsCertain);
		zv::Val finalScope = zv::Val::null();
		zv::Val bodyScope = zv::Val::copyOf(zv::Ref(scope));
		zend_long count = 0;
		bool isTopLevel;
		if (UNEXPECTED(!pt_statement_context_is_top_level(context, isTopLevel))) return zv::Val();

		zv::Val replayBodyRecording, replayPassStorage, replayPassResult;
		zv::Val prevEntryScope;
		if (isTopLevel) {
			zval *stmts = ptsh::readNodeProperty(pt_dwh_stmts_site, stmt, PT_LC("stmts"));
			if (UNEXPECTED(stmts == NULL)) return zv::Val();
			bool bodyIsReplayable;
			if (UNEXPECTED(!pt_node_scope_resolver_is_replayable_convergence_body(nodeScopeResolver, stmt, stmts, bodyIsReplayable))) return zv::Val();
			do {
				zv::Val prevScope = zv::Val::copyOf(bodyScope.ref());
				bodyScope = pt_mutating_scope_merge_with(Z_OBJ_P(bodyScope.raw()), scope);
				if (UNEXPECTED(bodyScope.isUndef())) return zv::Val();
				if (!prevEntryScope.isNull()) {
					bool equal;
					if (UNEXPECTED(!ptlh::scopesEqual(bodyScope.raw(), prevEntryScope.raw(), equal))) return zv::Val();
					if (equal) {
						// walking is deterministic in the entry scope - an unchanged entry
						// reproduces the previous pass's exit (and repeats only idempotent
						// merges into the final scope), so the verification walk is skipped
						bodyScope = std::move(prevScope);
						break;
					}
				}
				prevEntryScope = zv::Val::copyOf(bodyScope.ref());
				zv::Val storage = pt_expression_result_storage_duplicate(originalStorage);
				if (UNEXPECTED(storage.isUndef())) return zv::Val();
				zv::Val bodyRecording = ptlh::newPassNodeCallback(bodyIsReplayable);
				if (UNEXPECTED(bodyRecording.isUndef())) return zv::Val();
				if (UNEXPECTED(!pt_mutating_scope_push_expression_result_storage(Z_OBJ_P(scope), storage.raw()))) return zv::Val();
				zv::Val bodyScopeResult, passCondResult;
				bool brokeOut = false;
				[&]() {
					zval *passStmts = ptsh::readNodeProperty(pt_dwh_stmts_site, stmt, PT_LC("stmts"));
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
					zv::Val backEdgeScope = pt_internal_statement_result_loop_back_edge_scope(bodyScopeResult.raw());
					if (UNEXPECTED(backEdgeScope.isUndef())) return;
					if (!backEdgeScope.isNull()) {
						if (UNEXPECTED(!ptlh::otherMergeWith(backEdgeScope.raw(), finalScope))) return;
					}
					zv::Val breaks = ptlh::breakExitPoints(bodyScopeResult.raw());
					if (UNEXPECTED(breaks.isUndef())) return;
					for (auto entry : zv::ArrRef(breaks.raw())) {
						zv::Val hold;
						zval *breakScope = pt_internal_statement_exit_point_scope(entry.value().deref().raw(), hold);
						if (UNEXPECTED(breakScope == NULL)) return;
						if (UNEXPECTED(!ptlh::otherMergeWith(breakScope, finalScope))) return;
					}
					// the candidate to replace the final body walk when this pass's
					// entry turns out to be the fixpoint
					if (bodyIsReplayable) {
						replayBodyRecording = zv::Val::copyOf(bodyRecording.ref());
						replayPassStorage = zv::Val::copyOf(storage.ref());
						replayPassResult = zv::Val::copyOf(bodyScopeResult.ref());
					}
					if (backEdgeScope.isNull()) {
						bodyScope = std::move(prevScope);
						brokeOut = true;
						return;
					}
					zval *cond = ptsh::readNodeProperty(pt_dwh_cond_site, stmt, PT_LC("cond"));
					if (UNEXPECTED(cond == NULL)) return;
					zv::Val condHold = zv::Val::copyOf(zv::Ref(cond));
					zv::Val noop = ptlh::newNoopNodeCallback();
					if (UNEXPECTED(noop.isUndef())) return;
					zv::Val expressionContext = pt_expression_context_create_deep(false);
					if (UNEXPECTED(expressionContext.isUndef())) return;
					passCondResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, condHold.raw(), backEdgeScope.raw(), storage.raw(), noop.raw(), expressionContext.raw());
					if (UNEXPECTED(passCondResult.isUndef())) return;
					zv::Val truthyScope = pt_expression_result_get_truthy_scope(passCondResult.raw());
					if (UNEXPECTED(truthyScope.isUndef())) return;
					bodyScope = std::move(truthyScope);
				}();
				pt_finally([&]() { (void) pt_mutating_scope_pop_expression_result_storage(Z_OBJ_P(scope)); });
				if (UNEXPECTED(EG(exception))) return zv::Val();
				if (brokeOut) break;
				bool equal;
				if (UNEXPECTED(!ptlh::scopesEqual(bodyScope.raw(), prevScope.raw(), equal))) return zv::Val();
				if (equal) break;

				if (count >= PT_LH_GENERALIZE_AFTER_ITERATION_LIMIT) {
					zv::Val bodyFlowHold;
					zval *bodyFlow = pt_internal_statement_result_variable_flow(bodyScopeResult.raw(), bodyFlowHold);
					if (UNEXPECTED(bodyFlow == NULL)) return zv::Val();
					zv::Val condFlow = pt_expression_result_variable_flow(passCondResult.raw());
					if (UNEXPECTED(condFlow.isUndef())) return zv::Val();
					zv::Args flows{bodyFlow, condFlow.raw()};
					zv::Val passFlow = pt_variable_flow_sequence(2, flows);
					if (UNEXPECTED(passFlow.isUndef())) return zv::Val();
					bodyScope = ptlh::generalizeWithWrittenNames(prevScope.raw(), bodyScope.raw(), stmt, passFlow.raw());
					if (UNEXPECTED(bodyScope.isUndef())) return zv::Val();
				}
				count++;
			} while (count < PT_LH_LOOP_SCOPE_ITERATIONS_LIMIT);

			bodyScope = pt_mutating_scope_merge_with(Z_OBJ_P(bodyScope.raw()), scope);
			if (UNEXPECTED(bodyScope.isUndef())) return zv::Val();
		}

		zval *storage = originalStorage;
		bool replay = !replayBodyRecording.isNull() && !replayPassStorage.isNull() && !replayPassResult.isNull();
		if (replay) {
			if (UNEXPECTED(!ptlh::scopesEqual(bodyScope.raw(), prevEntryScope.raw(), replay))) return zv::Val();
		}
		zv::Val bodyScopeResult;
		if (replay) {
			// the final body walk would repeat the recorded fixpoint pass exactly
			// (same entry scope, deterministic walk) - adopt the pass's results
			// and replay its emissions through the real callback instead; the
			// condition walks below stay real
			if (UNEXPECTED(!pt_expression_result_storage_merge_results(originalStorage, replayPassStorage.raw()))) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_replay_recording(nodeScopeResolver, replayBodyRecording.raw(), nodeCallback, originalStorage, scope))) return zv::Val();
			bodyScopeResult = std::move(replayPassResult);
		} else {
			zval *stmts = ptsh::readNodeProperty(pt_dwh_stmts_site, stmt, PT_LC("stmts"));
			if (UNEXPECTED(stmts == NULL)) return zv::Val();
			zv::Val stmtsHold = zv::Val::copyOf(zv::Ref(stmts));
			zv::Val walked = pt_node_scope_resolver_process_stmt_nodes_internal(nodeScopeResolver, stmt, stmtsHold.raw(), bodyScope.raw(), storage, nodeCallback, context);
			if (UNEXPECTED(walked.isUndef())) return zv::Val();
			bodyScopeResult = pt_internal_statement_result_filter_out_loop_exit_points(walked.raw());
			if (UNEXPECTED(bodyScopeResult.isUndef())) return zv::Val();
		}
		zval *result = bodyScopeResult.raw();
		zv::Val backEdgeScope = pt_internal_statement_result_loop_back_edge_scope(result);
		if (UNEXPECTED(backEdgeScope.isUndef())) return zv::Val();
		bool backEdgeDead = backEdgeScope.isNull();
		if (backEdgeDead) {
			zv::Val hold;
			zval *resultScope = pt_internal_statement_result_scope(result, hold);
			if (UNEXPECTED(resultScope == NULL)) return zv::Val();
			bodyScope = zv::Val::copyOf(zv::Ref(resultScope));
		} else {
			bodyScope = std::move(backEdgeScope);
		}

		// the condition is processed once on the post-body scope; its result
		// answers both the always-iterates check below and the falsey post-loop
		// scope - the previous scope-based read here was a guaranteed storage
		// miss (the condition was only ever stored into discarded convergence
		// duplicates) that re-priced the condition on demand before this walk
		zv::Val condResult;
		{
			zval *cond = ptsh::readNodeProperty(pt_dwh_cond_site, stmt, PT_LC("cond"));
			if (UNEXPECTED(cond == NULL)) return zv::Val();
			zv::Val condHold = zv::Val::copyOf(zv::Ref(cond));
			bool resolveTemplateArguments;
			if (UNEXPECTED(!pt_statement_context_should_resolve_template_arguments(context, resolveTemplateArguments))) return zv::Val();
			zv::Val expressionContext = pt_expression_context_create_deep(resolveTemplateArguments);
			if (UNEXPECTED(expressionContext.isUndef())) return zv::Val();
			condResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, condHold.raw(), bodyScope.raw(), storage, nodeCallback, expressionContext.raw());
			if (UNEXPECTED(condResult.isUndef())) return zv::Val();
		}

		bool alwaysIterates = false;
		if (isTopLevel) {
			ptlh::ConditionBoolean condBooleanType;
			if (UNEXPECTED(!ptlh::conditionBoolean(condResult.raw(), treatPhpDocTypesAsCertain, condBooleanType))) return zv::Val();
			alwaysIterates = condBooleanType.isTrue == PT_TRI_YES;
		}

		bool alwaysTerminating;
		if (alwaysIterates || backEdgeDead) {
			zv::Val breaks = ptlh::breakExitPoints(result);
			if (UNEXPECTED(breaks.isUndef())) return zv::Val();
			alwaysTerminating = ptlh::countOf(breaks.raw()) == 0;
		} else {
			if (UNEXPECTED(!pt_internal_statement_result_is_always_terminating(result, alwaysTerminating))) return zv::Val();
		}
		if (!(alwaysTerminating || backEdgeDead)) {
			zv::Val merged = pt_mutating_scope_merge_with(Z_OBJ_P(bodyScope.raw()), finalScope.raw());
			if (UNEXPECTED(merged.isUndef())) return zv::Val();
			finalScope = std::move(merged);
		}
		if (finalScope.isNull()) {
			finalScope = zv::Val::copyOf(zv::Ref(scope));
		}
		bool hasYield = false;
		zv::Arr throwPoints = zv::Arr::empty();
		zv::Arr impurePoints = zv::Arr::empty();
		if (!alwaysTerminating && !backEdgeDead) {
			if (UNEXPECTED(!pt_expression_result_has_yield(condResult.raw(), hasYield))) return zv::Val();
			{
				zv::Val hold;
				if (UNEXPECTED(!ptlh::arrayOf(pt_expression_result_throw_points(condResult.raw(), hold), throwPoints))) return zv::Val();
			}
			{
				zv::Val hold;
				if (UNEXPECTED(!ptlh::arrayOf(pt_expression_result_impure_points(condResult.raw(), hold), impurePoints))) return zv::Val();
			}
			finalScope = pt_expression_result_get_falsey_scope(condResult.raw());
			if (UNEXPECTED(finalScope.isUndef())) return zv::Val();
		}

		// both emissions fire after the condition's final walk stored its
		// results, so rule-side asks about the condition answer from the
		// storage; the Do_ callback is deferred from processStmtNode()
		{
			zval *cond = ptsh::readNodeProperty(pt_dwh_cond_site, stmt, PT_LC("cond"));
			if (UNEXPECTED(cond == NULL)) return zv::Val();
			zv::Val condHold = zv::Val::copyOf(zv::Ref(cond));
			zv::Val publicResult = pt_internal_statement_result_to_public(result);
			if (UNEXPECTED(publicResult.isUndef())) return zv::Val();
			zv::Val exitPointsHold;
			zval *publicExitPoints = pt_statement_result_exit_points(publicResult.raw(), exitPointsHold);
			if (UNEXPECTED(publicExitPoints == NULL)) return zv::Val();
			bool resultHasYield;
			if (UNEXPECTED(!pt_internal_statement_result_has_yield(result, resultHasYield))) return zv::Val();
			zv::Args nodeArgv{condHold.raw(), publicExitPoints, resultHasYield};
			zv::Val conditionNode = pt_type_new(PT_CLASS_DO_WHILE_LOOP_CONDITION_NODE, 3, nodeArgv);
			if (UNEXPECTED(conditionNode.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, conditionNode.raw(), bodyScope.raw(), storage))) return zv::Val();
		}
		if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, stmt, scope, storage))) return zv::Val();

		zv::Val breakExitPoints = ptlh::breakExitPoints(result);
		if (UNEXPECTED(breakExitPoints.isUndef())) return zv::Val();
		if (ptlh::countOf(breakExitPoints.raw()) > 0) {
			zv::Val breakScope = alwaysIterates || backEdgeDead ? zv::Val::null() : std::move(finalScope);
			for (auto entry : zv::ArrRef(breakExitPoints.raw())) {
				zv::Val hold;
				zval *breakExitScope = pt_internal_statement_exit_point_scope(entry.value().deref().raw(), hold);
				if (UNEXPECTED(breakExitScope == NULL)) return zv::Val();
				if (UNEXPECTED(!ptlh::mergeOrTake(breakScope, breakExitScope))) return zv::Val();
			}
			finalScope = std::move(breakScope);
		}

		bool resultHasYield;
		if (UNEXPECTED(!pt_internal_statement_result_has_yield(result, resultHasYield))) return zv::Val();
		zv::Val exitPoints = pt_internal_statement_result_exit_points_for_outer_loop(result);
		if (UNEXPECTED(exitPoints.isUndef())) return zv::Val();
		{
			zv::Val hold;
			zval *more = pt_internal_statement_result_throw_points(result, hold);
			if (UNEXPECTED(more == NULL || !ptlh::mergeInto(throwPoints, more))) return zv::Val();
		}
		{
			zv::Val hold;
			zval *more = pt_internal_statement_result_impure_points(result, hold);
			if (UNEXPECTED(more == NULL || !ptlh::mergeInto(impurePoints, more))) return zv::Val();
		}
		zv::Val variableFlow;
		{
			zv::Val bodyFlowHold;
			zval *bodyFlow = pt_internal_statement_result_variable_flow(result, bodyFlowHold);
			if (UNEXPECTED(bodyFlow == NULL)) return zv::Val();
			zv::Val condFlow = pt_expression_result_variable_flow(condResult.raw());
			if (UNEXPECTED(condFlow.isUndef())) return zv::Val();
			ptlh::ConditionBoolean phpDocCondBoolean;
			zv::Val condType = pt_expression_result_get_type(condResult.raw());
			if (UNEXPECTED(condType.isUndef())) return zv::Val();
			if (UNEXPECTED(!ptlh::conditionBooleanOfType(condType.raw(), phpDocCondBoolean))) return zv::Val();
			variableFlow = pt_variable_flow_loop(NULL, bodyFlow, condFlow.raw(), true, !alwaysIterates, phpDocCondBoolean.isFalse != PT_TRI_YES);
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		}

		return pt_internal_statement_result_new(finalScope.raw(), resultHasYield || hasYield, alwaysTerminating, exitPoints.raw(), throwPoints.raw(), impurePoints.raw(), NULL, variableFlow.raw());
	}

	/* the statement-handler entry (Engine.h) */
	static zv::Val processStmtEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return DoWhileHandler(handler).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::DoWhileHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_do_while_handler()
{
	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\DoWhileHandler");
	ptdecl::DoWhileHandler::declareClass(cls);
	ptdecl::DoWhileHandler::declareProperties(cls);

	/* the real parameter names: the DI container pairs the
	 * #[AutowiredParameter] bool by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool treatPhpDocTypesAsCertain;
		if (!zp::parse<zp::Bool>(execute_data, treatPhpDocTypesAsCertain)) RETURN_THROWS();
		DoWhileHandler(Z_OBJ_P(ZEND_THIS)).construct(treatPhpDocTypesAsCertain);
	});

	cls.method<&DoWhileHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(DoWhileHandler(Z_OBJ_P(ZEND_THIS)).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_do_while_handler);
	pt_stmt_handler_entry_register(&pt_ce_do_while_handler, &DoWhileHandler::processStmtEntry);
}

/* }}} */
