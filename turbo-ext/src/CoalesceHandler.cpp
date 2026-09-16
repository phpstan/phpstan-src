/*
 * PHPStanTurbo\CoalesceHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\CoalesceHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback ($this, $nodeScopeResolver,
 * $expr, $condResult, $rightResult, $beforeScope, $chainResults), the
 * specifyTypesCallback and the createTypesCallback ($this, $expr,
 * $condResult, $rightResult, $beforeScope each), and the isSet() verdict
 * callback of the left side's resolution (nothing).
 *
 * NodeScopeResolver, NonNullabilityHelper, ExpressionResult,
 * ExpressionContext, MutatingScope, VariableFlow, SpecifiedTypes,
 * TypeSpecifierContext, DefaultNarrowingHelper, CoalesceCompositionHelper and
 * the Type kernel are called through their direct entries; the value classes
 * that stay PHP for now (EnsuredNonNullabilityResult's slots, the virtual
 * CoalesceExpressionNode, IssetabilityResolution::isSet()) through property
 * sites, the class map and a cached method site.
 */

#include "support.h"
#include "generated/CoalesceHandler.h"

namespace slots = ptdecl::CoalesceHandler::slot;
namespace sigs = ptdecl::CoalesceHandler::sig;
#include "OperatorHandlers.h"

zend_class_entry *pt_ce_coalesce_handler = nullptr;

