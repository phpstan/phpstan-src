/*
 * PHPStanTurbo\NullsafePropertyFetchHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\NullsafePropertyFetchHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback ($exprResult,
 * $receiverType), the specifyTypesCallback ($this, $expr, $propertyFetch,
 * $exprResult, $receiverResult, $nonNullabilityResult, $beforeScope,
 * $nodeScopeResolver and the by-reference $leftFalseyScope memo — a reference
 * zval shared with the left-falsey-scope closure), the createTypesCallback
 * ($this, $expr, $propertyFetch, $exprResult, $receiverResult,
 * $nullsafeTypeCallback, $beforeScope) and, inside the specifyTypesCallback,
 * the $leftTypes / $rightTypes / scope closures handed to
 * BooleanNarrowingHelper::specifyConjunction().
 *
 * NodeScopeResolver, NonNullabilityHelper, MutatingScope, ExpressionResult,
 * ExpressionContext, VariableFlow, SpecifiedTypes, TypeSpecifierContext,
 * DefaultNarrowingHelper, BooleanNarrowingHelper, EnsuredNonNullabilityResult,
 * TypeCombinator and the Type kernel are called through their direct entries;
 * NodeAbstract::getAttributes() through the cached method site in the block
 * below.
 */

#include "support.h"
#include "generated/NullsafePropertyFetchHandler.h"

namespace slots = ptdecl::NullsafePropertyFetchHandler::slot;
namespace sigs = ptdecl::NullsafePropertyFetchHandler::sig;
#include "CallHandlerSupport.h"

zend_class_entry *pt_ce_nullsafe_property_fetch_handler = nullptr;

