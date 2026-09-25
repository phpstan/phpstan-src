/*
 * PHPStanTurbo\TernaryHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\TernaryHandler.
 *
 * A DI service (#[AutowiredService]) and a PerFileAnalysisResettable: the
 * constructor keeps the twin's arginfo so Nette autowires it, the captured
 * results live in the twin's $capturedResults slot. processExpr() is
 * registered as the class's handler entry (Engine.h) and getCapturedResults()
 * — which AssignHandler calls across handlers — is exported as
 * pt_ternary_handler_get_captured_results(). The twin's closures are native
 * closures capturing what the PHP closures capture: the typeCallback ($expr,
 * $ternaryCondResult, $ifResult, $elseResult, $ifProcessingScope,
 * $elseProcessingScope, $nodeScopeResolver), the specifyTypesCallback ($this
 * and the same plus $scope and the by-reference $aFalseyScope memo) and, per
 * ask, the (cond && if) || (!cond && else) decomposition's operand callbacks
 * it hands to BooleanNarrowingHelper.
 *
 * NodeScopeResolver, ExpressionResult, ExpressionResultStorage,
 * ExpressionContext, MutatingScope, VariableFlow, SpecifiedTypes,
 * TypeSpecifierContext, DefaultNarrowingHelper, BooleanNarrowingHelper,
 * TypeCombinator and the Type kernel are called through their direct entries;
 * the fabricated BooleanNot / BooleanAnd nodes are instantiated through the
 * class map.
 */

#include "support.h"
#include "generated/TernaryHandler.h"

namespace slots = ptdecl::TernaryHandler::slot;
namespace sigs = ptdecl::TernaryHandler::sig;
#include "OperatorHandlers.h"

zend_class_entry *pt_ce_ternary_handler = nullptr;