namespace {

pt_method_site pt_ch_is_set_site;
pt_property_site pt_ch_ensured_scope_site;
pt_property_site pt_ch_ensured_specified_expressions_site;

/* $resolution->isSet($typeCallback): the ?bool verdict */
zv::Val resolutionIsSet(zval *resolution, zval *typeCallback)
{
	if (UNEXPECTED(Z_TYPE_P(resolution) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function isSet() on %s", zend_zval_value_name(resolution));
		return zv::Val();
	}
	return pt_call_method_cached(pt_ch_is_set_site, Z_OBJ_P(resolution), PT_LC("isset"), 1, typeCallback);
}

/* $ensuredNonNullabilityResult->getScope() / ->getSpecifiedExpressions() of
 * the final PHP value class: its promoted slots (borrowed); NULL with the
 * engine's Error pending */
zval *ensuredSlot(pt_property_site &site, zval *result, const char *name, size_t len, const char *getter)
{
	if (UNEXPECTED(Z_TYPE_P(result) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", getter, zend_zval_value_name(result));
		return NULL;
	}
	zval *value = pt_property_cached(site, Z_OBJ_P(result), name, len);
	if (EXPECTED(value != NULL && Z_TYPE_P(value) != IS_UNDEF)) return value;
	zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(Z_OBJCE_P(result)->name), name);
	return NULL;
}

/* a TypeSpecifierContext singleton as a zval (borrowed) */
inline zval objectZval(zend_object *object)
{
	zval z;
	ZVAL_OBJ(&z, object);
	return z;
}

/* the literal of the virtual node, a permanent interned string (module startup) */
zend_string *pt_ch_operator_description = nullptr;

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\CoalesceHandler; UNDEF = pending
 * exception. */
class CoalesceHandler
{
public:
	explicit CoalesceHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *nonNullabilityHelper, zval *expressionResultFactory, zval *defaultNarrowingHelper, zval *coalesceCompositionHelper) const
	{
		pt_write_slot(self, slots::nonNullabilityHelper, nonNullabilityHelper);
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
		pt_write_slot(self, slots::coalesceCompositionHelper, coalesceCompositionHelper);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		int is = ptoh::isInstance(expr, PT_CLASS_COALESCE_EXPR);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scopeArg, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *beforeScope = scopeArg;
		zval *nonNullabilityHelper = OBJ_PROP_NUM(self, slots::nonNullabilityHelper);
		zval *defaultNarrowingHelper = OBJ_PROP_NUM(self, slots::defaultNarrowingHelper);
		zval *coalesceCompositionHelper = OBJ_PROP_NUM(self, slots::coalesceCompositionHelper);

		zval *left = ptoh::binaryOpLeft(expr);
		if (UNEXPECTED(left == NULL)) return zv::Val();
		zv::Val nonNullabilityResult = pt_non_nullability_helper_ensure_non_nullability(nonNullabilityHelper, scopeArg, left);
		if (UNEXPECTED(nonNullabilityResult.isUndef())) return zv::Val();
		zval *ensuredScope = ensuredSlot(pt_ch_ensured_scope_site, nonNullabilityResult.raw(), PT_LC("scope"), "getScope");
		if (UNEXPECTED(ensuredScope == NULL)) return zv::Val();
		zv::Val condScope = pt_node_scope_resolver_look_for_set_allowed_undefined_expressions(nodeScopeResolver, ensuredScope, left);
		if (UNEXPECTED(condScope.isUndef())) return zv::Val();
		zv::Val condContext = pt_expression_context_enter_deep(context);
		if (UNEXPECTED(condContext.isUndef())) return zv::Val();
		zv::Val condResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, left, condScope.raw(), storage, nodeCallback, condContext.raw());
		if (UNEXPECTED(condResult.isUndef())) return zv::Val();
		zv::Val hold;
		zval *condResultScope = pt_expression_result_scope(condResult.raw(), hold);
		if (UNEXPECTED(condResultScope == NULL)) return zv::Val();
		zval *specifiedExpressions = ensuredSlot(pt_ch_ensured_specified_expressions_site, nonNullabilityResult.raw(), PT_LC("specifiedExpressions"), "getSpecifiedExpressions");
		if (UNEXPECTED(specifiedExpressions == NULL)) return zv::Val();
		zv::Val scope = pt_non_nullability_helper_revert_non_nullability(nonNullabilityHelper, condResultScope, specifiedExpressions);
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		left = ptoh::binaryOpLeft(expr);
		if (UNEXPECTED(left == NULL)) return zv::Val();
		scope = pt_node_scope_resolver_look_for_unset_allowed_undefined_expressions(nodeScopeResolver, scope.raw(), left);
		if (UNEXPECTED(scope.isUndef())) return zv::Val();

		zval chainResults;
		ZVAL_NEW_REF(&chainResults, &EG(uninitialized_zval));
		ZVAL_EMPTY_ARRAY(Z_REFVAL(chainResults));
		zv::Val chainResultsHold = zv::Val::adopt(chainResults);
		left = ptoh::binaryOpLeft(expr);
		if (UNEXPECTED(left == NULL)) return zv::Val();
		if (UNEXPECTED(!pt_default_narrowing_helper_capture_chain_results(defaultNarrowingHelper, left, storage, chainResultsHold.raw()))) return zv::Val();
		zval *chainResultsArray = Z_REFVAL_P(chainResultsHold.raw());

		// the falsey narrowing of this very node - asking the scope about it
		// mid-processing would take the on-demand path and recurse
		zend_object *falsey = pt_type_specifier_context_create_falsey();
		if (UNEXPECTED(falsey == NULL)) return zv::Val();
		zval falseyZval = objectZval(falsey);
		left = ptoh::binaryOpLeft(expr);
		if (UNEXPECTED(left == NULL)) return zv::Val();
		zv::Val rightSideSpecifiedTypes = pt_coalesce_composition_helper_get_falsey_specified_types(coalesceCompositionHelper, scope.raw(), scope.raw(), left, condResult.raw(), expr, &falseyZval);
		if (UNEXPECTED(rightSideSpecifiedTypes.isUndef())) return zv::Val();
		zv::Val resolution = pt_expression_result_get_issetability_resolution(condResult.raw(), scope.raw(), false, false);
		if (UNEXPECTED(resolution.isUndef())) return zv::Val();
		zv::Val notNullVerdict = pt_native_closure(&notNullVerdictBody);
		zv::Val leftIsSet = resolutionIsSet(resolution.raw(), notNullVerdict.raw());
		if (UNEXPECTED(leftIsSet.isUndef())) return zv::Val();
		bool leftSurelySetNonNull = Z_TYPE_P(leftIsSet.raw()) == IS_TRUE;
		if (!leftSurelySetNonNull) {
			// the right side only evaluates when the left side is null or unset -
			// the falsey isset() narrowing of the left side, like `??=`; skipped
			// when the right side cannot evaluate at all, so its counterfactual
			// certainty reductions do not survive the merge below
			if (UNEXPECTED(Z_TYPE_P(rightSideSpecifiedTypes.raw()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function unionWith() on %s", zend_zval_value_name(rightSideSpecifiedTypes.raw()));
				return zv::Val();
			}
			left = ptoh::binaryOpLeft(expr);
			if (UNEXPECTED(left == NULL)) return zv::Val();
			zv::Val rightSideScopeTypes = pt_coalesce_composition_helper_get_right_side_scope_specified_types(coalesceCompositionHelper, scope.raw(), left, condResult.raw(), chainResultsArray, expr);
			if (UNEXPECTED(rightSideScopeTypes.isUndef())) return zv::Val();
			rightSideSpecifiedTypes = pt_specified_types_union_with(Z_OBJ_P(rightSideSpecifiedTypes.raw()), rightSideScopeTypes.raw());
			if (UNEXPECTED(rightSideSpecifiedTypes.isUndef())) return zv::Val();
		}
		zv::Val rightScope = pt_mutating_scope_apply_specified_types(Z_OBJ_P(scope.raw()), rightSideSpecifiedTypes.raw());
		if (UNEXPECTED(rightScope.isUndef())) return zv::Val();
		zval *right = ptoh::binaryOpRight(expr);
		if (UNEXPECTED(right == NULL)) return zv::Val();
		zv::Val rightContext = pt_expression_context_enter_deep_keeping_value_flow(context);
		if (UNEXPECTED(rightContext.isUndef())) return zv::Val();
		zv::Val rightResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, right, rightScope.raw(), storage, nodeCallback, rightContext.raw());
		if (UNEXPECTED(rightResult.isUndef())) return zv::Val();
		// the left-is-set narrowing, composed from the already-processed chain
		// results - the inside-out equivalent of narrowing by isset($expr->left)
		// without synthesizing an Isset_ node and re-walking the chain on demand
		left = ptoh::binaryOpLeft(expr);
		if (UNEXPECTED(left == NULL)) return zv::Val();
		zv::Val readType = pt_default_narrowing_helper_build_chain_type_reader(defaultNarrowingHelper, chainResultsArray, scope.raw());
		if (UNEXPECTED(readType.isUndef())) return zv::Val();
		zend_object *truthy = pt_type_specifier_context_create_truthy();
		if (UNEXPECTED(truthy == NULL)) return zv::Val();
		zval truthyZval = objectZval(truthy);
		zv::Val leftIssetTypes = pt_default_narrowing_helper_create_isset_truthy_chain_types(defaultNarrowingHelper, scope.raw(), left, readType.raw(), expr, &truthyZval);
		if (UNEXPECTED(leftIssetTypes.isUndef())) return zv::Val();

		zv::Val rightExprType = pt_expression_result_get_type(rightResult.raw());
		if (UNEXPECTED(rightExprType.isUndef())) return zv::Val();
		bool explicitNever;
		if (UNEXPECTED(!ptoh::isExplicitNever(rightExprType.raw(), explicitNever))) return zv::Val();
		zv::Val leftIsSetScope = pt_mutating_scope_apply_specified_types(Z_OBJ_P(scope.raw()), leftIssetTypes.raw());
		if (UNEXPECTED(leftIsSetScope.isUndef())) return zv::Val();
		zval *rightResultScope = pt_expression_result_scope(rightResult.raw(), hold);
		if (UNEXPECTED(rightResultScope == NULL)) return zv::Val();
		if (UNEXPECTED(!leftIsSetScope.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function %s() on %s", explicitNever ? "addTemplateArgumentConstraints" : "mergeWith", zend_zval_value_name(leftIsSetScope.raw()));
			return zv::Val();
		}
		if (explicitNever) {
			zv::Val constraints = pt_mutating_scope_get_template_argument_constraints(Z_OBJ_P(rightResultScope));
			if (UNEXPECTED(constraints.isUndef())) return zv::Val();
			scope = pt_mutating_scope_add_template_argument_constraints(Z_OBJ_P(leftIsSetScope.raw()), constraints.raw());
		} else {
			scope = pt_mutating_scope_merge_with(Z_OBJ_P(leftIsSetScope.raw()), rightResultScope);
		}
		if (UNEXPECTED(scope.isUndef())) return zv::Val();

		{
			zval description;
			ZVAL_INTERNED_STR(&description, pt_ch_operator_description);
			zv::Args nodeArgv{expr, condResult.raw(), rightResult.raw(), &description};
			zv::Val node = pt_type_new(PT_CLASS_COALESCE_EXPRESSION_NODE, 4, nodeArgv);
			if (UNEXPECTED(node.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback_with_expression(nodeScopeResolver, nodeCallback, node.raw(), beforeScope, storage, context))) return zv::Val();
		}

		zv::Val variableFlow;
		{
			zv::Val condFlow = pt_expression_result_variable_flow(condResult.raw());
			if (UNEXPECTED(condFlow.isUndef())) return zv::Val();
			zv::Val rightFlow = pt_expression_result_variable_flow(rightResult.raw());
			if (UNEXPECTED(rightFlow.isUndef())) return zv::Val();
			zv::Args choiceArgv{rightFlow.raw(), zv::null};
			zv::Val choice = pt_variable_flow_choice(2, choiceArgv);
			if (UNEXPECTED(choice.isUndef())) return zv::Val();
			zv::Args sequenceArgv{condFlow.raw(), choice.raw()};
			variableFlow = pt_variable_flow_sequence(2, sequenceArgv);
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		}
		bool hasYield, isAlwaysTerminating;
		if (UNEXPECTED(!pt_expression_result_has_yield(condResult.raw(), hasYield))) return zv::Val();
		if (!hasYield && UNEXPECTED(!pt_expression_result_has_yield(rightResult.raw(), hasYield))) return zv::Val();
		if (UNEXPECTED(!pt_expression_result_is_always_terminating(condResult.raw(), isAlwaysTerminating))) return zv::Val();
		zv::Val condHold, rightHold;
		zval *condPoints = pt_expression_result_throw_points(condResult.raw(), condHold);
		if (UNEXPECTED(condPoints == NULL)) return zv::Val();
		zval *rightPoints = pt_expression_result_throw_points(rightResult.raw(), rightHold);
		if (UNEXPECTED(rightPoints == NULL)) return zv::Val();
		zv::Val throwPoints = ptoh::arrayMerge(condPoints, rightPoints);
		condPoints = pt_expression_result_impure_points(condResult.raw(), condHold);
		if (UNEXPECTED(condPoints == NULL)) return zv::Val();
		rightPoints = pt_expression_result_impure_points(rightResult.raw(), rightHold);
		if (UNEXPECTED(rightPoints == NULL)) return zv::Val();
		zv::Val impurePoints = ptoh::arrayMerge(condPoints, rightPoints);

		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, self, nodeScopeResolver, expr, condResult.raw(), rightResult.raw(), beforeScope, chainResultsArray);
		zv::Val specifyTypesCallback = pt_native_closure(&specifyTypesCallbackBody, self, expr, condResult.raw(), rightResult.raw(), beforeScope);
		zv::Val createTypesCallback = pt_native_closure(&createTypesCallbackBody, self, expr, condResult.raw(), rightResult.raw(), beforeScope);
		pt_expression_result_args args(scope.raw(), beforeScope, expr, hasYield, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw()).withCreateTypesCallback(createTypesCallback.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return CoalesceHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	static constexpr const char *closureName = "PHPStan\\Analyser\\ExprHandler\\CoalesceHandler::{closure}";

	/* static function (Type $type): ?bool — the "set and not null" verdict:
	 * null when the type may be null, !isNull()->yes() otherwise */
	static void notNullVerdictBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) captures;
		if (UNEXPECTED(!ptoh::requireArgs(argc, 1, closureName))) return;
		if (UNEXPECTED(Z_TYPE(argv[0]) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function isNull() on %s", zend_zval_value_name(&argv[0]));
			return;
		}
		zend_long isNull = pt_type_op_trinary(Z_OBJ(argv[0]), PT_OP_IS_NULL, 0, NULL);
		if (UNEXPECTED(isNull < 0)) return;
		if (isNull == PT_TRI_MAYBE) return;
		ZVAL_BOOL(return_value, isNull != PT_TRI_YES);
	}

	/* fn (bool $nativeTypesPromoted): Type =>
	 * $this->coalesceCompositionHelper->composeType($nodeScopeResolver,
	 * $expr->left, $condResult, $rightResult, $beforeScope, $chainResults,
	 * $expr, $nativeTypesPromoted) — captures: $this, $nodeScopeResolver,
	 * $expr, $condResult, $rightResult, $beforeScope, $chainResults */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptoh::requireArgs(argc, 1, closureName))) return;
		zval *left = ptoh::binaryOpLeft(&captures[2]);
		if (UNEXPECTED(left == NULL)) return;
		zv::Val type = pt_coalesce_composition_helper_compose_type(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::coalesceCompositionHelper), &captures[1], left, &captures[3], &captures[4], &captures[5], &captures[6], &captures[2], zend_is_true(&argv[0]));
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* function (TypeSpecifierContext $context, bool $nativeTypesPromoted) use
	 * ($expr, $condResult, $rightResult, $beforeScope): SpecifiedTypes —
	 * captures: $this, $expr, $condResult, $rightResult, $beforeScope */
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
		zval *condResult = &captures[2];
		zval *rightResult = &captures[3];
		zval *beforeScope = &captures[4];

		bool contextNull;
		if (UNEXPECTED(!pt_type_specifier_context_null(Z_OBJ_P(context), contextNull))) return zv::Val();
		if (contextNull) return pt_default_narrowing_helper_specify_default_types(OBJ_PROP_NUM(handler, slots::defaultNarrowingHelper), expr, context);

		zv::Val s = nativeTypesPromoted ? pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(beforeScope)) : zv::Val::copyOf(zv::Ref(beforeScope));
		if (UNEXPECTED(s.isUndef())) return zv::Val();
		bool contextTrue;
		if (UNEXPECTED(!pt_type_specifier_context_true(Z_OBJ_P(context), contextTrue))) return zv::Val();
		if (!contextTrue) {
			zval *left = ptoh::binaryOpLeft(expr);
			if (UNEXPECTED(left == NULL)) return zv::Val();
			return pt_coalesce_composition_helper_get_falsey_specified_types(OBJ_PROP_NUM(handler, slots::coalesceCompositionHelper), s.raw(), s.raw(), left, condResult, expr, context);
		}

		bool contextFalsey;
		if (UNEXPECTED(!pt_type_specifier_context_falsey(Z_OBJ_P(context), contextFalsey))) return zv::Val();
		if (!contextFalsey) {
			zv::Val falseType = ptoh::constantBoolean(false);
			if (UNEXPECTED(falseType.isUndef())) return zv::Val();
			zv::Val rightType = nativeTypesPromoted ? pt_expression_result_get_native_type(rightResult) : pt_expression_result_get_type(rightResult);
			if (UNEXPECTED(rightType.isUndef())) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(rightType.raw()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function toBoolean() on %s", zend_zval_value_name(rightType.raw()));
				return zv::Val();
			}
			zv::Val rightBoolean = Z_OBJCE_P(rightType.raw()) == pt_ce_constant_boolean_type ? zv::Val::copyOf(rightType.ref()) : pt_type_call(Z_OBJ_P(rightType.raw()), PT_LC("toboolean"), 0, NULL);
			if (UNEXPECTED(rightBoolean.isUndef())) return zv::Val();
			zv::Val superType = pt_type_op(Z_OBJ_P(falseType.raw()), PT_OP_IS_SUPER_TYPE_OF, 1, rightBoolean.raw());
			if (UNEXPECTED(superType.isUndef())) return zv::Val();
			zend_long verdict = pt_type_result_trinary(superType.raw());
			if (UNEXPECTED(verdict < 0)) return zv::Val();
			if (verdict == PT_TRI_YES) {
				zval *left = ptoh::binaryOpLeft(expr);
				if (UNEXPECTED(left == NULL)) return zv::Val();
				zval nullType;
				if (UNEXPECTED(!pt_null_type_new(&nullType))) return zv::Val();
				zv::Val nullTypeHold = zv::Val::adopt(nullType);
				zend_object *falseContext = pt_type_specifier_context_create_false();
				if (UNEXPECTED(falseContext == NULL)) return zv::Val();
				zval falseContextZval = objectZval(falseContext);
				zv::Val subjectTypes = pt_default_narrowing_helper_create_subject_types(OBJ_PROP_NUM(handler, slots::defaultNarrowingHelper), s.raw(), left, condResult, nullTypeHold.raw(), &falseContextZval);
				if (UNEXPECTED(subjectTypes.isUndef())) return zv::Val();
				if (UNEXPECTED(Z_TYPE_P(subjectTypes.raw()) != IS_OBJECT)) {
					zend_throw_error(NULL, "Call to a member function setRootExpr() on %s", zend_zval_value_name(subjectTypes.raw()));
					return zv::Val();
				}
				return pt_specified_types_set_root_expr(Z_OBJ_P(subjectTypes.raw()), expr);
			}
		}

		// The Coalesce condition matched but produced no narrowing; the legacy
		// if/elseif chain fell through to its empty-SpecifiedTypes tail here,
		// not to the truthy/falsey default.
		return pt_specified_types_new_with_root_expr(NULL, NULL, expr);
	}

	/* function (Type $type, TypeSpecifierContext $context, bool
	 * $nativeTypesPromoted) use ($expr, $condResult, $rightResult,
	 * $beforeScope): SpecifiedTypes — captures: $this, $expr, $condResult,
	 * $rightResult, $beforeScope */
	static void createTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptoh::requireArgs(argc, 3, closureName))) return;
		zv::Val specifiedTypes = createTypes(captures, &argv[0], &argv[1], zend_is_true(&argv[2]));
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}

	static zv::Val createTypes(zval *captures, zval *type, zval *context, bool nativeTypesPromoted)
	{
		zend_object *handler = Z_OBJ(captures[0]);
		zval *expr = &captures[1];
		zval *condResult = &captures[2];
		zval *rightResult = &captures[3];
		zval *beforeScope = &captures[4];
		zval *defaultNarrowingHelper = OBJ_PROP_NUM(handler, slots::defaultNarrowingHelper);

		zv::Val s = nativeTypesPromoted ? pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(beforeScope)) : zv::Val::copyOf(zv::Ref(beforeScope));
		if (UNEXPECTED(s.isUndef())) return zv::Val();
		bool contextNull;
		if (UNEXPECTED(!pt_type_specifier_context_null(Z_OBJ_P(context), contextNull))) return zv::Val();
		if (!contextNull) {
			zv::Val rightType = nativeTypesPromoted ? pt_expression_result_get_native_type(rightResult) : pt_expression_result_get_type(rightResult);
			if (UNEXPECTED(rightType.isUndef())) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function isSuperTypeOf() on %s", zend_zval_value_name(type));
				return zv::Val();
			}
			bool rulesRightSide = false;
			bool contextTrue;
			if (UNEXPECTED(!pt_type_specifier_context_true(Z_OBJ_P(context), contextTrue))) return zv::Val();
			if (contextTrue) {
				zv::Val result = pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUPER_TYPE_OF, 1, rightType.raw());
				if (UNEXPECTED(result.isUndef())) return zv::Val();
				zend_long verdict = pt_type_result_trinary(result.raw());
				if (UNEXPECTED(verdict < 0)) return zv::Val();
				rulesRightSide = verdict == PT_TRI_NO;
			}
			if (!rulesRightSide) {
				bool contextFalse;
				if (UNEXPECTED(!pt_type_specifier_context_false(Z_OBJ_P(context), contextFalse))) return zv::Val();
				if (contextFalse) {
					zv::Val result = pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUPER_TYPE_OF, 1, rightType.raw());
					if (UNEXPECTED(result.isUndef())) return zv::Val();
					zend_long verdict = pt_type_result_trinary(result.raw());
					if (UNEXPECTED(verdict < 0)) return zv::Val();
					rulesRightSide = verdict == PT_TRI_YES;
				}
			}
			if (rulesRightSide) {
				// the coalesce's own key is emitted alongside the left-side
				// narrowing (createForExpr's double-key, like the nullsafe
				// handlers) - consumers summing the checked expression's own
				// entry (ImpossibleCheckTypeHelper) rely on it
				zval *left = ptoh::binaryOpLeft(expr);
				if (UNEXPECTED(left == NULL)) return zv::Val();
				zv::Val leftTypes = pt_default_narrowing_helper_create_subject_types(defaultNarrowingHelper, s.raw(), left, condResult, type, context);
				if (UNEXPECTED(leftTypes.isUndef())) return zv::Val();
				if (UNEXPECTED(Z_TYPE_P(leftTypes.raw()) != IS_OBJECT)) {
					zend_throw_error(NULL, "Call to a member function unionWith() on %s", zend_zval_value_name(leftTypes.raw()));
					return zv::Val();
				}
				zv::Val ownTypes = pt_default_narrowing_helper_create_subject_types(defaultNarrowingHelper, s.raw(), expr, NULL, type, context);
				if (UNEXPECTED(ownTypes.isUndef())) return zv::Val();
				return pt_specified_types_union_with(Z_OBJ_P(leftTypes.raw()), ownTypes.raw());
			}
		}

		return pt_default_narrowing_helper_create_subject_types(defaultNarrowingHelper, s.raw(), expr, NULL, type, context);
	}
};

} // namespace phpstanturbo

using phpstanturbo::CoalesceHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_coalesce_handler()
{
	pt_ch_operator_description = zend_string_init_interned(PT_LC("on left side of ??"), 1);

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\CoalesceHandler");
	ptdecl::CoalesceHandler::declareClass(cls);
	ptdecl::CoalesceHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nonNullabilityHelper, *expressionResultFactory, *defaultNarrowingHelper, *coalesceCompositionHelper;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, nonNullabilityHelper, expressionResultFactory, defaultNarrowingHelper, coalesceCompositionHelper)) RETURN_THROWS();
		CoalesceHandler(Z_OBJ_P(ZEND_THIS)).construct(nonNullabilityHelper, expressionResultFactory, defaultNarrowingHelper, coalesceCompositionHelper);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!CoalesceHandler::supports(expr, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(CoalesceHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_coalesce_handler);
	pt_expr_handler_entry_register(&pt_ce_coalesce_handler, &CoalesceHandler::processExprEntry);
}

/* }}} */
