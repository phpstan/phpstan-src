/*
 * PHPStanTurbo\ClosureProcessor — native implementation of
 * PHPStan\Analyser\ClosureProcessor.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processClosureNode(),
 * processArrowFunctionNode() and processImmediatelyCalledCallable() — called
 * by the closure / arrow function handlers and ArgumentsHandler — are
 * exported as pt_closure_processor_*() (Engine.h conventions). ParametersProcessor
 * is still looked up in the container per call, as the twin does (the
 * attribute → argument → closure recursion constructor injection cannot
 * express).
 *
 * The body walks recurse through NodeScopeResolver's direct entries, which
 * keep the fresh-stack guard on the path. The gatherer frames the twin pushes
 * are native closures capturing what the PHP closures capture: the closure
 * gatherer's nine lists and $closureScope by reference (the convergence loop
 * and the replay reassign $closureScope after the gatherer is created), the
 * arrow function gatherer's scope by value. The arrow function result's
 * `static fn () => new MixedType()` is a native closure too.
 *
 * ClosureParameterResolver, ClosureTypeResolver, ParametersProcessor,
 * MutatingScope, ExpressionContext / StatementContext, the storage,
 * statement / expression results, ProcessClosureResult /
 * ProcessArrowFunctionResult, VariableLivenessResolver, VariableFlow,
 * RecordingNodeCallback and NodeScopeResolver are called through their direct
 * entries; the virtual nodes the twin emits (InClosureNode,
 * ClosureReturnStatementsNode, InArrowFunctionNode, ReturnStatement,
 * InvalidateExprNode) and NodeFinder's matching stay PHP classes / the native
 * node finder.
 */

#include "support.h"
#include "generated/ClosureProcessor.h"

namespace slots = ptdecl::ClosureProcessor::slot;
namespace sigs = ptdecl::ClosureProcessor::sig;
#include "ClosureSupport.h"
#include "generated/RecordingNodeCallback.h"

zend_class_entry *pt_ce_closure_processor = nullptr;

