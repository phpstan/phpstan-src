/*
 * PHPStanTurbo\EmptyHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\EmptyHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback ($exprResult, $beforeScope),
 * the specifyTypesCallback ($this, $expr, $exprResult, $chainResults,
 * $nodeScopeResolver, $beforeScope and the by-reference $foldScopes memo),
 * and inside it the `!isset($x) || !$x` disjuncts' closures handed to
 * BooleanNarrowingHelper::specifyDisjunction(): $leftTypes ($this,
 * $chainResults, $expr, $exprResult, $issetNode, $notIssetNode), $leftType
 * ($exprResult, $beforeScope), $rightTypes ($this, $exprResult,
 * $notExprNode), $rightType ($exprResult) and the branch-scope callbacks.
 *
 * NodeScopeResolver, NonNullabilityHelper, MutatingScope, ExpressionResult,
 * ExpressionContext, EnsuredNonNullabilityResult, IssetabilityResolution,
 * SpecifiedTypes, TypeSpecifierContext, DefaultNarrowingHelper,
 * BooleanNarrowingHelper and the Type kernel are called through their direct
 * entries; the EmptyExpressionNode and the fabricated Isset_ / BooleanNot
 * nodes are instantiated through the class map.
 */

#include "support.h"
#include "generated/EmptyHandler.h"

namespace slots = ptdecl::EmptyHandler::slot;
namespace sigs = ptdecl::EmptyHandler::sig;
#include "CallHandlerSupport.h"

zend_class_entry *pt_ce_empty_handler = nullptr;