namespace {

using namespace ptcall;

constexpr const char *pt_npfh_closure_name = "PHPStan\\Analyser\\ExprHandler\\NullsafePropertyFetchHandler::{closure}";

/* {{{ the PHP collaborators (one site each; switch to their direct entries
 * once they are ported) */

pt_method_site pt_npfh_get_attributes_site;

/* $nonNullabilityResult->getScope() (EnsuredNonNullabilityResult.cpp) */
zv::Val ensuredResultScope(zval *result)
{
	if (UNEXPECTED(Z_TYPE_P(result) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function getScope() on %s", zend_zval_value_name(result));
		return zv::Val();
	}
	zv::Val hold;
	zval *scope = pt_ensured_non_nullability_result_scope(result, hold);
	if (UNEXPECTED(scope == NULL)) return zv::Val();
	return hold.isUndef() ? zv::Val::copyOf(zv::Ref(scope)) : std::move(hold);
}

/* $nonNullabilityResult->getSpecifiedExpressions() */
zv::Val ensuredResultSpecifiedExpressions(zval *result)
{
	if (UNEXPECTED(Z_TYPE_P(result) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function getSpecifiedExpressions() on %s", zend_zval_value_name(result));
		return zv::Val();
	}
	zv::Val hold;
	zval *specifiedExpressions = pt_ensured_non_nullability_result_specified_expressions(result, hold);
	if (UNEXPECTED(specifiedExpressions == NULL)) return zv::Val();
	return hold.isUndef() ? zv::Val::copyOf(zv::Ref(specifiedExpressions)) : std::move(hold);
}

/* $expr->getAttributes() */
zv::Val nodeGetAttributes(zval *node)
{
	return pt_call_method_cached(pt_npfh_get_attributes_site, Z_OBJ_P(node), PT_LC("getattributes"), 0, NULL);
}

/* }}} */

/* {{{ the PhpParser nodes' properties */

pt_property_site pt_npfh_var_site;
pt_property_site pt_npfh_name_site;

zval *exprVar(zval *expr) { return nodeProperty(pt_npfh_var_site, expr, PT_LC("var")); }
zval *exprName(zval *expr) { return nodeProperty(pt_npfh_name_site, expr, PT_LC("name")); }

/* }}} */

/* {{{ small value helpers */

/* new NullType() */
zv::Val newNullType()
{
	zval out;
	if (UNEXPECTED(!pt_null_type_new(&out))) return zv::Val();
	return zv::Val::adopt(out);
}

/* the TypeSpecifierContext argument's flag; false = pending exception */
[[nodiscard]] bool contextFlag(zval *context, bool (*read)(zend_object *, bool &), const char *method, bool &out)
{
	if (UNEXPECTED(Z_TYPE_P(context) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(context));
		return false;
	}
	return read(Z_OBJ_P(context), out);
}

/* the singleton context as a zval (borrowed: the registry holds it); false =
 * pending exception */
[[nodiscard]] bool contextZval(zend_object *context, zval &out)
{
	if (UNEXPECTED(context == NULL)) return false;
	ZVAL_OBJ(&out, context);
	return true;
}

/* the permanent interned literals (module startup) */
zend_string *pt_npfh_virtual_nullsafe_property_fetch = nullptr;
zend_string *pt_npfh_null = nullptr;
/* ExprPrinter::ATTRIBUTE_CACHE_KEY */
zend_string *pt_npfh_cache_printer_attribute = nullptr;

/* }}} */

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\NullsafePropertyFetchHandler; UNDEF =
 * pending exception. */
class NullsafePropertyFetchHandler
{
public:
	explicit NullsafePropertyFetchHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *nonNullabilityHelper, zval *expressionResultFactory, zval *defaultNarrowingHelper, zval *booleanNarrowingHelper) const
	{
		pt_write_slot(self, slots::nonNullabilityHelper, nonNullabilityHelper);
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
		pt_write_slot(self, slots::booleanNarrowingHelper, booleanNarrowingHelper);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *expr, bool &out) const
	{
		int is = isInstanceOf(expr, PT_CLASS_NULLSAFE_PROPERTY_FETCH);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scopeArg, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *beforeScope = scopeArg;
		zval *nonNullabilityHelper = OBJ_PROP_NUM(self, slots::nonNullabilityHelper);
		zval *var = exprVar(expr);
		if (UNEXPECTED(var == NULL)) return zv::Val();
		zv::Val deepContext = pt_expression_context_enter_deep(context);
		if (UNEXPECTED(deepContext.isUndef())) return zv::Val();
		zv::Val processedReceiverResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, var, scopeArg, storage, nodeCallback, deepContext.raw());
		if (UNEXPECTED(processedReceiverResult.isUndef())) return zv::Val();
		zv::Val hold;
		zval *borrowed = pt_expression_result_scope(processedReceiverResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		zv::Val scope = zv::Val::copyOf(zv::Ref(borrowed));

		var = exprVar(expr);
		if (UNEXPECTED(var == NULL)) return zv::Val();
		zv::Val receiverType = pt_non_nullability_helper_get_active_ensured_original_type(nonNullabilityHelper, var, false);
		if (UNEXPECTED(receiverType.isUndef())) return zv::Val();
		if (receiverType.isNull()) {
			receiverType = pt_expression_result_get_type(processedReceiverResult.raw());
			if (UNEXPECTED(receiverType.isUndef())) return zv::Val();
		}
		var = exprVar(expr);
		if (UNEXPECTED(var == NULL)) return zv::Val();
		zv::Val receiverNativeType = pt_non_nullability_helper_get_active_ensured_original_type(nonNullabilityHelper, var, true);
		if (UNEXPECTED(receiverNativeType.isUndef())) return zv::Val();
		if (receiverNativeType.isNull()) {
			receiverNativeType = pt_expression_result_get_native_type(processedReceiverResult.raw());
			if (UNEXPECTED(receiverNativeType.isUndef())) return zv::Val();
		}
		// carry the receiver type to NullsafePropertyFetchRule
		{
			zv::Args nodeArgv{expr, receiverType.raw(), receiverNativeType.raw()};
			zv::Val node = pt_type_new(PT_CLASS_NULLSAFE_PROPERTY_FETCH_EXPRESSION_NODE, 3, nodeArgv);
			if (UNEXPECTED(node.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback_with_expression(nodeScopeResolver, nodeCallback, node.raw(), beforeScope, storage, context))) return zv::Val();
		}
		var = exprVar(expr);
		if (UNEXPECTED(var == NULL)) return zv::Val();
		zv::Val nonNullabilityResult = pt_non_nullability_helper_ensure_shallow_non_nullability(nonNullabilityHelper, scope.raw(), scope.raw(), var);
		if (UNEXPECTED(nonNullabilityResult.isUndef())) return zv::Val();
		// pre-store the receiver's ensured-position view
		{
			var = exprVar(expr);
			if (UNEXPECTED(var == NULL)) return zv::Val();
			zv::Val ensuredScope = ensuredResultScope(nonNullabilityResult.raw());
			if (UNEXPECTED(ensuredScope.isUndef())) return zv::Val();
			zv::Val atAskPosition = pt_expression_result_at_ask_position(processedReceiverResult.raw(), ensuredScope.raw());
			if (UNEXPECTED(atAskPosition.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_store_expression_result(nodeScopeResolver, storage, var, atAskPosition.raw()))) return zv::Val();
		}
		zv::Val attributes = virtualAttributes(expr);
		if (UNEXPECTED(attributes.isUndef())) return zv::Val();
		zv::Val propertyFetch;
		{
			var = exprVar(expr);
			if (UNEXPECTED(var == NULL)) return zv::Val();
			zval *name = exprName(expr);
			if (UNEXPECTED(name == NULL)) return zv::Val();
			zv::Args fetchArgv{var, name, attributes.raw()};
			propertyFetch = pt_type_new(PT_CLASS_PROPERTY_FETCH, 3, fetchArgv);
			if (UNEXPECTED(propertyFetch.isUndef())) return zv::Val();
		}
		zv::Val exprResult;
		{
			zv::Val ensuredScope = ensuredResultScope(nonNullabilityResult.raw());
			if (UNEXPECTED(ensuredScope.isUndef())) return zv::Val();
			exprResult = pt_node_scope_resolver_process_expr_node_consuming_stored(nodeScopeResolver, stmt, propertyFetch.raw(), ensuredScope.raw(), storage, nodeCallback, context);
			if (UNEXPECTED(exprResult.isUndef())) return zv::Val();
		}
		{
			borrowed = pt_expression_result_scope(exprResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			zv::Val exprScope = zv::Val::copyOf(zv::Ref(borrowed));
			zv::Val specifiedExpressions = ensuredResultSpecifiedExpressions(nonNullabilityResult.raw());
			if (UNEXPECTED(specifiedExpressions.isUndef())) return zv::Val();
			scope = pt_non_nullability_helper_revert_non_nullability(nonNullabilityHelper, exprScope.raw(), specifiedExpressions.raw());
			if (UNEXPECTED(scope.isUndef())) return zv::Val();
		}

		zv::Val nullsafeTypeCallback = pt_native_closure(&nullsafeTypeCallbackBody, exprResult.raw(), receiverType.raw());

		// the receiver's stored result and the lazily memoized receiver-is-null
		// branch scope (a reference both closures share)
		zval *receiverResult = processedReceiverResult.raw();
		zv::Val leftFalseyScope;
		{
			zval null = {};
			ZVAL_NULL(&null);
			zval reference;
			ZVAL_NEW_REF(&reference, &null);
			leftFalseyScope = zv::Val::adopt(reference);
		}

		zv::Val variableFlow;
		{
			zv::Val exprFlow = pt_expression_result_variable_flow(exprResult.raw());
			if (UNEXPECTED(exprFlow.isUndef())) return zv::Val();
			zv::Val receiverFlow = pt_expression_result_variable_flow(processedReceiverResult.raw());
			if (UNEXPECTED(receiverFlow.isUndef())) return zv::Val();
			zv::Args flows{exprFlow.raw(), receiverFlow.raw()};
			variableFlow = pt_variable_flow_choice(2, flows);
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		}
		bool hasYield;
		if (UNEXPECTED(!pt_expression_result_has_yield(exprResult.raw(), hasYield))) return zv::Val();
		borrowed = pt_expression_result_throw_points(exprResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		zv::Val throwPoints = zv::Val::copyOf(zv::Ref(borrowed));
		borrowed = pt_expression_result_impure_points(exprResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		zv::Val impurePoints = zv::Val::copyOf(zv::Ref(borrowed));

		zv::Val specifyTypesCallback;
		{
			zval captures[9];
			ZVAL_OBJ(&captures[0], self);
			ZVAL_COPY_VALUE(&captures[1], expr);
			ZVAL_COPY_VALUE(&captures[2], propertyFetch.raw());
			ZVAL_COPY_VALUE(&captures[3], exprResult.raw());
			ZVAL_COPY_VALUE(&captures[4], receiverResult);
			ZVAL_COPY_VALUE(&captures[5], nonNullabilityResult.raw());
			ZVAL_COPY_VALUE(&captures[6], beforeScope);
			ZVAL_COPY_VALUE(&captures[7], nodeScopeResolver);
			ZVAL_COPY_VALUE(&captures[8], leftFalseyScope.raw());
			specifyTypesCallback = pt_native_closure_new(&specifyTypesCallbackBody, 9, captures, 1u << 8);
		}
		zv::Val createTypesCallback = pt_native_closure(&createTypesCallbackBody, self, expr, propertyFetch.raw(), exprResult.raw(), receiverResult, nullsafeTypeCallback.raw(), beforeScope);

		pt_expression_result_args args(scope.raw(), beforeScope, expr, hasYield, false, throwPoints.raw(), impurePoints.raw(), nullsafeTypeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw()).withContainsNullsafe(true).withCreateTypesCallback(createTypesCallback.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return NullsafePropertyFetchHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* $attributes = array_merge($expr->getAttributes(), ['virtualNullsafePropertyFetch'
	 * => true]); unset($attributes[ExprPrinter::ATTRIBUTE_CACHE_KEY]); */
	static zv::Val virtualAttributes(zval *expr)
	{
		zv::Val nodeAttributes = nodeGetAttributes(expr);
		if (UNEXPECTED(nodeAttributes.isUndef())) return zv::Val();
		if (UNEXPECTED(!nodeAttributes.ref().isArray())) {
			zend_type_error("array_merge(): Argument #1 must be of type array, %s given", zend_zval_value_name(nodeAttributes.raw()));
			return zv::Val();
		}
		zv::Arr marker = zv::Arr::create(1);
		marker.set(pt_npfh_virtual_nullsafe_property_fetch, zv::Val::boolean(true));
		zv::Val attributes = arrayMerge(nodeAttributes.raw(), marker.raw());
		zval *raw = attributes.raw();
		if (zend_hash_exists(Z_ARRVAL_P(raw), pt_npfh_cache_printer_attribute)) {
			SEPARATE_ARRAY(raw);
			zend_hash_del(Z_ARRVAL_P(raw), pt_npfh_cache_printer_attribute);
		}
		return attributes;
	}

	/* the nullsafe typeCallback's body */
	static zv::Val nullsafeType(zval *exprResult, zval *receiverType, bool nativeTypesPromoted)
	{
		if (UNEXPECTED(Z_TYPE_P(receiverType) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function isNull() on %s", zend_zval_value_name(receiverType));
			return zv::Val();
		}
		zv::Val isNull = pt_type_op(Z_OBJ_P(receiverType), PT_OP_IS_NULL, 0, NULL);
		if (UNEXPECTED(isNull.isUndef())) return zv::Val();
		zend_long isNullValue = pt_type_trinary_value(isNull.raw());
		if (UNEXPECTED(isNullValue < 0)) return zv::Val();
		if (isNullValue == PT_TRI_YES) return newNullType();
		bool containsNull;
		if (UNEXPECTED(!pt_type_combinator_contains_null(receiverType, containsNull))) return zv::Val();
		if (!containsNull) {
			return nativeTypesPromoted ? pt_expression_result_get_native_type(exprResult) : pt_expression_result_get_type(exprResult);
		}

		// the plain fetch was already priced on the ensured (null-removed)
		// scope during processExpr; the short-circuit contributes the null
		zv::Val type = nativeTypesPromoted ? pt_expression_result_get_native_type(exprResult) : pt_expression_result_get_type(exprResult);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		zv::Val nullType = newNullType();
		if (UNEXPECTED(nullType.isUndef())) return zv::Val();
		zv::Args types{type.raw(), nullType.raw()};
		return pt_type_combinator_union(2, types);
	}

	/* static function (bool $nativeTypesPromoted) use ($exprResult,
	 * $receiverType): Type — captures: $exprResult, $receiverType */
	static void nullsafeTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 1, pt_npfh_closure_name))) return;
		zv::Val type = nullsafeType(&captures[0], &captures[1], zend_is_true(&argv[0]));
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* function (TypeSpecifierContext $context, bool $nativeTypesPromoted) use
	 * ($expr, $propertyFetch, $exprResult, $receiverResult,
	 * $nonNullabilityResult, $beforeScope, $nodeScopeResolver,
	 * &$leftFalseyScope): SpecifiedTypes — captures: $this, $expr,
	 * $propertyFetch, $exprResult, $receiverResult, $nonNullabilityResult,
	 * $beforeScope, $nodeScopeResolver, &$leftFalseyScope */
	static void specifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 2, pt_npfh_closure_name))) return;
		zend_object *handler = Z_OBJ(captures[0]);
		zval *expr = &captures[1];
		zval *propertyFetch = &captures[2];
		zval *exprResult = &captures[3];
		zval *receiverResult = &captures[4];
		zval *nonNullabilityResult = &captures[5];
		zval *beforeScope = &captures[6];
		zval *nodeScopeResolver = &captures[7];
		zval *leftFalseyScope = &captures[8];
		zval *context = &argv[0];
		zval *defaultNarrowingHelper = OBJ_PROP_NUM(handler, slots::defaultNarrowingHelper);

		bool isNullContext;
		if (UNEXPECTED(!contextFlag(context, pt_type_specifier_context_null, "null", isNullContext))) return;
		if (isNullContext) {
			zv::Val specifiedTypes = pt_default_narrowing_helper_specify_default_types(defaultNarrowingHelper, expr, context);
			if (UNEXPECTED(specifiedTypes.isUndef())) return;
			specifiedTypes.intoReturnValue(return_value);
			return;
		}

		zv::Val promotedScope;
		zval *s = beforeScope;
		if (zend_is_true(&argv[1])) {
			promotedScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(beforeScope));
			if (UNEXPECTED(promotedScope.isUndef())) return;
			s = promotedScope.raw();
		}

		// `$x?->...` narrows like ($x !== null) && $x->...; the fabricated
		// NotIdentical is only printed into holder keys, never walked
		zval *var = exprVar(expr);
		if (UNEXPECTED(var == NULL)) return;
		zv::Val notIdenticalNode;
		{
			zv::Val nullName = pt_type_new(PT_CLASS_NAME, 1, zv::Args{pt_npfh_null});
			if (UNEXPECTED(nullName.isUndef())) return;
			zv::Val nullConstFetch = pt_type_new(PT_CLASS_CONST_FETCH, 1, nullName.raw());
			if (UNEXPECTED(nullConstFetch.isUndef())) return;
			zv::Args notIdenticalArgv{var, nullConstFetch.raw()};
			notIdenticalNode = pt_type_new(PT_CLASS_BINARY_OP_NOT_IDENTICAL, 2, notIdenticalArgv);
			if (UNEXPECTED(notIdenticalNode.isUndef())) return;
		}
		zv::Val leftTypes = pt_native_closure(&leftTypesBody, handler, expr, receiverResult, notIdenticalNode.raw());
		zv::Val rightTypes = pt_native_closure(&rightTypesBody, exprResult);
		zv::Val leftTruthyScope = pt_native_closure(&leftTruthyScopeBody, nonNullabilityResult);
		zv::Val leftFalseyScopeCallback;
		{
			zval leftFalseyCaptures[3];
			ZVAL_COPY_VALUE(&leftFalseyCaptures[0], beforeScope);
			ZVAL_COPY_VALUE(&leftFalseyCaptures[1], leftTypes.raw());
			ZVAL_COPY_VALUE(&leftFalseyCaptures[2], leftFalseyScope);
			leftFalseyScopeCallback = pt_native_closure_new(&leftFalseyScopeBody, 3, leftFalseyCaptures, 1u << 2);
		}
		zv::Val rightFalseyScope = pt_native_closure(&rightFalseyScopeBody, exprResult);

		zval *booleanNarrowingHelper = OBJ_PROP_NUM(handler, slots::booleanNarrowingHelper);
		if (UNEXPECTED(Z_TYPE_P(booleanNarrowingHelper) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function specifyConjunction() on %s", zend_zval_value_name(booleanNarrowingHelper));
			return;
		}
		zv::Val conjunction = pt_boolean_narrowing_helper_specify_conjunction(Z_OBJ_P(booleanNarrowingHelper), nodeScopeResolver, s, context, expr, notIdenticalNode.raw(), leftTypes.raw(), leftTruthyScope.raw(), leftFalseyScopeCallback.raw(), propertyFetch, rightTypes.raw(), rightFalseyScope.raw());
		if (UNEXPECTED(conjunction.isUndef())) return;
		if (UNEXPECTED(!conjunction.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function setRootExpr() on %s", zend_zval_value_name(conjunction.raw()));
			return;
		}
		zv::Val types = pt_specified_types_set_root_expr(Z_OBJ_P(conjunction.raw()), expr);
		if (UNEXPECTED(types.isUndef())) return;

		zv::Val nullSafeTypes = pt_default_narrowing_helper_specify_default_types_with_plain_twin(defaultNarrowingHelper, expr, exprResult, context, s);
		if (UNEXPECTED(nullSafeTypes.isUndef())) return;
		bool isTrueContext;
		if (UNEXPECTED(!contextFlag(context, pt_type_specifier_context_true, "true", isTrueContext))) return;
		if (UNEXPECTED(!types.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function %s() on %s", isTrueContext ? "unionWith" : "intersectWith", zend_zval_value_name(types.raw()));
			return;
		}
		zv::Val result = isTrueContext
			? pt_specified_types_union_with(Z_OBJ_P(types.raw()), nullSafeTypes.raw())
			: pt_specified_types_intersect_with(Z_OBJ_P(types.raw()), nullSafeTypes.raw());
		if (UNEXPECTED(result.isUndef())) return;
		result.intoReturnValue(return_value);
	}

	/* function (MutatingScope $scope, TypeSpecifierContext $ctx) use ($expr,
	 * $receiverResult, $notIdenticalNode): SpecifiedTypes — captures: $this,
	 * $expr, $receiverResult, $notIdenticalNode */
	static zv::Val leftTypesOf(zval *captures, zval *scope, zval *ctx)
	{
		zval *defaultNarrowingHelper = OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::defaultNarrowingHelper);
		bool isNullContext;
		if (UNEXPECTED(!contextFlag(ctx, pt_type_specifier_context_null, "null", isNullContext))) return zv::Val();
		if (isNullContext) return pt_default_narrowing_helper_specify_default_types(defaultNarrowingHelper, &captures[3], ctx);

		zval *var = exprVar(&captures[1]);
		if (UNEXPECTED(var == NULL)) return zv::Val();
		zv::Val nullType = newNullType();
		if (UNEXPECTED(nullType.isUndef())) return zv::Val();
		zv::Val negated = pt_type_specifier_context_negate(Z_OBJ_P(ctx));
		if (UNEXPECTED(negated.isUndef())) return zv::Val();
		return pt_default_narrowing_helper_create_subject_types(defaultNarrowingHelper, scope, var, &captures[2], nullType.raw(), negated.raw());
	}

	static void leftTypesBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 2, pt_npfh_closure_name))) return;
		zv::Val specifiedTypes = leftTypesOf(captures, &argv[0], &argv[1]);
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}

	/* static fn (MutatingScope $scope, TypeSpecifierContext $ctx): SpecifiedTypes
	 * => $exprResult->getSpecifiedTypesForScope($scope, $ctx) — captures:
	 * $exprResult */
	static void rightTypesBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 2, pt_npfh_closure_name))) return;
		zv::Val specifiedTypes = pt_expression_result_get_specified_types_for_scope(&captures[0], &argv[0], &argv[1]);
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}

	/* static fn (): MutatingScope => $nonNullabilityResult->getScope() —
	 * captures: $nonNullabilityResult */
	static void leftTruthyScopeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) argc;
		(void) argv;
		zv::Val scope = ensuredResultScope(&captures[0]);
		if (UNEXPECTED(scope.isUndef())) return;
		scope.intoReturnValue(return_value);
	}

	/* static function () use ($beforeScope, $leftTypes, &$leftFalseyScope):
	 * MutatingScope { return $leftFalseyScope ??=
	 * $beforeScope->applySpecifiedTypes($leftTypes($beforeScope,
	 * TypeSpecifierContext::createFalsey())); } — captures: $beforeScope,
	 * $leftTypes, &$leftFalseyScope */
	static void leftFalseyScopeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) argc;
		(void) argv;
		zval *memo = Z_REFVAL(captures[2]);
		if (Z_TYPE_P(memo) != IS_NULL) {
			ZVAL_COPY(return_value, memo);
			return;
		}
		zval falsey;
		if (UNEXPECTED(!contextZval(pt_type_specifier_context_create_falsey(), falsey))) return;
		zval *leftTypesHolder = &captures[1];
		zv::Val specifiedTypes;
		if (EXPECTED(pt_native_closure_is(leftTypesHolder, &leftTypesBody))) {
			specifiedTypes = leftTypesOf(pt_native_closure_captures(Z_OBJ_P(leftTypesHolder)), &captures[0], &falsey);
		} else {
			zv::Args leftArgv{&captures[0], &falsey};
			specifiedTypes = pt_type_call_callable(leftTypesHolder, 2, leftArgv);
		}
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		zv::Val scope = pt_mutating_scope_apply_specified_types(Z_OBJ(captures[0]), specifiedTypes.raw());
		if (UNEXPECTED(scope.isUndef())) return;
		// ??= assigns the evaluated value unconditionally once the memo read
		// null (a re-entrant fill during the evaluation is overwritten)
		memo = Z_REFVAL(captures[2]);
		zval previous;
		ZVAL_COPY_VALUE(&previous, memo);
		ZVAL_COPY(memo, scope.raw());
		zval_ptr_dtor(&previous);
		scope.intoReturnValue(return_value);
	}

	/* static fn (): MutatingScope => $exprResult->getFalseyScope() — captures:
	 * $exprResult */
	static void rightFalseyScopeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) argc;
		(void) argv;
		zv::Val scope = pt_expression_result_get_falsey_scope(&captures[0]);
		if (UNEXPECTED(scope.isUndef())) return;
		scope.intoReturnValue(return_value);
	}

	/* function (Type $type, TypeSpecifierContext $context, bool
	 * $nativeTypesPromoted) use ($expr, $propertyFetch, $exprResult,
	 * $receiverResult, $nullsafeTypeCallback, $beforeScope): SpecifiedTypes —
	 * captures: $this, $expr, $propertyFetch, $exprResult, $receiverResult,
	 * $nullsafeTypeCallback, $beforeScope */
	static void createTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 3, pt_npfh_closure_name))) return;
		zval *defaultNarrowingHelper = OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::defaultNarrowingHelper);
		zval *expr = &captures[1];
		zval *propertyFetch = &captures[2];
		zval *exprResult = &captures[3];
		zval *receiverResult = &captures[4];
		zval *nullsafeTypeCallback = &captures[5];
		zval *beforeScope = &captures[6];
		zval *type = &argv[0];
		zval *context = &argv[1];
		bool nativeTypesPromoted = zend_is_true(&argv[2]);

		// null() context: createForExpr never computes $containsNull and
		// emits no entry for the subject - behave the same.
		bool isNullContext;
		if (UNEXPECTED(!contextFlag(context, pt_type_specifier_context_null, "null", isNullContext))) return;
		if (isNullContext) {
			zv::Val empty = pt_specified_types_new();
			if (UNEXPECTED(empty.isUndef())) return;
			zv::Val result = pt_specified_types_set_root_expr(Z_OBJ_P(empty.raw()), expr);
			if (UNEXPECTED(result.isUndef())) return;
			result.intoReturnValue(return_value);
			return;
		}

		zv::Val promotedScope;
		zval *s = beforeScope;
		if (nativeTypesPromoted) {
			promotedScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(beforeScope));
			if (UNEXPECTED(promotedScope.isUndef())) return;
			s = promotedScope.raw();
		}
		zv::Val nullsafeTypeValue;
		if (EXPECTED(pt_native_closure_is(nullsafeTypeCallback, &nullsafeTypeCallbackBody))) {
			zval *nullsafeCaptures = pt_native_closure_captures(Z_OBJ_P(nullsafeTypeCallback));
			nullsafeTypeValue = nullsafeType(&nullsafeCaptures[0], &nullsafeCaptures[1], nativeTypesPromoted);
		} else {
			zv::Args callbackArgv{nativeTypesPromoted};
			nullsafeTypeValue = pt_type_call_callable(nullsafeTypeCallback, 1, callbackArgv);
		}
		if (UNEXPECTED(nullsafeTypeValue.isUndef())) return;
		if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function isNull() on %s", zend_zval_value_name(type));
			return;
		}
		if (UNEXPECTED(!nullsafeTypeValue.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function isNull() on %s", zend_zval_value_name(nullsafeTypeValue.raw()));
			return;
		}
		bool isTrueContext;
		if (UNEXPECTED(!contextFlag(context, pt_type_specifier_context_true, "true", isTrueContext))) return;
		bool containsNull;
		if (isTrueContext) {
			zend_long typeIsNull = isNullTrinary(type);
			if (UNEXPECTED(typeIsNull < 0)) return;
			containsNull = typeIsNull != PT_TRI_NO;
			if (containsNull) {
				zend_long nullsafeIsNull = isNullTrinary(nullsafeTypeValue.raw());
				if (UNEXPECTED(nullsafeIsNull < 0)) return;
				containsNull = nullsafeIsNull != PT_TRI_NO;
			}
		} else {
			bool typeContainsNull;
			if (UNEXPECTED(!pt_type_combinator_contains_null(type, typeContainsNull))) return;
			containsNull = !typeContainsNull;
			if (containsNull) {
				zend_long nullsafeIsNull = isNullTrinary(nullsafeTypeValue.raw());
				if (UNEXPECTED(nullsafeIsNull < 0)) return;
				containsNull = nullsafeIsNull != PT_TRI_NO;
			}
		}

		// the ?-> may legitimately be null: keep the ?-> node's own key only
		if (containsNull) {
			zv::Val subjectTypes = pt_default_narrowing_helper_create_subject_types(defaultNarrowingHelper, s, expr, NULL, type, context);
			if (UNEXPECTED(subjectTypes.isUndef())) return;
			zv::Val result = setRootExpr(subjectTypes, expr);
			if (UNEXPECTED(result.isUndef())) return;
			result.intoReturnValue(return_value);
			return;
		}

		// !containsNull: the plain inner propertyFetch narrowed by $type, the
		// original ?-> key, and "receiver is not null"
		zv::Val plainTypes = pt_default_narrowing_helper_create_subject_types(defaultNarrowingHelper, s, propertyFetch, exprResult, type, context);
		if (UNEXPECTED(plainTypes.isUndef())) return;
		zv::Val nullsafeTypes = pt_default_narrowing_helper_create_subject_types(defaultNarrowingHelper, s, expr, NULL, type, context);
		if (UNEXPECTED(nullsafeTypes.isUndef())) return;
		zv::Val united = unionWith(plainTypes, nullsafeTypes.raw());
		if (UNEXPECTED(united.isUndef())) return;
		zval *var = exprVar(expr);
		if (UNEXPECTED(var == NULL)) return;
		zv::Val nullType = newNullType();
		if (UNEXPECTED(nullType.isUndef())) return;
		zval falseContext;
		if (UNEXPECTED(!contextZval(pt_type_specifier_context_create_false(), falseContext))) return;
		zv::Val receiverTypes = pt_default_narrowing_helper_create_subject_types(defaultNarrowingHelper, s, var, receiverResult, nullType.raw(), &falseContext);
		if (UNEXPECTED(receiverTypes.isUndef())) return;
		united = unionWith(united, receiverTypes.raw());
		if (UNEXPECTED(united.isUndef())) return;
		zv::Val result = setRootExpr(united, expr);
		if (UNEXPECTED(result.isUndef())) return;
		result.intoReturnValue(return_value);
	}

	/* $type->isNull() as a PT_TRI_* value; -1 = pending exception */
	static zend_long isNullTrinary(zval *type)
	{
		zv::Val isNull = pt_type_op(Z_OBJ_P(type), PT_OP_IS_NULL, 0, NULL);
		if (UNEXPECTED(isNull.isUndef())) return -1;
		return pt_type_trinary_value(isNull.raw());
	}

	/* $specifiedTypes->setRootExpr($expr) / ->unionWith($other) */
	static zv::Val setRootExpr(zv::Val &specifiedTypes, zval *expr)
	{
		if (UNEXPECTED(!specifiedTypes.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function setRootExpr() on %s", zend_zval_value_name(specifiedTypes.raw()));
			return zv::Val();
		}
		return pt_specified_types_set_root_expr(Z_OBJ_P(specifiedTypes.raw()), expr);
	}

	static zv::Val unionWith(zv::Val &specifiedTypes, zval *other)
	{
		if (UNEXPECTED(!specifiedTypes.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function unionWith() on %s", zend_zval_value_name(specifiedTypes.raw()));
			return zv::Val();
		}
		return pt_specified_types_union_with(Z_OBJ_P(specifiedTypes.raw()), other);
	}
};

} // namespace phpstanturbo

