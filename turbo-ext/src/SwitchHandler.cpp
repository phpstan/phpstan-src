/*
 * PHPStanTurbo\SwitchHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\SwitchHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processStmt() is registered as the class's
 * statement-handler entry (Engine.h).
 *
 * The subject, the case conditions and the case bodies are walked through
 * NodeScopeResolver's direct entries; the `==` narrowing through
 * IdenticalNarrowingHelper's (the helper looked up from the container per
 * case, like the twin); ExpressionResult, MutatingScope, SpecifiedTypes,
 * VariableFlow, VariableFlowBuilder and the statement results through
 * theirs. The synthetic Equal / BooleanOr nodes, SwitchConditionArm and
 * SwitchConditionNode are PHP classes created through the class map.
 */

#include "support.h"
#include "generated/SwitchHandler.h"

namespace slots = ptdecl::SwitchHandler::slot;
namespace sigs = ptdecl::SwitchHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"
#include "LoopHandlerCalls.h"

zend_class_entry *pt_ce_switch_handler = nullptr;

namespace {

pt_property_site pt_swh_cond_site;
pt_property_site pt_swh_cases_site;
pt_property_site pt_swh_case_cond_site;
pt_property_site pt_swh_case_stmts_site;
pt_method_site pt_swh_get_start_line_site;

/* the `IdenticalNarrowingHelper` class name of the twin's
 * container->getByType() (module startup) */
zend_string *pt_swh_identical_narrowing_helper_class = nullptr;

/* $node->getStartLine() */
zv::Val getStartLine(zval *node)
{
	return pt_call_method_cached(pt_swh_get_start_line_site, Z_OBJ_P(node), PT_LC("getstartline"), 0, NULL);
}

/* $caseNode->name of a case list element: a node's property, or — for an
 * element a hand-built Switch_ holds that is no object — the engine's
 * warning and null (NULL = the warning turned into an exception) */
zval *caseProperty(pt_property_site &site, zval *caseNode, const char *name, size_t len)
{
	if (EXPECTED(Z_TYPE_P(caseNode) == IS_OBJECT)) return ptsh::readNodeProperty(site, caseNode, name, len);
	zend_error(E_WARNING, "Attempt to read property \"%s\" on %s", name, zend_zval_value_name(caseNode));
	if (UNEXPECTED(EG(exception))) return NULL;
	return &EG(uninitialized_zval);
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\SwitchHandler; UNDEF = pending
 * exception. */
class SwitchHandler
{
public:
	explicit SwitchHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted property */
	void construct(zval *container)
	{
		zv::ObjRef(self).propAtWrite(slots::container, zv::Val::copyOf(zv::Ref(container)));
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *stmt, bool &out) const
	{
		bool error = false;
		out = ptsh::isInstanceOf(stmt, PT_CLASS_SWITCH_STMT, error);
		return !error;
	}

	/* Mirrors processStmt(). */
	zv::Val processStmt(zval *nodeScopeResolver, zval *stmt, zval *entryScope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zv::Arr caseFlows = zv::Arr::empty();
		bool resolveTemplateArguments;
		if (UNEXPECTED(!pt_statement_context_should_resolve_template_arguments(context, resolveTemplateArguments))) return zv::Val();
		zval *subject = ptsh::readNodeProperty(pt_swh_cond_site, stmt, PT_LC("cond"));
		if (UNEXPECTED(subject == NULL)) return zv::Val();
		zv::Val subjectHold = zv::Val::copyOf(zv::Ref(subject));
		subject = subjectHold.raw();
		zv::Val condResult;
		{
			zv::Val expressionContext = pt_expression_context_create_deep(resolveTemplateArguments);
			if (UNEXPECTED(expressionContext.isUndef())) return zv::Val();
			condResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, subject, entryScope, storage, nodeCallback, expressionContext.raw());
			if (UNEXPECTED(condResult.isUndef())) return zv::Val();
		}
		zv::Val scope;
		{
			zv::Val hold;
			zval *resultScope = pt_expression_result_scope(condResult.raw(), hold);
			if (UNEXPECTED(resultScope == NULL)) return zv::Val();
			scope = zv::Val::copyOf(zv::Ref(resultScope));
		}
		zv::Val scopeForBranches = zv::Val::copyOf(scope.ref());
		zv::Val finalScope = zv::Val::null();
		zv::Val prevScope = zv::Val::null();
		bool hasDefaultCase = false;
		bool alwaysTerminating = true;
		bool hasYield;
		if (UNEXPECTED(!pt_expression_result_has_yield(condResult.raw(), hasYield))) return zv::Val();
		zv::Arr exitPointsForOuterLoop = zv::Arr::empty();
		zv::Arr throwPoints, impurePoints;
		{
			zv::Val hold;
			if (UNEXPECTED(!ptlh::arrayOf(pt_expression_result_throw_points(condResult.raw(), hold), throwPoints))) return zv::Val();
		}
		{
			zv::Val hold;
			if (UNEXPECTED(!ptlh::arrayOf(pt_expression_result_impure_points(condResult.raw(), hold), impurePoints))) return zv::Val();
		}
		zv::Val fullCondExpr = zv::Val::null();
		zv::Arr switchConditionArms = zv::Arr::empty();

		zval *cases = ptsh::readNodeProperty(pt_swh_cases_site, stmt, PT_LC("cases"));
		if (UNEXPECTED(cases == NULL)) return zv::Val();
		/* the twin's two foreach loops over something else each warn and
		 * iterate nothing (the typed property holds an array) */
		zv::Val casesValue = zv::Val::copyOf(zv::Ref(cases));
		bool casesIterable = Z_TYPE_P(casesValue.raw()) == IS_ARRAY;
		if (UNEXPECTED(!casesIterable)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(casesValue.raw()));
			if (UNEXPECTED(EG(exception))) return zv::Val();
		}
		zv::Val casesHold = casesIterable ? zv::Val::copyOf(casesValue.ref()) : zv::Val(zv::Arr::empty());
		/* the key of the last case with a condition ($lastNonDefaultCaseKey) */
		bool hasLastNonDefaultCase = false;
		zend_ulong lastNonDefaultIndex = 0;
		zend_string *lastNonDefaultKey = NULL;
		for (auto entry : zv::ArrRef(casesHold.raw())) {
			zval *caseNode = entry.value().deref().raw();
			zval *caseCond = caseProperty(pt_swh_case_cond_site, caseNode, PT_LC("cond"));
			if (UNEXPECTED(caseCond == NULL)) return zv::Val();
			if (Z_TYPE_P(caseCond) == IS_NULL) continue;
			hasLastNonDefaultCase = true;
			lastNonDefaultKey = entry.stringKeyOrNull();
			lastNonDefaultIndex = entry.indexKey();
		}

		if (UNEXPECTED(!casesIterable)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(casesValue.raw()));
			if (UNEXPECTED(EG(exception))) return zv::Val();
		}
		zv::Val branchFinalScopeResult;
		for (auto entry : zv::ArrRef(casesHold.raw())) {
			zval *caseNode = entry.value().deref().raw();
			zval *caseCondSlot = caseProperty(pt_swh_case_cond_site, caseNode, PT_LC("cond"));
			if (UNEXPECTED(caseCondSlot == NULL)) return zv::Val();
			zv::Val caseCond = zv::Val::copyOf(zv::Ref(caseCondSlot));
			zv::Val branchScope;
			if (!caseCond.isNull()) {
				zv::Args equalArgv{subject, caseCond.raw()};
				zv::Val condExpr = pt_type_new(PT_CLASS_EQUAL_EXPR, 2, equalArgv);
				if (UNEXPECTED(condExpr.isUndef())) return zv::Val();
				if (fullCondExpr.isNull()) {
					fullCondExpr = zv::Val::copyOf(condExpr.ref());
				} else {
					zv::Args orArgv{fullCondExpr.raw(), condExpr.raw()};
					zv::Val orExpr = pt_type_new(PT_CLASS_BOOLEAN_OR_EXPR, 2, orArgv);
					if (UNEXPECTED(orExpr.isUndef())) return zv::Val();
					fullCondExpr = std::move(orExpr);
				}
				zv::Val expressionContext = pt_expression_context_create_deep(resolveTemplateArguments);
				if (UNEXPECTED(expressionContext.isUndef())) return zv::Val();
				zv::Val caseResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, caseCond.raw(), scopeForBranches.raw(), storage, nodeCallback, expressionContext.raw());
				if (UNEXPECTED(caseResult.isUndef())) return zv::Val();
				zval *caseScope;
				zv::Val caseScopeHold;
				caseScope = pt_expression_result_scope(caseResult.raw(), caseScopeHold);
				if (UNEXPECTED(caseScope == NULL)) return zv::Val();
				zv::Val caseScopeValue = zv::Val::copyOf(zv::Ref(caseScope));
				scopeForBranches = zv::Val::copyOf(caseScopeValue.ref());
				if (!hasYield) {
					if (UNEXPECTED(!pt_expression_result_has_yield(caseResult.raw(), hasYield))) return zv::Val();
				}
				{
					zv::Val hold;
					zval *more = pt_expression_result_throw_points(caseResult.raw(), hold);
					if (UNEXPECTED(more == NULL || !ptlh::mergeInto(throwPoints, more))) return zv::Val();
				}
				{
					zv::Val hold;
					zval *more = pt_expression_result_impure_points(caseResult.raw(), hold);
					if (UNEXPECTED(more == NULL || !ptlh::mergeInto(impurePoints, more))) return zv::Val();
				}
				{
					zv::Val line = getStartLine(caseCond.raw());
					if (UNEXPECTED(line.isUndef())) return zv::Val();
					bool isLast = hasLastNonDefaultCase && (lastNonDefaultKey != NULL
						? entry.stringKeyOrNull() != NULL && zend_string_equals(entry.stringKeyOrNull(), lastNonDefaultKey)
						: entry.stringKeyOrNull() == NULL && entry.indexKey() == lastNonDefaultIndex);
					zv::Args armArgv{caseCond.raw(), scopeForBranches.raw(), line.raw(), isLast};
					zv::Val arm = pt_type_new(PT_CLASS_SWITCH_CONDITION_ARM, 4, armArgv);
					if (UNEXPECTED(arm.isUndef())) return zv::Val();
					switchConditionArms.push(std::move(arm));
				}
				// the == narrowing composed from the subject's and the case's
				// results (what the walked synthetic delegates to); the walk is
				// the composition's miss seam
				zval *container = OBJ_PROP_NUM(self, slots::container);
				zv::Val helper = ptlh::containerGetByType(container, pt_swh_identical_narrowing_helper_class);
				if (UNEXPECTED(helper.isUndef())) return zv::Val();
				if (UNEXPECTED(Z_TYPE_P(helper.raw()) != IS_OBJECT)) {
					zend_throw_error(NULL, "Call to a member function specifyEqual() on %s", zend_zval_value_name(helper.raw()));
					return zv::Val();
				}
				zend_object *truthy = pt_type_specifier_context_create_truthy();
				if (UNEXPECTED(truthy == NULL)) return zv::Val();
				zval truthyContext;
				ZVAL_OBJ(&truthyContext, truthy);
				zv::Val caseEqualTypes = pt_identical_narrowing_helper_specify_equal(helper.raw(), nodeScopeResolver, subject, caseCond.raw(), condResult.raw(), caseResult.raw(), &truthyContext, caseScopeValue.raw(), NULL, NULL);
				if (UNEXPECTED(caseEqualTypes.isUndef())) return zv::Val();
				if (!caseEqualTypes.isNull()) {
					zv::Val rooted = pt_specified_types_set_root_expr(Z_OBJ_P(caseEqualTypes.raw()), condExpr.raw());
					if (UNEXPECTED(rooted.isUndef())) return zv::Val();
					branchScope = pt_mutating_scope_apply_specified_types(Z_OBJ_P(caseScopeValue.raw()), rooted.raw());
				} else {
					branchScope = pt_node_scope_resolver_narrow_scope_with_condition(nodeScopeResolver, caseScopeValue.raw(), condExpr.raw(), &truthyContext);
				}
				if (UNEXPECTED(branchScope.isUndef())) return zv::Val();
			} else {
				hasDefaultCase = true;
				fullCondExpr = zv::Val::null();
				branchScope = zv::Val::copyOf(scopeForBranches.ref());
			}

