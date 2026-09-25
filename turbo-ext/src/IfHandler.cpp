/*
 * PHPStanTurbo\IfHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\IfHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo (the #[AutowiredParameter] bool pairs by name) so Nette autowires
 * it. processStmt() is registered as the class's statement-handler entry
 * (Engine.h).
 *
 * ExpressionResult, MutatingScope, the contexts, VariableFlow, the
 * statement results and NodeScopeResolver are called through their direct
 * entries; the Type queries (toBoolean(), isTrue(), isFalse()) through the
 * native type dispatch. The twin's $flowBranches list of pairs is kept as
 * one flat list (condition flow, branch flow per branch).
 */

#include "support.h"
#include "generated/IfHandler.h"

namespace slots = ptdecl::IfHandler::slot;
namespace sigs = ptdecl::IfHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"

zend_class_entry *pt_ce_if_handler = nullptr;

namespace {

pt_property_site pt_ih_cond_site;
pt_property_site pt_ih_stmts_site;
pt_property_site pt_ih_elseifs_site;
pt_property_site pt_ih_else_site;
pt_property_site pt_ih_elseif_cond_site;
pt_property_site pt_ih_elseif_stmts_site;
pt_property_site pt_ih_else_stmts_site;

/* $array = array_merge($array, $more); false = pending exception */
[[nodiscard]] bool mergeInto(zv::Arr &into, zval *more)
{
	return pt_callable_array_merge_into(into, more);
}

/* the boolean projection of a condition's type and its isTrue() / isFalse()
 * answers (isFalse() asked only when isTrue() is not yes, like the twin's
 * ternary) */
struct ConditionType
{
	zend_long isTrue;
	zend_long isFalse;
};

/* ($treatPhpDocTypesAsCertain ? $result->getType() : $result->getNativeType())->toBoolean()
 * and its isTrue() / isFalse(); false = pending exception */
[[nodiscard]] bool conditionTypeOf(zval *result, bool treatPhpDocTypesAsCertain, ConditionType &out)
{
	zv::Val type = treatPhpDocTypesAsCertain ? pt_expression_result_get_type(result) : pt_expression_result_get_native_type(result);
	if (UNEXPECTED(type.isUndef())) return false;
	if (UNEXPECTED(Z_TYPE_P(type.raw()) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function toBoolean() on %s", zend_zval_value_name(type.raw()));
		return false;
	}
	zv::Val boolean = pt_type_call(Z_OBJ_P(type.raw()), PT_LC("toboolean"), 0, NULL);
	if (UNEXPECTED(boolean.isUndef())) return false;
	if (UNEXPECTED(Z_TYPE_P(boolean.raw()) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function isTrue() on %s", zend_zval_value_name(boolean.raw()));
		return false;
	}
	out.isTrue = pt_type_call_trinary(Z_OBJ_P(boolean.raw()), PT_LC("istrue"), 0, NULL);
	if (UNEXPECTED(out.isTrue < 0)) return false;
	out.isFalse = PT_TRI_NO;
	if (out.isTrue != PT_TRI_YES) {
		out.isFalse = pt_type_call_trinary(Z_OBJ_P(boolean.raw()), PT_LC("isfalse"), 0, NULL);
		if (UNEXPECTED(out.isFalse < 0)) return false;
	}
	return true;
}

/* $flowBranches[] = [$conditionFlow, $branchFlow] */
void pushFlowBranch(zv::Arr &flowBranches, zv::Val conditionFlow, zval *branchFlow)
{
	flowBranches.push(std::move(conditionFlow));
	flowBranches.push(zv::Ref(branchFlow));
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\IfHandler; UNDEF = pending
 * exception. */
class IfHandler
{
public:
	explicit IfHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted property */
	void construct(bool treatPhpDocTypesAsCertain)
	{
		zv::ObjRef(self).propAtWrite(slots::treatPhpDocTypesAsCertain, zv::Val::boolean(treatPhpDocTypesAsCertain));
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *stmt, bool &out) const
	{
		bool error = false;
		out = ptsh::isInstanceOf(stmt, PT_CLASS_IF_STMT, error);
		return !error;
	}

	/* Mirrors processStmt(). */
	zv::Val processStmt(zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		bool treatPhpDocTypesAsCertain = Z_TYPE_P(OBJ_PROP_NUM(self, slots::treatPhpDocTypesAsCertain)) == IS_TRUE;
		zval *entryScope = scope;
		zv::Arr flowBranches = zv::Arr::empty();
		zv::Val elseFlow = zv::Val::null();

		zval *cond = ptsh::readNodeProperty(pt_ih_cond_site, stmt, PT_LC("cond"));
		if (UNEXPECTED(cond == NULL)) return zv::Val();
		zv::Val condResult = processCondition(nodeScopeResolver, stmt, cond, scope, storage, nodeCallback, context);
		if (UNEXPECTED(condResult.isUndef())) return zv::Val();
		if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, stmt, entryScope, storage))) return zv::Val();
		ConditionType conditionType;
		if (UNEXPECTED(!conditionTypeOf(condResult.raw(), treatPhpDocTypesAsCertain, conditionType))) return zv::Val();
		bool ifAlwaysTrue = conditionType.isTrue == PT_TRI_YES;
		zv::Arr exitPoints = zv::Arr::empty();
		zv::Arr throwPoints = zv::Arr::empty();
		zv::Arr impurePoints = zv::Arr::empty();
		{
			zv::Val hold;
			zval *condThrowPoints = pt_expression_result_throw_points(condResult.raw(), hold);
			if (UNEXPECTED(condThrowPoints == NULL)) return zv::Val();
			throwPoints = zv::Arr::adoptVal(zv::Val::copyOf(zv::Ref(condThrowPoints)));
		}
		{
			zv::Val hold;
			zval *condImpurePoints = pt_expression_result_impure_points(condResult.raw(), hold);
			if (UNEXPECTED(condImpurePoints == NULL)) return zv::Val();
			impurePoints = zv::Arr::adoptVal(zv::Val::copyOf(zv::Ref(condImpurePoints)));
		}
		zv::Arr endStatements = zv::Arr::empty();
		zv::Val finalScope = zv::Val::null();
		bool alwaysTerminating = true;
		bool hasYield;
		if (UNEXPECTED(!pt_expression_result_has_yield(condResult.raw(), hasYield))) return zv::Val();

		{
			zval *stmts = ptsh::readNodeProperty(pt_ih_stmts_site, stmt, PT_LC("stmts"));
			if (UNEXPECTED(stmts == NULL)) return zv::Val();
			zv::Val truthyScope = pt_expression_result_get_truthy_scope(condResult.raw());
			if (UNEXPECTED(truthyScope.isUndef())) return zv::Val();
			zv::Val branchResult = pt_node_scope_resolver_process_stmt_nodes_internal(nodeScopeResolver, stmt, stmts, truthyScope.raw(), storage, nodeCallback, context);
			if (UNEXPECTED(branchResult.isUndef())) return zv::Val();
			zv::Val conditionFlow = pt_expression_result_variable_flow(condResult.raw());
			if (UNEXPECTED(conditionFlow.isUndef())) return zv::Val();
			zv::Val branchFlowHold;
			zval *branchFlow = pt_internal_statement_result_variable_flow(branchResult.raw(), branchFlowHold);
			if (UNEXPECTED(branchFlow == NULL)) return zv::Val();
			pushFlowBranch(flowBranches, std::move(conditionFlow), branchFlow);
			if (conditionType.isTrue != PT_TRI_NO) {
				zv::Val exitPointsHold;
				zval *branchExitPoints = pt_internal_statement_result_exit_points(branchResult.raw(), exitPointsHold);
				if (UNEXPECTED(branchExitPoints == NULL)) return zv::Val();
				exitPoints = zv::Arr::adoptVal(zv::Val::copyOf(zv::Ref(branchExitPoints)));
				if (UNEXPECTED(!mergeBranchPoints(branchResult.raw(), throwPoints, impurePoints))) return zv::Val();
				zv::Val branchScopeHold;
				zval *branchScope = pt_internal_statement_result_scope(branchResult.raw(), branchScopeHold);
				if (UNEXPECTED(branchScope == NULL)) return zv::Val();
				bool branchAlwaysTerminating;
				if (UNEXPECTED(!pt_internal_statement_result_is_always_terminating(branchResult.raw(), branchAlwaysTerminating))) return zv::Val();
				finalScope = branchAlwaysTerminating ? zv::Val::null() : zv::Val::copyOf(zv::Ref(branchScope));
				if (UNEXPECTED(!pt_internal_statement_result_is_always_terminating(branchResult.raw(), alwaysTerminating))) return zv::Val();
				if (UNEXPECTED(!addEndStatements(endStatements, branchResult.raw(), stmt, pt_ih_stmts_site))) return zv::Val();
				bool branchHasYield;
				if (UNEXPECTED(!pt_internal_statement_result_has_yield(branchResult.raw(), branchHasYield))) return zv::Val();
				hasYield = branchHasYield || hasYield;
			}
		}

		zv::Val scopeValue = pt_expression_result_get_falsey_scope(condResult.raw());
		if (UNEXPECTED(scopeValue.isUndef())) return zv::Val();
		bool lastElseIfConditionIsTrue = false;

		zv::Val condScope = zv::Val::copyOf(zv::Ref(scopeValue.raw()));
		zval *elseifs = ptsh::readNodeProperty(pt_ih_elseifs_site, stmt, PT_LC("elseifs"));
		if (UNEXPECTED(elseifs == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(elseifs) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(elseifs));
			if (UNEXPECTED(EG(exception))) return zv::Val();
		} else {
			zv::Arr iterated = zv::Arr::copyOfTable(Z_ARRVAL_P(elseifs));
			for (auto entry : zv::TableRef(iterated.table())) {
				zval *elseif = entry.value().deref().raw();
				if (UNEXPECTED(Z_TYPE_P(elseif) != IS_OBJECT)) {
					/* the twin reads null with a warning and hands it on */
					zend_error(E_WARNING, "Attempt to read property \"cond\" on %s", zend_zval_value_name(elseif));
					if (!EG(exception)) {
						zend_type_error("PHPStan\\Analyser\\NodeScopeResolver::processExprNode(): Argument #2 ($expr) must be of type PhpParser\\Node\\Expr, null given");
					}
					return zv::Val();
				}
				zval *elseifCond = ptsh::readNodeProperty(pt_ih_elseif_cond_site, elseif, PT_LC("cond"));
				if (UNEXPECTED(elseifCond == NULL)) return zv::Val();
				zv::Val elseifResult = processCondition(nodeScopeResolver, stmt, elseifCond, condScope.raw(), storage, nodeCallback, context);
				if (UNEXPECTED(elseifResult.isUndef())) return zv::Val();
				if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, elseif, scopeValue.raw(), storage))) return zv::Val();
				ConditionType elseIfConditionType;
				if (UNEXPECTED(!conditionTypeOf(elseifResult.raw(), treatPhpDocTypesAsCertain, elseIfConditionType))) return zv::Val();
				{
					zv::Val hold;
					zval *condThrowPoints = pt_expression_result_throw_points(elseifResult.raw(), hold);
					if (UNEXPECTED(condThrowPoints == NULL || !mergeInto(throwPoints, condThrowPoints))) return zv::Val();
				}
				{
					zv::Val hold;
					zval *condImpurePoints = pt_expression_result_impure_points(elseifResult.raw(), hold);
					if (UNEXPECTED(condImpurePoints == NULL || !mergeInto(impurePoints, condImpurePoints))) return zv::Val();
				}
				zval *elseifStmts = ptsh::readNodeProperty(pt_ih_elseif_stmts_site, elseif, PT_LC("stmts"));
				if (UNEXPECTED(elseifStmts == NULL)) return zv::Val();
				zv::Val truthyScope = pt_expression_result_get_truthy_scope(elseifResult.raw());
				if (UNEXPECTED(truthyScope.isUndef())) return zv::Val();
				zv::Val branchResult = pt_node_scope_resolver_process_stmt_nodes_internal(nodeScopeResolver, elseif, elseifStmts, truthyScope.raw(), storage, nodeCallback, context);
				if (UNEXPECTED(branchResult.isUndef())) return zv::Val();
				zv::Val conditionFlow = pt_expression_result_variable_flow(elseifResult.raw());
				if (UNEXPECTED(conditionFlow.isUndef())) return zv::Val();
				zv::Val branchFlowHold;
				zval *branchFlow = pt_internal_statement_result_variable_flow(branchResult.raw(), branchFlowHold);
				if (UNEXPECTED(branchFlow == NULL)) return zv::Val();
				pushFlowBranch(flowBranches, std::move(conditionFlow), branchFlow);
				if (!ifAlwaysTrue && !lastElseIfConditionIsTrue && elseIfConditionType.isTrue != PT_TRI_NO) {
					if (UNEXPECTED(!mergeBranch(branchResult.raw(), exitPoints, throwPoints, impurePoints, finalScope, alwaysTerminating, endStatements, elseif, pt_ih_elseif_stmts_site, hasYield))) return zv::Val();
				}

				if (elseIfConditionType.isTrue == PT_TRI_YES) {
					lastElseIfConditionIsTrue = true;
				}

				condScope = pt_expression_result_get_falsey_scope(elseifResult.raw());
				if (UNEXPECTED(condScope.isUndef())) return zv::Val();
				scopeValue = zv::Val::copyOf(zv::Ref(condScope.raw()));
			}
		}

		zval *elseNode = ptsh::readNodeProperty(pt_ih_else_site, stmt, PT_LC("else"));
		if (UNEXPECTED(elseNode == NULL)) return zv::Val();
		if (Z_TYPE_P(elseNode) == IS_NULL) {
			if (!ifAlwaysTrue && !lastElseIfConditionIsTrue) {
				finalScope = pt_mutating_scope_merge_with(Z_OBJ_P(scopeValue.raw()), finalScope.raw(), true);
				if (UNEXPECTED(finalScope.isUndef())) return zv::Val();
				alwaysTerminating = false;
			}
		} else {
			zv::Val elseHold = zv::Val::copyOf(zv::Ref(elseNode));
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, elseHold.raw(), scopeValue.raw(), storage))) return zv::Val();
			elseNode = ptsh::readNodeProperty(pt_ih_else_site, stmt, PT_LC("else"));
			if (UNEXPECTED(elseNode == NULL)) return zv::Val();
			elseHold = zv::Val::copyOf(zv::Ref(elseNode));
			if (UNEXPECTED(Z_TYPE_P(elseHold.raw()) != IS_OBJECT)) {
				/* the twin reads null stmts with a warning and hands the
				 * non-node on as the parent */
				zend_error(E_WARNING, "Attempt to read property \"stmts\" on %s", zend_zval_value_name(elseHold.raw()));
				if (!EG(exception)) {
					zend_type_error("PHPStan\\Analyser\\NodeScopeResolver::processStmtNodesInternal(): Argument #1 ($parentNode) must be of type PhpParser\\Node, %s given", zend_zval_value_name(elseHold.raw()));
				}
				return zv::Val();
			}
			zval *elseStmts = ptsh::readNodeProperty(pt_ih_else_stmts_site, elseHold.raw(), PT_LC("stmts"));
			if (UNEXPECTED(elseStmts == NULL)) return zv::Val();
			zv::Val branchResult = pt_node_scope_resolver_process_stmt_nodes_internal(nodeScopeResolver, elseHold.raw(), elseStmts, scopeValue.raw(), storage, nodeCallback, context);
			if (UNEXPECTED(branchResult.isUndef())) return zv::Val();
			{
				zv::Val elseFlowHold;
				zval *branchFlow = pt_internal_statement_result_variable_flow(branchResult.raw(), elseFlowHold);
				if (UNEXPECTED(branchFlow == NULL)) return zv::Val();
				elseFlow = zv::Val::copyOf(zv::Ref(branchFlow));
			}
			if (!ifAlwaysTrue && !lastElseIfConditionIsTrue) {
				elseNode = ptsh::readNodeProperty(pt_ih_else_site, stmt, PT_LC("else"));
				if (UNEXPECTED(elseNode == NULL)) return zv::Val();
				elseHold = zv::Val::copyOf(zv::Ref(elseNode));
				if (UNEXPECTED(!mergeBranch(branchResult.raw(), exitPoints, throwPoints, impurePoints, finalScope, alwaysTerminating, endStatements, elseHold.raw(), pt_ih_else_stmts_site, hasYield))) return zv::Val();
			}
		}

		if (finalScope.isNull()) {
			finalScope = zv::Val::copyOf(zv::Ref(scopeValue.raw()));
		}

		elseNode = ptsh::readNodeProperty(pt_ih_else_site, stmt, PT_LC("else"));
		if (UNEXPECTED(elseNode == NULL)) return zv::Val();
		if (Z_TYPE_P(elseNode) == IS_NULL && !ifAlwaysTrue && !lastElseIfConditionIsTrue) {
			zv::Val implicitElseResult = pt_internal_statement_result_new(finalScope.raw(), hasYield, alwaysTerminating, exitPoints.raw(), throwPoints.raw(), impurePoints.raw());
			if (UNEXPECTED(implicitElseResult.isUndef())) return zv::Val();
			zv::Val endStatement = pt_internal_end_statement_result_new(stmt, implicitElseResult.raw());
			if (UNEXPECTED(endStatement.isUndef())) return zv::Val();
			endStatements.push(std::move(endStatement));
		}

		// every branch may run in the variable flow, even one the condition's
		// type rules out: a usage in it counts
		/* foreach (array_reverse($flowBranches) as [$conditionFlow, $branchFlow]) */
		HashTable *branches = flowBranches.table();
		uint32_t branchCount = zend_hash_num_elements(branches) / 2;
		for (uint32_t i = branchCount; i-- > 0;) {
			zval *conditionFlow = zend_hash_index_find(branches, (zend_ulong) i * 2);
			zval *branchFlow = zend_hash_index_find(branches, (zend_ulong) i * 2 + 1);
			zv::Val conditional = pt_variable_flow_conditional(conditionFlow, branchFlow, elseFlow.raw());
			if (UNEXPECTED(conditional.isUndef())) return zv::Val();
			elseFlow = std::move(conditional);
		}
		return pt_internal_statement_result_new(finalScope.raw(), hasYield, alwaysTerminating, exitPoints.raw(), throwPoints.raw(), impurePoints.raw(), endStatements.raw(), elseFlow.raw());
	}

	/* the statement-handler entry (Engine.h) */
	static zv::Val processStmtEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return IfHandler(handler).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* $nodeScopeResolver->processExprNode($stmt, $cond, $scope, $storage,
	 * $nodeCallback, ExpressionContext::createDeep($context->shouldResolveTemplateArguments())) */
	static zv::Val processCondition(zval *nodeScopeResolver, zval *stmt, zval *cond, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		zv::Val condHold = zv::Val::copyOf(zv::Ref(cond));
		bool resolveTemplateArguments;
		if (UNEXPECTED(!pt_statement_context_should_resolve_template_arguments(context, resolveTemplateArguments))) return zv::Val();
		zv::Val expressionContext = pt_expression_context_create_deep(resolveTemplateArguments);
		if (UNEXPECTED(expressionContext.isUndef())) return zv::Val();
		return pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, condHold.raw(), scope, storage, nodeCallback, expressionContext.raw());
	}

	/* $throwPoints = array_merge($throwPoints, $branch->getThrowPoints());
	 * $impurePoints = array_merge($impurePoints, $branch->getImpurePoints());
	 * false = pending exception */
	[[nodiscard]] static bool mergeBranchPoints(zval *branchResult, zv::Arr &throwPoints, zv::Arr &impurePoints)
	{
		{
			zv::Val hold;
			zval *branchThrowPoints = pt_internal_statement_result_throw_points(branchResult, hold);
			if (UNEXPECTED(branchThrowPoints == NULL || !mergeInto(throwPoints, branchThrowPoints))) return false;
		}
		zv::Val hold;
		zval *branchImpurePoints = pt_internal_statement_result_impure_points(branchResult, hold);
		return branchImpurePoints != NULL && mergeInto(impurePoints, branchImpurePoints);
	}

	/* the end statements of a branch: its own, else an InternalEndStatementResult
	 * over the branch node's last statement, else over the branch node itself;
	 * false = pending exception */
	[[nodiscard]] static bool addEndStatements(zv::Arr &endStatements, zval *branchResult, zval *branchNode, pt_property_site &stmtsSite)
	{
		zv::Val branchEndStatementsHold;
		zval *branchEndStatements = pt_internal_statement_result_end_statements(branchResult, branchEndStatementsHold);
		if (UNEXPECTED(branchEndStatements == NULL)) return false;
		if (UNEXPECTED(Z_TYPE_P(branchEndStatements) != IS_ARRAY)) {
			zend_type_error("count(): Argument #1 ($value) must be of type Countable|array, %s given", zend_zval_value_name(branchEndStatements));
			return false;
		}
		if (zend_hash_num_elements(Z_ARRVAL_P(branchEndStatements)) > 0) {
			zv::Val againHold;
			zval *again = pt_internal_statement_result_end_statements(branchResult, againHold);
			if (UNEXPECTED(again == NULL)) return false;
			return mergeInto(endStatements, again);
		}
		zval *stmts = ptsh::readNodeProperty(stmtsSite, branchNode, PT_LC("stmts"));
		if (UNEXPECTED(stmts == NULL)) return false;
		if (UNEXPECTED(Z_TYPE_P(stmts) != IS_ARRAY)) {
			zend_type_error("count(): Argument #1 ($value) must be of type Countable|array, %s given", zend_zval_value_name(stmts));
			return false;
		}
		uint32_t count = zend_hash_num_elements(Z_ARRVAL_P(stmts));
		zv::Val endStatement;
		if (count > 0) {
			zval *last = zend_hash_index_find(Z_ARRVAL_P(stmts), (zend_ulong) (count - 1));
			if (UNEXPECTED(last == NULL)) {
				zend_error(E_WARNING, "Undefined array key " ZEND_LONG_FMT, (zend_long) count - 1);
				if (UNEXPECTED(EG(exception))) return false;
				last = &EG(uninitialized_zval);
			} else {
				ZVAL_DEREF(last);
			}
			zv::Val lastHold = zv::Val::copyOf(zv::Ref(last));
			endStatement = pt_internal_end_statement_result_new(lastHold.raw(), branchResult);
		} else {
			endStatement = pt_internal_end_statement_result_new(branchNode, branchResult);
		}
		if (UNEXPECTED(endStatement.isUndef())) return false;
		endStatements.push(std::move(endStatement));
		return true;
	}

	/* an elseif / else branch taking part in the result: its exit, throw
	 * and impure points merged, its scope merged into the final scope, its
	 * end statements added; false = pending exception */
	[[nodiscard]] static bool mergeBranch(zval *branchResult, zv::Arr &exitPoints, zv::Arr &throwPoints, zv::Arr &impurePoints, zv::Val &finalScope, bool &alwaysTerminating, zv::Arr &endStatements, zval *branchNode, pt_property_site &stmtsSite, bool &hasYield)
	{
		{
			zv::Val hold;
			zval *branchExitPoints = pt_internal_statement_result_exit_points(branchResult, hold);
			if (UNEXPECTED(branchExitPoints == NULL || !mergeInto(exitPoints, branchExitPoints))) return false;
		}
		if (UNEXPECTED(!mergeBranchPoints(branchResult, throwPoints, impurePoints))) return false;
		zv::Val branchScopeHold;
		zval *branchScope = pt_internal_statement_result_scope(branchResult, branchScopeHold);
		if (UNEXPECTED(branchScope == NULL)) return false;
		bool branchAlwaysTerminating;
		if (UNEXPECTED(!pt_internal_statement_result_is_always_terminating(branchResult, branchAlwaysTerminating))) return false;
		if (!branchAlwaysTerminating) {
			zv::Val merged = pt_mutating_scope_merge_with(Z_OBJ_P(branchScope), finalScope.raw(), true);
			if (UNEXPECTED(merged.isUndef())) return false;
			finalScope = std::move(merged);
		}
		if (alwaysTerminating) {
			if (UNEXPECTED(!pt_internal_statement_result_is_always_terminating(branchResult, alwaysTerminating))) return false;
		}
		if (UNEXPECTED(!addEndStatements(endStatements, branchResult, branchNode, stmtsSite))) return false;
		if (!hasYield) {
			if (UNEXPECTED(!pt_internal_statement_result_has_yield(branchResult, hasYield))) return false;
		}
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::IfHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_if_handler)
{
	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\IfHandler");
	ptdecl::IfHandler::declareClass(cls);
	ptdecl::IfHandler::declareProperties(cls);

	/* the real parameter names: the DI container pairs the
	 * #[AutowiredParameter] by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool treatPhpDocTypesAsCertain;
		if (!zp::parse<zp::Bool>(execute_data, treatPhpDocTypesAsCertain)) RETURN_THROWS();
		IfHandler(Z_OBJ_P(ZEND_THIS)).construct(treatPhpDocTypesAsCertain);
	});

	cls.method<&IfHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(IfHandler(Z_OBJ_P(ZEND_THIS)).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_if_handler);
	pt_stmt_handler_entry_register(&pt_ce_if_handler, &IfHandler::processStmtEntry);
}

/* }}} */