namespace {

using namespace ptcall;

constexpr const char *pt_emh_closure_name = "PHPStan\\Analyser\\ExprHandler\\EmptyHandler::{closure}";

/* {{{ the PhpParser nodes' properties */

pt_property_site pt_emh_expr_site;

zval *exprExpr(zval *expr) { return nodeProperty(pt_emh_expr_site, expr, PT_LC("expr")); }

/* }}} */

/* {{{ small value helpers */

zv::Val newBooleanType()
{
	zval out;
	if (UNEXPECTED(!pt_boolean_type_new(&out))) return zv::Val();
	return zv::Val::adopt(out);
}

zv::Val newConstantBooleanType(bool value)
{
	zval out;
	if (UNEXPECTED(!pt_constant_boolean_type_new(&out, value))) return zv::Val();
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

/* $scope->applySpecifiedTypes($specifiedTypes) */
zv::Val applySpecifiedTypes(zval *scope, zv::Val specifiedTypes)
{
	if (UNEXPECTED(specifiedTypes.isUndef())) return zv::Val();
	if (UNEXPECTED(Z_TYPE_P(scope) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function applySpecifiedTypes() on %s", zend_zval_value_name(scope));
		return zv::Val();
	}
	return pt_mutating_scope_apply_specified_types(Z_OBJ_P(scope), specifiedTypes.raw());
}

/* $callable($scope, TypeSpecifierContext::create<ctx>()) of a narrowing closure */
zv::Val callNarrowing(zval *callable, zval *scope, zend_object *context)
{
	if (UNEXPECTED(context == NULL)) return zv::Val();
	zval contextZv;
	ZVAL_OBJ(&contextZv, context);
	zv::Args argv{scope, &contextZv};
	return pt_type_call_callable(callable, 2, argv);
}

/* $defaultNarrowingHelper->captureChainResults($node, $storage, $chainResults)
 * — $chainResults by reference; false = pending exception */
[[nodiscard]] bool captureChainResults(zval *helper, zval *node, zval *storage, zv::Val &chainResults)
{
	zval reference;
	ZVAL_NEW_REF(&reference, chainResults.raw());
	ZVAL_UNDEF(chainResults.raw());
	bool ok = pt_default_narrowing_helper_capture_chain_results(helper, node, storage, &reference);
	chainResults = zv::Val::copyOf(zv::Ref(Z_REFVAL(reference)));
	zval_ptr_dtor(&reference);
	return ok;
}

/* $scope->doNotTreatPhpDocTypesAsCertain() when native types are promoted,
 * the scope itself otherwise (held in `promoted`); NULL = pending exception */
zval *evaluationScopeOf(zval *scope, bool nativeTypesPromoted, zv::Val &promoted)
{
	if (!nativeTypesPromoted) return scope;
	promoted = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scope));
	return promoted.isUndef() ? NULL : promoted.raw();
}

/* $exprResult->getIssetabilityResolution($scope, false) as an object; UNDEF =
 * pending exception */
zv::Val issetabilityResolution(zval *exprResult, zval *scope)
{
	if (UNEXPECTED(Z_TYPE_P(exprResult) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function getIssetabilityResolution() on %s", zend_zval_value_name(exprResult));
		return zv::Val();
	}
	zv::Val resolution = pt_expression_result_get_issetability_resolution(exprResult, scope, false, false);
	if (UNEXPECTED(resolution.isUndef())) return zv::Val();
	if (UNEXPECTED(!resolution.ref().isObject())) {
		zend_throw_error(NULL, "Call to a member function isSet() on %s", zend_zval_value_name(resolution.raw()));
		return zv::Val();
	}
	return resolution;
}

/* }}} */

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\EmptyHandler; UNDEF = pending
 * exception. */
class EmptyHandler
{
public:
	explicit EmptyHandler(zend_object *self) : self(self) {}

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
		int is = isInstanceOf(expr, PT_CLASS_EMPTY_EXPR);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scopeArg, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *beforeScope = scopeArg;
		zval *nonNullabilityHelper = OBJ_PROP_NUM(self, slots::nonNullabilityHelper);
		zval *subject = exprExpr(expr);
		if (UNEXPECTED(subject == NULL)) return zv::Val();
		zv::Val nonNullabilityResult = pt_non_nullability_helper_ensure_non_nullability(nonNullabilityHelper, scopeArg, subject);
		if (UNEXPECTED(nonNullabilityResult.isUndef())) return zv::Val();
		if (UNEXPECTED(!nonNullabilityResult.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function getScope() on %s", zend_zval_value_name(nonNullabilityResult.raw()));
			return zv::Val();
		}
		zv::Val hold;
		zv::Val scope;
		{
			zval *ensuredScope = pt_ensured_non_nullability_result_scope(nonNullabilityResult.raw(), hold);
			if (UNEXPECTED(ensuredScope == NULL)) return zv::Val();
			zv::Val heldScope = zv::Val::copyOf(zv::Ref(ensuredScope));
			subject = exprExpr(expr);
			if (UNEXPECTED(subject == NULL)) return zv::Val();
			scope = pt_node_scope_resolver_look_for_set_allowed_undefined_expressions(nodeScopeResolver, heldScope.raw(), subject);
			if (UNEXPECTED(scope.isUndef())) return zv::Val();
		}
		zv::Val exprResult;
		{
			zv::Val deepContext = pt_expression_context_enter_deep(context);
			if (UNEXPECTED(deepContext.isUndef())) return zv::Val();
			subject = exprExpr(expr);
			if (UNEXPECTED(subject == NULL)) return zv::Val();
			exprResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, subject, scope.raw(), storage, nodeCallback, deepContext.raw());
			if (UNEXPECTED(exprResult.isUndef())) return zv::Val();
		}
		{
			zval *borrowed = pt_expression_result_scope(exprResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			scope = zv::Val::copyOf(zv::Ref(borrowed));
			zval *specifiedExpressions = pt_ensured_non_nullability_result_specified_expressions(nonNullabilityResult.raw(), hold);
			if (UNEXPECTED(specifiedExpressions == NULL)) return zv::Val();
			zv::Val heldExpressions = zv::Val::copyOf(zv::Ref(specifiedExpressions));
			scope = pt_non_nullability_helper_revert_non_nullability(nonNullabilityHelper, scope.raw(), heldExpressions.raw());
			if (UNEXPECTED(scope.isUndef())) return zv::Val();
			subject = exprExpr(expr);
			if (UNEXPECTED(subject == NULL)) return zv::Val();
			scope = pt_node_scope_resolver_look_for_unset_allowed_undefined_expressions(nodeScopeResolver, scope.raw(), subject);
			if (UNEXPECTED(scope.isUndef())) return zv::Val();
		}

		zv::Val chainResults = zv::Val(zv::Arr::empty());
		subject = exprExpr(expr);
		if (UNEXPECTED(subject == NULL)) return zv::Val();
		if (UNEXPECTED(!captureChainResults(OBJ_PROP_NUM(self, slots::defaultNarrowingHelper), subject, storage, chainResults))) return zv::Val();

		{
			zv::Args nodeArgv{expr, exprResult.raw()};
			zv::Val node = pt_type_new(PT_CLASS_EMPTY_EXPRESSION_NODE, 2, nodeArgv);
			if (UNEXPECTED(node.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback_with_expression(nodeScopeResolver, nodeCallback, node.raw(), beforeScope, storage, context))) return zv::Val();
		}

		// lazily memoized branch scopes of the !isset($x) || !$x decomposition
		zv::Val foldScopes;
		{
			zval null = {};
			ZVAL_NULL(&null);
			zval reference;
			ZVAL_NEW_REF(&reference, &null);
			foldScopes = zv::Val::adopt(reference);
		}

		zv::Val variableFlow = pt_expression_result_variable_flow(exprResult.raw());
		if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		bool hasYield;
		if (UNEXPECTED(!pt_expression_result_has_yield(exprResult.raw(), hasYield))) return zv::Val();
		bool isAlwaysTerminating;
		if (UNEXPECTED(!pt_expression_result_is_always_terminating(exprResult.raw(), isAlwaysTerminating))) return zv::Val();
		zval *borrowed = pt_expression_result_throw_points(exprResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		zv::Val throwPoints = zv::Val::copyOf(zv::Ref(borrowed));
		borrowed = pt_expression_result_impure_points(exprResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		zv::Val impurePoints = zv::Val::copyOf(zv::Ref(borrowed));

		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, exprResult.raw(), beforeScope);
		zv::Val specifyTypesCallback;
		{
			zval captures[7];
			ZVAL_OBJ(&captures[0], self);
			ZVAL_COPY_VALUE(&captures[1], expr);
			ZVAL_COPY_VALUE(&captures[2], exprResult.raw());
			ZVAL_COPY_VALUE(&captures[3], chainResults.raw());
			ZVAL_COPY_VALUE(&captures[4], nodeScopeResolver);
			ZVAL_COPY_VALUE(&captures[5], beforeScope);
			ZVAL_COPY_VALUE(&captures[6], foldScopes.raw());
			specifyTypesCallback = pt_native_closure_new(&specifyTypesCallbackBody, 7, captures, 1u << 6);
		}

		pt_expression_result_args args(scope.raw(), beforeScope, expr, hasYield, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return EmptyHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* static function (bool $nativeTypesPromoted) use ($exprResult,
	 * $beforeScope): Type — captures: $exprResult, $beforeScope */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 1, pt_emh_closure_name))) return;
		zv::Val promoted;
		zval *s = evaluationScopeOf(&captures[1], zend_is_true(&argv[0]), promoted);
		if (UNEXPECTED(s == NULL)) return;
		zv::Val resolution = issetabilityResolution(&captures[0], s);
		if (UNEXPECTED(resolution.isUndef())) return;
		zv::Val result = pt_issetability_resolution_not_empty(resolution.raw());
		if (UNEXPECTED(result.isUndef())) return;
		zv::Val type = Z_TYPE_P(result.raw()) == IS_NULL ? newBooleanType() : newConstantBooleanType(!zend_is_true(result.raw()));
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* static fn (): bool => true */
	static void alwaysTrueBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) captures;
		(void) argc;
		(void) argv;
		ZVAL_TRUE(return_value);
	}