namespace {

/* $expr->cond / ->if (a null zval for a short ternary) / ->else */
zval *ternaryCond(zval *expr) { return ptoh::operand(ptoh::ternaryCondProp, expr); }
zval *ternaryIf(zval *expr) { return ptoh::operand(ptoh::ternaryIfProp, expr); }
zval *ternaryElse(zval *expr) { return ptoh::operand(ptoh::ternaryElseProp, expr); }

/* $scope->getTemplateArgumentConstraints() of a scope value */
zv::Val templateArgumentConstraints(zval *scope)
{
	return pt_mutating_scope_get_template_argument_constraints(Z_OBJ_P(scope));
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\TernaryHandler; UNDEF = pending
 * exception. */
class TernaryHandler
{
public:
	explicit TernaryHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *expressionResultFactory, zval *defaultNarrowingHelper, zval *booleanNarrowingHelper) const
	{
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
		pt_write_slot(self, slots::booleanNarrowingHelper, booleanNarrowingHelper);
	}

	/* Mirrors resetFileAnalysisState(). */
	void resetFileAnalysisState() const
	{
		zv::Ref(OBJ_PROP_NUM(self, slots::capturedResults)).assign(zv::Val(zv::Arr::empty()));
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		int is = ptoh::isInstance(expr, PT_CLASS_TERNARY_EXPR);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors getCapturedResults(). */
	zv::Val getCapturedResults(zval *expr) const
	{
		zval *capturedResults = OBJ_PROP_NUM(self, slots::capturedResults);
		zval *entry = Z_TYPE_P(capturedResults) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(capturedResults), Z_OBJ_HANDLE_P(expr)) : NULL;
		if (entry == NULL || Z_TYPE_P(entry) == IS_NULL) return zv::Val::null();
		zval *pinned = Z_TYPE_P(entry) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(entry), 0) : NULL;
		if (pinned == NULL || Z_TYPE_P(pinned) != IS_OBJECT || Z_OBJ_P(pinned) != Z_OBJ_P(expr)) return zv::Val::null();

		zv::Arr results = zv::Arr::create(3);
		for (zend_ulong i = 1; i <= 3; i++) {
			zval *result = zend_hash_index_find(Z_ARRVAL_P(entry), i);
			results.push(zv::Ref(result != NULL ? result : &EG(uninitialized_zval)));
		}
		return zv::Val(std::move(results));
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *cond = ternaryCond(expr);
		if (UNEXPECTED(cond == NULL)) return zv::Val();
		zv::Val condContext = pt_expression_context_enter_deep(context);
		if (UNEXPECTED(condContext.isUndef())) return zv::Val();
		zv::Val ternaryCondResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, cond, scope, storage, nodeCallback, condContext.raw());
		if (UNEXPECTED(ternaryCondResult.isUndef())) return zv::Val();
		zv::Val hold;
		zval *borrowed = pt_expression_result_throw_points(ternaryCondResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		zv::Val throwPoints = zv::Val::copyOf(zv::Ref(borrowed));
		borrowed = pt_expression_result_impure_points(ternaryCondResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		zv::Val impurePoints = zv::Val::copyOf(zv::Ref(borrowed));
		bool hasYield;
		if (UNEXPECTED(!pt_expression_result_has_yield(ternaryCondResult.raw(), hasYield))) return zv::Val();
		zv::Val ifTrueScope = pt_expression_result_get_truthy_scope(ternaryCondResult.raw());
		if (UNEXPECTED(ifTrueScope.isUndef())) return zv::Val();
		zv::Val ifFalseScope = pt_expression_result_get_falsey_scope(ternaryCondResult.raw());
		if (UNEXPECTED(ifFalseScope.isUndef())) return zv::Val();
		zv::Val ifTrueType;
		zv::Val ifResult = zv::Val::null();

		zv::Val ifProcessingScope = zv::Val::copyOf(ifTrueScope.ref());
		zv::Val elseProcessingScope = zv::Val::copyOf(ifFalseScope.ref());
		zv::Val elseResult;
		zval *ifExpr = ternaryIf(expr);
		if (UNEXPECTED(ifExpr == NULL)) return zv::Val();
		if (Z_TYPE_P(ifExpr) == IS_NULL) {
			zval *elseExpr = ternaryElse(expr);
			if (UNEXPECTED(elseExpr == NULL)) return zv::Val();
			elseResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, elseExpr, ifFalseScope.raw(), storage, nodeCallback, context);
			if (UNEXPECTED(elseResult.isUndef())) return zv::Val();
			if (UNEXPECTED(!mergePoints(elseResult.raw(), throwPoints, impurePoints, hasYield))) return zv::Val();
			borrowed = pt_expression_result_scope(elseResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			ifFalseScope = zv::Val::copyOf(zv::Ref(borrowed));
		} else {
			ifResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, ifExpr, ifTrueScope.raw(), storage, nodeCallback, context);
			if (UNEXPECTED(ifResult.isUndef())) return zv::Val();
			if (UNEXPECTED(!mergePoints(ifResult.raw(), throwPoints, impurePoints, hasYield))) return zv::Val();
			borrowed = pt_expression_result_scope(ifResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			ifTrueScope = zv::Val::copyOf(zv::Ref(borrowed));
			ifTrueType = pt_expression_result_get_type_on_scope(ifResult.raw(), ifProcessingScope.raw(), false);
			if (UNEXPECTED(ifTrueType.isUndef())) return zv::Val();

			zval *elseExpr = ternaryElse(expr);
			if (UNEXPECTED(elseExpr == NULL)) return zv::Val();
			elseResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, elseExpr, ifFalseScope.raw(), storage, nodeCallback, context);
			if (UNEXPECTED(elseResult.isUndef())) return zv::Val();
			if (UNEXPECTED(!mergePoints(elseResult.raw(), throwPoints, impurePoints, hasYield))) return zv::Val();
			borrowed = pt_expression_result_scope(elseResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			ifFalseScope = zv::Val::copyOf(zv::Ref(borrowed));
		}

		if (!ifResult.isNull()) {
			zv::Arr entry = zv::Arr::create(4);
			entry.push(zv::Ref(expr));
			entry.push(ternaryCondResult.ref());
			entry.push(ifResult.ref());
			entry.push(elseResult.ref());
			zval *capturedResults = OBJ_PROP_NUM(self, slots::capturedResults);
			SEPARATE_ARRAY(capturedResults);
			zval entryValue = zv::Val(std::move(entry)).take();
			zend_hash_index_update(Z_ARRVAL_P(capturedResults), Z_OBJ_HANDLE_P(expr), &entryValue);
		}

		zv::Val condType = pt_expression_result_get_type(ternaryCondResult.raw());
		if (UNEXPECTED(condType.isUndef())) return zv::Val();
		zval *finalScope;
		zv::Val mergedScope;
		int condTrue = ptoh::typeVerdict(condType.raw(), true);
		if (UNEXPECTED(condTrue < 0)) return zv::Val();
		if (condTrue) {
			finalScope = ifTrueScope.raw();
		} else {
			int condFalse = ptoh::typeVerdict(condType.raw(), false);
			if (UNEXPECTED(condFalse < 0)) return zv::Val();
			if (condFalse) {
				finalScope = ifFalseScope.raw();
			} else {
				bool ifTrueNever = false;
				if (!ifTrueType.isUndef() && UNEXPECTED(!ptoh::isExplicitNever(ifTrueType.raw(), ifTrueNever))) return zv::Val();
				if (ifTrueNever) {
					finalScope = ifFalseScope.raw();
				} else {
					zv::Val ifFalseType = pt_expression_result_get_type_on_scope(elseResult.raw(), elseProcessingScope.raw(), false);
					if (UNEXPECTED(ifFalseType.isUndef())) return zv::Val();
					bool ifFalseNever;
					if (UNEXPECTED(!ptoh::isExplicitNever(ifFalseType.raw(), ifFalseNever))) return zv::Val();
					if (ifFalseNever) {
						finalScope = ifTrueScope.raw();
					} else {
						mergedScope = pt_mutating_scope_merge_with(Z_OBJ_P(ifTrueScope.raw()), ifFalseScope.raw());
						if (UNEXPECTED(mergedScope.isUndef())) return zv::Val();
						finalScope = mergedScope.raw();
					}
				}
			}
		}

		zv::Val ifTrueConstraints = templateArgumentConstraints(ifTrueScope.raw());
		if (UNEXPECTED(ifTrueConstraints.isUndef())) return zv::Val();
		zv::Val constrainedScope = pt_mutating_scope_add_template_argument_constraints(Z_OBJ_P(finalScope), ifTrueConstraints.raw());
		if (UNEXPECTED(constrainedScope.isUndef())) return zv::Val();
		zv::Val ifFalseConstraints = templateArgumentConstraints(ifFalseScope.raw());
		if (UNEXPECTED(ifFalseConstraints.isUndef())) return zv::Val();
		if (UNEXPECTED(!constrainedScope.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function addTemplateArgumentConstraints() on %s", zend_zval_value_name(constrainedScope.raw()));
			return zv::Val();
		}
		zv::Val resultScope = pt_mutating_scope_add_template_argument_constraints(Z_OBJ_P(constrainedScope.raw()), ifFalseConstraints.raw());
		if (UNEXPECTED(resultScope.isUndef())) return zv::Val();

		// lazily memoized merged-falsey scope of the (cond && if) disjunct
		zval aFalseyScope;
		ZVAL_NEW_REF(&aFalseyScope, &EG(uninitialized_zval));
		zv::Val aFalseyScopeHold = zv::Val::adopt(aFalseyScope);

		zv::Val variableFlow;
		{
			zv::Val condFlow = pt_expression_result_variable_flow(ternaryCondResult.raw());
			if (UNEXPECTED(condFlow.isUndef())) return zv::Val();
			zv::Val ifFlow = zv::Val::null();
			if (!ifResult.isNull()) {
				ifFlow = pt_expression_result_variable_flow(ifResult.raw());
				if (UNEXPECTED(ifFlow.isUndef())) return zv::Val();
			}
			zv::Val elseFlow = pt_expression_result_variable_flow(elseResult.raw());
			if (UNEXPECTED(elseFlow.isUndef())) return zv::Val();
			zv::Args choiceArgv{ifFlow.raw(), elseFlow.raw()};
			zv::Val choice = pt_variable_flow_choice(2, choiceArgv);
			if (UNEXPECTED(choice.isUndef())) return zv::Val();
			zv::Args sequenceArgv{condFlow.raw(), choice.raw()};
			variableFlow = pt_variable_flow_sequence(2, sequenceArgv);
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		}
		bool isAlwaysTerminating;
		if (UNEXPECTED(!pt_expression_result_is_always_terminating(ternaryCondResult.raw(), isAlwaysTerminating))) return zv::Val();

		// the branches were processed on the cond-truthy/cond-falsey scopes
		// including the condition's side effects - those captured scopes
		// are the evaluation points, no re-walk needed. Reading the branch
		// results ON those scopes matters when processExprNode answered a
		// branch from a stored result (an on-demand ternary whose branches
		// are already-walked real nodes): the stored walk-position type
		// predates the condition's narrowing the branch scope carries.
		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, expr, ternaryCondResult.raw(), ifResult.raw(), elseResult.raw(), ifProcessingScope.raw(), elseProcessingScope.raw(), nodeScopeResolver);
		zval specifyCaptures[10];
		ZVAL_OBJ(&specifyCaptures[0], self);
		ZVAL_COPY_VALUE(&specifyCaptures[1], expr);
		ZVAL_COPY_VALUE(&specifyCaptures[2], ternaryCondResult.raw());
		ZVAL_COPY_VALUE(&specifyCaptures[3], ifResult.raw());
		ZVAL_COPY_VALUE(&specifyCaptures[4], elseResult.raw());
		ZVAL_COPY_VALUE(&specifyCaptures[5], ifProcessingScope.raw());
		ZVAL_COPY_VALUE(&specifyCaptures[6], elseProcessingScope.raw());
		ZVAL_COPY_VALUE(&specifyCaptures[7], nodeScopeResolver);
		ZVAL_COPY_VALUE(&specifyCaptures[8], scope);
		ZVAL_COPY_VALUE(&specifyCaptures[9], aFalseyScopeHold.raw());
		zv::Val specifyTypesCallback = pt_native_closure_new(&specifyTypesCallbackBody, 10, specifyCaptures, 1u << 9);

		pt_expression_result_args args(resultScope.raw(), scope, expr, hasYield, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return TernaryHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	static constexpr const char *closureName = "PHPStan\\Analyser\\ExprHandler\\TernaryHandler::{closure}";

	/* $throwPoints = array_merge($throwPoints, $branchResult->getThrowPoints());
	 * $impurePoints = array_merge(...); $hasYield = $hasYield ||
	 * $branchResult->hasYield(); false = pending exception */
	[[nodiscard]] static bool mergePoints(zval *branchResult, zv::Val &throwPoints, zv::Val &impurePoints, bool &hasYield)
	{
		zv::Val hold;
		zval *borrowed = pt_expression_result_throw_points(branchResult, hold);
		if (UNEXPECTED(borrowed == NULL)) return false;
		throwPoints = ptoh::arrayMerge(throwPoints.raw(), borrowed);
		borrowed = pt_expression_result_impure_points(branchResult, hold);
		if (UNEXPECTED(borrowed == NULL)) return false;
		impurePoints = ptoh::arrayMerge(impurePoints.raw(), borrowed);
		if (!hasYield && UNEXPECTED(!pt_expression_result_has_yield(branchResult, hasYield))) return false;
		return true;
	}

	/* static function (bool $nativeTypesPromoted) use ($expr,
	 * $ternaryCondResult, $ifResult, $elseResult, $ifProcessingScope,
	 * $elseProcessingScope, $nodeScopeResolver): Type — captures in that order */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptoh::requireArgs(argc, 1, closureName))) return;
		bool nativeTypesPromoted = zend_is_true(&argv[0]);
		zv::Val type;
		pt_engine_with_stack([&]() { type = resolveType(captures, nativeTypesPromoted); });
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	static zv::Val resolveType(zval *captures, bool nativeTypesPromoted)
	{
		zval *expr = &captures[0];
		zval *ternaryCondResult = &captures[1];
		zval *ifResult = &captures[2];
		zval *elseResult = &captures[3];
		zval *ifProcessingScope = &captures[4];
		zval *elseProcessingScope = &captures[5];
		zval *nodeScopeResolver = &captures[6];

		zv::Val promotedIfProcessingScope;
		if (nativeTypesPromoted) {
			promotedIfProcessingScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(ifProcessingScope));
			if (UNEXPECTED(promotedIfProcessingScope.isUndef())) return zv::Val();
			ifProcessingScope = promotedIfProcessingScope.raw();
		}
		zv::Val condType = nativeTypesPromoted ? pt_expression_result_get_native_type(ternaryCondResult) : pt_expression_result_get_type(ternaryCondResult);
		if (UNEXPECTED(condType.isUndef())) return zv::Val();
		ptoh::BooleanOf booleanConditionType;
		if (UNEXPECTED(!booleanConditionType.init(condType.raw()))) return zv::Val();
		zv::Val elseType = pt_expression_result_get_type_on_scope(elseResult, elseProcessingScope, nativeTypesPromoted);
		if (UNEXPECTED(elseType.isUndef())) return zv::Val();
		zval *ifExpr = ternaryIf(expr);
		if (UNEXPECTED(ifExpr == NULL)) return zv::Val();
		if (Z_TYPE_P(ifExpr) == IS_NULL || Z_TYPE_P(ifResult) == IS_NULL) {
			// short-ternary truthy value: the condition read on its own truthy
			// scope. The truthy narrowing is tracked by the scope
			// (getTypeOnScope's authoritative read); only an untracked
			// condition needs reprocessing there.
			bool answers;
			if (UNEXPECTED(!pt_expression_result_answers_on_scope(ternaryCondResult, ifProcessingScope, false, answers))) return zv::Val();
			zv::Val condTruthyType;
			if (answers) {
				condTruthyType = pt_expression_result_get_type_on_scope(ternaryCondResult, ifProcessingScope, false);
			} else {
				zval *cond = ternaryCond(expr);
				if (UNEXPECTED(cond == NULL)) return zv::Val();
				zv::Val onDemandStorage = pt_expression_result_storage_new();
				if (UNEXPECTED(onDemandStorage.isUndef())) return zv::Val();
				zv::Val onDemandResult = pt_node_scope_resolver_process_expr_on_demand(nodeScopeResolver, cond, ifProcessingScope, onDemandStorage.raw());
				if (UNEXPECTED(onDemandResult.isUndef())) return zv::Val();
				condTruthyType = pt_expression_result_get_type(onDemandResult.raw());
			}
			if (UNEXPECTED(condTruthyType.isUndef())) return zv::Val();
			int isTrue = booleanConditionType.isTrue();
			if (UNEXPECTED(isTrue < 0)) return zv::Val();
			if (isTrue) return condTruthyType;

			int isFalse = booleanConditionType.isFalse();
			if (UNEXPECTED(isFalse < 0)) return zv::Val();
			if (isFalse) return elseType;

			zv::Val truthyPart = pt_type_combinator_call(PT_LC("removefalsey"), 1, condTruthyType.raw());
			if (UNEXPECTED(truthyPart.isUndef())) return zv::Val();
			zv::Args unionArgv{truthyPart.raw(), elseType.raw()};
			return pt_type_combinator_union(2, unionArgv);
		}

		zv::Val ifType = pt_expression_result_get_type_on_scope(ifResult, ifProcessingScope, nativeTypesPromoted);
		if (UNEXPECTED(ifType.isUndef())) return zv::Val();
		int isTrue = booleanConditionType.isTrue();
		if (UNEXPECTED(isTrue < 0)) return zv::Val();
		if (isTrue) return ifType;

		int isFalse = booleanConditionType.isFalse();
		if (UNEXPECTED(isFalse < 0)) return zv::Val();
		if (isFalse) return elseType;

		zv::Args unionArgv{ifType.raw(), elseType.raw()};
		return pt_type_combinator_union(2, unionArgv);
	}