			branchScope = pt_mutating_scope_merge_with(Z_OBJ_P(branchScope.raw()), prevScope.raw());
			if (UNEXPECTED(branchScope.isUndef())) return zv::Val();
			zval *caseStmts = caseProperty(pt_swh_case_stmts_site, caseNode, PT_LC("stmts"));
			if (UNEXPECTED(caseStmts == NULL)) return zv::Val();
			zv::Val caseStmtsHold = zv::Val::copyOf(zv::Ref(caseStmts));
			/* the twin's processStmtNodesInternal() parameter types, for a
			 * hand-built case list's foreign element */
			{
				bool error = false;
				if (UNEXPECTED(!ptsh::isInstanceOf(caseNode, PT_CLASS_NODE, error))) {
					if (!error) {
						zend_type_error("PHPStan\\Analyser\\NodeScopeResolver::processStmtNodesInternal(): Argument #1 ($parentNode) must be of type PhpParser\\Node, %s given", zend_zval_value_name(caseNode));
					}
					return zv::Val();
				}
				if (UNEXPECTED(Z_TYPE_P(caseStmtsHold.raw()) != IS_ARRAY)) {
					zend_type_error("PHPStan\\Analyser\\NodeScopeResolver::processStmtNodesInternal(): Argument #2 ($stmts) must be of type array, %s given", zend_zval_value_name(caseStmtsHold.raw()));
					return zv::Val();
				}
			}
			zv::Val branchScopeResult = pt_node_scope_resolver_process_stmt_nodes_internal(nodeScopeResolver, caseNode, caseStmtsHold.raw(), branchScope.raw(), storage, nodeCallback, context);
			if (UNEXPECTED(branchScopeResult.isUndef())) return zv::Val();
			{
				zval *caseCondNow = ptsh::readNodeProperty(pt_swh_case_cond_site, caseNode, PT_LC("cond"));
				if (UNEXPECTED(caseCondNow == NULL)) return zv::Val();
				zv::Val caseCondFlow = pt_variable_flow_builder_child(caseCondNow, storage);
				if (UNEXPECTED(caseCondFlow.isUndef())) return zv::Val();
				zv::Val branchFlowHold;
				zval *branchFlow = pt_internal_statement_result_variable_flow(branchScopeResult.raw(), branchFlowHold);
				if (UNEXPECTED(branchFlow == NULL)) return zv::Val();
				caseCondNow = ptsh::readNodeProperty(pt_swh_case_cond_site, caseNode, PT_LC("cond"));
				if (UNEXPECTED(caseCondNow == NULL)) return zv::Val();
				zv::Arr caseFlow = zv::Arr::create(3);
				caseFlow.push(std::move(caseCondFlow));
				caseFlow.push(zv::Ref(branchFlow));
				caseFlow.push(zv::Val::boolean(Z_TYPE_P(caseCondNow) == IS_NULL));
				caseFlows.push(zv::Val(std::move(caseFlow)));
			}
			{
				zv::Val hold;
				zval *resultScope = pt_internal_statement_result_scope(branchScopeResult.raw(), hold);
				if (UNEXPECTED(resultScope == NULL)) return zv::Val();
				branchScope = zv::Val::copyOf(zv::Ref(resultScope));
			}
			branchFinalScopeResult = pt_internal_statement_result_filter_out_loop_exit_points(branchScopeResult.raw());
			if (UNEXPECTED(branchFinalScopeResult.isUndef())) return zv::Val();
			if (!hasYield) {
				if (UNEXPECTED(!pt_internal_statement_result_has_yield(branchFinalScopeResult.raw(), hasYield))) return zv::Val();
			}
			{
				zv::Val breaks = ptlh::breakExitPoints(branchScopeResult.raw());
				if (UNEXPECTED(breaks.isUndef())) return zv::Val();
				for (auto breakEntry : zv::ArrRef(breaks.raw())) {
					alwaysTerminating = false;
					zv::Val hold;
					zval *breakScope = pt_internal_statement_exit_point_scope(breakEntry.value().deref().raw(), hold);
					if (UNEXPECTED(breakScope == NULL)) return zv::Val();
					if (UNEXPECTED(!ptlh::otherMergeWith(breakScope, finalScope))) return zv::Val();
				}
			}
			{
				zv::Val continues = ptlh::continueExitPoints(branchScopeResult.raw());
				if (UNEXPECTED(continues.isUndef())) return zv::Val();
				for (auto continueEntry : zv::ArrRef(continues.raw())) {
					zv::Val hold;
					zval *continueScope = pt_internal_statement_exit_point_scope(continueEntry.value().deref().raw(), hold);
					if (UNEXPECTED(continueScope == NULL)) return zv::Val();
					if (UNEXPECTED(!ptlh::otherMergeWith(continueScope, finalScope))) return zv::Val();
				}
			}
			{
				zv::Val outer = pt_internal_statement_result_exit_points_for_outer_loop(branchFinalScopeResult.raw());
				if (UNEXPECTED(outer.isUndef() || !ptlh::mergeInto(exitPointsForOuterLoop, outer.raw()))) return zv::Val();
			}
			{
				zv::Val hold;
				zval *more = pt_internal_statement_result_throw_points(branchFinalScopeResult.raw(), hold);
				if (UNEXPECTED(more == NULL || !ptlh::mergeInto(throwPoints, more))) return zv::Val();
			}
			{
				zv::Val hold;
				zval *more = pt_internal_statement_result_impure_points(branchFinalScopeResult.raw(), hold);
				if (UNEXPECTED(more == NULL || !ptlh::mergeInto(impurePoints, more))) return zv::Val();
			}
			bool branchAlwaysTerminating;
			if (UNEXPECTED(!pt_internal_statement_result_is_always_terminating(branchScopeResult.raw(), branchAlwaysTerminating))) return zv::Val();
			if (branchAlwaysTerminating) {
				bool finalAlwaysTerminating;
				if (UNEXPECTED(!pt_internal_statement_result_is_always_terminating(branchFinalScopeResult.raw(), finalAlwaysTerminating))) return zv::Val();
				alwaysTerminating = alwaysTerminating && finalAlwaysTerminating;
				prevScope = zv::Val::null();
				if (!fullCondExpr.isNull()) {
					zend_object *falsey = pt_type_specifier_context_create_falsey();
					if (UNEXPECTED(falsey == NULL)) return zv::Val();
					zval falseyContext;
					ZVAL_OBJ(&falseyContext, falsey);
					scopeForBranches = pt_node_scope_resolver_narrow_scope_with_condition(nodeScopeResolver, scopeForBranches.raw(), fullCondExpr.raw(), &falseyContext);
					if (UNEXPECTED(scopeForBranches.isUndef())) return zv::Val();
					fullCondExpr = zv::Val::null();
				}
				if (!finalAlwaysTerminating) {
					zv::Val merged = pt_mutating_scope_merge_with(Z_OBJ_P(branchScope.raw()), finalScope.raw());
					if (UNEXPECTED(merged.isUndef())) return zv::Val();
					finalScope = std::move(merged);
				}
			} else {
				prevScope = std::move(branchScope);
			}
		}

		// the Switch_ callback is deferred from processStmtNode(): it fires
		// after every case condition's walk stored its result, with the entry
		// scope, so rules pricing the case conditions answer from the storage
		if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, stmt, entryScope, storage))) return zv::Val();

		if (zend_hash_num_elements(switchConditionArms.table()) > 0) {
			zval *subjectNow = ptsh::readNodeProperty(pt_swh_cond_site, stmt, PT_LC("cond"));
			if (UNEXPECTED(subjectNow == NULL)) return zv::Val();
			zv::Args nodeArgv{subjectNow, switchConditionArms.raw(), stmt};
			zv::Val conditionNode = pt_type_new(PT_CLASS_SWITCH_CONDITION_NODE, 3, nodeArgv);
			if (UNEXPECTED(conditionNode.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, conditionNode.raw(), scope.raw(), storage))) return zv::Val();
		}

		// $scopeForBranches is the subject narrowed by "none of the cases
		// matched". The narrowing is tracked by the scope (getTypeOnScope's
		// authoritative read); only an untracked subject needs reprocessing there.
		zv::Val remainingCaseType;
		{
			bool answers;
			if (UNEXPECTED(!pt_expression_result_answers_on_scope(condResult.raw(), scopeForBranches.raw(), false, answers))) return zv::Val();
			if (answers) {
				remainingCaseType = pt_expression_result_get_type_on_scope(condResult.raw(), scopeForBranches.raw(), false);
			} else {
				zval *subjectNow = ptsh::readNodeProperty(pt_swh_cond_site, stmt, PT_LC("cond"));
				if (UNEXPECTED(subjectNow == NULL)) return zv::Val();
				zv::Val subjectNowHold = zv::Val::copyOf(zv::Ref(subjectNow));
				zv::Val freshStorage = pt_expression_result_storage_new();
				if (UNEXPECTED(freshStorage.isUndef())) return zv::Val();
				zv::Val onDemand = pt_node_scope_resolver_process_expr_on_demand(nodeScopeResolver, subjectNowHold.raw(), scopeForBranches.raw(), freshStorage.raw());
				if (UNEXPECTED(onDemand.isUndef())) return zv::Val();
				remainingCaseType = pt_expression_result_get_type(onDemand.raw());
			}
			if (UNEXPECTED(remainingCaseType.isUndef())) return zv::Val();
		}
		bool exhaustive = Z_TYPE_P(remainingCaseType.raw()) == IS_OBJECT && instanceof_function(Z_OBJCE_P(remainingCaseType.raw()), pt_ce_never_type);

		if (!hasDefaultCase && !exhaustive) {
			alwaysTerminating = false;
		}

		if (!prevScope.isNull()) {
			zv::Val merged = pt_mutating_scope_merge_with(Z_OBJ_P(prevScope.raw()), finalScope.raw());
			if (UNEXPECTED(merged.isUndef())) return zv::Val();
			finalScope = std::move(merged);
			if (alwaysTerminating) {
				if (UNEXPECTED(!pt_internal_statement_result_is_always_terminating(branchFinalScopeResult.raw(), alwaysTerminating))) return zv::Val();
			}
		}

		if ((!hasDefaultCase && !exhaustive) || finalScope.isNull()) {
			zv::Val merged = pt_mutating_scope_merge_with(Z_OBJ_P(scopeForBranches.raw()), finalScope.raw());
			if (UNEXPECTED(merged.isUndef())) return zv::Val();
			finalScope = std::move(merged);
		}

		zv::Val condFlow = pt_expression_result_variable_flow(condResult.raw());
		if (UNEXPECTED(condFlow.isUndef())) return zv::Val();
		zv::Val variableFlow = pt_variable_flow_switch(condFlow.raw(), caseFlows.raw(), hasDefaultCase || exhaustive);
		if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		return pt_internal_statement_result_new(finalScope.raw(), hasYield, alwaysTerminating, exitPointsForOuterLoop.raw(), throwPoints.raw(), impurePoints.raw(), NULL, variableFlow.raw());
	}

	/* the statement-handler entry (Engine.h) */
	static zv::Val processStmtEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return SwitchHandler(handler).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::SwitchHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_switch_handler()
{
	pt_swh_identical_narrowing_helper_class = zend_string_init_interned(PT_LC("PHPStan\\Analyser\\ExprHandler\\Helper\\IdenticalNarrowingHelper"), 1);

	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\SwitchHandler");
	ptdecl::SwitchHandler::declareClass(cls);
	ptdecl::SwitchHandler::declareProperties(cls);

	cls.method<&SwitchHandler::supports, zp::Obj>(sigs::supports);

	/* the real parameter class name: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *container;
		if (!zp::parse<zp::Obj>(execute_data, container)) RETURN_THROWS();
		SwitchHandler(Z_OBJ_P(ZEND_THIS)).construct(container);
	});

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
		PT_RETURN_VAL(SwitchHandler(Z_OBJ_P(ZEND_THIS)).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_switch_handler);
	pt_stmt_handler_entry_register(&pt_ce_switch_handler, &SwitchHandler::processStmtEntry);
}

/* }}} */