namespace {

/* NodeScopeResolver::GENERALIZE_AFTER_ITERATION / ::LOOP_SCOPE_ITERATIONS */
constexpr zend_long PT_CP_GENERALIZE_AFTER_ITERATION_LIMIT = 1;
constexpr zend_long PT_CP_LOOP_SCOPE_ITERATIONS_LIMIT = 3;

/* the attribute names (ClosureArgVisitor::ATTRIBUTE_NAME,
 * ArrowFunctionArgVisitor::ATTRIBUTE_NAME,
 * ImmediatelyInvokedClosureVisitor::ATTRIBUTE_NAME) and 'this', permanent
 * interned strings (module startup) */
zend_string *pt_cp_closure_call_args = nullptr;
zend_string *pt_cp_arrow_function_call_args = nullptr;
zend_string *pt_cp_immediately_invoked_closure = nullptr;
zend_string *pt_cp_this = nullptr;

pt_method_site pt_cp_get_by_type_site;
pt_method_site pt_cp_invalidate_expr_node_get_expr_site;
pt_property_site pt_cp_invalidate_expr_node_expr_site;
pt_property_site pt_cp_return_node_site;

/* $this->container->getByType(ParametersProcessor::class) */
zv::Val getParametersProcessor(zval *container)
{
	if (UNEXPECTED(Z_TYPE_P(container) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function getByType() on %s", zend_zval_value_name(container));
		return zv::Val();
	}
	zval className;
	ZVAL_STRINGL(&className, "PHPStan\\Analyser\\ParametersProcessor", sizeof("PHPStan\\Analyser\\ParametersProcessor") - 1);
	zv::Val service = pt_call_method_cached(pt_cp_get_by_type_site, Z_OBJ_P(container), PT_LC("getbytype"), 1, &className);
	zval_ptr_dtor(&className);
	return service;
}

/* $invalidateExprNode->getExpr(): the final node's promoted $expr, the method
 * of anything else; UNDEF = pending exception */
zv::Val invalidateExprNodeExpr(zval *node)
{
	if (UNEXPECTED(Z_TYPE_P(node) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function getExpr() on %s", zend_zval_value_name(node));
		return zv::Val();
	}
	zend_class_entry *invalidateExprNodeCe = pt_class(PT_CLASS_INVALIDATE_EXPR_NODE);
	if (UNEXPECTED(invalidateExprNodeCe == NULL)) return zv::Val();
	if (EXPECTED(Z_OBJCE_P(node) == invalidateExprNodeCe)) {
		zval *expr = ptclosure::prop(pt_cp_invalidate_expr_node_expr_site, node, PT_LC("expr"));
		return expr != NULL ? zv::Val::copyOf(zv::Ref(expr)) : zv::Val();
	}
	return pt_call_method_cached(pt_cp_invalidate_expr_node_get_expr_site, Z_OBJ_P(node), PT_LC("getexpr"), 0, NULL);
}

/* static function (Node $node, Scope $scope) use (&$executionEnds,
 * &$gatheredReturnStatements, &$gatheredReturnStatementsAfterFinally,
 * &$gatheredReturnStatementsWithScope, &$gatheredYieldStatements,
 * &$gatheredYieldStatementsWithScope, &$closureScope, &$closureImpurePoints,
 * &$invalidateExpressions): void — captures in that order, all by reference */
enum : uint32_t
{
	PT_CP_EXECUTION_ENDS = 0,
	PT_CP_RETURN_STATEMENTS,
	PT_CP_RETURN_STATEMENTS_AFTER_FINALLY,
	PT_CP_RETURN_STATEMENTS_WITH_SCOPE,
	PT_CP_YIELD_STATEMENTS,
	PT_CP_YIELD_STATEMENTS_WITH_SCOPE,
	PT_CP_CLOSURE_SCOPE,
	PT_CP_CLOSURE_IMPURE_POINTS,
	PT_CP_INVALIDATE_EXPRESSIONS,
	PT_CP_CLOSURE_GATHERER_CAPTURES,
};

void closureGathererBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
{
	(void) return_value;
	if (UNEXPECTED(!ptcall::requireArguments(argc, 2, "PHPStan\\Analyser\\ClosureProcessor::{closure}"))) return;
	zval *node = &argv[0];
	zval *scope = &argv[1];
	if (ptclosure::differentAnonymousFunction(scope, Z_REFVAL(captures[PT_CP_CLOSURE_SCOPE])) != 0) return;

	int is = ptclosure::instanceOf(node, PT_CLASS_PROPERTY_ASSIGN_NODE);
	if (UNEXPECTED(is < 0)) return;
	if (is) {
		(void) ptclosure::gatherPropertyAssign(node, scope, &captures[PT_CP_CLOSURE_IMPURE_POINTS], &captures[PT_CP_INVALIDATE_EXPRESSIONS]);
		return;
	}
	is = ptclosure::instanceOf(node, PT_CLASS_EXECUTION_END_NODE);
	if (UNEXPECTED(is < 0)) return;
	if (is) {
		ptsh::appendToReference(&captures[PT_CP_EXECUTION_ENDS], node);
		return;
	}
	is = ptclosure::instanceOf(node, PT_CLASS_RETURN_AFTER_FINALLY_NODE);
	if (UNEXPECTED(is < 0)) return;
	if (is) {
		zval *returnNode = ptclosure::prop(pt_cp_return_node_site, node, PT_LC("returnNode"));
		if (UNEXPECTED(returnNode == NULL)) return;
		zv::Args statementArgv{scope, returnNode};
		zv::Val statement = pt_type_new(PT_CLASS_RETURN_STATEMENT, 2, statementArgv);
		if (UNEXPECTED(statement.isUndef())) return;
		ptsh::appendToReference(&captures[PT_CP_RETURN_STATEMENTS_AFTER_FINALLY], statement.raw());
		return;
	}
	is = ptclosure::instanceOf(node, PT_CLASS_INVALIDATE_EXPR_NODE);
	if (UNEXPECTED(is < 0)) return;
	if (is) {
		ptsh::appendToReference(&captures[PT_CP_INVALIDATE_EXPRESSIONS], node);
		return;
	}
	is = ptclosure::instanceOf(node, PT_CLASS_YIELD);
	if (UNEXPECTED(is < 0)) return;
	if (!is) {
		is = ptclosure::instanceOf(node, PT_CLASS_YIELD_FROM);
		if (UNEXPECTED(is < 0)) return;
	}
	if (is) {
		ptsh::appendToReference(&captures[PT_CP_YIELD_STATEMENTS], node);
		zv::Val pair = ptclosure::pairOf(node, scope);
		ptsh::appendToReference(&captures[PT_CP_YIELD_STATEMENTS_WITH_SCOPE], pair.raw());
	}
	is = ptclosure::instanceOf(node, PT_CLASS_RETURN_STMT);
	if (UNEXPECTED(is < 0) || !is) return;

	zv::Args statementArgv{scope, node};
	zv::Val statement = pt_type_new(PT_CLASS_RETURN_STATEMENT, 2, statementArgv);
	if (UNEXPECTED(statement.isUndef())) return;
	ptsh::appendToReference(&captures[PT_CP_RETURN_STATEMENTS], statement.raw());
	zv::Val pair = ptclosure::pairOf(node, scope);
	ptsh::appendToReference(&captures[PT_CP_RETURN_STATEMENTS_WITH_SCOPE], pair.raw());
}

/* static function (Node $node, Scope $innerScope) use ($arrowFunctionScope,
 * &$arrowFunctionImpurePoints, &$invalidateExpressions): void */
void arrowFunctionGathererBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
{
	(void) return_value;
	ptclosure::arrowFunctionGatherer(captures, argc, argv, "PHPStan\\Analyser\\ClosureProcessor::{closure}");
}

/* static fn () => new MixedType() */
void mixedTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
{
	(void) captures;
	(void) argc;
	(void) argv;
	zv::Val type = pt_type_new_mixed_type();
	if (UNEXPECTED(type.isUndef())) return;
	type.intoReturnValue(return_value);
}

/* NodeFinder's filter `$node instanceof Variable && in_array($node->name, $uses, true)` */
struct UsedVariableFindCtx : pt_find_ctx
{
	HashTable *uses;
};

bool usedVariableMatcher(zend_object *node, void *vctx)
{
	UsedVariableFindCtx *ctx = static_cast<UsedVariableFindCtx *>(static_cast<pt_find_ctx *>(vctx));
	if (!instanceof_function(node->ce, ctx->target_ce)) return false;
	zval *name = pt_property_cached(ptclosure::variableNameSite, node, PT_LC("name"));
	if (UNEXPECTED(name == NULL)) return false;
	ZVAL_DEREF(name);
	for (zv::ArrayEntry entry : zv::TableRef(ctx->uses)) {
		if (zend_is_identical(name, entry.value().deref().raw())) return true;
	}
	return false;
}

/* array_merge($a, $b) of two lists */
zv::Val mergeLists(zval *a, zval *b)
{
	zv::Arr merged = zv::Arr::empty();
	if (UNEXPECTED(!pt_callable_array_merge_into(merged, a) || !pt_callable_array_merge_into(merged, b))) return zv::Val();
	return zv::Val(std::move(merged));
}

/* array_map(static fn (InternalThrowPoint $throwPoint) => $throwPoint->toPublic(), $throwPoints) */
zv::Val throwPointsToPublic(zval *throwPoints)
{
	HashTable *table = Z_ARRVAL_P(throwPoints);
	uint32_t count = zend_hash_num_elements(table);
	if (count == 0) return zv::Val(zv::Arr::empty());
	zv::Arr mapped = zv::Arr::create(count);
	for (zv::ArrayEntry entry : zv::TableRef(table)) {
		zv::Val publicPoint = pt_internal_throw_point_to_public(entry.value().deref().raw());
		if (UNEXPECTED(publicPoint.isUndef())) return zv::Val();
		if (entry.hasStringKey()) {
			mapped.set(entry.stringKey(), std::move(publicPoint));
		} else {
			zval value = publicPoint.take();
			zend_hash_index_update(mapped.table(), entry.indexKey(), &value);
		}
	}
	return zv::Val(std::move(mapped));
}

/* an owned `$x = []` captured by reference */
inline zv::Val newListReference()
{
	return ptsh::newArrayReference();
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ClosureProcessor; UNDEF / false = pending
 * exception. */
class ClosureProcessor
{
public:
	explicit ClosureProcessor(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *container, zval *expressionResultFactory, zval *closureParameterResolver, zval *closureTypeResolver)
	{
		writeSlot(slots::container, container);
		writeSlot(slots::expressionResultFactory, expressionResultFactory);
		writeSlot(slots::closureParameterResolver, closureParameterResolver);
		writeSlot(slots::closureTypeResolver, closureTypeResolver);
	}

	/* Mirrors processClosureNode() over processClosureNodeInternal()
	 * ($passedToType / $nativePassedToType NULL for null) */
	zv::Val processClosureNode(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context, zval *passedToType, zval *nativePassedToType) const
	{
		ClosureWalk w;
		w.nodeScopeResolver = nodeScopeResolver;
		w.stmt = stmt;
		w.expr = expr;
		w.storage = storage;
		w.nodeCallback = nodeCallback;
		w.context = context;
		w.scope = zv::Val::copyOf(zv::Ref(scope));
		if (UNEXPECTED(!enterClosure(w, passedToType, nativePassedToType))) return zv::Val();
		if (zend_hash_num_elements(w.byRefUses.table()) == 0) {
			if (UNEXPECTED(!walkClosureBody(w))) return zv::Val();
		} else {
			if (UNEXPECTED(!convergeClosureBody(w))) return zv::Val();
		}
		return finishClosure(w);
	}

	/* Mirrors processImmediatelyCalledCallable() */
	zv::Val processImmediatelyCalledCallable(zval *scopeArg, zval *invalidatedExpressions, zval *usesArg) const
	{
		bool inClass = false;
		if (UNEXPECTED(!pt_mutating_scope_is_in_class(Z_OBJ_P(scopeArg), inClass))) return zv::Val();
		zv::Arr uses = zv::Arr::copyOfTable(Z_ARRVAL_P(usesArg));
		if (inClass) {
			uses.push(zv::Val::string(pt_cp_this));
		}

		zv::Val scope = zv::Val::copyOf(zv::Ref(scopeArg));
		if (zend_hash_num_elements(Z_ARRVAL_P(invalidatedExpressions)) == 0) return scope;
		UsedVariableFindCtx ctx{};
		ctx.target_ce = pt_class(PT_CLASS_VARIABLE);
		if (UNEXPECTED(ctx.target_ce == NULL)) return zv::Val();
		ctx.uses = uses.table();
		zv::Val invalidatedHold = zv::Val::copyOf(zv::Ref(invalidatedExpressions));
		for (zv::ArrayEntry entry : zv::ArrRef(invalidatedHold.raw())) {
			zval *invalidateExpression = entry.value().deref().raw();
			zv::Val expr = invalidateExprNodeExpr(invalidateExpression);
			if (UNEXPECTED(expr.isUndef())) return zv::Val();
			if (UNEXPECTED(!expr.ref().isObject())) continue;
			zend_object *found = pt_find_first_recursive(Z_OBJ_P(expr.raw()), usedVariableMatcher, static_cast<pt_find_ctx *>(&ctx));
			if (UNEXPECTED(ctx.failed || EG(exception))) return zv::Val();
			if (found == NULL) continue;

			zv::Val requireExpr = invalidateExprNodeExpr(invalidateExpression);
			if (UNEXPECTED(requireExpr.isUndef())) return zv::Val();
			bool requireMoreCharacters = requireExpr.ref().isObject() && instanceof_function(Z_OBJCE_P(requireExpr.raw()), ctx.target_ce);
			zv::Val invalidatedExpr = invalidateExprNodeExpr(invalidateExpression);
			if (UNEXPECTED(invalidatedExpr.isUndef())) return zv::Val();
			zv::Val invalidated = pt_mutating_scope_invalidate_expression(Z_OBJ_P(scope.raw()), invalidatedExpr.raw(), requireMoreCharacters);
			if (UNEXPECTED(invalidated.isUndef())) return zv::Val();
			scope = std::move(invalidated);
		}
		return scope;
	}

	/* Mirrors processArrowFunctionNode() ($passedToType / $nativePassedToType /
	 * $context NULL for null) */
	zv::Val processArrowFunctionNode(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scopeArg, zval *storage, zval *nodeCallback, zval *passedToType, zval *nativePassedToType, zval *contextArg) const
	{
		zv::Val contextHold;
		zval *context = contextArg;
		if (context == NULL) {
			contextHold = pt_expression_context_create_top_level();
			if (UNEXPECTED(contextHold.isUndef())) return zv::Val();
			context = contextHold.raw();
		}
		if (UNEXPECTED(!processParams(nodeScopeResolver, stmt, expr, scopeArg, storage, nodeCallback))) return zv::Val();
		zval *returnType = ptclosure::prop(ptclosure::returnTypeSite, expr, PT_LC("returnType"));
		if (UNEXPECTED(returnType == NULL)) return zv::Val();
		if (Z_TYPE_P(returnType) != IS_NULL) {
			zv::Val returnTypeHold = zv::Val::copyOf(zv::Ref(returnType));
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, returnTypeHold.raw(), scopeArg, storage))) return zv::Val();
		}

		zval *callArgs = ptclosure::attribute(expr, pt_cp_arrow_function_call_args);
		zv::Val callArgsHold = callArgs != NULL ? zv::Val::copyOf(zv::Ref(callArgs)) : zv::Val::null();
		zv::Val callableParameters, nativeCallableParameters;
		if (UNEXPECTED(!pt_closure_parameter_resolver_resolve(slot(slots::closureParameterResolver), scopeArg, expr, storage, callArgsHold.raw(), passedToType, nativePassedToType, callableParameters, nativeCallableParameters))) return zv::Val();
		zv::Val arrowFunctionScope = pt_mutating_scope_enter_arrow_function(Z_OBJ_P(scopeArg), expr, callableParameters.raw(), nativeCallableParameters.raw());
		if (UNEXPECTED(arrowFunctionScope.isUndef())) return zv::Val();
		if (UNEXPECTED(!arrowFunctionScope.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function getAnonymousFunctionReflection() on %s", zend_zval_value_name(arrowFunctionScope.raw()));
			return zv::Val();
		}
		{
			zv::Val reflection = pt_mutating_scope_get_anonymous_function_reflection(Z_OBJ_P(arrowFunctionScope.raw()));
			if (UNEXPECTED(reflection.isUndef())) return zv::Val();
			if (UNEXPECTED(reflection.isNull())) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
		}

		// Gather the property-assign impure points and invalidate expressions the
		// arrow function type needs (mirroring ClosureTypeResolver::getClosureType()),
		// on top of the regular rule node callback, so the single body walk here
		// feeds ClosureTypeResolver::buildClosureTypeForArrowFunction().
		zv::Val arrowFunctionImpurePoints = newListReference();
		zv::Val invalidateExpressions = newListReference();
		zval captures[3];
		ZVAL_COPY_VALUE(&captures[0], arrowFunctionScope.raw());
		ZVAL_COPY_VALUE(&captures[1], arrowFunctionImpurePoints.raw());
		ZVAL_COPY_VALUE(&captures[2], invalidateExpressions.raw());
		zv::Val gatherer = pt_native_closure_new(&arrowFunctionGathererBody, 3, captures, 0b110);

		if (UNEXPECTED(!pt_node_scope_resolver_push_node_gatherer(nodeScopeResolver, gatherer.raw()))) return zv::Val();
		zv::Val exprResult;
		{
			zval *body = ptclosure::prop(ptclosure::arrowExprSite, expr, PT_LC("expr"));
			bool resolveTemplateArguments = false;
			if (EXPECTED(body != NULL) && EXPECTED(pt_expression_context_should_resolve_template_arguments(context, resolveTemplateArguments))) {
				zv::Val bodyHold = zv::Val::copyOf(zv::Ref(body));
				zv::Val bodyContext = pt_expression_context_create_top_level(resolveTemplateArguments);
				if (EXPECTED(!bodyContext.isUndef())) {
					exprResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, bodyHold.raw(), arrowFunctionScope.raw(), storage, nodeCallback, bodyContext.raw());
				}
			}
		}
		pt_finally([&]() { (void) pt_node_scope_resolver_pop_node_gatherer(nodeScopeResolver); });
		if (UNEXPECTED(exprResult.isUndef() || EG(exception) != NULL)) return zv::Val();

		zv::Val scope;
		{
			zv::Val resultScopeHold;
			zval *resultScope = pt_expression_result_scope(exprResult.raw(), resultScopeHold);
			if (UNEXPECTED(resultScope == NULL)) return zv::Val();
			zv::Val constraints = pt_mutating_scope_get_template_argument_constraints(Z_OBJ_P(resultScope));
			if (UNEXPECTED(constraints.isUndef())) return zv::Val();
			scope = pt_mutating_scope_add_template_argument_constraints(Z_OBJ_P(scopeArg), constraints.raw());
			if (UNEXPECTED(scope.isUndef())) return zv::Val();
			zv::Val sendConstraints = pt_node_scope_resolver_collect_return_send(nodeScopeResolver, arrowFunctionScope.raw(), exprResult.raw());
			if (UNEXPECTED(sendConstraints.isUndef())) return zv::Val();
			scope = pt_mutating_scope_add_template_argument_constraints(Z_OBJ_P(scope.raw()), sendConstraints.raw());
			if (UNEXPECTED(scope.isUndef())) return zv::Val();
		}

		zv::Val throwPointsHold, impurePointsHold;
		zval *throwPoints = pt_expression_result_throw_points(exprResult.raw(), throwPointsHold);
		if (UNEXPECTED(throwPoints == NULL)) return zv::Val();
		zv::Val closureTypeThrowPoints = throwPointsToPublic(throwPoints);
		if (UNEXPECTED(closureTypeThrowPoints.isUndef())) return zv::Val();
		zval *impurePoints = pt_expression_result_impure_points(exprResult.raw(), impurePointsHold);
		if (UNEXPECTED(impurePoints == NULL)) return zv::Val();
		zv::Val closureTypeImpurePoints = mergeLists(Z_REFVAL_P(arrowFunctionImpurePoints.raw()), impurePoints);
		if (UNEXPECTED(closureTypeImpurePoints.isUndef())) return zv::Val();

		// The arrow scope was entered with a shallow reflection (parameters +
		// declared return, no body walk). Now that the single body walk above has
		// run, build the refined arrow function type from the body expression's
		// stored type (no second walk) and fire InArrowFunctionNode with it.
		zv::Val invalidateHold = zv::Val::copyOf(zv::Ref(Z_REFVAL_P(invalidateExpressions.raw())));
		zv::Val refinedArrowFunctionType = pt_closure_type_resolver_build_closure_type_for_arrow_function(slot(slots::closureTypeResolver), scope.raw(), expr, arrowFunctionScope.raw(), closureTypeThrowPoints.raw(), closureTypeImpurePoints.raw(), invalidateHold.raw(), false, storage);
		if (UNEXPECTED(refinedArrowFunctionType.isUndef())) return zv::Val();
		zv::Val refinedArrowFunctionScope = pt_mutating_scope_with_anonymous_function_reflection(Z_OBJ_P(arrowFunctionScope.raw()), refinedArrowFunctionType.raw());
		if (UNEXPECTED(refinedArrowFunctionScope.isUndef())) return zv::Val();
		zv::Args nodeArgv{refinedArrowFunctionType.raw(), expr};
		zv::Val inArrowFunctionNode = pt_type_new(PT_CLASS_IN_ARROW_FUNCTION_NODE, 2, nodeArgv);
		if (UNEXPECTED(inArrowFunctionNode.isUndef())) return zv::Val();
		if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, inArrowFunctionNode.raw(), refinedArrowFunctionScope.raw(), storage))) return zv::Val();

		bool isAlwaysTerminating = false;
		if (UNEXPECTED(!pt_expression_result_is_always_terminating(exprResult.raw(), isAlwaysTerminating))) return zv::Val();
		zv::Val variableFlow = pt_arrow_function_handler_get_variable_flow(expr, exprResult.raw());
		if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		zv::Val resultThrowPointsHold, resultImpurePointsHold;
		zval *resultThrowPoints = pt_expression_result_throw_points(exprResult.raw(), resultThrowPointsHold);
		if (UNEXPECTED(resultThrowPoints == NULL)) return zv::Val();
		zval *resultImpurePoints = pt_expression_result_impure_points(exprResult.raw(), resultImpurePointsHold);
		if (UNEXPECTED(resultImpurePoints == NULL)) return zv::Val();
		zv::Val typeCallback = pt_native_closure(&mixedTypeCallbackBody);
		zv::Val specifyTypesCallback = pt_specified_types_empty_specify_callback();
		if (UNEXPECTED(specifyTypesCallback.isUndef())) return zv::Val();
		pt_expression_result_args args(scope.raw(), scope.raw(), expr, false, isAlwaysTerminating, resultThrowPoints, resultImpurePoints, typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw());
		zv::Val expressionResult = pt_expression_result_create(slot(slots::expressionResultFactory), args);
		if (UNEXPECTED(expressionResult.isUndef())) return zv::Val();

		return pt_process_arrow_function_result_new(expressionResult.raw(), arrowFunctionScope.raw(), closureTypeThrowPoints.raw(), closureTypeImpurePoints.raw(), invalidateHold.raw());
	}