	/* function (TypeSpecifierContext $context, bool $nativeTypesPromoted) use
	 * ($expr, $ternaryCondResult, $ifResult, $elseResult, $ifProcessingScope,
	 * $elseProcessingScope, $nodeScopeResolver, $scope, &$aFalseyScope):
	 * SpecifiedTypes — captures: $this and those, in that order */
	static void specifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptoh::requireArgs(argc, 2, closureName))) return;
		zv::Val specifiedTypes = specifyTypes(captures, &argv[0], zend_is_true(&argv[1]));
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}

	static zv::Val specifyTypes(zval *captures, zval *context, bool nativeTypesPromoted)
	{
		zend_object *handler = Z_OBJ(captures[0]);
		zval *expr = &captures[1];
		zval *ternaryCondResult = &captures[2];
		zval *ifResult = &captures[3];
		zval *elseResult = &captures[4];
		zval *ifProcessingScope = &captures[5];
		zval *elseProcessingScope = &captures[6];
		zval *nodeScopeResolver = &captures[7];
		zval *scope = &captures[8];
		zval *aFalseyScope = &captures[9];

		zv::Val s = nativeTypesPromoted ? pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scope)) : zv::Val::copyOf(zv::Ref(scope));
		if (UNEXPECTED(s.isUndef())) return zv::Val();
		zval *cond = ternaryCond(expr);
		if (UNEXPECTED(cond == NULL)) return zv::Val();
		int condIsTernary = ptoh::isInstance(cond, PT_CLASS_TERNARY_EXPR);
		if (UNEXPECTED(condIsTernary < 0)) return zv::Val();
		bool contextNull = false;
		if (!condIsTernary && UNEXPECTED(!pt_type_specifier_context_null(Z_OBJ_P(context), contextNull))) return zv::Val();
		if (condIsTernary || contextNull) {
			return pt_default_narrowing_helper_specify_default_types(OBJ_PROP_NUM(handler, slots::defaultNarrowingHelper), expr, context);
		}

		// An exact context (`=== true`, `!== false`, ...) asks about the taken
		// arm's value, not its truthiness - `0 !== false` holds although `0` is
		// falsey. The decomposition then holds for the arms compared to the
		// constant (`(cond && if !== false) || ...`) being true, the condition
		// still read by truthiness.
		zval *armContext = NULL;
		zval truthyContext;
		zend_object *truthy = pt_type_specifier_context_create_truthy();
		if (UNEXPECTED(truthy == NULL)) return zv::Val();
		zend_object *falsey = pt_type_specifier_context_create_falsey();
		if (UNEXPECTED(falsey == NULL)) return zv::Val();
		if (Z_OBJ_P(context) != truthy && Z_OBJ_P(context) != falsey) {
			bool contextTrue;
			if (UNEXPECTED(!pt_type_specifier_context_true(Z_OBJ_P(context), contextTrue))) return zv::Val();
			// `=== false` / `!== true` of an arm are false when the arm is
			// true: a disjunction arm would be split as if it were negated
			if (!contextTrue) {
				zval *ifExpr = ternaryIf(expr);
				if (UNEXPECTED(ifExpr == NULL)) return zv::Val();
				if (Z_TYPE_P(ifExpr) == IS_NULL) {
					ifExpr = ternaryCond(expr);
					if (UNEXPECTED(ifExpr == NULL)) return zv::Val();
				}
				int disjunction = isDisjunction(ifExpr);
				if (UNEXPECTED(disjunction < 0)) return zv::Val();
				if (!disjunction) {
					zval *elseExpr = ternaryElse(expr);
					if (UNEXPECTED(elseExpr == NULL)) return zv::Val();
					disjunction = isDisjunction(elseExpr);
					if (UNEXPECTED(disjunction < 0)) return zv::Val();
				}
				if (disjunction) {
					return pt_default_narrowing_helper_specify_default_types(OBJ_PROP_NUM(handler, slots::defaultNarrowingHelper), expr, context);
				}
			}
			armContext = context;
			ZVAL_OBJ(&truthyContext, truthy);
			context = &truthyContext;
		}

		// cond ? if : else narrows like (cond && if) || (!cond && else),
		// composed from the walk's results through the boolean helpers -
		// the fabricated nodes are only printed into holder keys
		cond = ternaryCond(expr);
		if (UNEXPECTED(cond == NULL)) return zv::Val();
		zv::Val notCondNode = pt_type_new(PT_CLASS_BOOLEAN_NOT_EXPR, 1, cond);
		if (UNEXPECTED(notCondNode.isUndef())) return zv::Val();

		zv::Val condTypes = pt_native_closure(&specifiedTypesForScopeBody, ternaryCondResult);
		zv::Val condType = pt_native_closure(&resultTypeBody, ternaryCondResult);
		zv::Val notCondTypes = pt_native_closure(&negatedSpecifiedTypesForScopeBody, ternaryCondResult);
		zv::Val notCondType = pt_native_closure(&notCondTypeBody, ternaryCondResult);
		zval *elseExpr = ternaryElse(expr);
		if (UNEXPECTED(elseExpr == NULL)) return zv::Val();
		zv::Val elseScopeHold;
		zval *elseEvaluatedScope = pt_expression_result_scope(elseResult, elseScopeHold);
		if (UNEXPECTED(elseEvaluatedScope == NULL)) return zv::Val();
		ArmOperand elseOperand;
		if (UNEXPECTED(!createArmOperand(handler, elseExpr, elseResult, elseProcessingScope, elseEvaluatedScope, armContext, elseOperand))) return zv::Val();

		// the decomposition's branch scopes are the operand walks' own
		// memoized branch scopes (the evaluation points), not ask-derived;
		// thunked so deep chains do not derive every level eagerly
		zv::Val condTruthyScope = pt_native_closure(&truthyScopeBody, ternaryCondResult);
		zv::Val condFalseyScope = pt_native_closure(&falseyScopeBody, ternaryCondResult);

		// right disjunct: !cond && else
		elseExpr = ternaryElse(expr);
		if (UNEXPECTED(elseExpr == NULL)) return zv::Val();
		zv::Args bNodeArgv{notCondNode.raw(), elseExpr};
		zv::Val bNode = pt_type_new(PT_CLASS_BOOLEAN_AND_EXPR, 2, bNodeArgv);
		if (UNEXPECTED(bNode.isUndef())) return zv::Val();
		zv::Val bTypes = pt_native_closure(&bTypesBody, handler, nodeScopeResolver, bNode.raw(), notCondNode.raw(), notCondTypes.raw(), condFalseyScope.raw(), condTruthyScope.raw(), expr, elseOperand.types.raw(), elseOperand.falseyScope.raw());
		zv::Val bType = pt_native_closure(&andVerdictBody, notCondType.raw(), elseOperand.type.raw());

		zend_object *booleanNarrowingHelper = Z_OBJ_P(OBJ_PROP_NUM(handler, slots::booleanNarrowingHelper));
		zval *ifExpr = NULL;
		if (Z_TYPE_P(ifResult) != IS_NULL) {
			ifExpr = ternaryIf(expr);
			if (UNEXPECTED(ifExpr == NULL)) return zv::Val();
		}
		bool fullTernary = ifExpr != NULL && Z_TYPE_P(ifExpr) != IS_NULL;
		// the short ternary's truthy value is the condition itself - in an
		// exact context it is compared like an arm: cond && (cond === true)
		bool shortTernaryArm = armContext != NULL && !fullTernary;
		zv::Val disjunction;
		if (fullTernary || shortTernaryArm) {
			// left disjunct: cond && if
			cond = ternaryCond(expr);
			if (UNEXPECTED(cond == NULL)) return zv::Val();
			zval *armExpr;
			zval *armResult;
			zval *evaluatedScope;
			zv::Val ifScopeHold;
			if (fullTernary) {
				armExpr = ternaryIf(expr);
				if (UNEXPECTED(armExpr == NULL)) return zv::Val();
				armResult = ifResult;
				evaluatedScope = pt_expression_result_scope(ifResult, ifScopeHold);
				if (UNEXPECTED(evaluatedScope == NULL)) return zv::Val();
			} else {
				armExpr = cond;
				armResult = ternaryCondResult;
				evaluatedScope = ifProcessingScope;
			}
			ArmOperand ifOperand;
			if (UNEXPECTED(!createArmOperand(handler, armExpr, armResult, ifProcessingScope, evaluatedScope, armContext, ifOperand))) return zv::Val();
			zv::Args aNodeArgv{cond, armExpr};
			zv::Val aNode = pt_type_new(PT_CLASS_BOOLEAN_AND_EXPR, 2, aNodeArgv);
			if (UNEXPECTED(aNode.isUndef())) return zv::Val();
			zv::Val aTypes = pt_native_closure(&aTypesBody, handler, nodeScopeResolver, aNode.raw(), expr, condTypes.raw(), condTruthyScope.raw(), condFalseyScope.raw(), ifOperand.types.raw(), ifOperand.falseyScope.raw(), armExpr);
			zv::Val aType = pt_native_closure(&andVerdictBody, condType.raw(), ifOperand.type.raw());
			// the merged falsey of (cond && if) has no single walk scope -
			// derived from the evaluation point on first demand, reused across asks
			zval thunkCaptures[3];
			ZVAL_COPY_VALUE(&thunkCaptures[0], scope);
			ZVAL_COPY_VALUE(&thunkCaptures[1], aTypes.raw());
			ZVAL_COPY_VALUE(&thunkCaptures[2], aFalseyScope);
			zv::Val aFalseyScopeThunk = pt_native_closure_new(&aFalseyScopeThunkBody, 3, thunkCaptures, 1u << 2);

			disjunction = pt_boolean_narrowing_helper_specify_disjunction(booleanNarrowingHelper, nodeScopeResolver, s.raw(), context, expr, aNode.raw(), aTypes.raw(), aType.raw(), ifOperand.truthyScope.raw(), aFalseyScopeThunk.raw(), bNode.raw(), bTypes.raw(), bType.raw(), elseOperand.truthyScope.raw());
		} else {
			// short ternary: cond || (!cond && else)
			cond = ternaryCond(expr);
			if (UNEXPECTED(cond == NULL)) return zv::Val();
			disjunction = pt_boolean_narrowing_helper_specify_disjunction(booleanNarrowingHelper, nodeScopeResolver, s.raw(), context, expr, cond, condTypes.raw(), condType.raw(), condTruthyScope.raw(), condFalseyScope.raw(), bNode.raw(), bTypes.raw(), bType.raw(), elseOperand.truthyScope.raw());
		}
		if (UNEXPECTED(disjunction.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(disjunction.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function setRootExpr() on %s", zend_zval_value_name(disjunction.raw()));
			return zv::Val();
		}
		return pt_specified_types_set_root_expr(Z_OBJ_P(disjunction.raw()), expr);
	}

	/* static fn (MutatingScope $scope, TypeSpecifierContext $ctx): SpecifiedTypes
	 * => $result->getSpecifiedTypesForScope($scope, $ctx) — captures: the
	 * result */
	static void specifiedTypesForScopeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptoh::requireArgs(argc, 2, closureName))) return;
		zv::Val specifiedTypes = pt_expression_result_get_specified_types_for_scope(&captures[0], &argv[0], &argv[1]);
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}

	/* static fn (MutatingScope $scope, TypeSpecifierContext $ctx): SpecifiedTypes
	 * => $ternaryCondResult->getSpecifiedTypesForScope($scope, $ctx->negate())
	 * — captures: $ternaryCondResult */
	static void negatedSpecifiedTypesForScopeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptoh::requireArgs(argc, 2, closureName))) return;
		zv::Val negated = pt_type_specifier_context_negate(Z_OBJ(argv[1]));
		if (UNEXPECTED(negated.isUndef())) return;
		zv::Val specifiedTypes = pt_expression_result_get_specified_types_for_scope(&captures[0], &argv[0], negated.raw());
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}

	/* static fn (bool $nativeTypesPromoted): Type => $nativeTypesPromoted ?
	 * $result->getNativeType() : $result->getType() — captures: the result */
	static void resultTypeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptoh::requireArgs(argc, 1, closureName))) return;
		zv::Val type = zend_is_true(&argv[0]) ? pt_expression_result_get_native_type(&captures[0]) : pt_expression_result_get_type(&captures[0]);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* static fn (bool $nativeTypesPromoted): Type =>
	 * $result->getTypeOnScope($processingScope, $nativeTypesPromoted) —
	 * captures: the result, its processing scope */
	static void typeOnScopeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptoh::requireArgs(argc, 1, closureName))) return;
		zv::Val type = pt_expression_result_get_type_on_scope(&captures[0], &captures[1], zend_is_true(&argv[0]));
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* static function (bool $nativeTypesPromoted) use ($ternaryCondResult):
	 * Type — the negated condition's verdict; captures: $ternaryCondResult */
	static void notCondTypeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptoh::requireArgs(argc, 1, closureName))) return;
		zv::Val type = zend_is_true(&argv[0]) ? pt_expression_result_get_native_type(&captures[0]) : pt_expression_result_get_type(&captures[0]);
		if (UNEXPECTED(type.isUndef())) return;
		ptoh::BooleanOf boolean;
		if (UNEXPECTED(!boolean.init(type.raw()))) return;
		int isTrue = boolean.isTrue();
		if (UNEXPECTED(isTrue < 0)) return;
		zv::Val verdict;
		if (isTrue) {
			verdict = ptoh::constantBoolean(false);
		} else {
			int isFalse = boolean.isFalse();
			if (UNEXPECTED(isFalse < 0)) return;
			verdict = isFalse ? ptoh::constantBoolean(true) : ptoh::booleanType();
		}
		if (UNEXPECTED(verdict.isUndef())) return;
		verdict.intoReturnValue(return_value);
	}

	/* static function (bool $nativeTypesPromoted) use ($left, $right): Type
	 * — $andVerdict($left, $right)'s closure; captures: $left, $right */
	static void andVerdictBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptoh::requireArgs(argc, 1, closureName))) return;
		zv::Args promoted{(bool) zend_is_true(&argv[0])};
		zv::Val leftType = pt_type_call_callable(&captures[0], 1, promoted);
		if (UNEXPECTED(leftType.isUndef())) return;
		ptoh::BooleanOf leftBool;
		if (UNEXPECTED(!leftBool.init(leftType.raw()))) return;
		zv::Val rightType = pt_type_call_callable(&captures[1], 1, promoted);
		if (UNEXPECTED(rightType.isUndef())) return;
		ptoh::BooleanOf rightBool;
		if (UNEXPECTED(!rightBool.init(rightType.raw()))) return;
		zv::Val verdict;
		int leftFalse = leftBool.isFalse();
		if (UNEXPECTED(leftFalse < 0)) return;
		int rightFalse = 0;
		if (!leftFalse) {
			rightFalse = rightBool.isFalse();
			if (UNEXPECTED(rightFalse < 0)) return;
		}
		if (leftFalse || rightFalse) {
			verdict = ptoh::constantBoolean(false);
		} else {
			int leftTrue = leftBool.isTrue();
			if (UNEXPECTED(leftTrue < 0)) return;
			int rightTrue = 0;
			if (leftTrue) {
				rightTrue = rightBool.isTrue();
				if (UNEXPECTED(rightTrue < 0)) return;
			}
			verdict = leftTrue && rightTrue ? ptoh::constantBoolean(true) : ptoh::booleanType();
		}
		if (UNEXPECTED(verdict.isUndef())) return;
		verdict.intoReturnValue(return_value);
	}

	/* static fn (): MutatingScope => $result->getTruthyScope() — captures:
	 * the result */
	static void truthyScopeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) argc;
		(void) argv;
		zv::Val truthyScope = pt_expression_result_get_truthy_scope(&captures[0]);
		if (UNEXPECTED(truthyScope.isUndef())) return;
		truthyScope.intoReturnValue(return_value);
	}

	/* static fn (): MutatingScope => $result->getFalseyScope() — captures:
	 * the result */
	static void falseyScopeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) argc;
		(void) argv;
		zv::Val falseyScope = pt_expression_result_get_falsey_scope(&captures[0]);
		if (UNEXPECTED(falseyScope.isUndef())) return;
		falseyScope.intoReturnValue(return_value);
	}

	/* fn (MutatingScope $scope, TypeSpecifierContext $ctx): SpecifiedTypes =>
	 * $this->booleanNarrowingHelper->specifyConjunction($nodeScopeResolver,
	 * $scope, $ctx, $bNode, $notCondNode, $notCondTypes, $condFalseyScope,
	 * $condTruthyScope, $expr->else, $elseTypes, $elseFalseyScope)
	 * — captures: $this, $nodeScopeResolver, $bNode, $notCondNode,
	 * $notCondTypes, $condFalseyScope, $condTruthyScope, $expr, $elseTypes,
	 * $elseFalseyScope */
	static void bTypesBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptoh::requireArgs(argc, 2, closureName))) return;
		zval *elseExpr = ternaryElse(&captures[7]);
		if (UNEXPECTED(elseExpr == NULL)) return;
		zv::Val specifiedTypes = pt_boolean_narrowing_helper_specify_conjunction(Z_OBJ_P(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::booleanNarrowingHelper)), &captures[1], &argv[0], &argv[1], &captures[2], &captures[3], &captures[4], &captures[5], &captures[6], elseExpr, &captures[8], &captures[9]);
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}

	/* fn (MutatingScope $scope, TypeSpecifierContext $ctx): SpecifiedTypes =>
	 * $this->booleanNarrowingHelper->specifyConjunction($nodeScopeResolver,
	 * $scope, $ctx, $aNode, $expr->cond, $condTypes, $condTruthyScope,
	 * $condFalseyScope, $ifExpr, $ifTypes, $ifFalseyScope) — captures: $this,
	 * $nodeScopeResolver, $aNode, $expr, $condTypes, $condTruthyScope,
	 * $condFalseyScope, $ifTypes, $ifFalseyScope, $ifExpr */
	static void aTypesBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptoh::requireArgs(argc, 2, closureName))) return;
		zval *cond = ternaryCond(&captures[3]);
		if (UNEXPECTED(cond == NULL)) return;
		zv::Val specifiedTypes = pt_boolean_narrowing_helper_specify_conjunction(Z_OBJ_P(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::booleanNarrowingHelper)), &captures[1], &argv[0], &argv[1], &captures[2], cond, &captures[4], &captures[5], &captures[6], &captures[9], &captures[7], &captures[8]);
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}

	/* the four callables createArmOperand() returns */
	struct ArmOperand
	{
		zv::Val types;
		zv::Val type;
		zv::Val truthyScope;
		zv::Val falseyScope;
	};

	/* Mirrors createArmOperand(); false = pending exception */
	[[nodiscard]] static bool createArmOperand(zend_object *handler, zval *armExpr, zval *armResult, zval *processingScope, zval *evaluatedScope, zval *armContext, ArmOperand &out)
	{
		if (armContext == NULL) {
			out.types = pt_native_closure(&specifiedTypesForScopeBody, armResult);
			out.type = pt_native_closure(&typeOnScopeBody, armResult, processingScope);
			out.truthyScope = pt_native_closure(&truthyScopeBody, armResult);
			out.falseyScope = pt_native_closure(&falseyScopeBody, armResult);
			return true;
		}

		// `!== true` / `!== false` are the mixed contexts, `=== true` / `=== false` the pure ones
		bool truthy, falsey, isTrue;
		if (UNEXPECTED(!pt_type_specifier_context_truthy(Z_OBJ_P(armContext), truthy))) return false;
		if (UNEXPECTED(!pt_type_specifier_context_falsey(Z_OBJ_P(armContext), falsey))) return false;
		if (UNEXPECTED(!pt_type_specifier_context_true(Z_OBJ_P(armContext), isTrue))) return false;
		bool isIdentical = !(truthy && falsey);
		bool value = isIdentical ? isTrue : !isTrue;

		out.types = pt_native_closure(&exactArmTypesBody, handler, armExpr, armResult, isIdentical, value);
		out.type = pt_native_closure(&exactArmTypeBody, armResult, processingScope, isIdentical, value);
		zend_object *truthyContext = pt_type_specifier_context_create_truthy();
		if (UNEXPECTED(truthyContext == NULL)) return false;
		zend_object *falseyContext = pt_type_specifier_context_create_falsey();
		if (UNEXPECTED(falseyContext == NULL)) return false;
		out.truthyScope = pt_native_closure(&derivedScopeBody, evaluatedScope, out.types.raw(), truthyContext);
		out.falseyScope = pt_native_closure(&derivedScopeBody, evaluatedScope, out.types.raw(), falseyContext);
		return true;
	}

	/* $expr instanceof BooleanOr || $expr instanceof LogicalOr (isDisjunction());
	 * -1 = pending exception */
	static int isDisjunction(zval *expr)
	{
		int is = ptoh::isInstance(expr, PT_CLASS_BOOLEAN_OR_EXPR);
		if (is != 0) return is;
		return ptoh::isInstance(expr, PT_CLASS_LOGICAL_OR_EXPR);
	}

	/* function (MutatingScope $scope, TypeSpecifierContext $ctx) use ($armExpr,
	 * $armResult, $isIdentical, $value): SpecifiedTypes — the exact arm
	 * operand's narrowing; captures: $this, $armExpr, $armResult,
	 * $isIdentical, $value */
	static void exactArmTypesBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptoh::requireArgs(argc, 2, closureName))) return;
		zval *armExpr = &captures[1];
		zval *armResult = &captures[2];
		bool isIdentical = Z_TYPE(captures[3]) == IS_TRUE;
		bool value = Z_TYPE(captures[4]) == IS_TRUE;

		zv::Val negatedContext;
		zval *identicalContext = &argv[1];
		if (!isIdentical) {
			negatedContext = pt_type_specifier_context_negate(Z_OBJ(argv[1]));
			if (UNEXPECTED(negatedContext.isUndef())) return;
			identicalContext = negatedContext.raw();
		}
		zv::Val constant = ptoh::constantBoolean(value);
		if (UNEXPECTED(constant.isUndef())) return;
		zv::Val types = pt_default_narrowing_helper_create_subject_types(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::defaultNarrowingHelper), &argv[0], armExpr, armResult, constant.raw(), identicalContext);
		if (UNEXPECTED(types.isUndef())) return;

		// a nullsafe chain that did not produce the constant may have
		// short-circuited instead
		bool identicalTrue;
		if (UNEXPECTED(!pt_type_specifier_context_true(Z_OBJ_P(identicalContext), identicalTrue))) return;
		if (!identicalTrue) {
			int nullsafe = ptoh::isInstance(armExpr, PT_CLASS_NULLSAFE_METHOD_CALL);
			if (UNEXPECTED(nullsafe < 0)) return;
			if (!nullsafe) {
				nullsafe = ptoh::isInstance(armExpr, PT_CLASS_NULLSAFE_PROPERTY_FETCH);
				if (UNEXPECTED(nullsafe < 0)) return;
			}
			if (nullsafe) {
				types.intoReturnValue(return_value);
				return;
			}
		}

		zend_object *boolContext = value ? pt_type_specifier_context_create_true() : pt_type_specifier_context_create_false();
		if (UNEXPECTED(boolContext == NULL)) return;
		zv::Val negatedBoolContext;
		zval armContext;
		if (identicalTrue) {
			ZVAL_OBJ(&armContext, boolContext);
		} else {
			negatedBoolContext = pt_type_specifier_context_negate(boolContext);
			if (UNEXPECTED(negatedBoolContext.isUndef())) return;
			ZVAL_COPY_VALUE(&armContext, negatedBoolContext.raw());
		}
		zv::Val armTypes = pt_expression_result_get_specified_types_for_scope(armResult, &argv[0], &armContext);
		if (UNEXPECTED(armTypes.isUndef())) return;
		if (UNEXPECTED(Z_TYPE_P(types.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function unionWith() on %s", zend_zval_value_name(types.raw()));
			return;
		}
		zv::Val unioned = pt_specified_types_union_with(Z_OBJ_P(types.raw()), armTypes.raw());
		if (UNEXPECTED(unioned.isUndef())) return;
		unioned.intoReturnValue(return_value);
	}

	/* static function (bool $nativeTypesPromoted) use ($armResult,
	 * $processingScope, $isIdentical, $value): Type — the exact arm operand's
	 * verdict; captures: $armResult, $processingScope, $isIdentical, $value */
	static void exactArmTypeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptoh::requireArgs(argc, 1, closureName))) return;
		bool isIdentical = Z_TYPE(captures[2]) == IS_TRUE;
		bool value = Z_TYPE(captures[3]) == IS_TRUE;
		zv::Val armType = pt_expression_result_get_type_on_scope(&captures[0], &captures[1], zend_is_true(&argv[0]));
		if (UNEXPECTED(armType.isUndef())) return;
		if (UNEXPECTED(Z_TYPE_P(armType.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function %s() on %s", value ? "isTrue" : "isFalse", zend_zval_value_name(armType.raw()));
			return;
		}
		zend_long matches = value ? pt_type_call_trinary(Z_OBJ_P(armType.raw()), PT_LC("istrue"), 0, NULL) : pt_type_call_trinary(Z_OBJ_P(armType.raw()), PT_LC("isfalse"), 0, NULL);
		if (UNEXPECTED(matches < 0)) return;
		if (!isIdentical) {
			matches = matches == PT_TRI_YES ? PT_TRI_NO : (matches == PT_TRI_NO ? PT_TRI_YES : matches);
		}
		zv::Val verdict;
		if (matches == PT_TRI_YES) {
			verdict = ptoh::constantBoolean(true);
		} else if (matches == PT_TRI_NO) {
			verdict = ptoh::constantBoolean(false);
		} else {
			verdict = ptoh::booleanType();
		}
		if (UNEXPECTED(verdict.isUndef())) return;
		verdict.intoReturnValue(return_value);
	}

	/* static fn (): MutatingScope => $evaluatedScope->applySpecifiedTypes(
	 * $types($evaluatedScope, <ctx>)) — the exact arm operand's branch scope;
	 * captures: $evaluatedScope, $types, the truthy / falsey context */
	static void derivedScopeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) argc;
		(void) argv;
		zv::Args typesArgv{&captures[0], &captures[2]};
		zv::Val types = pt_type_call_callable(&captures[1], 2, typesArgv);
		if (UNEXPECTED(types.isUndef())) return;
		zv::Val derivedScope = pt_mutating_scope_apply_specified_types(Z_OBJ(captures[0]), types.raw());
		if (UNEXPECTED(derivedScope.isUndef())) return;
		derivedScope.intoReturnValue(return_value);
	}

	/* static function () use ($scope, $aTypes, &$aFalseyScope): MutatingScope
	 * => $aFalseyScope ??= $scope->applySpecifiedTypes($aTypes($scope,
	 * TypeSpecifierContext::createFalsey())) — captures: $scope, $aTypes,
	 * &$aFalseyScope */
	static void aFalseyScopeThunkBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) argc;
		(void) argv;
		zval *memo = &captures[2];
		ZVAL_DEREF(memo);
		if (Z_TYPE_P(memo) != IS_NULL && Z_TYPE_P(memo) != IS_UNDEF) {
			ZVAL_COPY(return_value, memo);
			return;
		}
		zend_object *falsey = pt_type_specifier_context_create_falsey();
		if (UNEXPECTED(falsey == NULL)) return;
		zv::Args typesArgv{&captures[0], falsey};
		zv::Val types = pt_type_call_callable(&captures[1], 2, typesArgv);
		if (UNEXPECTED(types.isUndef())) return;
		zv::Val falseyScope = pt_mutating_scope_apply_specified_types(Z_OBJ(captures[0]), types.raw());
		if (UNEXPECTED(falseyScope.isUndef())) return;
		memo = &captures[2];
		ZVAL_DEREF(memo);
		ZVAL_COPY(return_value, falseyScope.raw());
		zv::Ref(memo).assign(std::move(falseyScope));
	}
};

} // namespace phpstanturbo