	/* static function (Type $type): ?bool — $leftType's verdict callback */
	static void notNullCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) captures;
		if (UNEXPECTED(!requireArguments(argc, 1, pt_emh_closure_name))) return;
		zval *type = &argv[0];
		ZVAL_DEREF(type);
		zend_class_entry *typeCe = pt_class(PT_CLASS_TYPE);
		if (UNEXPECTED(typeCe == NULL)) return;
		if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(type), typeCe))) {
			zend_type_error("%s(): Argument #1 ($type) must be of type PHPStan\\Type\\Type, %s given", pt_emh_closure_name, zend_zval_value_name(type));
			return;
		}
		zend_long isNull = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_NULL, 0, NULL);
		if (UNEXPECTED(isNull < 0)) return;
		if (isNull == PT_TRI_MAYBE) {
			ZVAL_NULL(return_value);
			return;
		}
		ZVAL_BOOL(return_value, isNull != PT_TRI_YES);
	}

	/* function (TypeSpecifierContext $context, bool $nativeTypesPromoted) use
	 * ($expr, $exprResult, $chainResults, $nodeScopeResolver, $beforeScope,
	 * &$foldScopes): SpecifiedTypes — captures: $this, $expr, $exprResult,
	 * $chainResults, $nodeScopeResolver, $beforeScope, &$foldScopes */
	static zv::Val specifyTypes(zval *captures, zval *context, bool nativeTypesPromoted)
	{
		zend_object *handler = Z_OBJ(captures[0]);
		zval *expr = &captures[1];
		zval *exprResult = &captures[2];
		zval *chainResults = &captures[3];
		zval *nodeScopeResolver = &captures[4];
		zval *beforeScope = &captures[5];
		zval *foldScopes = &captures[6];

		zv::Val promoted;
		zval *s = evaluationScopeOf(beforeScope, nativeTypesPromoted, promoted);
		if (UNEXPECTED(s == NULL)) return zv::Val();
		{
			zv::Val resolution = issetabilityResolution(exprResult, s);
			if (UNEXPECTED(resolution.isUndef())) return zv::Val();
			zv::Val alwaysTrue = pt_native_closure(&alwaysTrueBody);
			zv::Val isset = pt_issetability_resolution_is_set(resolution.raw(), alwaysTrue.raw());
			if (UNEXPECTED(isset.isUndef())) return zv::Val();
			if (Z_TYPE_P(isset.raw()) == IS_FALSE) return pt_specified_types_new();
		}

		// empty($x) narrows like !isset($x) || !$x; the fabricated nodes are
		// only printed into holder keys, never walked
		zval *subject = exprExpr(expr);
		if (UNEXPECTED(subject == NULL)) return zv::Val();
		zv::Val issetNode;
		{
			zv::Arr vars = zv::Arr::create(1);
			vars.push(zv::Ref(subject));
			zv::Val varsValue(std::move(vars));
			issetNode = pt_type_new(PT_CLASS_PARSER_ISSET_EXPR, 1, varsValue.raw());
			if (UNEXPECTED(issetNode.isUndef())) return zv::Val();
		}
		zv::Val notIssetNode = pt_type_new(PT_CLASS_BOOLEAN_NOT_EXPR, 1, issetNode.raw());
		if (UNEXPECTED(notIssetNode.isUndef())) return zv::Val();
		subject = exprExpr(expr);
		if (UNEXPECTED(subject == NULL)) return zv::Val();
		zv::Val notExprNode = pt_type_new(PT_CLASS_BOOLEAN_NOT_EXPR, 1, subject);
		if (UNEXPECTED(notExprNode.isUndef())) return zv::Val();

		zv::Val leftTypes = pt_native_closure(&leftTypesBody, handler, chainResults, expr, exprResult, issetNode.raw(), notIssetNode.raw());
		zv::Val leftType = pt_native_closure(&leftTypeBody, exprResult, beforeScope);
		zv::Val rightTypes = pt_native_closure(&rightTypesBody, handler, exprResult, notExprNode.raw());
		zv::Val rightType = pt_native_closure(&rightTypeBody, exprResult);

		// the disjuncts' branch scopes derive from the evaluation point -
		// computed once, reused across asks
		zval *memo = Z_REFVAL_P(foldScopes);
		if (Z_TYPE_P(memo) == IS_NULL) {
			zv::Val leftTruthyScope = applySpecifiedTypes(beforeScope, callNarrowing(leftTypes.raw(), beforeScope, pt_type_specifier_context_create_truthy()));
			if (UNEXPECTED(leftTruthyScope.isUndef())) return zv::Val();
			zv::Val leftFalseyScope = applySpecifiedTypes(beforeScope, callNarrowing(leftTypes.raw(), beforeScope, pt_type_specifier_context_create_falsey()));
			if (UNEXPECTED(leftFalseyScope.isUndef())) return zv::Val();
			zv::Val rightTruthyScope = applySpecifiedTypes(leftFalseyScope.raw(), callNarrowing(rightTypes.raw(), leftFalseyScope.raw(), pt_type_specifier_context_create_truthy()));
			if (UNEXPECTED(rightTruthyScope.isUndef())) return zv::Val();
			zv::Arr scopes = zv::Arr::create(3);
			scopes.push(std::move(leftTruthyScope));
			scopes.push(std::move(leftFalseyScope));
			scopes.push(std::move(rightTruthyScope));
			zval scopesValue;
			ZVAL_ARR(&scopesValue, scopes.table());
			memo = Z_REFVAL_P(foldScopes);
			zval previous;
			ZVAL_COPY_VALUE(&previous, memo);
			ZVAL_COPY(memo, &scopesValue);
			zval_ptr_dtor(&previous);
		}
		// [$leftTruthyScope, $leftFalseyScope, $rightTruthyScope] = $foldScopes
		zv::Val scopesHeld = zv::Val::copyOf(zv::Ref(Z_REFVAL_P(foldScopes)));
		zval *scopes[3];
		for (zend_ulong i = 0; i < 3; i++) {
			scopes[i] = Z_TYPE_P(scopesHeld.raw()) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(scopesHeld.raw()), i) : NULL;
			if (UNEXPECTED(scopes[i] == NULL)) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
		}
		zv::Val leftTruthyScopeCallback = pt_native_closure(&constantScopeBody, scopes[0]);
		zv::Val leftFalseyScopeCallback = pt_native_closure(&constantScopeBody, scopes[1]);
		zv::Val rightTruthyScopeCallback = pt_native_closure(&constantScopeBody, scopes[2]);

		zval *booleanNarrowingHelper = OBJ_PROP_NUM(handler, slots::booleanNarrowingHelper);
		if (UNEXPECTED(Z_TYPE_P(booleanNarrowingHelper) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function specifyDisjunction() on %s", zend_zval_value_name(booleanNarrowingHelper));
			return zv::Val();
		}
		zv::Val disjunction = pt_boolean_narrowing_helper_specify_disjunction(Z_OBJ_P(booleanNarrowingHelper), nodeScopeResolver, s, context, expr, notIssetNode.raw(), leftTypes.raw(), leftType.raw(), leftTruthyScopeCallback.raw(), leftFalseyScopeCallback.raw(), notExprNode.raw(), rightTypes.raw(), rightType.raw(), rightTruthyScopeCallback.raw());
		if (UNEXPECTED(disjunction.isUndef())) return zv::Val();
		if (UNEXPECTED(!disjunction.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function setRootExpr() on %s", zend_zval_value_name(disjunction.raw()));
			return zv::Val();
		}
		return pt_specified_types_set_root_expr(Z_OBJ_P(disjunction.raw()), expr);
	}

	static void specifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 2, pt_emh_closure_name))) return;
		zv::Val specifiedTypes = specifyTypes(captures, &argv[0], zend_is_true(&argv[1]));
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}

	/* function (MutatingScope $scope, TypeSpecifierContext $ctx) use
	 * ($chainResults, $expr, $exprResult, $issetNode, $notIssetNode):
	 * SpecifiedTypes — captures: $this, $chainResults, $expr, $exprResult,
	 * $issetNode, $notIssetNode */
	static void leftTypesBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 2, pt_emh_closure_name))) return;
		zval *defaultNarrowingHelper = OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::defaultNarrowingHelper);
		zval *chainResults = &captures[1];
		zval *expr = &captures[2];
		zval *exprResult = &captures[3];
		zval *issetNode = &captures[4];
		zval *notIssetNode = &captures[5];
		zval *scope = &argv[0];
		zval *ctx = &argv[1];

		bool flag;
		if (UNEXPECTED(!contextFlag(ctx, pt_type_specifier_context_null, "null", flag))) return;
		zv::Val specifiedTypes;
		if (flag) {
			specifiedTypes = pt_default_narrowing_helper_specify_default_types(defaultNarrowingHelper, notIssetNode, ctx);
		} else {
			zv::Val negated = pt_type_specifier_context_negate(Z_OBJ_P(ctx));
			if (UNEXPECTED(negated.isUndef())) return;
			zv::Val readType = pt_default_narrowing_helper_build_chain_type_reader(defaultNarrowingHelper, chainResults, scope);
			if (UNEXPECTED(readType.isUndef())) return;
			if (UNEXPECTED(!contextFlag(negated.raw(), pt_type_specifier_context_true, "true", flag))) return;
			zval *subject = exprExpr(expr);
			if (UNEXPECTED(subject == NULL)) return;
			specifiedTypes = !flag
				? pt_default_narrowing_helper_create_isset_single_subject_non_true_types(defaultNarrowingHelper, scope, subject, exprResult, readType.raw(), negated.raw(), issetNode)
				: pt_default_narrowing_helper_create_isset_truthy_chain_types(defaultNarrowingHelper, scope, subject, readType.raw(), issetNode, negated.raw());
		}
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}

	/* static function (bool $nativeTypesPromoted) use ($exprResult,
	 * $beforeScope): Type — captures: $exprResult, $beforeScope */
	static void leftTypeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 1, pt_emh_closure_name))) return;
		zv::Val promoted;
		zval *issetabilityScope = evaluationScopeOf(&captures[1], zend_is_true(&argv[0]), promoted);
		if (UNEXPECTED(issetabilityScope == NULL)) return;
		zv::Val resolution = issetabilityResolution(&captures[0], issetabilityScope);
		if (UNEXPECTED(resolution.isUndef())) return;
		zv::Val callback = pt_native_closure(&notNullCallbackBody);
		zv::Val result = pt_issetability_resolution_is_set(resolution.raw(), callback.raw());
		if (UNEXPECTED(result.isUndef())) return;
		zv::Val type = Z_TYPE_P(result.raw()) == IS_NULL ? newBooleanType() : newConstantBooleanType(!zend_is_true(result.raw()));
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* function (MutatingScope $scope, TypeSpecifierContext $ctx) use
	 * ($exprResult, $notExprNode): SpecifiedTypes — captures: $this,
	 * $exprResult, $notExprNode */
	static void rightTypesBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 2, pt_emh_closure_name))) return;
		zval *ctx = &argv[1];
		bool isNullContext;
		if (UNEXPECTED(!contextFlag(ctx, pt_type_specifier_context_null, "null", isNullContext))) return;
		zv::Val specifiedTypes;
		if (isNullContext) {
			specifiedTypes = pt_default_narrowing_helper_specify_default_types(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::defaultNarrowingHelper), &captures[2], ctx);
		} else {
			zv::Val negated = pt_type_specifier_context_negate(Z_OBJ_P(ctx));
			if (UNEXPECTED(negated.isUndef())) return;
			specifiedTypes = pt_expression_result_get_specified_types_for_scope(&captures[1], &argv[0], negated.raw());
		}
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}

	/* static function (bool $nativeTypesPromoted) use ($exprResult): Type —
	 * captures: $exprResult */
	static void rightTypeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 1, pt_emh_closure_name))) return;
		zv::Val type = zend_is_true(&argv[0]) ? pt_expression_result_get_native_type(&captures[0]) : pt_expression_result_get_type(&captures[0]);
		if (UNEXPECTED(type.isUndef())) return;
		if (UNEXPECTED(!type.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function toBoolean() on %s", zend_zval_value_name(type.raw()));
			return;
		}
		zv::Val boolean = pt_type_call(Z_OBJ_P(type.raw()), PT_LC("toboolean"), 0, NULL);
		if (UNEXPECTED(boolean.isUndef())) return;
		if (UNEXPECTED(!boolean.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function isTrue() on %s", zend_zval_value_name(boolean.raw()));
			return;
		}
		zend_long isTrue = pt_type_call_trinary(Z_OBJ_P(boolean.raw()), PT_LC("istrue"), 0, NULL);
		if (UNEXPECTED(isTrue < 0)) return;
		zv::Val result;
		if (isTrue == PT_TRI_YES) {
			result = newConstantBooleanType(false);
		} else {
			zend_long isFalse = pt_type_call_trinary(Z_OBJ_P(boolean.raw()), PT_LC("isfalse"), 0, NULL);
			if (UNEXPECTED(isFalse < 0)) return;
			result = isFalse == PT_TRI_YES ? newConstantBooleanType(true) : newBooleanType();
		}
		if (UNEXPECTED(result.isUndef())) return;
		result.intoReturnValue(return_value);
	}

	/* static fn (): MutatingScope => $scope — captures: $scope */
	static void constantScopeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) argc;
		(void) argv;
		ZVAL_COPY(return_value, &captures[0]);
	}
};

} // namespace phpstanturbo

using phpstanturbo::EmptyHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_empty_handler()
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\EmptyHandler");
	ptdecl::EmptyHandler::declareClass(cls);
	ptdecl::EmptyHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nonNullabilityHelper, *expressionResultFactory, *defaultNarrowingHelper, *booleanNarrowingHelper;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, nonNullabilityHelper, expressionResultFactory, defaultNarrowingHelper, booleanNarrowingHelper)) RETURN_THROWS();
		EmptyHandler(Z_OBJ_P(ZEND_THIS)).construct(nonNullabilityHelper, expressionResultFactory, defaultNarrowingHelper, booleanNarrowingHelper);
	});

	cls.method<&EmptyHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(EmptyHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_empty_handler);
	pt_expr_handler_entry_register(&pt_ce_empty_handler, &EmptyHandler::processExprEntry);
}

/* }}} */