private:
	zend_object *self;

	zval *slot(uint32_t index) const { return OBJ_PROP_NUM(self, index); }

	void writeSlot(uint32_t index, zval *value)
	{
		zv::ObjRef(self).propAtWrite(index, zv::Val::copyOf(zv::Ref(value)));
		Z_PROP_FLAG_P(OBJ_PROP_NUM(self, index)) = 0;
	}

	/* $this->getParametersProcessor()->processParams($nodeScopeResolver, $stmt,
	 * $expr->params, $scope, $storage, $nodeCallback) */
	[[nodiscard]] bool processParams(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback) const
	{
		zv::Val parametersProcessor = getParametersProcessor(slot(slots::container));
		if (UNEXPECTED(parametersProcessor.isUndef())) return false;
		if (UNEXPECTED(!parametersProcessor.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function processParams() on %s", zend_zval_value_name(parametersProcessor.raw()));
			return false;
		}
		zval *params = ptclosure::prop(ptclosure::paramsSite, expr, PT_LC("params"));
		if (UNEXPECTED(params == NULL)) return false;
		zv::Val paramsHold = zv::Val::copyOf(zv::Ref(params));
		return pt_parameters_processor_process_params(parametersProcessor.raw(), nodeScopeResolver, stmt, paramsHold.raw(), scope, storage, nodeCallback);
	}

	/* the locals of processClosureNodeInternal() */
	struct ClosureWalk
	{
		zval *nodeScopeResolver;
		zval *stmt;
		zval *expr;
		zval *storage; /* $originalStorage */
		zval *nodeCallback;
		zval *context;
		zv::Val scope;
		zv::Arr byRefUses = zv::Arr::empty();
		zv::Val callableParameters;
		zv::Val nativeCallableParameters;
		/* the gatherer's by-reference captures (IS_REFERENCE zvals) */
		zval captures[PT_CP_CLOSURE_GATHERER_CAPTURES];
		zv::Val gatherer;
		zv::Val statementResult; /* InternalStatementResult */
		zv::Val closureResultScope; /* UNDEF = the non-by-ref path */

		ClosureWalk()
		{
			for (uint32_t i = 0; i < PT_CP_CLOSURE_GATHERER_CAPTURES; i++) {
				ZVAL_UNDEF(&captures[i]);
			}
		}

		ClosureWalk(const ClosureWalk &) = delete;
		ClosureWalk &operator=(const ClosureWalk &) = delete;

		~ClosureWalk()
		{
			for (uint32_t i = 0; i < PT_CP_CLOSURE_GATHERER_CAPTURES; i++) {
				zval_ptr_dtor(&captures[i]);
			}
		}

		zval *list(uint32_t index) { return Z_REFVAL(captures[index]); }
		zval *closureScope() { return Z_REFVAL(captures[PT_CP_CLOSURE_SCOPE]); }

		/* $closureScope = $value */
		void setClosureScope(zv::Val value)
		{
			zval *target = Z_REFVAL(captures[PT_CP_CLOSURE_SCOPE]);
			zval old;
			ZVAL_COPY_VALUE(&old, target);
			zval v = value.take();
			ZVAL_COPY_VALUE(target, &v);
			zval_ptr_dtor(&old);
		}
	};

	/* everything of processClosureNodeInternal() ahead of the body walk: the
	 * parameters, the uses, the entered closure scope, InClosureNode and the
	 * gatherer; false = pending exception */
	[[nodiscard]] zend_never_inline bool enterClosure(ClosureWalk &w, zval *passedToType, zval *nativePassedToType) const
	{
		if (UNEXPECTED(!processParams(w.nodeScopeResolver, w.stmt, w.expr, w.scope.raw(), w.storage, w.nodeCallback))) return false;

		zval *closureCallArgs = ptclosure::attribute(w.expr, pt_cp_closure_call_args);
		zv::Val closureCallArgsHold = closureCallArgs != NULL ? zv::Val::copyOf(zv::Ref(closureCallArgs)) : zv::Val::null();
		zval *closureParameterResolver = slot(slots::closureParameterResolver);
		if (UNEXPECTED(!pt_closure_parameter_resolver_resolve(closureParameterResolver, w.scope.raw(), w.expr, w.storage, closureCallArgsHold.raw(), passedToType, nativePassedToType, w.callableParameters, w.nativeCallableParameters))) return false;

		zval *uses = ptclosure::prop(ptclosure::usesSite, w.expr, PT_LC("uses"));
		if (UNEXPECTED(uses == NULL)) return false;
		zv::Val usesHold = zv::Val::copyOf(zv::Ref(uses));
		zv::Val useScope = zv::Val::copyOf(zv::Ref(w.scope.raw()));
		if (EXPECTED(Z_TYPE_P(usesHold.raw()) == IS_ARRAY)) {
			for (zv::ArrayEntry entry : zv::ArrRef(usesHold.raw())) {
				zval *use = entry.value().deref().raw();
				zval *byRef = ptclosure::prop(ptclosure::useByRefSite, use, PT_LC("byRef"));
				if (UNEXPECTED(byRef == NULL)) return false;
				bool isByRef = zend_is_true(byRef);
				zval *var = ptclosure::prop(ptclosure::useVarSite, use, PT_LC("var"));
				if (UNEXPECTED(var == NULL)) return false;
				zv::Val varHold = zv::Val::copyOf(zv::Ref(var));
				if (isByRef) {
					w.byRefUses.push(zv::Ref(use));
					zv::Val entered = pt_mutating_scope_enter_expression_assign(Z_OBJ_P(useScope.raw()), Z_OBJ_P(varHold.raw()), true);
					if (UNEXPECTED(entered.isUndef())) return false;
					useScope = std::move(entered);
					if (UNEXPECTED(!assignRightSideUse(w, varHold.raw()))) return false;
				}
				zv::Val useContext = pt_expression_context_without_value_flow(w.context);
				if (UNEXPECTED(useContext.isUndef())) return false;
				zv::Val result = pt_node_scope_resolver_process_expr_node(w.nodeScopeResolver, w.stmt, varHold.raw(), useScope.raw(), w.storage, w.nodeCallback, useContext.raw());
				if (UNEXPECTED(result.isUndef())) return false;
				if (!isByRef) continue;

				zv::Val exited = pt_mutating_scope_exit_expression_assign(Z_OBJ_P(useScope.raw()), Z_OBJ_P(varHold.raw()));
				if (UNEXPECTED(exited.isUndef())) return false;
				useScope = std::move(exited);
			}
		}

		zval *returnType = ptclosure::prop(ptclosure::returnTypeSite, w.expr, PT_LC("returnType"));
		if (UNEXPECTED(returnType == NULL)) return false;
		if (Z_TYPE_P(returnType) != IS_NULL) {
			zv::Val returnTypeHold = zv::Val::copyOf(zv::Ref(returnType));
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(w.nodeScopeResolver, w.nodeCallback, returnTypeHold.raw(), w.scope.raw(), w.storage))) return false;
		}

		zv::Val closureScope = pt_mutating_scope_enter_anonymous_function(Z_OBJ_P(w.scope.raw()), w.expr, w.callableParameters.raw(), w.nativeCallableParameters.raw());
		if (UNEXPECTED(closureScope.isUndef())) return false;
		if (UNEXPECTED(!closureScope.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function processClosureScope() on %s", zend_zval_value_name(closureScope.raw()));
			return false;
		}
		zval null;
		ZVAL_NULL(&null);
		closureScope = pt_mutating_scope_process_closure_scope(Z_OBJ_P(closureScope.raw()), w.scope.raw(), &null, w.byRefUses.raw());
		if (UNEXPECTED(closureScope.isUndef())) return false;
		if (UNEXPECTED(!closureScope.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function getAnonymousFunctionReflection() on %s", zend_zval_value_name(closureScope.raw()));
			return false;
		}
		zv::Val closureType = pt_mutating_scope_get_anonymous_function_reflection(Z_OBJ_P(closureScope.raw()));
		if (UNEXPECTED(closureType.isUndef())) return false;
		if (UNEXPECTED(!closureType.ref().isObject() || !instanceof_function(Z_OBJCE_P(closureType.raw()), pt_ce_closure_type))) {
			pt_throw_should_not_happen();
			return false;
		}

		zv::Args inClosureArgv{closureType.raw(), w.expr};
		zv::Val inClosureNode = pt_type_new(PT_CLASS_IN_CLOSURE_NODE, 2, inClosureArgv);
		if (UNEXPECTED(inClosureNode.isUndef())) return false;
		if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(w.nodeScopeResolver, w.nodeCallback, inClosureNode.raw(), closureScope.raw(), w.storage))) return false;

		for (uint32_t i = 0; i < PT_CP_CLOSURE_GATHERER_CAPTURES; i++) {
			if (i == PT_CP_CLOSURE_SCOPE) {
				zval value = closureScope.take();
				ZVAL_NEW_REF(&w.captures[i], &value);
			} else {
				zv::Val list = newListReference();
				w.captures[i] = list.take();
			}
		}
		w.gatherer = pt_native_closure_new(&closureGathererBody, PT_CP_CLOSURE_GATHERER_CAPTURES, w.captures, (1u << PT_CP_CLOSURE_GATHERER_CAPTURES) - 1);
		return true;
	}

	/* the by-ref use of the variable the closure is assigned to: its type
	 * assigned on $scope before the body walk; false = pending exception */
	[[nodiscard]] zend_never_inline bool assignRightSideUse(ClosureWalk &w, zval *var) const
	{
		zv::Val inAssignRightSideVariableName = pt_expression_context_get_in_assign_right_side_variable_name(w.context);
		if (UNEXPECTED(inAssignRightSideVariableName.isUndef())) return false;
		zv::Val inAssignRightSideExpr = pt_expression_context_get_in_assign_right_side_expr(w.context);
		if (UNEXPECTED(inAssignRightSideExpr.isUndef())) return false;
		zval *varName = ptclosure::prop(ptclosure::variableNameSite, var, PT_LC("name"));
		if (UNEXPECTED(varName == NULL)) return false;
		if (!zend_is_identical(inAssignRightSideVariableName.raw(), varName) || inAssignRightSideExpr.isNull()) return true;
		if (UNEXPECTED(!inAssignRightSideVariableName.ref().isString())) {
			zend_type_error("PHPStan\\Analyser\\MutatingScope::hasVariableType(): Argument #1 ($variableName) must be of type string, %s given", zend_zval_value_name(inAssignRightSideVariableName.raw()));
			return false;
		}
		zend_string *name = Z_STR_P(inAssignRightSideVariableName.raw());
		zval *closureParameterResolver = slot(slots::closureParameterResolver);

		// a call's type is carried by the context (see
		// ExpressionContext::enterAssignRightSideCallArgs()); a closure
		// right side resolves through the closure type resolver
		zv::Val inAssignRightSideType = pt_expression_context_get_in_assign_right_side_type(w.context);
		if (UNEXPECTED(inAssignRightSideType.isUndef())) return false;
		if (inAssignRightSideType.isNull()) {
			inAssignRightSideType = pt_closure_parameter_resolver_resolve_callable_type_for_scope(closureParameterResolver, inAssignRightSideExpr.raw(), w.scope.raw());
			if (UNEXPECTED(inAssignRightSideType.isUndef())) return false;
		}
		zv::Val variableType = rightSideVariableType(w.scope.raw(), name, inAssignRightSideType.raw());
		if (UNEXPECTED(variableType.isUndef())) return false;

		zv::Val inAssignRightSideNativeType = pt_expression_context_get_in_assign_right_side_native_type(w.context);
		if (UNEXPECTED(inAssignRightSideNativeType.isUndef())) return false;
		if (inAssignRightSideNativeType.isNull()) {
			zv::Val nativeScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(w.scope.raw()));
			if (UNEXPECTED(nativeScope.isUndef())) return false;
			inAssignRightSideNativeType = pt_closure_parameter_resolver_resolve_callable_type_for_scope(closureParameterResolver, inAssignRightSideExpr.raw(), nativeScope.raw());
			if (UNEXPECTED(inAssignRightSideNativeType.isUndef())) return false;
		}
		zv::Val variableNativeType = rightSideVariableType(w.scope.raw(), name, inAssignRightSideNativeType.raw());
		if (UNEXPECTED(variableNativeType.isUndef())) return false;

		zv::Val assigned = pt_mutating_scope_assign_variable(Z_OBJ_P(w.scope.raw()), name, variableType.raw(), variableNativeType.raw(), pt_trinary_singleton(PT_TRI_YES));
		if (UNEXPECTED(assigned.isUndef())) return false;
		w.scope = std::move(assigned);
		return true;
	}

	/* $rightSideType instanceof ClosureType ? $rightSideType :
	 * TypeCombinator::union($scope->hasVariableType($name)->no() ? new
	 * NullType() : $scope->getVariableType($name), $rightSideType) */
	static zv::Val rightSideVariableType(zval *scope, zend_string *name, zval *rightSideType)
	{
		if (Z_TYPE_P(rightSideType) == IS_OBJECT && instanceof_function(Z_OBJCE_P(rightSideType), pt_ce_closure_type)) return zv::Val::copyOf(zv::Ref(rightSideType));
		zv::Val has = pt_mutating_scope_has_variable_type(Z_OBJ_P(scope), name);
		if (UNEXPECTED(has.isUndef())) return zv::Val();
		zend_long hasValue = pt_type_trinary_value(has.raw());
		if (UNEXPECTED(hasValue < 0)) return zv::Val();
		zv::Val first;
		if (hasValue == PT_TRI_NO) {
			zval nullType;
			if (UNEXPECTED(!pt_null_type_new(&nullType))) return zv::Val();
			first = zv::Val::adopt(nullType);
		} else {
			first = pt_mutating_scope_get_variable_type(Z_OBJ_P(scope), name);
			if (UNEXPECTED(first.isUndef())) return zv::Val();
		}
		zv::Args unionArgv{first.raw(), rightSideType};
		return pt_type_combinator_union(2, unionArgv);
	}

	/* the body walk without by-ref uses; false = pending exception */
	[[nodiscard]] bool walkClosureBody(ClosureWalk &w) const
	{
		if (UNEXPECTED(!pt_node_scope_resolver_push_node_gatherer(w.nodeScopeResolver, w.gatherer.raw()))) return false;
		{
			zval *stmts = ptclosure::prop(ptclosure::stmtsSite, w.expr, PT_LC("stmts"));
			bool resolveTemplateArguments = false;
			if (EXPECTED(stmts != NULL) && EXPECTED(pt_expression_context_should_resolve_template_arguments(w.context, resolveTemplateArguments))) {
				zv::Val stmtsHold = zv::Val::copyOf(zv::Ref(stmts));
				zv::Val statementContext = pt_statement_context_create_top_level(resolveTemplateArguments);
				if (EXPECTED(!statementContext.isUndef())) {
					w.statementResult = pt_node_scope_resolver_process_stmt_nodes_internal(w.nodeScopeResolver, w.expr, stmtsHold.raw(), w.closureScope(), w.storage, w.nodeCallback, statementContext.raw());
				}
			}
		}
		pt_finally([&]() { (void) pt_node_scope_resolver_pop_node_gatherer(w.nodeScopeResolver); });
		return !w.statementResult.isUndef() && EG(exception) == NULL;
	}

	/* the by-ref uses' convergence passes and the final walk (or its replay);
	 * false = pending exception */
	[[nodiscard]] zend_never_inline bool convergeClosureBody(ClosureWalk &w) const
	{
		zval *stmts = ptclosure::prop(ptclosure::stmtsSite, w.expr, PT_LC("stmts"));
		if (UNEXPECTED(stmts == NULL)) return false;
		zv::Val stmtsHold = zv::Val::copyOf(zv::Ref(stmts));

		zend_long count = 0;
		zv::Val replayBodyRecording, replayPassStorage, replayPassResult, replayEntryScope;
		bool bodyIsReplayable = false;
		if (UNEXPECTED(!pt_node_scope_resolver_is_replayable_convergence_body(w.nodeScopeResolver, w.expr, stmtsHold.raw(), bodyIsReplayable))) return false;
		do {
			zv::Val prevScope = zv::Val::copyOf(zv::Ref(w.closureScope()));

			zv::Val storage = pt_expression_result_storage_duplicate(w.storage);
			if (UNEXPECTED(storage.isUndef())) return false;
			zv::Val bodyRecording = bodyIsReplayable ? pt_type_new_ce(pt_ce_recording_node_callback, 0, NULL) : pt_type_new(PT_CLASS_NOOP_NODE_CALLBACK, 0, NULL);
			if (UNEXPECTED(bodyRecording.isUndef())) return false;
			// deep context, like the loop handlers' own convergence passes: inner
			// loops walk single-pass here and only the final walk below (top-level)
			// runs their full convergence
			zv::Val passContext = pt_statement_context_create_deep(false);
			if (UNEXPECTED(passContext.isUndef())) return false;
			zv::Val intermediaryClosureScopeResult = pt_node_scope_resolver_process_stmt_nodes_internal(w.nodeScopeResolver, w.expr, stmtsHold.raw(), w.closureScope(), storage.raw(), bodyRecording.raw(), passContext.raw());
			if (UNEXPECTED(intermediaryClosureScopeResult.isUndef())) return false;
			// the candidate to replace the final walk when this pass's entry
			// turns out to be the fixpoint
			if (bodyIsReplayable) {
				replayBodyRecording = zv::Val::copyOf(zv::Ref(bodyRecording.raw()));
				replayPassStorage = zv::Val::copyOf(zv::Ref(storage.raw()));
				replayPassResult = zv::Val::copyOf(zv::Ref(intermediaryClosureScopeResult.raw()));
				replayEntryScope = zv::Val::copyOf(zv::Ref(prevScope.raw()));
			}
			zv::Val intermediaryClosureScope;
			{
				zv::Val scopeHold, exitPointsHold;
				zval *resultScope = pt_internal_statement_result_scope(intermediaryClosureScopeResult.raw(), scopeHold);
				if (UNEXPECTED(resultScope == NULL)) return false;
				intermediaryClosureScope = zv::Val::copyOf(zv::Ref(resultScope));
				zval *exitPoints = pt_internal_statement_result_exit_points(intermediaryClosureScopeResult.raw(), exitPointsHold);
				if (UNEXPECTED(exitPoints == NULL)) return false;
				zv::Val exitPointsIterated = zv::Val::copyOf(zv::Ref(exitPoints));
				if (EXPECTED(Z_TYPE_P(exitPointsIterated.raw()) == IS_ARRAY)) {
					for (zv::ArrayEntry entry : zv::ArrRef(exitPointsIterated.raw())) {
						zv::Val exitScopeHold;
						zval *exitScope = pt_internal_statement_exit_point_scope(entry.value().deref().raw(), exitScopeHold);
						if (UNEXPECTED(exitScope == NULL)) return false;
						zv::Val merged = pt_mutating_scope_merge_with(Z_OBJ_P(intermediaryClosureScope.raw()), exitScope);
						if (UNEXPECTED(merged.isUndef())) return false;
						intermediaryClosureScope = std::move(merged);
					}
				}
			}

			zval *immediatelyInvoked = ptclosure::attribute(w.expr, pt_cp_immediately_invoked_closure);
			if (immediatelyInvoked != NULL && Z_TYPE_P(immediatelyInvoked) == IS_TRUE) {
				w.closureResultScope = std::move(intermediaryClosureScope);
				break;
			}

			zv::Val entered = pt_mutating_scope_enter_anonymous_function(Z_OBJ_P(w.scope.raw()), w.expr, w.callableParameters.raw(), w.nativeCallableParameters.raw());
			if (UNEXPECTED(entered.isUndef())) return false;
			w.setClosureScope(std::move(entered));
			if (UNEXPECTED(Z_TYPE_P(w.closureScope()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function processClosureScope() on %s", zend_zval_value_name(w.closureScope()));
				return false;
			}
			zv::Val processed = pt_mutating_scope_process_closure_scope(Z_OBJ_P(w.closureScope()), intermediaryClosureScope.raw(), prevScope.raw(), w.byRefUses.raw());
			if (UNEXPECTED(processed.isUndef())) return false;
			w.setClosureScope(std::move(processed));
			if (UNEXPECTED(Z_TYPE_P(w.closureScope()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function equals() on %s", zend_zval_value_name(w.closureScope()));
				return false;
			}

			bool equals = false;
			if (UNEXPECTED(!pt_mutating_scope_equals(Z_OBJ_P(w.closureScope()), Z_OBJ_P(prevScope.raw()), equals))) return false;
			if (equals) break;
			if (count >= PT_CP_GENERALIZE_AFTER_ITERATION_LIMIT) {
				zv::Val generalized = pt_mutating_scope_generalize_with(Z_OBJ_P(prevScope.raw()), Z_OBJ_P(w.closureScope()));
				if (UNEXPECTED(generalized.isUndef())) return false;
				w.setClosureScope(std::move(generalized));
			}
			count++;
		} while (count < PT_CP_LOOP_SCOPE_ITERATIONS_LIMIT);

		if (w.closureResultScope.isUndef()) {
			w.closureResultScope = zv::Val::copyOf(zv::Ref(w.closureScope()));
		}

		if (UNEXPECTED(!pt_node_scope_resolver_push_node_gatherer(w.nodeScopeResolver, w.gatherer.raw()))) return false;
		(void) finalWalk(w, stmtsHold.raw(), replayBodyRecording, replayPassStorage, replayPassResult, replayEntryScope);
		pt_finally([&]() { (void) pt_node_scope_resolver_pop_node_gatherer(w.nodeScopeResolver); });
		return !w.statementResult.isUndef() && EG(exception) == NULL;
	}

	/* the try block of the final walk: the recorded fixpoint pass replayed, or
	 * the top-level walk; false = pending exception */
	[[nodiscard]] zend_never_inline bool finalWalk(ClosureWalk &w, zval *stmts, zv::Val &replayBodyRecording, zv::Val &replayPassStorage, zv::Val &replayPassResult, zv::Val &replayEntryScope) const
	{
		bool replay = !replayBodyRecording.isUndef() && !replayPassStorage.isUndef() && !replayPassResult.isUndef() && !replayEntryScope.isUndef();
		if (replay) {
			if (UNEXPECTED(Z_TYPE_P(w.closureScope()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function equals() on %s", zend_zval_value_name(w.closureScope()));
				return false;
			}
			if (UNEXPECTED(!pt_mutating_scope_equals(Z_OBJ_P(w.closureScope()), Z_OBJ_P(replayEntryScope.raw()), replay))) return false;
		}
		if (replay) {
			// the final walk would repeat the recorded fixpoint pass exactly
			// (same entry scope, deterministic walk) - adopt the pass's result
			// and replay its emissions through the real callback instead.
			w.setClosureScope(zv::Val::copyOf(zv::Ref(replayEntryScope.raw())));
			if (UNEXPECTED(!pt_expression_result_storage_merge_results(w.storage, replayPassStorage.raw()))) return false;
			if (UNEXPECTED(!pt_node_scope_resolver_replay_recording(w.nodeScopeResolver, replayBodyRecording.raw(), w.nodeCallback, w.storage, w.closureScope()))) return false;
			w.statementResult = zv::Val::copyOf(zv::Ref(replayPassResult.raw()));
			return true;
		}
		bool resolveTemplateArguments = false;
		if (UNEXPECTED(!pt_expression_context_should_resolve_template_arguments(w.context, resolveTemplateArguments))) return false;
		zv::Val statementContext = pt_statement_context_create_top_level(resolveTemplateArguments);
		if (UNEXPECTED(statementContext.isUndef())) return false;
		w.statementResult = pt_node_scope_resolver_process_stmt_nodes_internal(w.nodeScopeResolver, w.expr, stmts, w.closureScope(), w.storage, w.nodeCallback, statementContext.raw());
		return !w.statementResult.isUndef();
	}

	/* everything of processClosureNodeInternal() after the body walk: the
	 * refined node scope, ClosureReturnStatementsNode, the liveness node and
	 * the result */
	zend_never_inline zv::Val finishClosure(ClosureWalk &w) const
	{
		zval *statementResult = w.statementResult.raw();
		if (UNEXPECTED(Z_TYPE_P(statementResult) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function toPublic() on %s", zend_zval_value_name(statementResult));
			return zv::Val();
		}
		zv::Val publicStatementResult = pt_internal_statement_result_to_public(statementResult);
		if (UNEXPECTED(publicStatementResult.isUndef())) return zv::Val();

		zv::Val throwPointsHold, impurePointsHold;
		zval *throwPoints = pt_internal_statement_result_throw_points(statementResult, throwPointsHold);
		if (UNEXPECTED(throwPoints == NULL)) return zv::Val();
		zval *impurePoints = pt_internal_statement_result_impure_points(statementResult, impurePointsHold);
		if (UNEXPECTED(impurePoints == NULL)) return zv::Val();
		zv::Val closureTypeImpurePoints = mergeLists(w.list(PT_CP_CLOSURE_IMPURE_POINTS), impurePoints);
		if (UNEXPECTED(closureTypeImpurePoints.isUndef())) return zv::Val();

		zv::Val returnsWithScope = zv::Val::copyOf(zv::Ref(w.list(PT_CP_RETURN_STATEMENTS_WITH_SCOPE)));
		zv::Val yieldsWithScope = zv::Val::copyOf(zv::Ref(w.list(PT_CP_YIELD_STATEMENTS_WITH_SCOPE)));
		zv::Val executionEnds = zv::Val::copyOf(zv::Ref(w.list(PT_CP_EXECUTION_ENDS)));
		zv::Val invalidateExpressions = zv::Val::copyOf(zv::Ref(w.list(PT_CP_INVALIDATE_EXPRESSIONS)));
		zv::Val closureReturnStatementsNodeScope;
		{
			zv::Val refinedClosureType = pt_closure_type_resolver_build_closure_type_for_closure(slot(slots::closureTypeResolver), w.scope.raw(), w.expr, returnsWithScope.raw(), yieldsWithScope.raw(), executionEnds.raw(), throwPoints, closureTypeImpurePoints.raw(), invalidateExpressions.raw(), false, w.storage);
			if (UNEXPECTED(refinedClosureType.isUndef())) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(w.closureScope()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function withAnonymousFunctionReflection() on %s", zend_zval_value_name(w.closureScope()));
				return zv::Val();
			}
			closureReturnStatementsNodeScope = pt_mutating_scope_with_anonymous_function_reflection(Z_OBJ_P(w.closureScope()), refinedClosureType.raw());
			if (UNEXPECTED(closureReturnStatementsNodeScope.isUndef())) return zv::Val();
		}

		{
			zv::Val publicImpurePointsHold;
			zval *publicImpurePoints = pt_statement_result_impure_points(publicStatementResult.raw(), publicImpurePointsHold);
			if (UNEXPECTED(publicImpurePoints == NULL)) return zv::Val();
			zv::Val nodeImpurePoints = mergeLists(publicImpurePoints, w.list(PT_CP_CLOSURE_IMPURE_POINTS));
			if (UNEXPECTED(nodeImpurePoints.isUndef())) return zv::Val();
			zv::Args nodeArgv{w.expr, w.list(PT_CP_RETURN_STATEMENTS), w.list(PT_CP_RETURN_STATEMENTS_AFTER_FINALLY), w.list(PT_CP_YIELD_STATEMENTS), publicStatementResult.raw(), executionEnds.raw(), nodeImpurePoints.raw()};
			zv::Val closureReturnStatementsNode = pt_type_new(PT_CLASS_CLOSURE_RETURN_STATEMENTS_NODE, 7, nodeArgv);
			if (UNEXPECTED(closureReturnStatementsNode.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(w.nodeScopeResolver, w.nodeCallback, closureReturnStatementsNode.raw(), closureReturnStatementsNodeScope.raw(), w.storage))) return zv::Val();
		}
		{
			zv::Val flowHold;
			zval *variableFlow = pt_internal_statement_result_variable_flow(statementResult, flowHold);
			if (UNEXPECTED(variableFlow == NULL)) return zv::Val();
			zv::Val livenessNode = pt_variable_liveness_resolver_resolve(w.expr, variableFlow);
			if (UNEXPECTED(livenessNode.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(w.nodeScopeResolver, w.nodeCallback, livenessNode.raw(), closureReturnStatementsNodeScope.raw(), w.storage))) return zv::Val();
		}

		zv::Val resultScope;
		{
			zv::Val scopeHold;
			zval *statementScope = pt_internal_statement_result_scope(statementResult, scopeHold);
			if (UNEXPECTED(statementScope == NULL)) return zv::Val();
			zv::Val constraints = pt_mutating_scope_get_template_argument_constraints(Z_OBJ_P(statementScope));
			if (UNEXPECTED(constraints.isUndef())) return zv::Val();
			resultScope = pt_mutating_scope_add_template_argument_constraints(Z_OBJ_P(w.scope.raw()), constraints.raw());
			if (UNEXPECTED(resultScope.isUndef())) return zv::Val();
		}
		zv::Val resultThrowPointsHold, resultImpurePointsHold;
		zval *resultThrowPoints = pt_internal_statement_result_throw_points(statementResult, resultThrowPointsHold);
		if (UNEXPECTED(resultThrowPoints == NULL)) return zv::Val();
		zval *resultImpurePoints = pt_internal_statement_result_impure_points(statementResult, resultImpurePointsHold);
		if (UNEXPECTED(resultImpurePoints == NULL)) return zv::Val();
		zv::Val resultClosureTypeImpurePoints = mergeLists(w.list(PT_CP_CLOSURE_IMPURE_POINTS), resultImpurePoints);
		if (UNEXPECTED(resultClosureTypeImpurePoints.isUndef())) return zv::Val();
		if (w.closureResultScope.isUndef()) {
			return pt_process_closure_result_new(resultScope.raw(), resultThrowPoints, resultImpurePoints, w.list(PT_CP_INVALIDATE_EXPRESSIONS), w.list(PT_CP_RETURN_STATEMENTS_WITH_SCOPE), w.list(PT_CP_YIELD_STATEMENTS_WITH_SCOPE), w.list(PT_CP_EXECUTION_ENDS), resultClosureTypeImpurePoints.raw());
		}
		return pt_process_closure_result_new(resultScope.raw(), resultThrowPoints, resultImpurePoints, w.list(PT_CP_INVALIDATE_EXPRESSIONS), w.list(PT_CP_RETURN_STATEMENTS_WITH_SCOPE), w.list(PT_CP_YIELD_STATEMENTS_WITH_SCOPE), w.list(PT_CP_EXECUTION_ENDS), resultClosureTypeImpurePoints.raw(), w.closureResultScope.raw(), w.byRefUses.raw());
	}
};

} // namespace phpstanturbo

using phpstanturbo::ClosureProcessor;

/* {{{ direct entries (support.h) */

zv::Val pt_closure_processor_process_closure_node(zval *processor, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context, zval *passedToType, zval *nativePassedToType)
{
	if (passedToType != NULL && Z_TYPE_P(passedToType) == IS_NULL) passedToType = NULL;
	if (nativePassedToType != NULL && Z_TYPE_P(nativePassedToType) == IS_NULL) nativePassedToType = NULL;
	if (EXPECTED(Z_TYPE_P(processor) == IS_OBJECT && Z_OBJCE_P(processor) == pt_ce_closure_processor)) return ClosureProcessor(Z_OBJ_P(processor)).processClosureNode(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context, passedToType, nativePassedToType);
	if (UNEXPECTED(Z_TYPE_P(processor) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function processClosureNode() on %s", zend_zval_value_name(processor));
		return zv::Val();
	}
	zval null;
	ZVAL_NULL(&null);
	zv::Args argv{nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context, passedToType != NULL ? passedToType : &null, nativePassedToType != NULL ? nativePassedToType : &null};
	return pt_type_call(Z_OBJ_P(processor), PT_LC("processclosurenode"), 9, argv);
}

zv::Val pt_closure_processor_process_arrow_function_node(zval *processor, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *passedToType, zval *nativePassedToType, zval *context)
{
	if (passedToType != NULL && Z_TYPE_P(passedToType) == IS_NULL) passedToType = NULL;
	if (nativePassedToType != NULL && Z_TYPE_P(nativePassedToType) == IS_NULL) nativePassedToType = NULL;
	if (context != NULL && Z_TYPE_P(context) == IS_NULL) context = NULL;
	if (EXPECTED(Z_TYPE_P(processor) == IS_OBJECT && Z_OBJCE_P(processor) == pt_ce_closure_processor)) return ClosureProcessor(Z_OBJ_P(processor)).processArrowFunctionNode(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, passedToType, nativePassedToType, context);
	if (UNEXPECTED(Z_TYPE_P(processor) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function processArrowFunctionNode() on %s", zend_zval_value_name(processor));
		return zv::Val();
	}
	zval null;
	ZVAL_NULL(&null);
	zv::Args argv{nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, passedToType != NULL ? passedToType : &null, nativePassedToType != NULL ? nativePassedToType : &null, context != NULL ? context : &null};
	return pt_type_call(Z_OBJ_P(processor), PT_LC("processarrowfunctionnode"), 9, argv);
}

zv::Val pt_closure_processor_process_immediately_called_callable(zval *processor, zval *scope, zval *invalidatedExpressions, zval *uses)
{
	if (EXPECTED(Z_TYPE_P(processor) == IS_OBJECT && Z_OBJCE_P(processor) == pt_ce_closure_processor && Z_TYPE_P(scope) == IS_OBJECT && Z_TYPE_P(invalidatedExpressions) == IS_ARRAY && Z_TYPE_P(uses) == IS_ARRAY)) return ClosureProcessor(Z_OBJ_P(processor)).processImmediatelyCalledCallable(scope, invalidatedExpressions, uses);
	if (UNEXPECTED(Z_TYPE_P(processor) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function processImmediatelyCalledCallable() on %s", zend_zval_value_name(processor));
		return zv::Val();
	}
	zv::Args argv{scope, invalidatedExpressions, uses};
	return pt_type_call(Z_OBJ_P(processor), PT_LC("processimmediatelycalledcallable"), 3, argv);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_closure_processor()
{
	ptclosure::initStrings();
	pt_cp_closure_call_args = zend_string_init_interned(PT_LC("closureCallArgs"), 1);
	pt_cp_arrow_function_call_args = zend_string_init_interned(PT_LC("arrowFunctionCallArgs"), 1);
	pt_cp_immediately_invoked_closure = zend_string_init_interned(PT_LC("isImmediatelyInvokedClosure"), 1);
	pt_cp_this = zend_string_init_interned(PT_LC("this"), 1);

	reg::Class cls("PHPStan\\Analyser\\ClosureProcessor");
	ptdecl::ClosureProcessor::declareClass(cls);
	ptdecl::ClosureProcessor::declareProperties(cls);

	/* the DI service's constructor: the generated arginfo names the twin's
	 * parameter classes exactly */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *container, *expressionResultFactory, *closureParameterResolver, *closureTypeResolver;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, container, expressionResultFactory, closureParameterResolver, closureTypeResolver)) RETURN_THROWS();
		ClosureProcessor(Z_OBJ_P(ZEND_THIS)).construct(container, expressionResultFactory, closureParameterResolver, closureTypeResolver);
	});

	cls.method(sigs::processClosureNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *stmt, *expr, *scope, *storage, *nodeCallback, *context, *passedToType, *nativePassedToType = NULL;
		ZEND_PARSE_PARAMETERS_START(8, 9)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(context)
			Z_PARAM_OBJECT_OR_NULL(passedToType)
			Z_PARAM_OPTIONAL
			Z_PARAM_OBJECT_OR_NULL(nativePassedToType)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(ClosureProcessor(Z_OBJ_P(ZEND_THIS)).processClosureNode(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context, passedToType, nativePassedToType));
	});

	cls.method(sigs::processImmediatelyCalledCallable, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *invalidatedExpressions, *uses;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_ARRAY(invalidatedExpressions)
			Z_PARAM_ARRAY(uses)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(ClosureProcessor(Z_OBJ_P(ZEND_THIS)).processImmediatelyCalledCallable(scope, invalidatedExpressions, uses));
	});

	cls.method(sigs::processArrowFunctionNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *stmt, *expr, *scope, *storage, *nodeCallback, *passedToType, *nativePassedToType = NULL, *context = NULL;
		ZEND_PARSE_PARAMETERS_START(7, 9)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT_OR_NULL(passedToType)
			Z_PARAM_OPTIONAL
			Z_PARAM_OBJECT_OR_NULL(nativePassedToType)
			Z_PARAM_OBJECT_OR_NULL(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(ClosureProcessor(Z_OBJ_P(ZEND_THIS)).processArrowFunctionNode(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, passedToType, nativePassedToType, context));
	});

	cls.shadow(&pt_ce_closure_processor);
}

/* }}} */