using phpstanturbo::TernaryHandler;

zv::Val pt_ternary_handler_get_captured_results(zval *handler, zval *expr)
{
	if (EXPECTED(Z_OBJCE_P(handler) == pt_ce_ternary_handler)) return TernaryHandler(Z_OBJ_P(handler)).getCapturedResults(expr);
	return pt_type_call(Z_OBJ_P(handler), PT_LC("getcapturedresults"), 1, expr);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_ternary_handler)
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\TernaryHandler");
	ptdecl::TernaryHandler::declareClass(cls);
	ptdecl::TernaryHandler::declareProperties(cls);

	cls.method(sigs::resetFileAnalysisState, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		TernaryHandler(Z_OBJ_P(ZEND_THIS)).resetFileAnalysisState();
	});

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expressionResultFactory, *defaultNarrowingHelper, *booleanNarrowingHelper;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, expressionResultFactory, defaultNarrowingHelper, booleanNarrowingHelper)) RETURN_THROWS();
		TernaryHandler(Z_OBJ_P(ZEND_THIS)).construct(expressionResultFactory, defaultNarrowingHelper, booleanNarrowingHelper);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!TernaryHandler::supports(expr, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.method(sigs::getCapturedResults, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		PT_RETURN_VAL(TernaryHandler(Z_OBJ_P(ZEND_THIS)).getCapturedResults(expr));
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
		PT_RETURN_VAL(TernaryHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_ternary_handler);
	pt_expr_handler_entry_register(&pt_ce_ternary_handler, &TernaryHandler::processExprEntry);
}

/* }}} */
