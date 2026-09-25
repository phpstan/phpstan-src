/*
 * PHPStanTurbo\IssetHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\IssetHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback ($varResults, $afterScope)
 * with its not-null isSet() callback, the specifyTypesCallback ($this, $expr,
 * $varResults, $chainResults, $nodeScopeResolver, $afterScope and the
 * by-reference $foldAccTypes memo), and inside it the per-subject narrowing
 * closures ($makeSubjectTypes' closure: $this, $chainResults, $expr, $var,
 * $varResult), the accumulated conjunction closures ($this,
 * $nodeScopeResolver, $expr and the left / right operands with their branch
 * scopes) and the `static fn (): MutatingScope => $scope` branch-scope
 * callbacks handed to BooleanNarrowingHelper::specifyConjunction().
 * $makeSubjectTypes itself is inlined (it only creates the closure).
 *
 * NodeScopeResolver, NonNullabilityHelper, MutatingScope, ExpressionResult,
 * ExpressionContext, EnsuredNonNullabilityResult, IssetabilityResolution,
 * VariableFlow, SpecifiedTypes, TypeSpecifierContext, DefaultNarrowingHelper,
 * BooleanNarrowingHelper, MethodThrowPointHelper and the Type kernel are
 * called through their direct entries; NodeAbstract::getAttributes() through
 * a cached method site; the IssetExpressionNode and the fabricated Isset_ /
 * BooleanAnd / MethodCall / TypeExpr nodes are instantiated through the class
 * map.
 */

#include "support.h"
#include "generated/IssetHandler.h"

namespace slots = ptdecl::IssetHandler::slot;
namespace sigs = ptdecl::IssetHandler::sig;
#include "CallHandlerSupport.h"

zend_class_entry *pt_ce_isset_handler = nullptr;

