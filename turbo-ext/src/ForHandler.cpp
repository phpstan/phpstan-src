/*
 * PHPStanTurbo\ForHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\ForHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo (the #[AutowiredParameter] bools pair by name) so Nette autowires
 * it. processStmt() is registered as the class's statement-handler entry
 * (Engine.h).
 *
 * The init, condition and loop expressions and the convergence passes over
 * the body go through NodeScopeResolver's direct entries (the fresh-stack
 * guard stays on the recursion); ExpressionResult, MutatingScope,
 * ExpressionResultStorage, the contexts, VariableFlow, VariableFlowBuilder
 * and the statement results through theirs. The twin's private static
 * targetVariables() recursion follows the destructuring's nesting and moves
 * to a fresh C stack segment when the current one runs low.
 */

#include "support.h"
#include "generated/ForHandler.h"

namespace slots = ptdecl::ForHandler::slot;
namespace sigs = ptdecl::ForHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"
#include "LoopHandlerCalls.h"

zend_class_entry *pt_ce_for_handler = nullptr;

namespace {

pt_property_site pt_fh_init_site;
pt_property_site pt_fh_cond_site;
pt_property_site pt_fh_loop_site;
pt_property_site pt_fh_stmts_site;
pt_property_site pt_fh_assign_var_site;
pt_property_site pt_fh_assign_expr_site;
pt_property_site pt_fh_int_value_site;
pt_property_site pt_fh_inc_var_site;
pt_property_site pt_fh_variable_name_site;
pt_property_site pt_fh_binary_left_site;
pt_property_site pt_fh_binary_right_site;
pt_property_site pt_fh_call_name_site;
pt_property_site pt_fh_arg_value_site;
pt_property_site pt_fh_items_site;
pt_property_site pt_fh_item_value_site;

/* the list a node property holds, or the TypeError foreach/count() would
 * raise; NULL = pending exception */
zval *readList(pt_property_site &site, zval *node, const char *name, size_t len)
{
	zval *value = ptsh::readNodeProperty(site, node, name, len);
	if (UNEXPECTED(value == NULL)) return NULL;
	if (UNEXPECTED(Z_TYPE_P(value) != IS_ARRAY)) {
		zend_type_error("count(): Argument #1 ($value) must be of type Countable|array, %s given", zend_zval_value_name(value));
		return NULL;
	}
	return value;
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\ForHandler; UNDEF = pending
 * exception. */
class ForHandler
{
public:
	explicit ForHandler(zend_object *self) : self(self) {}

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
		out = ptsh::isInstanceOf(stmt, PT_CLASS_FOR_STMT, error);
		return !error;
	}

	/* Mirrors processStmt(). */
	zv::Val processStmt(zval *nodeScopeResolver, zval *stmt, zval *scope, zval *originalStorage, zval *nodeCallback, zval *context) const
	{
		bool treatPhpDocTypesAsCertain = ptlh::boolSlot(self, slots::treatPhpDocTypesAsCertain);
		bool polluteScopeWithLoopInitialAssignments = ptlh::boolSlot(self, slots::polluteScopeWithLoopInitialAssignments);
		bool resolveTemplateArguments;
		if (UNEXPECTED(!pt_statement_context_should_resolve_template_arguments(context, resolveTemplateArguments))) return zv::Val();
		zv::Val initScope = zv::Val::copyOf(zv::Ref(scope));
		bool hasYield = false;
		zv::Arr throwPoints = zv::Arr::empty();
		zv::Arr impurePoints = zv::Arr::empty();
		zv::Arr initFlow = zv::Arr::empty();
		zv::Arr conditionFlow = zv::Arr::empty();

		zval *init = readList(pt_fh_init_site, stmt, PT_LC("init"));
		if (UNEXPECTED(init == NULL)) return zv::Val();
		zv::Val initHold = zv::Val::copyOf(zv::Ref(init));
		for (auto entry : zv::ArrRef(initHold.raw())) {
			zval *initExpr = entry.value().deref().raw();
			zv::Val expressionContext = pt_expression_context_create_top_level(resolveTemplateArguments);
			if (UNEXPECTED(expressionContext.isUndef())) return zv::Val();
			zv::Val initResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, initExpr, initScope.raw(), originalStorage, nodeCallback, expressionContext.raw());
			if (UNEXPECTED(initResult.isUndef())) return zv::Val();
			if (UNEXPECTED(!absorbExpressionResult(initResult.raw(), &initScope, &initFlow, hasYield, throwPoints, impurePoints))) return zv::Val();
		}
		/* $initTargets[spl_object_id($variable)] = true */
		zv::ScratchTable initTargets(8);
		init = readList(pt_fh_init_site, stmt, PT_LC("init"));
		if (UNEXPECTED(init == NULL)) return zv::Val();
		initHold = zv::Val::copyOf(zv::Ref(init));
		for (auto entry : zv::ArrRef(initHold.raw())) {
			zval *initExpr = entry.value().deref().raw();
			bool error = false;
			if (!ptsh::isInstanceOf(initExpr, PT_CLASS_ASSIGN_EXPR, error)) {
				if (UNEXPECTED(error)) return zv::Val();
				continue;
			}
			zval *var = ptsh::readNodeProperty(pt_fh_assign_var_site, initExpr, PT_LC("var"));
			if (UNEXPECTED(var == NULL)) return zv::Val();
			if (UNEXPECTED(!targetVariables(var, initTargets.table()))) return zv::Val();
		}

		zv::Val bodyScope = zv::Val::copyOf(initScope.ref());
		zend_long isIterableAtLeastOnce = PT_TRI_YES;
		zval *cond = readList(pt_fh_cond_site, stmt, PT_LC("cond"));
		if (UNEXPECTED(cond == NULL)) return zv::Val();
		zv::Val condHold = zv::Val::copyOf(zv::Ref(cond));
		/* array_last($stmt->cond) */
		zval *lastCondExpr = NULL;
		for (auto entry : zv::ArrRef(condHold.raw())) {
			lastCondExpr = entry.value().deref().raw();
		}
		uint32_t condCount = zend_hash_num_elements(Z_ARRVAL_P(condHold.raw()));
		if (condCount > 0) {
			zv::Val storage = pt_expression_result_storage_duplicate(originalStorage);
			if (UNEXPECTED(storage.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_mutating_scope_push_expression_result_storage(Z_OBJ_P(scope), storage.raw()))) return zv::Val();
			[&]() {
				for (auto entry : zv::ArrRef(condHold.raw())) {
					zval *condExpr = entry.value().deref().raw();
					zv::Val noop = ptlh::newNoopNodeCallback();
					if (UNEXPECTED(noop.isUndef())) return;
					zv::Val expressionContext = pt_expression_context_create_deep(false);
					if (UNEXPECTED(expressionContext.isUndef())) return;
					zv::Val condResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, condExpr, bodyScope.raw(), storage.raw(), noop.raw(), expressionContext.raw());
					if (UNEXPECTED(condResult.isUndef())) return;
					{
						zv::Val hold;
						zval *resultScope = pt_expression_result_scope(condResult.raw(), hold);
						if (UNEXPECTED(resultScope == NULL)) return;
						initScope = zv::Val::copyOf(zv::Ref(resultScope));
					}
					zv::Val flow = pt_expression_result_variable_flow(condResult.raw());
					if (UNEXPECTED(flow.isUndef())) return;
					conditionFlow.separate();
					if (entry.hasStringKey()) {
						zend_hash_update(conditionFlow.table(), entry.stringKey(), flow.raw());
					} else {
						zend_hash_index_update(conditionFlow.table(), entry.indexKey(), flow.raw());
					}
					(void) flow.take();

					// only the last condition expression is relevant whether the loop continues
					// see https://www.php.net/manual/en/control-structures.for.php
					if (Z_OBJ_P(condExpr) == Z_OBJ_P(lastCondExpr)) {
						ptlh::ConditionBoolean condTruthiness;
						if (UNEXPECTED(!ptlh::conditionBoolean(condResult.raw(), treatPhpDocTypesAsCertain, condTruthiness))) return;
						isIterableAtLeastOnce &= condTruthiness.isTrue;
					}

					if (UNEXPECTED(!absorbExpressionResult(condResult.raw(), NULL, NULL, hasYield, throwPoints, impurePoints))) return;
					bodyScope = pt_expression_result_get_truthy_scope(condResult.raw());
					if (UNEXPECTED(bodyScope.isUndef())) return;
				}
			}();
			pt_finally([&]() { (void) pt_mutating_scope_pop_expression_result_storage(Z_OBJ_P(scope)); });
			if (UNEXPECTED(EG(exception))) return zv::Val();
		}