using phpstanturbo::NullsafePropertyFetchHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_nullsafe_property_fetch_handler()
{
	pt_npfh_virtual_nullsafe_property_fetch = zend_string_init_interned(PT_LC("virtualNullsafePropertyFetch"), 1);
	pt_npfh_null = zend_string_init_interned(PT_LC("null"), 1);
	pt_npfh_cache_printer_attribute = zend_string_init_interned(PT_LC("phpstan_cache_printer"), 1);

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\NullsafePropertyFetchHandler");
	ptdecl::NullsafePropertyFetchHandler::declareClass(cls);
	ptdecl::NullsafePropertyFetchHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nonNullabilityHelper, *expressionResultFactory, *defaultNarrowingHelper, *booleanNarrowingHelper;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, nonNullabilityHelper, expressionResultFactory, defaultNarrowingHelper, booleanNarrowingHelper)) RETURN_THROWS();
		NullsafePropertyFetchHandler(Z_OBJ_P(ZEND_THIS)).construct(nonNullabilityHelper, expressionResultFactory, defaultNarrowingHelper, booleanNarrowingHelper);
	});

	cls.method<&NullsafePropertyFetchHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(NullsafePropertyFetchHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_nullsafe_property_fetch_handler);
	pt_expr_handler_entry_register(&pt_ce_nullsafe_property_fetch_handler, &NullsafePropertyFetchHandler::processExprEntry);
}

/* }}} */