namespace {

using namespace ptcall;

constexpr const char *pt_ish_closure_name = "PHPStan\\Analyser\\ExprHandler\\IssetHandler::{closure}";

/* {{{ the PHP collaborators (one site each; switch to their direct entries
 * once they are ported) */

pt_method_site pt_ish_get_attributes_site;

/* $expr->getAttributes() */
zv::Val nodeGetAttributes(zval *node)
{
	return pt_call_method_cached(pt_ish_get_attributes_site, Z_OBJ_P(node), PT_LC("getattributes"), 0, NULL);
}

/* }}} */

/* {{{ the PhpParser nodes' properties */

pt_property_site pt_ish_vars_site;
pt_property_site pt_ish_var_site;

zval *exprVars(zval *expr) { return nodeProperty(pt_ish_vars_site, expr, PT_LC("vars")); }
/* $arrayDimFetch->var */
zval *dimFetchVar(zval *dimFetch) { return nodeProperty(pt_ish_var_site, dimFetch, PT_LC("var")); }

/* }}} */

/* {{{ small value helpers */

/* the permanent interned literals (module startup) */
zend_string *pt_ish_array_access = nullptr;
zend_string *pt_ish_offset_exists = nullptr;

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

/* $specifiedTypes->setRootExpr($expr) */
zv::Val setRootExpr(zv::Val specifiedTypes, zval *expr)
{
	if (UNEXPECTED(specifiedTypes.isUndef())) return zv::Val();
	if (UNEXPECTED(!specifiedTypes.ref().isObject())) {
		zend_throw_error(NULL, "Call to a member function setRootExpr() on %s", zend_zval_value_name(specifiedTypes.raw()));
		return zv::Val();
	}
	return pt_specified_types_set_root_expr(Z_OBJ_P(specifiedTypes.raw()), expr);
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

/* new Isset_([$var], $subjectAttributes) — the whole isset's attributes
 * without its printed expression key */
zv::Val newIsset(zval *var, zval *attributes)
{
	zv::Arr vars = zv::Arr::create(1);
	vars.push(zv::Ref(var));
	zv::Val varsValue(std::move(vars));
	zv::Val subjectAttributes = pt_attributes_without_expression_key(attributes);
	zv::Args argv{varsValue.raw(), subjectAttributes.raw()};
	return pt_type_new(PT_CLASS_PARSER_ISSET_EXPR, 2, argv);
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

/* }}} */

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\IssetHandler; UNDEF = pending
 * exception. */
class IssetHandler
{
public:
	explicit IssetHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *nonNullabilityHelper, zval *expressionResultFactory, zval *methodThrowPointHelper, zval *defaultNarrowingHelper, zval *booleanNarrowingHelper) const
	{
		pt_write_slot(self, slots::nonNullabilityHelper, nonNullabilityHelper);
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::methodThrowPointHelper, methodThrowPointHelper);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
		pt_write_slot(self, slots::booleanNarrowingHelper, booleanNarrowingHelper);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *expr, bool &out) const
	{
		int is = isInstanceOf(expr, PT_CLASS_PARSER_ISSET_EXPR);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scopeArg, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *beforeScope = scopeArg;
		zval *nonNullabilityHelper = OBJ_PROP_NUM(self, slots::nonNullabilityHelper);
		zv::Val scope = zv::Val::copyOf(zv::Ref(scopeArg));
		bool hasYield = false;
		zv::Val throwPoints = zv::Val(zv::Arr::empty());
		zv::Val impurePoints = zv::Val(zv::Arr::empty());
		bool isAlwaysTerminating = false;

		zval *varsSlot = exprVars(expr);
		if (UNEXPECTED(varsSlot == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(varsSlot) != IS_ARRAY)) {
			zend_type_error("foreach() argument must be of type array|object, %s given", zend_zval_value_name(varsSlot));
			return zv::Val();
		}
		// foreach iterates the array it was handed
		zv::Val vars = zv::Val::copyOf(zv::Ref(varsSlot));
		uint32_t varCount = zend_hash_num_elements(Z_ARRVAL_P(vars.raw()));
		zv::Arr nonNullabilityResults = zv::Arr::create(varCount);
		zv::Arr varResults = zv::Arr::create(varCount);

		zend_class_entry *arrayDimFetchCe = pt_class(PT_CLASS_ARRAY_DIM_FETCH);
		if (UNEXPECTED(arrayDimFetchCe == NULL)) return zv::Val();
		zv::Val hold;
		for (zv::ArrayEntry entry : zv::TableRef(Z_ARRVAL_P(vars.raw()))) {
			zval *var = entry.value().deref().raw();
			zv::Val nonNullabilityResult = pt_non_nullability_helper_ensure_non_nullability(nonNullabilityHelper, scope.raw(), var);
			if (UNEXPECTED(nonNullabilityResult.isUndef())) return zv::Val();
			if (UNEXPECTED(!nonNullabilityResult.ref().isObject())) {
				zend_throw_error(NULL, "Call to a member function getScope() on %s", zend_zval_value_name(nonNullabilityResult.raw()));
				return zv::Val();
			}
			{
				zval *ensuredScope = pt_ensured_non_nullability_result_scope(nonNullabilityResult.raw(), hold);
				if (UNEXPECTED(ensuredScope == NULL)) return zv::Val();
				zv::Val heldScope = zv::Val::copyOf(zv::Ref(ensuredScope));
				scope = pt_node_scope_resolver_look_for_set_allowed_undefined_expressions(nodeScopeResolver, heldScope.raw(), var);
				if (UNEXPECTED(scope.isUndef())) return zv::Val();
			}
			zv::Val varResult;
			{
				zv::Val deepContext = pt_expression_context_enter_deep(context);
				if (UNEXPECTED(deepContext.isUndef())) return zv::Val();
				varResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, var, scope.raw(), storage, nodeCallback, deepContext.raw());
				if (UNEXPECTED(varResult.isUndef())) return zv::Val();
			}
			varResults.push(zv::Ref(varResult.raw()));
			zval *borrowed = pt_expression_result_scope(varResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			scope = zv::Val::copyOf(zv::Ref(borrowed));
			if (!hasYield && UNEXPECTED(!pt_expression_result_has_yield(varResult.raw(), hasYield))) return zv::Val();
			borrowed = pt_expression_result_throw_points(varResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			throwPoints = arrayMerge(throwPoints.raw(), borrowed);
			borrowed = pt_expression_result_impure_points(varResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			impurePoints = arrayMerge(impurePoints.raw(), borrowed);
			if (!isAlwaysTerminating && UNEXPECTED(!pt_expression_result_is_always_terminating(varResult.raw(), isAlwaysTerminating))) return zv::Val();
			nonNullabilityResults.push(std::move(nonNullabilityResult));

			if (Z_TYPE_P(var) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(var), arrayDimFetchCe)) continue;

			zv::Val varType;
			{
				zval *dimVar = dimFetchVar(var);
				if (UNEXPECTED(dimVar == NULL)) return zv::Val();
				zv::Val stored = pt_node_scope_resolver_read_stored_result(nodeScopeResolver, dimVar, storage);
				if (UNEXPECTED(stored.isUndef())) return zv::Val();
				if (UNEXPECTED(!stored.ref().isObject())) {
					zend_throw_error(NULL, "Call to a member function getTypeOnScope() on %s", zend_zval_value_name(stored.raw()));
					return zv::Val();
				}
				varType = pt_expression_result_get_type_on_scope(stored.raw(), scope.raw(), false);
				if (UNEXPECTED(varType.isUndef())) return zv::Val();
			}
			if (UNEXPECTED(!varType.ref().isObject())) {
				zend_throw_error(NULL, "Call to a member function isArray() on %s", zend_zval_value_name(varType.raw()));
				return zv::Val();
			}
			zend_long isArray = pt_type_op_trinary(Z_OBJ_P(varType.raw()), PT_OP_IS_ARRAY, 0, NULL);
			if (UNEXPECTED(isArray < 0)) return zv::Val();
			if (isArray == PT_TRI_YES) continue;
			{
				zval arrayAccessType;
				if (UNEXPECTED(!pt_object_type_new(&arrayAccessType, pt_ish_array_access))) return zv::Val();
				zv::Val arrayAccess = zv::Val::adopt(arrayAccessType);
				zv::Val isSuperType = pt_type_op(Z_OBJ_P(arrayAccess.raw()), PT_OP_IS_SUPER_TYPE_OF, 1, varType.raw());
				if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
				zend_long isSuperTypeValue = pt_type_result_trinary(isSuperType.raw());
				if (UNEXPECTED(isSuperTypeValue < 0)) return zv::Val();
				if (isSuperTypeValue == PT_TRI_NO) continue;
			}

			zv::Val methodCall;
			{
				zv::Val typeExpr = pt_type_new(PT_CLASS_TYPE_EXPR, 1, varType.raw());
				if (UNEXPECTED(typeExpr.isUndef())) return zv::Val();
				zv::Args callArgv{typeExpr.raw(), pt_ish_offset_exists};
				methodCall = pt_type_new(PT_CLASS_METHOD_CALL, 2, callArgv);
				if (UNEXPECTED(methodCall.isUndef())) return zv::Val();
			}
			zv::Val callThrowPoints = pt_method_throw_point_helper_get_throw_points_for_call_on_type(OBJ_PROP_NUM(self, slots::methodThrowPointHelper), scope.raw(), context, varType.raw(), methodCall.raw());
			if (UNEXPECTED(callThrowPoints.isUndef())) return zv::Val();
			if (UNEXPECTED(!callThrowPoints.ref().isArray())) {
				zend_type_error("array_merge(): Argument #2 must be of type array, %s given", zend_zval_value_name(callThrowPoints.raw()));
				return zv::Val();
			}
			throwPoints = arrayMerge(throwPoints.raw(), callThrowPoints.raw());
		}

		// foreach (array_reverse($expr->vars) as $var)
		{
			HashTable *table = Z_ARRVAL_P(vars.raw());
			zval *var;
			ZEND_HASH_REVERSE_FOREACH_VAL(table, var) {
				ZVAL_DEREF(var);
				zv::Val next = pt_node_scope_resolver_look_for_unset_allowed_undefined_expressions(nodeScopeResolver, scope.raw(), var);
				if (UNEXPECTED(next.isUndef())) return zv::Val();
				scope = std::move(next);
			} ZEND_HASH_FOREACH_END();
		}
		// foreach (array_reverse($nonNullabilityResults) as $nonNullabilityResult)
		{
			HashTable *table = nonNullabilityResults.table();
			zval *nonNullabilityResult;
			ZEND_HASH_REVERSE_FOREACH_VAL(table, nonNullabilityResult) {
				zval *specifiedExpressions = pt_ensured_non_nullability_result_specified_expressions(nonNullabilityResult, hold);
				if (UNEXPECTED(specifiedExpressions == NULL)) return zv::Val();
				zv::Val heldExpressions = zv::Val::copyOf(zv::Ref(specifiedExpressions));
				zv::Val next = pt_non_nullability_helper_revert_non_nullability(nonNullabilityHelper, scope.raw(), heldExpressions.raw());
				if (UNEXPECTED(next.isUndef())) return zv::Val();
				scope = std::move(next);
			} ZEND_HASH_FOREACH_END();
		}

		// capture the subjects' and their chain links' stored results
		zv::Val chainResults = zv::Val(zv::Arr::empty());
		{
			zval *defaultNarrowingHelper = OBJ_PROP_NUM(self, slots::defaultNarrowingHelper);
			for (zv::ArrayEntry entry : zv::TableRef(Z_ARRVAL_P(vars.raw()))) {
				if (UNEXPECTED(!captureChainResults(defaultNarrowingHelper, entry.value().deref().raw(), storage, chainResults))) return zv::Val();
			}
		}

		zv::Val varResultsValue(std::move(varResults));
		{
			zv::Args nodeArgv{expr, varResultsValue.raw()};
			zv::Val node = pt_type_new(PT_CLASS_ISSET_EXPRESSION_NODE, 2, nodeArgv);
			if (UNEXPECTED(node.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback_with_expression(nodeScopeResolver, nodeCallback, node.raw(), beforeScope, storage, context))) return zv::Val();
		}

		// the verdict and narrowing evaluate on the post-revert scope
		zval *afterScope = scope.raw();

		// lazily memoized multi-subject conjunction fold (a reference the
		// specifyTypesCallback shares with itself across asks)
		zv::Val foldAccTypes;
		{
			zval null = {};
			ZVAL_NULL(&null);
			zval reference;
			ZVAL_NEW_REF(&reference, &null);
			foldAccTypes = zv::Val::adopt(reference);
		}

		zv::Val variableFlow;
		{
			HashTable *resultsTable = Z_ARRVAL_P(varResultsValue.raw());
			zv::Arr flows = zv::Arr::create(zend_hash_num_elements(resultsTable));
			for (zv::ArrayEntry entry : zv::TableRef(resultsTable)) {
				zv::Val flow = pt_expression_result_variable_flow(entry.value().raw());
				if (UNEXPECTED(flow.isUndef())) return zv::Val();
				flows.push(std::move(flow));
			}
			variableFlow = pt_variable_flow_sequence_list(flows.table());
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		}

		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, varResultsValue.raw(), afterScope);
		zv::Val specifyTypesCallback;
		{
			zval captures[7];
			ZVAL_OBJ(&captures[0], self);
			ZVAL_COPY_VALUE(&captures[1], expr);
			ZVAL_COPY_VALUE(&captures[2], varResultsValue.raw());
			ZVAL_COPY_VALUE(&captures[3], chainResults.raw());
			ZVAL_COPY_VALUE(&captures[4], nodeScopeResolver);
			ZVAL_COPY_VALUE(&captures[5], afterScope);
			ZVAL_COPY_VALUE(&captures[6], foldAccTypes.raw());
			specifyTypesCallback = pt_native_closure_new(&specifyTypesCallbackBody, 7, captures, 1u << 6);
		}

		pt_expression_result_args args(scope.raw(), beforeScope, expr, hasYield, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return IssetHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* $scope->doNotTreatPhpDocTypesAsCertain() when native types are
	 * promoted, the scope itself otherwise (held in `promoted`) */
	static zval *evaluationScopeOf(zval *scope, bool nativeTypesPromoted, zv::Val &promoted)
	{
		if (!nativeTypesPromoted) return scope;
		promoted = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scope));
		return promoted.isUndef() ? NULL : promoted.raw();
	}

	/* static function (Type $type): ?bool — the isset verdict callback */
	static void notNullCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) captures;
		if (UNEXPECTED(!requireArguments(argc, 1, pt_ish_closure_name))) return;
		zval *type = &argv[0];
		ZVAL_DEREF(type);
		zend_class_entry *typeCe = pt_class(PT_CLASS_TYPE);
		if (UNEXPECTED(typeCe == NULL)) return;
		if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(type), typeCe))) {
			zend_type_error("%s(): Argument #1 ($type) must be of type PHPStan\\Type\\Type, %s given", pt_ish_closure_name, zend_zval_value_name(type));
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

	/* static function (bool $nativeTypesPromoted) use ($varResults,
	 * $afterScope): Type — captures: $varResults, $afterScope */
	static zv::Val resolveType(zval *captures, bool nativeTypesPromoted)
	{
		zval *varResults = &captures[0];
		zval *afterScope = &captures[1];
		bool issetIsNull = false;
		zv::Val promoted;
		zv::Val callback;
		for (zv::ArrayEntry entry : zv::TableRef(Z_ARRVAL_P(varResults))) {
			zval *varResult = entry.value().deref().raw();
			zval *s = evaluationScopeOf(afterScope, nativeTypesPromoted, promoted);
			if (UNEXPECTED(s == NULL)) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(varResult) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function getIssetabilityResolution() on %s", zend_zval_value_name(varResult));
				return zv::Val();
			}
			zv::Val resolution = pt_expression_result_get_issetability_resolution(varResult, s, false, false);
			if (UNEXPECTED(resolution.isUndef())) return zv::Val();
			if (callback.isUndef()) callback = pt_native_closure(&notNullCallbackBody);
			if (UNEXPECTED(!resolution.ref().isObject())) {
				zend_throw_error(NULL, "Call to a member function isSet() on %s", zend_zval_value_name(resolution.raw()));
				return zv::Val();
			}
			zv::Val result = pt_issetability_resolution_is_set(resolution.raw(), callback.raw());
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			if (Z_TYPE_P(result.raw()) != IS_NULL) {
				if (Z_TYPE_P(result.raw()) == IS_FALSE) return newConstantBooleanType(false);

				continue;
			}

			issetIsNull = true;
		}

		if (issetIsNull) return newBooleanType();

		return newConstantBooleanType(true);
	}

	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 1, pt_ish_closure_name))) return;
		zv::Val type = resolveType(captures, zend_is_true(&argv[0]));
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* function (TypeSpecifierContext $context, bool $nativeTypesPromoted) use
	 * ($expr, $varResults, $chainResults, $nodeScopeResolver, $afterScope,
	 * &$foldAccTypes): SpecifiedTypes — captures: $this, $expr, $varResults,
	 * $chainResults, $nodeScopeResolver, $afterScope, &$foldAccTypes */
	static zv::Val specifyTypes(zval *captures, zval *context, bool nativeTypesPromoted)
	{
		zend_object *handler = Z_OBJ(captures[0]);
		zval *defaultNarrowingHelper = OBJ_PROP_NUM(handler, slots::defaultNarrowingHelper);
		zval *expr = &captures[1];
		zval *varResults = &captures[2];
		zval *chainResults = &captures[3];
		zval *nodeScopeResolver = &captures[4];
		zval *afterScope = &captures[5];
		zval *foldAccTypes = &captures[6];

		// the type of an already-processed chain link, read from its captured
		// result on the evaluation point
		zv::Val promoted;
		zval *evaluationScope = evaluationScopeOf(afterScope, nativeTypesPromoted, promoted);
		if (UNEXPECTED(evaluationScope == NULL)) return zv::Val();
		zv::Val readType = pt_default_narrowing_helper_build_chain_type_reader(defaultNarrowingHelper, chainResults, evaluationScope);
		if (UNEXPECTED(readType.isUndef())) return zv::Val();

		zval *vars = exprVars(expr);
		if (UNEXPECTED(vars == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(vars) != IS_ARRAY)) {
			zend_type_error("count(): Argument #1 ($value) must be of type Countable|array, %s given", zend_zval_value_name(vars));
			return zv::Val();
		}
		uint32_t varCount = zend_hash_num_elements(Z_ARRVAL_P(vars));
		bool isNullContext = false;
		if (varCount != 0 && UNEXPECTED(!contextFlag(context, pt_type_specifier_context_null, "null", isNullContext))) return zv::Val();
		if (varCount == 0 || isNullContext) return pt_default_narrowing_helper_specify_default_types(defaultNarrowingHelper, expr, context);

		bool isTrueContext;
		if (varCount > 1) {
			// isset($a, $b) is true only when every subject is set
			if (UNEXPECTED(!contextFlag(context, pt_type_specifier_context_true, "true", isTrueContext))) return zv::Val();
			if (isTrueContext) {
				zv::Val types = pt_specified_types_new();
				if (UNEXPECTED(types.isUndef())) return zv::Val();
				for (zv::ArrayEntry entry : zv::TableRef(Z_ARRVAL_P(vars))) {
					zv::Val chainTypes = pt_default_narrowing_helper_create_isset_truthy_chain_types(defaultNarrowingHelper, evaluationScope, entry.value().deref().raw(), readType.raw(), expr, context);
					if (UNEXPECTED(chainTypes.isUndef())) return zv::Val();
					if (UNEXPECTED(!types.ref().isObject())) {
						zend_throw_error(NULL, "Call to a member function unionWith() on %s", zend_zval_value_name(types.raw()));
						return zv::Val();
					}
					types = pt_specified_types_union_with(Z_OBJ_P(types.raw()), chainTypes.raw());
					if (UNEXPECTED(types.isUndef())) return zv::Val();
				}

				return setRootExpr(std::move(types), expr);
			}

			// non-true contexts: fold the subjects through the conjunction
			// narrowing; the accumulated closure is built once, reused across asks
			zval *memo = Z_REFVAL_P(foldAccTypes);
			if (Z_TYPE_P(memo) != IS_NULL) {
				zv::Val held = zv::Val::copyOf(zv::Ref(memo));
				zv::Args memoArgv{evaluationScope, context};
				return setRootExpr(pt_type_call_callable(held.raw(), 2, memoArgv), expr);
			}

			zval *firstVar = zend_hash_index_find(Z_ARRVAL_P(vars), 0);
			zval *firstResult = zend_hash_index_find(Z_ARRVAL_P(varResults), 0);
			if (UNEXPECTED(firstVar == NULL || firstResult == NULL)) {
				// $expr->vars and $varResults are parallel lists
				pt_throw_should_not_happen();
				return zv::Val();
			}
			zv::Val attributes = nodeGetAttributes(expr);
			if (UNEXPECTED(attributes.isUndef())) return zv::Val();
			zv::Val accExpr = newIsset(firstVar, attributes.raw());
			if (UNEXPECTED(accExpr.isUndef())) return zv::Val();
			zv::Val accTypes = pt_native_closure(&subjectTypesBody, handler, chainResults, expr, firstVar, firstResult);
			zv::Val accTruthyScope = applySpecifiedTypes(afterScope, callNarrowing(accTypes.raw(), afterScope, pt_type_specifier_context_create_truthy()));
			if (UNEXPECTED(accTruthyScope.isUndef())) return zv::Val();
			zv::Val accFalseyScope = applySpecifiedTypes(afterScope, callNarrowing(accTypes.raw(), afterScope, pt_type_specifier_context_create_falsey()));
			if (UNEXPECTED(accFalseyScope.isUndef())) return zv::Val();

			for (uint32_t i = 1; i < varCount; i++) {
				zval *var = zend_hash_index_find(Z_ARRVAL_P(vars), i);
				zval *varResult = zend_hash_index_find(Z_ARRVAL_P(varResults), i);
				if (UNEXPECTED(var == NULL || varResult == NULL)) {
					// $expr->vars and $varResults are parallel lists
					pt_throw_should_not_happen();
					return zv::Val();
				}
				attributes = nodeGetAttributes(expr);
				if (UNEXPECTED(attributes.isUndef())) return zv::Val();
				zv::Val rightExprNode = newIsset(var, attributes.raw());
				if (UNEXPECTED(rightExprNode.isUndef())) return zv::Val();
				zv::Val rightTypes = pt_native_closure(&subjectTypesBody, handler, chainResults, expr, var, varResult);
				zv::Val rightFalseyScope = applySpecifiedTypes(accTruthyScope.raw(), callNarrowing(rightTypes.raw(), accTruthyScope.raw(), pt_type_specifier_context_create_falsey()));
				if (UNEXPECTED(rightFalseyScope.isUndef())) return zv::Val();

				zv::Val leftExprNode = std::move(accExpr);
				zval conjunctionCaptures[10];
				ZVAL_OBJ(&conjunctionCaptures[0], handler);
				ZVAL_COPY_VALUE(&conjunctionCaptures[1], nodeScopeResolver);
				ZVAL_COPY_VALUE(&conjunctionCaptures[2], expr);
				ZVAL_COPY_VALUE(&conjunctionCaptures[3], leftExprNode.raw());
				ZVAL_COPY_VALUE(&conjunctionCaptures[4], accTypes.raw());
				ZVAL_COPY_VALUE(&conjunctionCaptures[5], accTruthyScope.raw());
				ZVAL_COPY_VALUE(&conjunctionCaptures[6], accFalseyScope.raw());
				ZVAL_COPY_VALUE(&conjunctionCaptures[7], rightExprNode.raw());
				ZVAL_COPY_VALUE(&conjunctionCaptures[8], rightTypes.raw());
				ZVAL_COPY_VALUE(&conjunctionCaptures[9], rightFalseyScope.raw());
				accTypes = pt_native_closure_new(&conjunctionBody, 10, conjunctionCaptures);

				zv::Args andArgv{leftExprNode.raw(), rightExprNode.raw()};
				accExpr = pt_type_new(PT_CLASS_BOOLEAN_AND_EXPR, 2, andArgv);
				if (UNEXPECTED(accExpr.isUndef())) return zv::Val();
				accTruthyScope = applySpecifiedTypes(accTruthyScope.raw(), callNarrowing(rightTypes.raw(), accTruthyScope.raw(), pt_type_specifier_context_create_truthy()));
				if (UNEXPECTED(accTruthyScope.isUndef())) return zv::Val();
				accFalseyScope = applySpecifiedTypes(afterScope, callNarrowing(accTypes.raw(), afterScope, pt_type_specifier_context_create_falsey()));
				if (UNEXPECTED(accFalseyScope.isUndef())) return zv::Val();
			}

			// $foldAccTypes = $accTypes
			memo = Z_REFVAL_P(foldAccTypes);
			zval previous;
			ZVAL_COPY_VALUE(&previous, memo);
			ZVAL_COPY(memo, accTypes.raw());
			zval_ptr_dtor(&previous);

			zv::Args accArgv{evaluationScope, context};
			return setRootExpr(pt_type_call_callable(accTypes.raw(), 2, accArgv), expr);
		}

		zval *issetExpr = zend_hash_index_find(Z_ARRVAL_P(vars), 0);
		if (UNEXPECTED(issetExpr == NULL)) {
			// $expr->vars and $varResults are parallel lists
			pt_throw_should_not_happen();
			return zv::Val();
		}
		if (UNEXPECTED(!contextFlag(context, pt_type_specifier_context_true, "true", isTrueContext))) return zv::Val();
		if (!isTrueContext) {
			zval *varResult = zend_hash_index_find(Z_ARRVAL_P(varResults), 0);
			if (UNEXPECTED(varResult == NULL)) {
				// $expr->vars and $varResults are parallel lists
				pt_throw_should_not_happen();
				return zv::Val();
			}
			return pt_default_narrowing_helper_create_isset_single_subject_non_true_types(defaultNarrowingHelper, evaluationScope, issetExpr, varResult, readType.raw(), context, expr);
		}

		return pt_default_narrowing_helper_create_isset_truthy_chain_types(defaultNarrowingHelper, evaluationScope, issetExpr, readType.raw(), expr, context);
	}

	static void specifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 2, pt_ish_closure_name))) return;
		zv::Val specifiedTypes = specifyTypes(captures, &argv[0], zend_is_true(&argv[1]));
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}

	/* function (MutatingScope $scope, TypeSpecifierContext $ctx) use
	 * ($chainResults, $expr, $var, $varResult): SpecifiedTypes — the closure
	 * $makeSubjectTypes creates; captures: $this, $chainResults, $expr, $var,
	 * $varResult */
	static void subjectTypesBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 2, pt_ish_closure_name))) return;
		zval *defaultNarrowingHelper = OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::defaultNarrowingHelper);
		zval *chainResults = &captures[1];
		zval *expr = &captures[2];
		zval *var = &captures[3];
		zval *varResult = &captures[4];
		zval *scope = &argv[0];
		zval *ctx = &argv[1];

		zv::Val scopedReadType = pt_default_narrowing_helper_build_chain_type_reader(defaultNarrowingHelper, chainResults, scope);
		if (UNEXPECTED(scopedReadType.isUndef())) return;
		bool flag;
		if (UNEXPECTED(!contextFlag(ctx, pt_type_specifier_context_null, "null", flag))) return;
		zv::Val specifiedTypes;
		if (flag) {
			zv::Val attributes = nodeGetAttributes(expr);
			if (UNEXPECTED(attributes.isUndef())) return;
			zv::Val issetNode = newIsset(var, attributes.raw());
			if (UNEXPECTED(issetNode.isUndef())) return;
			specifiedTypes = pt_default_narrowing_helper_specify_default_types(defaultNarrowingHelper, issetNode.raw(), ctx);
		} else {
			if (UNEXPECTED(!contextFlag(ctx, pt_type_specifier_context_true, "true", flag))) return;
			specifiedTypes = !flag
				? pt_default_narrowing_helper_create_isset_single_subject_non_true_types(defaultNarrowingHelper, scope, var, varResult, scopedReadType.raw(), ctx, expr)
				: pt_default_narrowing_helper_create_isset_truthy_chain_types(defaultNarrowingHelper, scope, var, scopedReadType.raw(), expr, ctx);
		}
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}

	/* static fn (): MutatingScope => $scope — captures: $scope */
	static void constantScopeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) argc;
		(void) argv;
		ZVAL_COPY(return_value, &captures[0]);
	}

	/* fn (MutatingScope $scope, TypeSpecifierContext $ctx): SpecifiedTypes =>
	 * $this->booleanNarrowingHelper->specifyConjunction(...) — captures:
	 * $this, $nodeScopeResolver, $expr, $leftExprNode, $leftTypes,
	 * $leftTruthyScope, $leftFalseyScope, $rightExprNode, $rightTypes,
	 * $rightFalseyScope */
	static void conjunctionBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 2, pt_ish_closure_name))) return;
		zval *booleanNarrowingHelper = OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::booleanNarrowingHelper);
		if (UNEXPECTED(Z_TYPE_P(booleanNarrowingHelper) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function specifyConjunction() on %s", zend_zval_value_name(booleanNarrowingHelper));
			return;
		}
		zv::Val leftTruthyScope = pt_native_closure(&constantScopeBody, &captures[5]);
		zv::Val leftFalseyScope = pt_native_closure(&constantScopeBody, &captures[6]);
		zv::Val rightFalseyScope = pt_native_closure(&constantScopeBody, &captures[9]);
		zv::Val specifiedTypes = pt_boolean_narrowing_helper_specify_conjunction(Z_OBJ_P(booleanNarrowingHelper), &captures[1], &argv[0], &argv[1], &captures[2], &captures[3], &captures[4], leftTruthyScope.raw(), leftFalseyScope.raw(), &captures[7], &captures[8], rightFalseyScope.raw());
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::IssetHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_isset_handler)
{
	pt_ish_array_access = zend_string_init_interned(PT_LC("ArrayAccess"), 1);
	pt_ish_offset_exists = zend_string_init_interned(PT_LC("offsetExists"), 1);

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\IssetHandler");
	ptdecl::IssetHandler::declareClass(cls);
	ptdecl::IssetHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nonNullabilityHelper, *expressionResultFactory, *methodThrowPointHelper, *defaultNarrowingHelper, *booleanNarrowingHelper;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, nonNullabilityHelper, expressionResultFactory, methodThrowPointHelper, defaultNarrowingHelper, booleanNarrowingHelper)) RETURN_THROWS();
		IssetHandler(Z_OBJ_P(ZEND_THIS)).construct(nonNullabilityHelper, expressionResultFactory, methodThrowPointHelper, defaultNarrowingHelper, booleanNarrowingHelper);
	});

	cls.method<&IssetHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(IssetHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_isset_handler);
	pt_expr_handler_entry_register(&pt_ce_isset_handler, &IssetHandler::processExprEntry);
}

/* }}} */