		bool isTopLevel;
		if (UNEXPECTED(!pt_statement_context_is_top_level(context, isTopLevel))) return zv::Val();
		if (isTopLevel) {
			zend_long count = 0;
			zv::Val prevEntryScope;
			do {
				zv::Val prevScope = zv::Val::copyOf(bodyScope.ref());
				zv::Val storage = pt_expression_result_storage_duplicate(originalStorage);
				if (UNEXPECTED(storage.isUndef())) return zv::Val();
				bodyScope = pt_mutating_scope_merge_with(Z_OBJ_P(bodyScope.raw()), initScope.raw());
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
				if (UNEXPECTED(!pt_mutating_scope_push_expression_result_storage(Z_OBJ_P(scope), storage.raw()))) return zv::Val();
				zv::Arr passFlows = zv::Arr::empty();
				bool brokeOut = false;
				[&]() {
					if (lastCondExpr != NULL) {
						zv::Val noop = ptlh::newNoopNodeCallback();
						if (UNEXPECTED(noop.isUndef())) return;
						zv::Val expressionContext = pt_expression_context_create_deep(false);
						if (UNEXPECTED(expressionContext.isUndef())) return;
						zv::Val passCondResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, lastCondExpr, bodyScope.raw(), storage.raw(), noop.raw(), expressionContext.raw());
						if (UNEXPECTED(passCondResult.isUndef())) return;
						bodyScope = pt_expression_result_get_truthy_scope(passCondResult.raw());
						if (UNEXPECTED(bodyScope.isUndef())) return;
						zv::Val flow = pt_expression_result_variable_flow(passCondResult.raw());
						if (UNEXPECTED(flow.isUndef())) return;
						passFlows.push(std::move(flow));
					}
					zval *stmts = ptsh::readNodeProperty(pt_fh_stmts_site, stmt, PT_LC("stmts"));
					if (UNEXPECTED(stmts == NULL)) return;
					zv::Val stmtsHold = zv::Val::copyOf(zv::Ref(stmts));
					zv::Val noop = ptlh::newNoopNodeCallback();
					if (UNEXPECTED(noop.isUndef())) return;
					zv::Val deepContext = pt_statement_context_enter_deep(context);
					if (UNEXPECTED(deepContext.isUndef())) return;
					zv::Val passContext = pt_statement_context_without_template_argument_resolution(deepContext.raw());
					if (UNEXPECTED(passContext.isUndef())) return;
					zv::Val walked = pt_node_scope_resolver_process_stmt_nodes_internal(nodeScopeResolver, stmt, stmtsHold.raw(), bodyScope.raw(), storage.raw(), noop.raw(), passContext.raw());
					if (UNEXPECTED(walked.isUndef())) return;
					zv::Val bodyScopeResult = pt_internal_statement_result_filter_out_loop_exit_points(walked.raw());
					if (UNEXPECTED(bodyScopeResult.isUndef())) return;
					{
						zv::Val hold;
						zval *flow = pt_internal_statement_result_variable_flow(bodyScopeResult.raw(), hold);
						if (UNEXPECTED(flow == NULL)) return;
						passFlows.push(zv::Ref(flow));
					}
					zv::Val backEdgeScope = pt_internal_statement_result_loop_back_edge_scope(bodyScopeResult.raw());
					if (UNEXPECTED(backEdgeScope.isUndef())) return;
					if (backEdgeScope.isNull()) {
						bodyScope = std::move(prevScope);
						brokeOut = true;
						return;
					}
					bodyScope = std::move(backEdgeScope);

					zval *loop = readList(pt_fh_loop_site, stmt, PT_LC("loop"));
					if (UNEXPECTED(loop == NULL)) return;
					zv::Val loopHold = zv::Val::copyOf(zv::Ref(loop));
					for (auto entry : zv::ArrRef(loopHold.raw())) {
						zval *loopExpr = entry.value().deref().raw();
						zv::Val loopNoop = ptlh::newNoopNodeCallback();
						if (UNEXPECTED(loopNoop.isUndef())) return;
						zv::Val expressionContext = pt_expression_context_create_top_level(false);
						if (UNEXPECTED(expressionContext.isUndef())) return;
						zv::Val exprResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, loopExpr, bodyScope.raw(), storage.raw(), loopNoop.raw(), expressionContext.raw());
						if (UNEXPECTED(exprResult.isUndef())) return;
						if (UNEXPECTED(!absorbExpressionResult(exprResult.raw(), &bodyScope, &passFlows, hasYield, throwPoints, impurePoints))) return;
					}
				}();
				pt_finally([&]() { (void) pt_mutating_scope_pop_expression_result_storage(Z_OBJ_P(scope)); });
				if (UNEXPECTED(EG(exception))) return zv::Val();
				if (brokeOut) break;

				bool equal;
				if (UNEXPECTED(!ptlh::scopesEqual(bodyScope.raw(), prevScope.raw(), equal))) return zv::Val();
				if (equal) break;

				if (count >= PT_LH_GENERALIZE_AFTER_ITERATION_LIMIT) {
					zv::Val passFlow = pt_variable_flow_sequence_list(passFlows.table());
					if (UNEXPECTED(passFlow.isUndef())) return zv::Val();
					bodyScope = ptlh::generalizeWithWrittenNames(prevScope.raw(), bodyScope.raw(), stmt, passFlow.raw());
					if (UNEXPECTED(bodyScope.isUndef())) return zv::Val();
				}
				count++;
			} while (count < PT_LH_LOOP_SCOPE_ITERATIONS_LIMIT);
		}

		zval *storage = originalStorage;
		bodyScope = pt_mutating_scope_merge_with(Z_OBJ_P(bodyScope.raw()), initScope.raw());
		if (UNEXPECTED(bodyScope.isUndef())) return zv::Val();

		zend_long alwaysIterates = isTopLevel ? PT_TRI_YES : PT_TRI_NO;
		if (lastCondExpr != NULL) {
			// process the condition once and read the always-iterates check off
			// its result - the previous scope-based read was a guaranteed
			// storage miss (the condition was only stored into discarded
			// convergence duplicates) that re-priced it on demand
			zv::Val expressionContext = pt_expression_context_create_deep(resolveTemplateArguments);
			if (UNEXPECTED(expressionContext.isUndef())) return zv::Val();
			zv::Val condResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, lastCondExpr, bodyScope.raw(), storage, nodeCallback, expressionContext.raw());
			if (UNEXPECTED(condResult.isUndef())) return zv::Val();
			{
				ptlh::ConditionBoolean condBoolean;
				if (UNEXPECTED(!ptlh::conditionBoolean(condResult.raw(), true, condBoolean))) return zv::Val();
				alwaysIterates &= condBoolean.isTrue;
			}
			{
				zv::Val flow = pt_expression_result_variable_flow(condResult.raw());
				if (UNEXPECTED(flow.isUndef())) return zv::Val();
				conditionFlow.separate();
				zend_long key = (zend_long) condCount - 1;
				zend_hash_index_update(conditionFlow.table(), (zend_ulong) key, flow.raw());
				(void) flow.take();
			}
			bodyScope = pt_expression_result_get_truthy_scope(condResult.raw());
			if (UNEXPECTED(bodyScope.isUndef())) return zv::Val();
			bodyScope = inferForLoopExpressions(nodeScopeResolver, stmt, lastCondExpr, bodyScope.raw(), storage);
			if (UNEXPECTED(bodyScope.isUndef())) return zv::Val();
		}

		zv::Val finalScopeResult;
		{
			zval *stmts = ptsh::readNodeProperty(pt_fh_stmts_site, stmt, PT_LC("stmts"));
			if (UNEXPECTED(stmts == NULL)) return zv::Val();
			zv::Val stmtsHold = zv::Val::copyOf(zv::Ref(stmts));
			zv::Val walked = pt_node_scope_resolver_process_stmt_nodes_internal(nodeScopeResolver, stmt, stmtsHold.raw(), bodyScope.raw(), storage, nodeCallback, context);
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

		zv::Val loopScope = zv::Val::copyOf(finalScope.ref());
		{
			zval *loop = readList(pt_fh_loop_site, stmt, PT_LC("loop"));
			if (UNEXPECTED(loop == NULL)) return zv::Val();
			zv::Val loopHold = zv::Val::copyOf(zv::Ref(loop));
			for (auto entry : zv::ArrRef(loopHold.raw())) {
				zval *loopExpr = entry.value().deref().raw();
				zv::Val expressionContext = pt_expression_context_create_top_level(resolveTemplateArguments);
				if (UNEXPECTED(expressionContext.isUndef())) return zv::Val();
				zv::Val loopResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, loopExpr, loopScope.raw(), storage, nodeCallback, expressionContext.raw());
				if (UNEXPECTED(loopResult.isUndef())) return zv::Val();
				zv::Val hold;
				zval *resultScope = pt_expression_result_scope(loopResult.raw(), hold);
				if (UNEXPECTED(resultScope == NULL)) return zv::Val();
				loopScope = zv::Val::copyOf(zv::Ref(resultScope));
			}
		}
		finalScope = pt_mutating_scope_generalize_with(Z_OBJ_P(finalScope.raw()), Z_OBJ_P(loopScope.raw()));
		if (UNEXPECTED(finalScope.isUndef())) return zv::Val();

		if (lastCondExpr != NULL) {
			// the loop condition narrows the post-loop scope to its falsey branch,
			// priced on the GENERALIZED exit scope. The condition's stored result
			// was walked before generalizeWith() widened the counter, so its
			// verdict is stale here: `$k <= $d` with a literal `$k` reads as
			// always-true, whose falsey branch is unreachable - and that narrowed
			// every operand to never, killing the enclosing loop's counter
			// (a nested loop's counter never widened). Same shape as WhileHandler.
			zv::Val duplicate = pt_expression_result_storage_duplicate(storage);
			if (UNEXPECTED(duplicate.isUndef())) return zv::Val();
			zv::Val onDemand = pt_node_scope_resolver_process_expr_on_demand(nodeScopeResolver, lastCondExpr, finalScope.raw(), duplicate.raw());
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

		zv::Val breakExitPoints = ptlh::breakExitPoints(result);
		if (UNEXPECTED(breakExitPoints.isUndef())) return zv::Val();
		uint32_t breakCount = ptlh::countOf(breakExitPoints.raw());
		if (breakCount > 0) {
			zv::Val breakScope = alwaysIterates == PT_TRI_YES || backEdgeDead ? zv::Val::null() : std::move(finalScope);
			for (auto entry : zv::ArrRef(breakExitPoints.raw())) {
				zv::Val hold;
				zval *breakExitScope = pt_internal_statement_exit_point_scope(entry.value().deref().raw(), hold);
				if (UNEXPECTED(breakExitScope == NULL)) return zv::Val();
				if (UNEXPECTED(!ptlh::mergeOrTake(breakScope, breakExitScope))) return zv::Val();
			}
			finalScope = std::move(breakScope);
		}

		bool resultAlwaysTerminating;
		bool resultAlwaysTerminatingRead = false;
		bool resetToEntry = isIterableAtLeastOnce == PT_TRI_NO;
		if (!resetToEntry) {
			if (UNEXPECTED(!pt_internal_statement_result_is_always_terminating(result, resultAlwaysTerminating))) return zv::Val();
			resultAlwaysTerminatingRead = true;
			resetToEntry = resultAlwaysTerminating || (backEdgeDead && breakCount == 0);
		}
		if (resetToEntry) {
			finalScope = zv::Val::copyOf(zv::Ref(polluteScopeWithLoopInitialAssignments ? initScope.raw() : scope));
		} else if (isIterableAtLeastOnce == PT_TRI_MAYBE) {
			finalScope = pt_mutating_scope_merge_with(Z_OBJ_P(finalScope.raw()), polluteScopeWithLoopInitialAssignments ? initScope.raw() : scope);
			if (UNEXPECTED(finalScope.isUndef())) return zv::Val();
		} else if (!polluteScopeWithLoopInitialAssignments) {
			finalScope = pt_mutating_scope_merge_with(Z_OBJ_P(finalScope.raw()), scope);
			if (UNEXPECTED(finalScope.isUndef())) return zv::Val();
		}

		bool isAlwaysTerminating;
		if (alwaysIterates == PT_TRI_YES) {
			zv::Val againBreakExitPoints = ptlh::breakExitPoints(result);
			if (UNEXPECTED(againBreakExitPoints.isUndef())) return zv::Val();
			isAlwaysTerminating = ptlh::countOf(againBreakExitPoints.raw()) == 0;
		} else if (isIterableAtLeastOnce == PT_TRI_YES) {
			if (resultAlwaysTerminatingRead) {
				isAlwaysTerminating = resultAlwaysTerminating;
			} else if (UNEXPECTED(!pt_internal_statement_result_is_always_terminating(result, isAlwaysTerminating))) {
				return zv::Val();
			}
		} else {
			isAlwaysTerminating = false;
		}

		zv::Arr updateFlow = zv::Arr::empty();
		{
			zval *loop = readList(pt_fh_loop_site, stmt, PT_LC("loop"));
			if (UNEXPECTED(loop == NULL)) return zv::Val();
			zv::Val loopHold = zv::Val::copyOf(zv::Ref(loop));
			for (auto entry : zv::ArrRef(loopHold.raw())) {
				zv::Val child = pt_variable_flow_builder_child(entry.value().deref().raw(), storage);
				if (UNEXPECTED(child.isUndef())) return zv::Val();
				updateFlow.push(std::move(child));
			}
		}
		zv::Val condition = pt_variable_flow_sequence_list(conditionFlow.table());
		if (UNEXPECTED(condition.isUndef())) return zv::Val();
		zv::Val update = pt_variable_flow_sequence_list(updateFlow.table());
		if (UNEXPECTED(update.isUndef())) return zv::Val();
		zv::Val loopFlow;
		{
			zv::Val bodyFlowHold;
			zval *bodyFlow = pt_internal_statement_result_variable_flow(result, bodyFlowHold);
			if (UNEXPECTED(bodyFlow == NULL)) return zv::Val();
			// the body may run or not whatever the condition says: a write in
			// it does not make an earlier write dead, and a usage in it counts
			loopFlow = pt_variable_flow_loop(condition.raw(), bodyFlow, update.raw(), false, true);
			if (UNEXPECTED(loopFlow.isUndef())) return zv::Val();
		}
		zv::Val initSequence = pt_variable_flow_sequence_list(initFlow.table());
		if (UNEXPECTED(initSequence.isUndef())) return zv::Val();
		zv::Val initWrites = pt_variable_flow_builder_writes(initSequence.raw());
		if (UNEXPECTED(initWrites.isUndef())) return zv::Val();
		zv::Arr bindings = zv::Arr::empty();
		for (auto entry : zv::ArrRef(initWrites.raw())) {
			zval *write = entry.value().deref().raw();
			bool isOffsetWrite;
			zend_long id = 0;
			if (UNEXPECTED(!ptlh::variableWriteInfo(write, isOffsetWrite, id))) return zv::Val();
			if (isOffsetWrite) continue;
			zval *target = zend_hash_index_find(initTargets.table(), (zend_ulong) id);
			if (target == NULL) continue;
			bindings.push(zv::Ref(write));
		}
		zv::Arr statementFlows = zv::Arr::copyOfTable(initFlow.table());
		statementFlows.push(std::move(loopFlow));
		zv::Val statementFlow = pt_variable_flow_sequence_list(statementFlows.table());
		if (UNEXPECTED(statementFlow.isUndef())) return zv::Val();
		zv::Arr ownWrites = zv::Arr::copyOfTable(Z_ARRVAL_P(initWrites.raw()));
		{
			zv::Val updateWrites = pt_variable_flow_builder_writes(update.raw());
			if (UNEXPECTED(updateWrites.isUndef())) return zv::Val();
			for (auto entry : zv::ArrRef(updateWrites.raw())) {
				ownWrites.push(entry.value());
			}
		}
		zv::Val variableFlow = pt_variable_flow_loop_statement(stmt, statementFlow.raw(), bindings.raw(), ownWrites.raw());
		if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();

		zv::Val constraints = pt_mutating_scope_get_template_argument_constraints(Z_OBJ_P(loopScope.raw()));
		if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		zv::Val resultScope = pt_mutating_scope_add_template_argument_constraints(Z_OBJ_P(finalScope.raw()), constraints.raw());
		if (UNEXPECTED(resultScope.isUndef())) return zv::Val();
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
		return pt_internal_statement_result_new(resultScope.raw(), resultHasYield || hasYield, isAlwaysTerminating, exitPoints.raw(), throwPoints.raw(), impurePoints.raw(), NULL, variableFlow.raw());
	}

	/* the statement-handler entry (Engine.h) */
	static zv::Val processStmtEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return ForHandler(handler).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* an init / condition / loop expression's result folded in:
	 * [$scope = $result->getScope();] [$flows[] = $result->getVariableFlow();]
	 * $hasYield = $hasYield || $result->hasYield();
	 * $throwPoints = array_merge($throwPoints, $result->getThrowPoints());
	 * $impurePoints = array_merge($impurePoints, $result->getImpurePoints());
	 * false = pending exception */
	[[nodiscard]] static bool absorbExpressionResult(zval *result, zv::Val *scope, zv::Arr *flows, bool &hasYield, zv::Arr &throwPoints, zv::Arr &impurePoints)
	{
		if (scope != NULL) {
			zv::Val hold;
			zval *resultScope = pt_expression_result_scope(result, hold);
			if (UNEXPECTED(resultScope == NULL)) return false;
			*scope = zv::Val::copyOf(zv::Ref(resultScope));
		}
		if (flows != NULL) {
			zv::Val flow = pt_expression_result_variable_flow(result);
			if (UNEXPECTED(flow.isUndef())) return false;
			flows->push(std::move(flow));
		}
		if (!hasYield) {
			if (UNEXPECTED(!pt_expression_result_has_yield(result, hasYield))) return false;
		}
		{
			zv::Val hold;
			zval *more = pt_expression_result_throw_points(result, hold);
			if (UNEXPECTED(more == NULL || !ptlh::mergeInto(throwPoints, more))) return false;
		}
		zv::Val hold;
		zval *more = pt_expression_result_impure_points(result, hold);
		return more != NULL && ptlh::mergeInto(impurePoints, more);
	}

	/* Mirrors the private static targetVariables(): the variables collected
	 * as keys (object handles) of `into`; false = pending exception */
	[[nodiscard]] static bool targetVariables(zval *target, HashTable *into)
	{
		if (EXPECTED(!pt_engine_stack_low())) return targetVariablesBody(target, into);
		bool ok = false;
		pt_engine_with_stack([&]() { ok = targetVariablesBody(target, into); });
		return ok;
	}

	[[nodiscard]] static bool targetVariablesBody(zval *target, HashTable *into)
	{
		bool error = false;
		if (ptsh::isInstanceOf(target, PT_CLASS_VARIABLE, error)) {
			zval flag;
			ZVAL_TRUE(&flag);
			zend_hash_index_update(into, Z_OBJ_HANDLE_P(target), &flag);
			return true;
		}
		if (UNEXPECTED(error)) return false;
		bool destructuring = ptsh::isInstanceOf(target, PT_CLASS_LIST_EXPR, error) || ptsh::isInstanceOf(target, PT_CLASS_ARRAY_EXPR, error);
		if (UNEXPECTED(error)) return false;
		if (!destructuring) return true;
		zval *items = ptsh::readNodeProperty(pt_fh_items_site, target, PT_LC("items"));
		if (UNEXPECTED(items == NULL)) return false;
		if (UNEXPECTED(Z_TYPE_P(items) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(items));
			return EG(exception) == NULL;
		}
		zv::Val itemsHold = zv::Val::copyOf(zv::Ref(items));
		for (auto entry : zv::ArrRef(itemsHold.raw())) {
			zval *item = entry.value().deref().raw();
			if (Z_TYPE_P(item) == IS_NULL) continue;
			if (UNEXPECTED(Z_TYPE_P(item) != IS_OBJECT)) {
				zend_error(E_WARNING, "Attempt to read property \"value\" on %s", zend_zval_value_name(item));
				if (UNEXPECTED(EG(exception))) return false;
				continue;
			}
			zval *value = ptsh::readNodeProperty(pt_fh_item_value_site, item, PT_LC("value"));
			if (UNEXPECTED(value == NULL)) return false;
			zv::Val valueHold = zv::Val::copyOf(zv::Ref(value));
			if (UNEXPECTED(!targetVariables(valueHold.raw(), into))) return false;
		}
		return true;
	}

	/* $name of a Variable node when it is a string, NULL otherwise (and on
	 * a pending exception, with `error` set) */
	static zval *stringVariableName(zval *node, bool &error)
	{
		if (!ptsh::isInstanceOf(node, PT_CLASS_VARIABLE, error)) return NULL;
		zval *name = ptsh::readNodeProperty(pt_fh_variable_name_site, node, PT_LC("name"));
		if (UNEXPECTED(name == NULL)) {
			error = true;
			return NULL;
		}
		return Z_TYPE_P(name) == IS_STRING ? name : NULL;
	}

	/* $call instanceof FuncCall && $call->name instanceof Name &&
	 * !$call->isFirstClassCallable() && in_array($call->name->toLowerString(),
	 * ['count', 'sizeof'], true) && count($call->getArgs()) > 0 &&
	 * $call->getArgs()[0]->value instanceof Variable — the array argument
	 * (borrowed from `hold`), NULL otherwise (and on a pending exception,
	 * with `error` set) */
	static zval *countedArray(zval *call, zv::Val &hold, bool &error)
	{
		if (!ptsh::isInstanceOf(call, PT_CLASS_FUNC_CALL, error)) return NULL;
		zval *name = ptsh::readNodeProperty(pt_fh_call_name_site, call, PT_LC("name"));
		if (UNEXPECTED(name == NULL)) {
			error = true;
			return NULL;
		}
		if (!ptsh::isInstanceOf(name, PT_CLASS_NAME, error)) return NULL;
		bool firstClassCallable;
		if (UNEXPECTED(!pt_call_like_is_first_class_callable(Z_OBJ_P(call), firstClassCallable))) {
			error = true;
			return NULL;
		}
		if (firstClassCallable) return NULL;
		zv::Val lower = ptlh::nameToLowerString(name);
		if (UNEXPECTED(lower.isUndef())) {
			error = true;
			return NULL;
		}
		if (!lower.ref().stringEquals("count") && !lower.ref().stringEquals("sizeof")) return NULL;
		zv::Val argsHold;
		zval *args = pt_call_like_args(Z_OBJ_P(call), argsHold);
		if (UNEXPECTED(args == NULL)) {
			error = true;
			return NULL;
		}
		if (Z_TYPE_P(args) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL_P(args)) == 0) return NULL;
		zval *first = zend_hash_index_find(Z_ARRVAL_P(args), 0);
		if (UNEXPECTED(first == NULL)) {
			zend_error(E_WARNING, "Undefined array key 0");
			error = EG(exception) != NULL;
			return NULL;
		}
		ZVAL_DEREF(first);
		if (UNEXPECTED(Z_TYPE_P(first) != IS_OBJECT)) {
			zend_error(E_WARNING, "Attempt to read property \"value\" on %s", zend_zval_value_name(first));
			error = EG(exception) != NULL;
			return NULL;
		}
		zval *value = ptsh::readNodeProperty(pt_fh_arg_value_site, first, PT_LC("value"));
		if (UNEXPECTED(value == NULL)) {
			error = true;
			return NULL;
		}
		if (!ptsh::isInstanceOf(value, PT_CLASS_VARIABLE, error)) return NULL;
		hold = zv::Val::copyOf(zv::Ref(value));
		return hold.raw();
	}

	/* Mirrors the private inferForLoopExpressions(). */
	static zv::Val inferForLoopExpressions(zval *nodeScopeResolver, zval *stmt, zval *lastCondExpr, zval *bodyScopeArg, zval *storage)
	{
		// infer $items[$i] type from for ($i = 0; $i < count($items); $i++) {...}
		zv::Val bodyScope = zv::Val::copyOf(zv::Ref(bodyScopeArg));
		bool error = false;

		zval *init = readList(pt_fh_init_site, stmt, PT_LC("init"));
		if (UNEXPECTED(init == NULL)) return zv::Val();
		if (zend_hash_num_elements(Z_ARRVAL_P(init)) != 1) return bodyScope;
		zval *init0 = ptsh::listItem(init, 0);
		if (UNEXPECTED(init0 == NULL)) return zv::Val();
		zv::Val init0Hold = zv::Val::copyOf(zv::Ref(init0));
		if (!ptsh::isInstanceOf(init0Hold.raw(), PT_CLASS_ASSIGN_EXPR, error)) return error ? zv::Val() : std::move(bodyScope);
		zval *initVar = ptsh::readNodeProperty(pt_fh_assign_var_site, init0Hold.raw(), PT_LC("var"));
		if (UNEXPECTED(initVar == NULL)) return zv::Val();
		if (!ptsh::isInstanceOf(initVar, PT_CLASS_VARIABLE, error)) return error ? zv::Val() : std::move(bodyScope);
		zval *initExpr = ptsh::readNodeProperty(pt_fh_assign_expr_site, init0Hold.raw(), PT_LC("expr"));
		if (UNEXPECTED(initExpr == NULL)) return zv::Val();
		if (!ptsh::isInstanceOf(initExpr, PT_CLASS_SCALAR_INT, error)) return error ? zv::Val() : std::move(bodyScope);
		zval *initValue = ptsh::readNodeProperty(pt_fh_int_value_site, initExpr, PT_LC("value"));
		if (UNEXPECTED(initValue == NULL)) return zv::Val();
		if (!(Z_TYPE_P(initValue) == IS_LONG && Z_LVAL_P(initValue) == 0)) return bodyScope;
		zval *loop = readList(pt_fh_loop_site, stmt, PT_LC("loop"));
		if (UNEXPECTED(loop == NULL)) return zv::Val();
		if (zend_hash_num_elements(Z_ARRVAL_P(loop)) != 1) return bodyScope;
		zval *loop0 = ptsh::listItem(loop, 0);
		if (UNEXPECTED(loop0 == NULL)) return zv::Val();
		zv::Val loop0Hold = zv::Val::copyOf(zv::Ref(loop0));
		bool inc = ptsh::isInstanceOf(loop0Hold.raw(), PT_CLASS_PRE_INC, error) || ptsh::isInstanceOf(loop0Hold.raw(), PT_CLASS_POST_INC, error);
		if (UNEXPECTED(error)) return zv::Val();
		if (!inc) return bodyScope;
		zval *loopVar = ptsh::readNodeProperty(pt_fh_inc_var_site, loop0Hold.raw(), PT_LC("var"));
		if (UNEXPECTED(loopVar == NULL)) return zv::Val();
		if (!ptsh::isInstanceOf(loopVar, PT_CLASS_VARIABLE, error)) return error ? zv::Val() : std::move(bodyScope);

		// $i < count($items)
		if (UNEXPECTED(!inferCountedDimFetch(nodeScopeResolver, stmt, lastCondExpr, PT_CLASS_SMALLER_EXPR, true, bodyScope, storage))) return zv::Val();
		// count($items) > $i
		if (UNEXPECTED(!inferCountedDimFetch(nodeScopeResolver, stmt, lastCondExpr, PT_CLASS_GREATER_EXPR, false, bodyScope, storage))) return zv::Val();

		return bodyScope;
	}

	/* one of inferForLoopExpressions()'s two shapes: `$i < count($items)`
	 * (variableOnLeft) or `count($items) > $i`, assigning $items[$i] its
	 * list's value type on the body scope; false = pending exception */
	[[nodiscard]] static bool inferCountedDimFetch(zval *nodeScopeResolver, zval *stmt, zval *lastCondExpr, int comparisonClass, bool variableOnLeft, zv::Val &bodyScope, zval *storage)
	{
		bool error = false;
		if (!ptsh::isInstanceOf(lastCondExpr, comparisonClass, error)) return !error;
		zval *left = ptsh::readNodeProperty(pt_fh_binary_left_site, lastCondExpr, PT_LC("left"));
		if (UNEXPECTED(left == NULL)) return false;
		zv::Val leftHold = zv::Val::copyOf(zv::Ref(left));
		zval *right = ptsh::readNodeProperty(pt_fh_binary_right_site, lastCondExpr, PT_LC("right"));
		if (UNEXPECTED(right == NULL)) return false;
		zv::Val rightHold = zv::Val::copyOf(zv::Ref(right));
		zval *variableSide = variableOnLeft ? leftHold.raw() : rightHold.raw();
		zval *callSide = variableOnLeft ? rightHold.raw() : leftHold.raw();
		if (!ptsh::isInstanceOf(variableSide, PT_CLASS_VARIABLE, error)) return !error;
		zv::Val arrayArgHold;
		zval *arrayArg = countedArray(callSide, arrayArgHold, error);
		if (arrayArg == NULL) return !error;

		zval *init = readList(pt_fh_init_site, stmt, PT_LC("init"));
		if (UNEXPECTED(init == NULL)) return false;
		zval *init0 = ptsh::listItem(init, 0);
		if (UNEXPECTED(init0 == NULL)) return false;
		zval *initVar = ptsh::readNodeProperty(pt_fh_assign_var_site, init0, PT_LC("var"));
		if (UNEXPECTED(initVar == NULL)) return false;
		zval *initName = ptsh::readNodeProperty(pt_fh_variable_name_site, initVar, PT_LC("name"));
		if (UNEXPECTED(initName == NULL)) return false;
		if (Z_TYPE_P(initName) != IS_STRING) return true;
		zval *loop = readList(pt_fh_loop_site, stmt, PT_LC("loop"));
		if (UNEXPECTED(loop == NULL)) return false;
		zval *loop0 = ptsh::listItem(loop, 0);
		if (UNEXPECTED(loop0 == NULL)) return false;
		zval *loopVar = ptsh::readNodeProperty(pt_fh_inc_var_site, loop0, PT_LC("var"));
		if (UNEXPECTED(loopVar == NULL)) return false;
		zval *loopName = ptsh::readNodeProperty(pt_fh_variable_name_site, loopVar, PT_LC("name"));
		if (UNEXPECTED(loopName == NULL)) return false;
		if (!zend_is_identical(initName, loopName)) return true;
		zval *variableName = ptsh::readNodeProperty(pt_fh_variable_name_site, variableSide, PT_LC("name"));
		if (UNEXPECTED(variableName == NULL)) return false;
		if (!zend_is_identical(initName, variableName)) return true;

		zv::Val stored = pt_node_scope_resolver_read_stored_result(nodeScopeResolver, arrayArg, storage);
		if (UNEXPECTED(stored.isUndef())) return false;
		zv::Val arrayType = pt_expression_result_get_type_on_scope(stored.raw(), bodyScope.raw(), false);
		if (UNEXPECTED(arrayType.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(arrayType.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function isList() on %s", zend_zval_value_name(arrayType.raw()));
			return false;
		}
		zend_long isList = pt_type_op_trinary(Z_OBJ_P(arrayType.raw()), PT_OP_IS_LIST, 0, NULL);
		if (UNEXPECTED(isList < 0)) return false;
		if (isList != PT_TRI_YES) return true;

		zv::Args dimArgs{arrayArg, variableSide};
		zv::Val dimFetch = pt_type_new(PT_CLASS_ARRAY_DIM_FETCH, 2, dimArgs);
		if (UNEXPECTED(dimFetch.isUndef())) return false;
		zv::Val valueType = pt_type_op(Z_OBJ_P(arrayType.raw()), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
		if (UNEXPECTED(valueType.isUndef())) return false;
		zv::Val storedAgain = pt_node_scope_resolver_read_stored_result(nodeScopeResolver, arrayArg, storage);
		if (UNEXPECTED(storedAgain.isUndef())) return false;
		zv::Val nativeArrayType = pt_expression_result_get_type_on_scope(storedAgain.raw(), bodyScope.raw(), true);
		if (UNEXPECTED(nativeArrayType.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(nativeArrayType.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getIterableValueType() on %s", zend_zval_value_name(nativeArrayType.raw()));
			return false;
		}
		zv::Val nativeValueType = pt_type_op(Z_OBJ_P(nativeArrayType.raw()), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
		if (UNEXPECTED(nativeValueType.isUndef())) return false;
		zv::Val assigned = pt_mutating_scope_assign_expression(Z_OBJ_P(bodyScope.raw()), Z_OBJ_P(dimFetch.raw()), valueType.raw(), nativeValueType.raw());
		if (UNEXPECTED(assigned.isUndef())) return false;
		bodyScope = std::move(assigned);
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::ForHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_for_handler()
{
	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\ForHandler");
	ptdecl::ForHandler::declareClass(cls);
	ptdecl::ForHandler::declareProperties(cls);

	/* the real parameter names: the DI container pairs the
	 * #[AutowiredParameter] bools by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool polluteScopeWithLoopInitialAssignments, treatPhpDocTypesAsCertain;
		if (!zp::parse<zp::Bool, zp::Bool>(execute_data, polluteScopeWithLoopInitialAssignments, treatPhpDocTypesAsCertain)) RETURN_THROWS();
		ForHandler(Z_OBJ_P(ZEND_THIS)).construct(polluteScopeWithLoopInitialAssignments, treatPhpDocTypesAsCertain);
	});

	cls.method<&ForHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(ForHandler(Z_OBJ_P(ZEND_THIS)).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_for_handler);
	pt_stmt_handler_entry_register(&pt_ce_for_handler, &ForHandler::processStmtEntry);
}

/* }}} */
