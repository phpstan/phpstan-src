/*
 * PHPStanTurbo\MethodCallHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\MethodCallHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback ($this, $beforeScope,
 * $expr, $varResult, $nameResult, $resolvedParametersAcceptor, $argsResult;
 * none for the early-terminating `new NeverType(true)` one), the
 * specifyTypesCallback ($this, $beforeScope, $expr, $normalizedExpr,
 * $varResult, $resolvedParametersAcceptor, $argsResult), the
 * createTypesCallback ($this, $expr, $varResult, $beforeScope) and the
 * asserts mapping callback of specifyTypes() ($resolvedParametersAcceptor).
 * The closures resolveReturnType() creates and calls itself are inlined.
 *
 * NodeScopeResolver, MutatingScope, ExpressionResult, ArgsResult,
 * ExpressionContext, ExpressionResultStorage, VariableFlow(Builder), the
 * method reflections, SimpleImpurePoint, DynamicReturnTypeStoragePrimer,
 * ImpurePoint, InternalThrowPoint, TemplateArgumentFrame, SpecifiedTypes,
 * TypeSpecifierContext, TypeSpecifier, DefaultNarrowingHelper,
 * EarlyTerminatingCallHelper, MethodCallReturnTypeHelper,
 * MethodThrowPointHelper, CalledMethodProcessor and the Type kernel are called
 * through their direct entries; the collaborators that stay PHP for now
 * (ArgumentsHandler, ParametersAcceptorSelector, ArgumentsNormalizer, the
 * parameters acceptors, Assertions and the extensions) through the cached
 * method sites in the block below, one helper each.
 */

#include "support.h"
#include "generated/MethodCallHandler.h"

namespace slots = ptdecl::MethodCallHandler::slot;
namespace sigs = ptdecl::MethodCallHandler::sig;
#include "CallHandlerSupport.h"

zend_class_entry *pt_ce_method_call_handler = nullptr;

namespace {

using namespace ptcall;

/* {{{ the PHP collaborators only this handler calls (one site each; switch to
 * their direct entries once they are ported); the shared ones are in
 * CallHandlerSupport.h */

pt_method_site pt_mch_is_method_supported_site;
pt_method_site pt_mch_extension_specify_types_site;

/* $extension->isMethodSupported($methodReflection, $normalizedExpr, $context) */
bool extensionIsMethodSupported(zval *extension, zval *methodReflection, zval *normalizedExpr, zval *context, bool &out)
{
	zv::Args argv{methodReflection, normalizedExpr, context};
	zv::Val result = pt_call_method_cached(pt_mch_is_method_supported_site, Z_OBJ_P(extension), PT_LC("ismethodsupported"), 3, argv);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

/* $extension->specifyTypes($methodReflection, $normalizedExpr, $scope, $context) */
zv::Val extensionSpecifyTypes(zval *extension, zval *methodReflection, zval *normalizedExpr, zval *scope, zval *context)
{
	zv::Args argv{methodReflection, normalizedExpr, scope, context};
	return pt_call_method_cached(pt_mch_extension_specify_types_site, Z_OBJ_P(extension), PT_LC("specifytypes"), 4, argv);
}

/* }}} */

/* {{{ the PhpParser nodes' properties */

pt_property_site pt_mch_var_site;
pt_property_site pt_mch_name_site;
pt_property_site pt_mch_args_site;
pt_property_site pt_mch_identifier_name_site;
pt_property_site pt_mch_arg_value_site;

zval *exprVar(zval *expr) { return nodeProperty(pt_mch_var_site, expr, PT_LC("var")); }
zval *exprName(zval *expr) { return nodeProperty(pt_mch_name_site, expr, PT_LC("name")); }
/* $expr->getArgs() of a MethodCall that is not a first-class callable (the
 * handler never sees one): its $args */
zval *exprArgs(zval *expr) { return nodeProperty(pt_mch_args_site, expr, PT_LC("args")); }

/* $identifier->name */
zval *identifierName(zval *identifier) { return nodeProperty(pt_mch_identifier_name_site, identifier, PT_LC("name")); }

/* }}} */

/* {{{ small value helpers */

/* the twin's literals, permanent interned strings (module startup) */
zend_string *pt_mch_method_call = nullptr;
zend_string *pt_mch_unknown_method = nullptr;

/* the method reflection's trinary answer; -1 = pending exception */
zend_long reflectionTrinary(zval *methodReflection, pt_method_reflection_member member)
{
	return pt_extended_method_reflection_trinary(methodReflection, member);
}

/* }}} */

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\MethodCallHandler; UNDEF = pending
 * exception. */
class MethodCallHandler
{
public:
	explicit MethodCallHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval **services, bool rememberPossiblyImpureFunctionValues) const
	{
		static const uint32_t serviceSlots[10] = {
			slots::calledMethodProcessor, slots::methodCallReturnTypeHelper, slots::methodThrowPointHelper, slots::reflectionProvider,
			slots::expressionResultFactory, slots::typeSpecifier, slots::defaultNarrowingHelper, slots::storagePrimer,
			slots::earlyTerminatingHelper, slots::argumentsHandler,
		};
		for (uint32_t i = 0; i < 4; i++) {
			pt_write_slot(self, serviceSlots[i], services[i]);
		}
		zval remember = {};
		ZVAL_BOOL(&remember, rememberPossiblyImpureFunctionValues);
		pt_write_slot(self, slots::rememberPossiblyImpureFunctionValues, &remember);
		for (uint32_t i = 4; i < 10; i++) {
			pt_write_slot(self, serviceSlots[i], services[i]);
		}
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *expr, bool &out) const
	{
		zend_class_entry *methodCallCe = pt_class(PT_CLASS_METHOD_CALL);
		if (UNEXPECTED(methodCallCe == NULL)) return false;
		if (!instanceof_function(Z_OBJCE_P(expr), methodCallCe)) {
			out = false;
			return true;
		}
		bool firstClassCallable;
		if (UNEXPECTED(!pt_call_like_is_first_class_callable(Z_OBJ_P(expr), firstClassCallable))) return false;
		out = !firstClassCallable;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval null;
		ZVAL_NULL(&null);
		zval *beforeScope = scope;
		zval *originalScope = scope;

		zval *var = exprVar(expr);
		if (UNEXPECTED(var == NULL)) return zv::Val();
		zval *name = exprName(expr);
		if (UNEXPECTED(name == NULL)) return zv::Val();
		int nameIsIdentifier = isIdentifier(name);
		if (UNEXPECTED(nameIsIdentifier < 0)) return zv::Val();

		zv::Val closureCallScope;
		zend_class_entry *closureCe = pt_class(PT_CLASS_CLOSURE_EXPR);
		if (UNEXPECTED(closureCe == NULL)) return zv::Val();
		zend_class_entry *arrowFunctionCe = pt_class(PT_CLASS_ARROW_FUNCTION);
		if (UNEXPECTED(arrowFunctionCe == NULL)) return zv::Val();
		if ((instanceof_function(Z_OBJCE_P(var), closureCe) || instanceof_function(Z_OBJCE_P(var), arrowFunctionCe)) && nameIsIdentifier) {
			zval *methodName = identifierName(name);
			if (UNEXPECTED(methodName == NULL)) return zv::Val();
			if (Z_TYPE_P(methodName) == IS_STRING && zend_string_equals_literal_ci(Z_STR_P(methodName), "call")) {
				zval *args = exprArgs(expr);
				if (UNEXPECTED(args == NULL)) return zv::Val();
				zval *firstArg = zend_hash_index_find(Z_ARRVAL_P(args), 0);
				if (firstArg != NULL) ZVAL_DEREF(firstArg);
				if (firstArg != NULL && Z_TYPE_P(firstArg) != IS_NULL) {
					// process the new-$this argument as a read so enterClosureCall() consumes
					// its stored ExpressionResult instead of reading the unprocessed node via
					// Scope::getType(). processArgs() below processes it again as call()'s first
					// argument; the NoopNodeCallback here avoids a duplicate node-callback.
					zval *firstArgValue = nodeProperty(pt_mch_arg_value_site, firstArg, PT_LC("value"));
					if (UNEXPECTED(firstArgValue == NULL)) return zv::Val();
					zv::Val noopNodeCallback = pt_type_new(PT_CLASS_NOOP_NODE_CALLBACK, 0, NULL);
					if (UNEXPECTED(noopNodeCallback.isUndef())) return zv::Val();
					zv::Val deepContext = pt_expression_context_enter_deep(context);
					if (UNEXPECTED(deepContext.isUndef())) return zv::Val();
					zv::Val newThisContext = pt_expression_context_without_template_argument_resolution(deepContext.raw());
					if (UNEXPECTED(newThisContext.isUndef())) return zv::Val();
					zv::Val newThisResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, firstArgValue, scope, storage, noopNodeCallback.raw(), newThisContext.raw());
					if (UNEXPECTED(newThisResult.isUndef())) return zv::Val();
					zv::Val thisType = pt_expression_result_get_type(newThisResult.raw());
					if (UNEXPECTED(thisType.isUndef())) return zv::Val();
					zv::Val nativeThisType = pt_expression_result_get_native_type(newThisResult.raw());
					if (UNEXPECTED(nativeThisType.isUndef())) return zv::Val();
					closureCallScope = pt_mutating_scope_enter_closure_call(Z_OBJ_P(scope), thisType.raw(), nativeThisType.raw());
					if (UNEXPECTED(closureCallScope.isUndef())) return zv::Val();
				}
			}
		}

		zv::Val varContext = pt_expression_context_enter_deep(context);
		if (UNEXPECTED(varContext.isUndef())) return zv::Val();
		zv::Val varResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, var, closureCallScope.isUndef() ? scope : closureCallScope.raw(), storage, nodeCallback, varContext.raw());
		if (UNEXPECTED(varResult.isUndef())) return zv::Val();
		bool hasYield;
		if (UNEXPECTED(!pt_expression_result_has_yield(varResult.raw(), hasYield))) return zv::Val();
		zv::Val hold;
		zval *borrowed = pt_expression_result_throw_points(varResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		zv::Val throwPoints = zv::Val::copyOf(zv::Ref(borrowed));
		borrowed = pt_expression_result_impure_points(varResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		zv::Val impurePoints = zv::Val::copyOf(zv::Ref(borrowed));
		bool isAlwaysTerminating;
		if (UNEXPECTED(!pt_expression_result_is_always_terminating(varResult.raw(), isAlwaysTerminating))) return zv::Val();
		borrowed = pt_expression_result_scope(varResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		zv::Val currentScope = zv::Val::copyOf(zv::Ref(borrowed));
		if (!closureCallScope.isUndef()) {
			currentScope = pt_mutating_scope_restore_original_scope_after_closure_bind(Z_OBJ_P(currentScope.raw()), originalScope);
			if (UNEXPECTED(currentScope.isUndef())) return zv::Val();
		}
		zv::Val parametersAcceptor = zv::Val::null();
		zv::Val variants = zv::Val(zv::Arr::empty());
		zv::Val namedArgumentsVariants = zv::Val::null();
		zv::Val methodReflection = zv::Val::null();
		zv::Val nameResult = zv::Val::null();
		// the var was processed above as the receiver; read its already-computed
		// result instead of re-walking via Scope::getType().
		zv::Val calledOnType = pt_expression_result_get_type(varResult.raw());
		if (UNEXPECTED(calledOnType.isUndef())) return zv::Val();
		// A plain call that is a link in a nullsafe chain may never run: the chain
		// short-circuits to null before the arguments are evaluated and before any
		// of the call's effects happen. NullsafeMethodCallHandler does the same for
		// the `?->` it owns; here the `?->` sits below a plain `->`.
		bool mayShortCircuit;
		if (UNEXPECTED(!pt_expression_result_may_short_circuit(varResult.raw(), calledOnType.raw(), mayShortCircuit))) return zv::Val();
		// A call configured as early-terminating never returns: give it an explicit
		// never so the statement's exit point follows from the result type, instead of
		// NodeScopeResolver re-deriving it via Scope::getType().
		bool isEarlyTerminating = false;
		if (nameIsIdentifier && !mayShortCircuit) {
			zval *methodName = identifierName(name);
			if (UNEXPECTED(methodName == NULL)) return zv::Val();
			if (UNEXPECTED(!pt_early_terminating_call_helper_is_early_terminating_method_call(OBJ_PROP_NUM(self, slots::earlyTerminatingHelper), methodName, calledOnType.raw(), isEarlyTerminating))) return zv::Val();
		}
		isAlwaysTerminating = isAlwaysTerminating || isEarlyTerminating;
		if (nameIsIdentifier) {
			zval *methodName = identifierName(name);
			if (UNEXPECTED(methodName == NULL)) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(methodName) != IS_STRING)) {
				zend_type_error("PHPStan\\Analyser\\MutatingScope::getMethodReflection(): Argument #2 ($methodName) must be of type string, %s given", zend_zval_value_name(methodName));
				return zv::Val();
			}
			methodReflection = pt_mutating_scope_get_method_reflection(Z_OBJ_P(currentScope.raw()), calledOnType.raw(), Z_STR_P(methodName));
			if (UNEXPECTED(methodReflection.isUndef())) return zv::Val();
			if (!methodReflection.isNull()) {
				variants = pt_extended_method_reflection_call(methodReflection.raw(), PT_MR_GET_VARIANTS);
				if (UNEXPECTED(variants.isUndef())) return zv::Val();
				namedArgumentsVariants = pt_extended_method_reflection_call(methodReflection.raw(), PT_MR_GET_NAMED_ARGUMENTS_VARIANTS);
				if (UNEXPECTED(namedArgumentsVariants.isUndef())) return zv::Val();
				// A structural acceptor (names/positions/variadic) drives argument
				// normalization, the impure point and the throw point - generics are
				// resolved type-driven by processArgs() into $resolvedParametersAcceptor.
				zval *args = exprArgs(expr);
				if (UNEXPECTED(args == NULL)) return zv::Val();
				parametersAcceptor = combineVariantsForNormalization(args, variants.raw(), namedArgumentsVariants.raw());
				if (UNEXPECTED(parametersAcceptor.isUndef())) return zv::Val();
			}
		} else {
			zv::Val nameContext = pt_expression_context_enter_deep(context);
			if (UNEXPECTED(nameContext.isUndef())) return zv::Val();
			nameResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, name, currentScope.raw(), storage, nodeCallback, nameContext.raw());
			if (UNEXPECTED(nameResult.isUndef())) return zv::Val();
			if (!hasYield && UNEXPECTED(!pt_expression_result_has_yield(nameResult.raw(), hasYield))) return zv::Val();
			borrowed = pt_expression_result_throw_points(nameResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			throwPoints = arrayMerge(throwPoints.raw(), borrowed);
			borrowed = pt_expression_result_impure_points(nameResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			impurePoints = arrayMerge(impurePoints.raw(), borrowed);
			if (!isAlwaysTerminating && UNEXPECTED(!pt_expression_result_is_always_terminating(nameResult.raw(), isAlwaysTerminating))) return zv::Val();
			borrowed = pt_expression_result_scope(nameResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			currentScope = zv::Val::copyOf(zv::Ref(borrowed));
		}

		zv::Val normalizedExpr = zv::Val::copyOf(zv::Ref(expr));
		if (!parametersAcceptor.isNull()) {
			zv::Val reordered = reorderMethodArguments(parametersAcceptor.raw(), expr);
			if (UNEXPECTED(reordered.isUndef())) return zv::Val();
			if (!reordered.isNull()) normalizedExpr = std::move(reordered);
			zv::Val returnType = acceptorReturnType(parametersAcceptor.raw());
			if (UNEXPECTED(returnType.isUndef())) return zv::Val();
			if (!isAlwaysTerminating && !mayShortCircuit) {
				bool explicitNever;
				if (UNEXPECTED(!isExplicitNever(returnType.raw(), explicitNever))) return zv::Val();
				isAlwaysTerminating = explicitNever;
			}
		}

		zv::Val scopeBeforeArgs = zv::Val::copyOf(zv::Ref(currentScope.raw()));
		zv::Val currentContext = zv::Val::copyOf(zv::Ref(context));
		if (!parametersAcceptor.isNull()) {
			zv::Val inAssignRightSideExpr = pt_expression_context_get_in_assign_right_side_expr(context);
			if (UNEXPECTED(inAssignRightSideExpr.isUndef())) return zv::Val();
			if (Z_TYPE_P(inAssignRightSideExpr.raw()) == IS_OBJECT && Z_OBJ_P(inAssignRightSideExpr.raw()) == Z_OBJ_P(expr)) {
				currentContext = pt_expression_context_enter_assign_right_side_call_args(context, parametersAcceptor.raw());
				if (UNEXPECTED(currentContext.isUndef())) return zv::Val();
			}
		}
		zv::Val nakedMethod = zv::Val::null();
		if (!methodReflection.isNull()) {
			zv::Val methodName = pt_extended_method_reflection_call(methodReflection.raw(), PT_MR_GET_NAME);
			if (UNEXPECTED(methodName.isUndef())) return zv::Val();
			nakedMethod = pt_mutating_scope_get_naked_method(Z_OBJ_P(currentScope.raw()), calledOnType.raw(), Z_STR_P(methodName.raw()));
			if (UNEXPECTED(nakedMethod.isUndef())) return zv::Val();
		}
		zv::Val argsResult;
		{
			zv::Args argv{nodeScopeResolver, stmt, methodReflection.raw(), nakedMethod.raw(), variants.raw(), namedArgumentsVariants.raw(), normalizedExpr.raw(), currentScope.raw(), storage, nodeCallback, currentContext.raw()};
			argsResult = processArgs(OBJ_PROP_NUM(self, slots::argumentsHandler), 11, argv);
		}
		if (UNEXPECTED(argsResult.isUndef())) return zv::Val();
		borrowed = pt_args_result_resolved_parameters_acceptor(argsResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		zv::Val resolvedParametersAcceptor = zv::Val::copyOf(zv::Ref(borrowed));
		borrowed = pt_args_result_scope(argsResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		currentScope = zv::Val::copyOf(zv::Ref(borrowed));
		{
			zv::Args argv{nodeScopeResolver, stmt, expr, normalizedExpr.raw(), currentScope.raw(), storage, currentContext.raw()};
			if (UNEXPECTED(!processDroppedArgs(OBJ_PROP_NUM(self, slots::argumentsHandler), argv))) return zv::Val();
		}

		if (!methodReflection.isNull()) {
			// created after the args were processed - the pure-unless-callable-
			// is-impure parameters read an argument's type, which is only
			// available once its result is stored
			zval *args = exprArgs(expr);
			if (UNEXPECTED(args == NULL)) return zv::Val();
			pt_simple_impure_point_data impurePoint;
			if (UNEXPECTED(!pt_simple_impure_point_resolve(methodReflection.raw(), parametersAcceptor.raw(), currentScope.raw(), args, impurePoint))) return zv::Val();
			if (impurePoint.exists) {
				zv::Val point = pt_impure_point_new(scopeBeforeArgs.raw(), expr, impurePoint.identifier, impurePoint.description, impurePoint.certain);
				zend_string_release(impurePoint.description);
				if (UNEXPECTED(point.isUndef())) return zv::Val();
				appendTo(impurePoints, std::move(point));
			}
		} else {
			zv::Val point = pt_impure_point_new(scopeBeforeArgs.raw(), expr, pt_mch_method_call, pt_mch_unknown_method, false);
			if (UNEXPECTED(point.isUndef())) return zv::Val();
			appendTo(impurePoints, std::move(point));
		}

		// The return type is derived from $resolvedParametersAcceptor - the acceptor
		// processArgs() selected from the arg types gathered on the arg-to-arg
		// evolving scope (type-driven, generics resolved). When null
		// (native-types-promoted, or on-demand / synthetic pricing) the acceptor is
		// re-derived from the already-processed argument results on the asking scope.
		zv::Val typeCallback = isEarlyTerminating
			? pt_native_closure(&earlyTerminatingTypeCallbackBody)
			: pt_native_closure(&typeCallbackBody, self, beforeScope, expr, varResult.raw(), nameResult.raw(), resolvedParametersAcceptor.raw(), argsResult.raw());
		zv::Val specifyTypesCallback = pt_native_closure(&specifyTypesCallbackBody, self, beforeScope, expr, normalizedExpr.raw(), varResult.raw(), resolvedParametersAcceptor.raw(), methodReflection.raw(), argsResult.raw());
		// A type constraint on a (narrowable, i.e. non-side-effecting) method call
		// narrows the call itself - the inside-out equivalent of createForExpr's
		// MethodCall purity gate + tail entry. An impure call narrows to nothing.
		zv::Val createTypesCallback = pt_native_closure(&createTypesCallbackBody, self, expr, varResult.raw(), beforeScope);

		// Store a preliminary result carrying the type/specify callbacks before the
		// throw point is computed: the method throw point resolves the return type
		// through dynamic return type extensions, which can narrow this very call on
		// demand. finalize() below completes it with the resolved scope and
		// throw/impure points.
		bool containsNullsafe;
		if (UNEXPECTED(!pt_expression_result_contains_nullsafe(varResult.raw(), containsNullsafe))) return zv::Val();
		pt_expression_result_args resultArgs(currentScope.raw(), beforeScope, expr, hasYield, isAlwaysTerminating, NULL, NULL, typeCallback.raw(), specifyTypesCallback.raw());
		resultArgs.withContainsNullsafe(containsNullsafe).withCreateTypesCallback(createTypesCallback.raw()).withArgsResult(argsResult.raw());
		zv::Val preliminaryResult = pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), resultArgs);
		if (UNEXPECTED(preliminaryResult.isUndef())) return zv::Val();
		if (UNEXPECTED(!pt_node_scope_resolver_store_expression_result(nodeScopeResolver, storage, expr, preliminaryResult.raw()))) return zv::Val();

		if (!methodReflection.isNull()) {
			// The early structural check above only sees the unresolved acceptor
			// return type; a conditional-return never only resolves to never once the
			// actual argument types are folded in by the type-driven resolved acceptor.
			if (!resolvedParametersAcceptor.isNull() && !mayShortCircuit) {
				zv::Val resolvedReturnType = resolvedAcceptorReturnType(resolvedParametersAcceptor.raw());
				if (UNEXPECTED(resolvedReturnType.isUndef())) return zv::Val();
				if (!isAlwaysTerminating) {
					bool explicitNever;
					if (UNEXPECTED(!isExplicitNever(resolvedReturnType.raw(), explicitNever))) return zv::Val();
					isAlwaysTerminating = explicitNever;
				}
			}

			// Resolve the call's return type through the stored preliminary result so
			// the memoized value seeds the final result below.
			zv::Val methodCallReturnType = pt_expression_result_get_keep_void_type(preliminaryResult.raw(), false);
			if (UNEXPECTED(methodCallReturnType.isUndef())) return zv::Val();
			zv::Val methodThrowPoint = pt_method_throw_point_helper_get_throw_point(OBJ_PROP_NUM(self, slots::methodThrowPointHelper), methodReflection.raw(), parametersAcceptor.raw(), normalizedExpr.raw(), currentScope.raw(), currentContext.raw(), methodCallReturnType.raw());
			if (UNEXPECTED(methodThrowPoint.isUndef())) return zv::Val();
			if (!methodThrowPoint.isNull()) {
				appendTo(throwPoints, std::move(methodThrowPoint));
			}

			zv::Val methodName = pt_extended_method_reflection_call(methodReflection.raw(), PT_MR_GET_NAME);
			if (UNEXPECTED(methodName.isUndef())) return zv::Val();
			bool invalidate = Z_TYPE_P(methodName.raw()) == IS_STRING && zend_string_equals_literal(Z_STR_P(methodName.raw()), "__construct");
			if (!invalidate) {
				zend_long hasSideEffects = reflectionTrinary(methodReflection.raw(), PT_MR_HAS_SIDE_EFFECTS);
				if (UNEXPECTED(hasSideEffects < 0)) return zv::Val();
				invalidate = hasSideEffects == PT_TRI_YES;
			}
			if (invalidate) {
				zval *normalizedVar = exprVar(normalizedExpr.raw());
				if (UNEXPECTED(normalizedVar == NULL)) return zv::Val();
				zv::Val invalidateNode = pt_type_new(PT_CLASS_INVALIDATE_EXPR_NODE, 1, normalizedVar);
				if (UNEXPECTED(invalidateNode.isUndef())) return zv::Val();
				if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, invalidateNode.raw(), currentScope.raw(), storage))) return zv::Val();
				normalizedVar = exprVar(normalizedExpr.raw());
				if (UNEXPECTED(normalizedVar == NULL)) return zv::Val();
				zv::Val declaringClass = pt_extended_method_reflection_call(methodReflection.raw(), PT_MR_GET_DECLARING_CLASS);
				if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
				zv::Val isStatic = pt_extended_method_reflection_call(methodReflection.raw(), PT_MR_IS_STATIC);
				if (UNEXPECTED(isStatic.isUndef())) return zv::Val();
				currentScope = pt_mutating_scope_invalidate_expression(Z_OBJ_P(currentScope.raw()), normalizedVar, true, declaringClass.raw(), zend_is_true(isStatic.raw()));
				if (UNEXPECTED(currentScope.isUndef())) return zv::Val();
			} else if (zend_is_true(OBJ_PROP_NUM(self, slots::rememberPossiblyImpureFunctionValues))) {
				zend_long hasSideEffects = reflectionTrinary(methodReflection.raw(), PT_MR_HAS_SIDE_EFFECTS);
				if (UNEXPECTED(hasSideEffects < 0)) return zv::Val();
				bool remember = false;
				if (hasSideEffects == PT_TRI_MAYBE) {
					zv::Val declaringClass = pt_extended_method_reflection_call(methodReflection.raw(), PT_MR_GET_DECLARING_CLASS);
					if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
					bool builtin;
					if (UNEXPECTED(!pt_class_reflection_is_builtin(Z_OBJ_P(declaringClass.raw()), builtin))) return zv::Val();
					remember = !builtin;
				}
				if (remember) {
					// the remembered call value and the @phpstan-self-out type are
					// generic-sensitive: resolve them from the type-driven acceptor
					// processArgs() selected, falling back to the structural acceptor
					zval *acceptorForGenerics = resolvedParametersAcceptor.isNull() ? parametersAcceptor.raw() : resolvedParametersAcceptor.raw();
					zv::Val rememberedType = pt_template_argument_frame_return_type_of_call(acceptorForGenerics, currentScope.raw(), expr);
					if (UNEXPECTED(rememberedType.isUndef())) return zv::Val();
					bool varContainsNullsafe;
					if (UNEXPECTED(!pt_expression_result_contains_nullsafe(varResult.raw(), varContainsNullsafe))) return zv::Val();
					if (varContainsNullsafe) {
						bool calledOnContainsNull;
						if (UNEXPECTED(!pt_type_combinator_contains_null(calledOnType.raw(), calledOnContainsNull))) return zv::Val();
						if (calledOnContainsNull) {
							// a call on a nullsafe chain whose receiver is nullable
							// short-circuits to null - the tracked entry is keyed by the
							// whole chain, so it must remember the propagated type
							rememberedType = pt_type_combinator_add_null(rememberedType.raw());
							if (UNEXPECTED(rememberedType.isUndef())) return zv::Val();
						}
					}
					zval *normalizedVar = exprVar(normalizedExpr.raw());
					if (UNEXPECTED(normalizedVar == NULL)) return zv::Val();
					zv::Val description = possiblyImpureCallDescription(methodReflection.raw());
					if (UNEXPECTED(description.isUndef())) return zv::Val();
					zv::Args exprArgv{normalizedExpr.raw(), normalizedVar, description.raw()};
					zv::Val possiblyImpureCallExpr = pt_type_new(PT_CLASS_POSSIBLY_IMPURE_CALL_EXPR, 3, exprArgv);
					if (UNEXPECTED(possiblyImpureCallExpr.isUndef())) return zv::Val();
					zv::Val mixed = pt_type_new_mixed_type();
					if (UNEXPECTED(mixed.isUndef())) return zv::Val();
					currentScope = pt_mutating_scope_assign_expression(Z_OBJ_P(currentScope.raw()), Z_OBJ_P(possiblyImpureCallExpr.raw()), rememberedType.raw(), mixed.raw());
					if (UNEXPECTED(currentScope.isUndef())) return zv::Val();
				}
			}
			zv::Val isStatic = pt_extended_method_reflection_call(methodReflection.raw(), PT_MR_IS_STATIC);
			if (UNEXPECTED(isStatic.isUndef())) return zv::Val();
			if (!zend_is_true(isStatic.raw())) {
				zv::Val selfOutType = pt_extended_method_reflection_call(methodReflection.raw(), PT_MR_GET_SELF_OUT_TYPE);
				if (UNEXPECTED(selfOutType.isUndef())) return zv::Val();
				if (!selfOutType.isNull()) {
					zval *acceptorForGenerics = resolvedParametersAcceptor.isNull() ? parametersAcceptor.raw() : resolvedParametersAcceptor.raw();
					zval *normalizedVar = exprVar(normalizedExpr.raw());
					if (UNEXPECTED(normalizedVar == NULL)) return zv::Val();
					zv::Val argsHold;
					zval *callArgs = pt_call_like_args(Z_OBJ_P(normalizedExpr.raw()), argsHold);
					if (UNEXPECTED(callArgs == NULL)) return zv::Val();
					zv::Val resolvedSelfOutType = pt_conditional_type_resolver_resolve_for_call(selfOutType.raw(), acceptorForGenerics, callArgs, currentScope.raw());
					if (UNEXPECTED(resolvedSelfOutType.isUndef())) return zv::Val();
					zv::Val varNativeType = pt_expression_result_get_native_type(varResult.raw());
					if (UNEXPECTED(varNativeType.isUndef())) return zv::Val();
					currentScope = pt_mutating_scope_assign_expression(Z_OBJ_P(currentScope.raw()), Z_OBJ_P(normalizedVar), resolvedSelfOutType.raw(), varNativeType.raw());
					if (UNEXPECTED(currentScope.isUndef())) return zv::Val();
				}
			}
		} else {
			zval *normalizedVar = exprVar(normalizedExpr.raw());
			if (UNEXPECTED(normalizedVar == NULL)) return zv::Val();
			zv::Val invalidateNode = pt_type_new(PT_CLASS_INVALIDATE_EXPR_NODE, 1, normalizedVar);
			if (UNEXPECTED(invalidateNode.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, invalidateNode.raw(), currentScope.raw(), storage))) return zv::Val();
			normalizedVar = exprVar(normalizedExpr.raw());
			if (UNEXPECTED(normalizedVar == NULL)) return zv::Val();
			currentScope = pt_mutating_scope_invalidate_expression(Z_OBJ_P(currentScope.raw()), normalizedVar, true);
			if (UNEXPECTED(currentScope.isUndef())) return zv::Val();
			zv::Val throwPoint = pt_internal_throw_point_create_implicit(currentScope.raw(), expr);
			if (UNEXPECTED(throwPoint.isUndef())) return zv::Val();
			appendTo(throwPoints, std::move(throwPoint));
		}
		bool invalidateVolatile = methodReflection.isNull();
		if (!invalidateVolatile) {
			zv::Val declaringClass = pt_extended_method_reflection_call(methodReflection.raw(), PT_MR_GET_DECLARING_CLASS);
			if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
			bool builtin;
			if (UNEXPECTED(!pt_class_reflection_is_builtin(Z_OBJ_P(declaringClass.raw()), builtin))) return zv::Val();
			if (!builtin) {
				zend_long hasSideEffects = reflectionTrinary(methodReflection.raw(), PT_MR_HAS_SIDE_EFFECTS);
				if (UNEXPECTED(hasSideEffects < 0)) return zv::Val();
				invalidateVolatile = hasSideEffects != PT_TRI_NO;
			}
		}
		if (invalidateVolatile) {
			currentScope = pt_mutating_scope_invalidate_volatile_expressions(Z_OBJ_P(currentScope.raw()));
			if (UNEXPECTED(currentScope.isUndef())) return zv::Val();
		}

		if (!hasYield && UNEXPECTED(!pt_args_result_has_yield(argsResult.raw(), hasYield))) return zv::Val();
		borrowed = pt_args_result_throw_points(argsResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		throwPoints = arrayMerge(throwPoints.raw(), borrowed);
		borrowed = pt_args_result_impure_points(argsResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		impurePoints = arrayMerge(impurePoints.raw(), borrowed);
		if (!isAlwaysTerminating && !mayShortCircuit && UNEXPECTED(!pt_args_result_is_always_terminating(argsResult.raw(), isAlwaysTerminating))) return zv::Val();

		zv::Val variableFlow;
		{
			zv::Val varFlow = pt_expression_result_variable_flow(varResult.raw());
			if (UNEXPECTED(varFlow.isUndef())) return zv::Val();
			zv::Val nameFlow = zv::Val::null();
			if (!nameResult.isNull()) {
				nameFlow = pt_expression_result_variable_flow(nameResult.raw());
				if (UNEXPECTED(nameFlow.isUndef())) return zv::Val();
			}
			zv::Val argumentsFlow = pt_variable_flow_builder_arguments(expr, argsResult.raw(), storage);
			if (UNEXPECTED(argumentsFlow.isUndef())) return zv::Val();
			if (mayShortCircuit) {
				// the short-circuited world evaluates none of the arguments
				zv::Args choiceArgv{argumentsFlow.raw(), zv::null};
				zv::Val choice = pt_variable_flow_choice(2, choiceArgv);
				if (UNEXPECTED(choice.isUndef())) return zv::Val();
				argumentsFlow = std::move(choice);
			}
			zv::Val throwsFlow = pt_variable_flow_builder_throws(expr, Z_ARRVAL_P(throwPoints.raw()));
			if (UNEXPECTED(throwsFlow.isUndef())) return zv::Val();
			zv::Val exitFlow = zv::Val::null();
			if (isAlwaysTerminating) {
				exitFlow = pt_variable_flow_exit_stop();
				if (UNEXPECTED(exitFlow.isUndef())) return zv::Val();
			}
			zv::Args flows{varFlow.raw(), nameFlow.raw(), argumentsFlow.raw(), throwsFlow.raw(), exitFlow.raw()};
			variableFlow = pt_variable_flow_sequence(5, flows);
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		}

		// the call's scope effects (@param-out, @phpstan-self-out, invalidations)
		// only happened in the world where the chain did not short-circuit
		if (mayShortCircuit) {
			currentScope = pt_mutating_scope_merge_with(Z_OBJ_P(currentScope.raw()), scopeBeforeArgs.raw());
			if (UNEXPECTED(currentScope.isUndef())) return zv::Val();
		}

		zv::Val result = pt_expression_result_finalize(preliminaryResult.raw(), currentScope.raw(), hasYield, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), variableFlow.raw());
		if (UNEXPECTED(result.isUndef())) return zv::Val();

		// the var was processed above as the receiver; read its already-computed
		// result on the original scope instead of re-walking via Scope::getType().
		calledOnType = pt_expression_result_get_type(varResult.raw());
		if (UNEXPECTED(calledOnType.isUndef())) return zv::Val();
		name = exprName(expr);
		if (UNEXPECTED(name == NULL)) return zv::Val();
		nameIsIdentifier = isIdentifier(name);
		if (UNEXPECTED(nameIsIdentifier < 0)) return zv::Val();
		if (!nameIsIdentifier) return result;
		zval *methodName = identifierName(name);
		if (UNEXPECTED(methodName == NULL)) return zv::Val();
		methodReflection = pt_mutating_scope_get_method_reflection(Z_OBJ_P(originalScope), calledOnType.raw(), Z_STR_P(methodName));
		if (UNEXPECTED(methodReflection.isUndef())) return zv::Val();
		if (methodReflection.isNull()) return result;

		bool processCalled = false;
		if (UNEXPECTED(!shouldProcessCalledMethod(currentScope.raw(), methodReflection.raw(), calledOnType.raw(), processCalled))) return zv::Val();
		if (processCalled) {
			zv::Val calledMethodScope = pt_called_method_processor_process_called_method(OBJ_PROP_NUM(self, slots::calledMethodProcessor), nodeScopeResolver, methodReflection.raw());
			if (UNEXPECTED(calledMethodScope.isUndef())) return zv::Val();
			if (!calledMethodScope.isNull()) {
				currentScope = pt_mutating_scope_merge_initialized_properties(Z_OBJ_P(currentScope.raw()), calledMethodScope.raw());
				if (UNEXPECTED(currentScope.isUndef())) return zv::Val();
				return pt_expression_result_with_scope(result.raw(), currentScope.raw());
			}
		}

		return result;
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return MethodCallHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

	/* Mirrors resolveReturnType(); $nameResult / $preResolvedAcceptor /
	 * $argsResult NULL or IS_NULL for null */
	zv::Val resolveReturnType(zval *reflectionScope, bool nativeTypesPromoted, zval *expr, zval *varResult, zval *nameResult, zval *preResolvedAcceptor, zval *argsResult) const
	{
		if (nameResult != NULL && Z_TYPE_P(nameResult) == IS_NULL) nameResult = NULL;
		// the receiver (scope-dependent) is read from the operand result; the
		// method reflection and dynamic-return-type extensions run on the
		// reflection scope (the lexical context / beforeScope).
		zv::Val calledOnType = nativeTypesPromoted ? pt_expression_result_get_native_type(varResult) : pt_expression_result_get_type(varResult);
		if (UNEXPECTED(calledOnType.isUndef())) return zv::Val();

		zval *name = exprName(expr);
		if (UNEXPECTED(name == NULL)) return zv::Val();
		int nameIsIdentifier = isIdentifier(name);
		if (UNEXPECTED(nameIsIdentifier < 0)) return zv::Val();
		if (nameIsIdentifier) {
			zval *methodName = identifierName(name);
			if (UNEXPECTED(methodName == NULL)) return zv::Val();
			zv::Val type = resolveMethod(reflectionScope, nativeTypesPromoted, calledOnType.raw(), preResolvedAcceptor, argsResult, methodName, expr);
			if (UNEXPECTED(type.isUndef())) return zv::Val();
			// a call on a nullsafe chain whose receiver is currently nullable
			// short-circuits to null - the receiver result carries whether the chain
			// contains a ?-> (a plain nullable receiver does not propagate).
			bool containsNullsafe;
			if (UNEXPECTED(!pt_expression_result_contains_nullsafe(varResult, containsNullsafe))) return zv::Val();
			if (containsNullsafe) {
				bool containsNull;
				if (UNEXPECTED(!pt_type_combinator_contains_null(calledOnType.raw(), containsNull))) return zv::Val();
				if (containsNull) return pt_type_combinator_add_null(type.raw());
			}
			return type;
		}

		// dynamic method call $obj->$name(): resolve each possible name on the
		// reflection scope. The asking scope is not narrowed per name, so such
		// calls can be less precise. Every caller walks a non-Identifier name
		// and passes its result.
		if (nameResult == NULL) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		zv::Val nameType = nativeTypesPromoted ? pt_expression_result_get_native_type(nameResult) : pt_expression_result_get_type(nameResult);
		if (UNEXPECTED(nameType.isUndef())) return zv::Val();
		zv::Val constantStrings = getConstantStrings(nameType.raw());
		if (UNEXPECTED(constantStrings.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(constantStrings.raw()) != IS_ARRAY)) {
			zend_type_error("count(): Argument #1 ($value) must be of type Countable|array, %s given", zend_zval_value_name(constantStrings.raw()));
			return zv::Val();
		}
		if (zend_hash_num_elements(Z_ARRVAL_P(constantStrings.raw())) == 0) return pt_type_new_mixed_type();

		zv::Val iterated = getConstantStrings(nameType.raw());
		if (UNEXPECTED(iterated.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(iterated.raw()) != IS_ARRAY)) {
			zend_type_error("array_map(): Argument #2 ($array) must be of type array, %s given", zend_zval_value_name(iterated.raw()));
			return zv::Val();
		}
		zv::Arr types = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(iterated.raw())));
		for (auto entry : zv::TableRef(Z_ARRVAL_P(iterated.raw()))) {
			zval *constantString = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(constantString) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function getValue() on %s", zend_zval_value_name(constantString));
				return zv::Val();
			}
			zv::Val value = pt_type_op(Z_OBJ_P(constantString), PT_OP_GET_VALUE, 0, NULL);
			if (UNEXPECTED(value.isUndef())) return zv::Val();
			if (Z_TYPE_P(value.raw()) == IS_STRING && ZSTR_LEN(Z_STR_P(value.raw())) == 0) {
				zv::Val error = pt_type_new_error_type();
				if (UNEXPECTED(error.isUndef())) return zv::Val();
				types.push(std::move(error));
				continue;
			}
			zval *var = exprVar(expr);
			if (UNEXPECTED(var == NULL)) return zv::Val();
			zv::Val identifier = pt_type_new(PT_CLASS_IDENTIFIER, 1, value.raw());
			if (UNEXPECTED(identifier.isUndef())) return zv::Val();
			zval *args = exprArgs(expr);
			if (UNEXPECTED(args == NULL)) return zv::Val();
			zv::Args methodCallArgv{var, identifier.raw(), args};
			zv::Val methodCall = pt_type_new(PT_CLASS_METHOD_CALL, 3, methodCallArgv);
			if (UNEXPECTED(methodCall.isUndef())) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(value.raw()) != IS_STRING)) {
				zend_type_error("PHPStan\\Analyser\\ExprHandler\\MethodCallHandler::{closure}(): Argument #1 ($methodName) must be of type string, %s given", zend_zval_value_name(value.raw()));
				return zv::Val();
			}
			zv::Val type = resolveMethod(reflectionScope, nativeTypesPromoted, calledOnType.raw(), preResolvedAcceptor, argsResult, value.raw(), methodCall.raw());
			if (UNEXPECTED(type.isUndef())) return zv::Val();
			types.push(std::move(type));
		}
		HashTable *typesTable = types.table();
		return pt_type_combinator_union(zend_hash_num_elements(typesTable), typesTable->arPacked);
	}

	/* Mirrors specifyTypes(); $resolvedParametersAcceptor / $argsResult NULL
	 * or IS_NULL for null */
	zv::Val specifyTypes(zval *scope, zval *expr, zval *normalizedExpr, zval *varResult, zval *resolvedParametersAcceptor, zval *walkMethodReflection, zval *context, zval *argsResult) const
	{
		if (resolvedParametersAcceptor != NULL && Z_TYPE_P(resolvedParametersAcceptor) == IS_NULL) resolvedParametersAcceptor = NULL;
		if (argsResult != NULL && Z_TYPE_P(argsResult) == IS_NULL) argsResult = NULL;
		zval null;
		ZVAL_NULL(&null);

		zval *name = exprName(expr);
		if (UNEXPECTED(name == NULL)) return zv::Val();
		int nameIsIdentifier = isIdentifier(name);
		if (UNEXPECTED(nameIsIdentifier < 0)) return zv::Val();
		if (!nameIsIdentifier) return defaultMethodCallNarrowing(scope, expr, varResult, context);

		// the var was processed during processExpr; read its already-computed
		// result instead of re-walking via Scope::getType().
		bool nativeTypesPromoted;
		if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(scope), nativeTypesPromoted))) return zv::Val();
		zv::Val methodCalledOnType = pt_expression_result_get_type_on_scope(varResult, scope, nativeTypesPromoted);
		if (UNEXPECTED(methodCalledOnType.isUndef())) return zv::Val();
		zval *methodName = identifierName(name);
		if (UNEXPECTED(methodName == NULL)) return zv::Val();
		zv::Val methodReflection = pt_mutating_scope_get_method_reflection(Z_OBJ_P(scope), methodCalledOnType.raw(), Z_STR_P(methodName));
		if (UNEXPECTED(methodReflection.isUndef())) return zv::Val();
		// a call on a nullsafe chain may never have run - the branches that still
		// admit the short-circuit's null get no callee-derived narrowing at all
		bool mayHaveBeenSkipped;
		if (UNEXPECTED(!pt_default_narrowing_helper_call_may_have_been_skipped(OBJ_PROP_NUM(self, slots::defaultNarrowingHelper), varResult, methodCalledOnType.raw(), context, mayHaveBeenSkipped))) return zv::Val();
		if (!methodReflection.isNull() && !mayHaveBeenSkipped) {
			zval *args = exprArgs(expr);
			if (UNEXPECTED(args == NULL)) return zv::Val();
			zv::Val argsHold = zv::Val::copyOf(zv::Ref(args));

			zv::Val referencedClasses = pt_type_op(Z_OBJ_P(methodCalledOnType.raw()), PT_OP_GET_OBJECT_CLASS_NAMES, 0, NULL);
			if (UNEXPECTED(referencedClasses.isUndef())) return zv::Val();
			if (Z_TYPE_P(referencedClasses.raw()) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(referencedClasses.raw())) == 1) {
				zval *className = zend_hash_index_find(Z_ARRVAL_P(referencedClasses.raw()), 0);
				if (UNEXPECTED(className == NULL)) {
					zend_error(E_WARNING, "Undefined array key 0");
					if (UNEXPECTED(EG(exception))) return zv::Val();
					className = &null;
				}
				zval *reflectionProvider = OBJ_PROP_NUM(self, slots::reflectionProvider);
				bool hasClass;
				if (UNEXPECTED(!pt_reflection_provider_has_class(Z_OBJ_P(reflectionProvider), className, hasClass))) return zv::Val();
				if (hasClass) {
					zv::Val methodClassReflection = pt_reflection_provider_get_class(Z_OBJ_P(reflectionProvider), className);
					if (UNEXPECTED(methodClassReflection.isUndef())) return zv::Val();
					// runs lazily at narrowing-apply time - prime the storage with the
					// argument results so the extensions' Scope::getType() asks about
					// the arguments answer from them instead of re-walking on demand
					pt_primed_storage primed;
					if (UNEXPECTED(!pt_dynamic_return_type_storage_primer_push(OBJ_PROP_NUM(self, slots::storagePrimer), scope, argsResult != NULL ? argsResult : &null, primed))) return zv::Val();
					zv::Val extensionResult = specifyTypesByExtensions(methodClassReflection.raw(), methodReflection.raw(), normalizedExpr, scope, context);
					pt_finally([&]() { (void) pt_dynamic_return_type_storage_primer_pop(primed); });
					if (UNEXPECTED(extensionResult.isUndef() || EG(exception) != NULL)) return zv::Val();
					if (!extensionResult.isNull()) return extensionResult;
				}
			}

			if (zend_hash_num_elements(Z_ARRVAL_P(argsHold.raw())) > 0 && resolvedParametersAcceptor != NULL) {
				zv::Val specifiedTypes = pt_default_narrowing_helper_specify_types_from_conditional_return_type(OBJ_PROP_NUM(self, slots::defaultNarrowingHelper), context, expr, resolvedParametersAcceptor, scope);
				if (UNEXPECTED(specifiedTypes.isUndef())) return zv::Val();
				if (!specifiedTypes.isNull()) return specifiedTypes;
			}

			// The assertions are paired with $resolvedParametersAcceptor, which
			// processArgs() resolved on the walk scope. $methodReflection above is
			// re-derived from the receiver type of the asking scope, so a
			// native-types-promoted ask resolves the class-level template types of
			// the assert types to their bounds while the acceptor's template map -
			// the one the narrowing checks them for unresolved templates against -
			// still resolves them from the PHPDoc receiver. The assert would look
			// resolved while carrying a bound, and `mixed` would be asserted as a
			// real type. A @phpstan-assert is a PHPDoc claim either way, so the
			// promoted ask reads it off the walk's reflection: both halves then
			// describe the same receiver.
			zval *assertsReflection = methodReflection.raw();
			if (nativeTypesPromoted && walkMethodReflection != NULL && Z_TYPE_P(walkMethodReflection) != IS_NULL) assertsReflection = walkMethodReflection;
			zv::Val assertions = pt_extended_method_reflection_call(assertsReflection, PT_MR_GET_ASSERTS);
			if (UNEXPECTED(assertions.isUndef())) return zv::Val();
			zv::Val all = assertionsGetAll(assertions.raw());
			if (UNEXPECTED(all.isUndef())) return zv::Val();
			bool hasAsserts = !(Z_TYPE_P(all.raw()) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(all.raw())) == 0);
			if (hasAsserts && resolvedParametersAcceptor != NULL) {
				zv::Val mapCallback = pt_native_closure(&resolveAssertTypeBody, resolvedParametersAcceptor);
				zv::Val asserts = assertionsMapTypes(assertions.raw(), mapCallback.raw());
				if (UNEXPECTED(asserts.isUndef())) return zv::Val();
				zv::Val specifiedTypes = pt_default_narrowing_helper_specify_types_from_asserts(OBJ_PROP_NUM(self, slots::defaultNarrowingHelper), context, expr, asserts.raw(), resolvedParametersAcceptor, scope);
				if (UNEXPECTED(specifiedTypes.isUndef())) return zv::Val();
				if (!specifiedTypes.isNull()) {
					// the asserts narrow their subjects regardless; the call's OWN
					// key goes through the same purity gate as the assert-less
					// default narrowing - an impure method evaluated a second time
					// must not read the first call's truthiness
					zv::Val defaultNarrowing = defaultMethodCallNarrowing(scope, expr, varResult, context);
					if (UNEXPECTED(defaultNarrowing.isUndef())) return zv::Val();
					zv::Val united = pt_specified_types_union_with(Z_OBJ_P(specifiedTypes.raw()), defaultNarrowing.raw());
					if (UNEXPECTED(united.isUndef())) return zv::Val();
					zv::Val rootExpr = pt_specified_types_get_root_expr(Z_OBJ_P(specifiedTypes.raw()));
					if (UNEXPECTED(rootExpr.isUndef())) return zv::Val();
					return pt_specified_types_set_root_expr(Z_OBJ_P(united.raw()), rootExpr.raw());
				}
			}
		}

		return defaultMethodCallNarrowing(scope, expr, varResult, context);
	}

	/* Mirrors defaultMethodCallNarrowing(). */
	zv::Val defaultMethodCallNarrowing(zval *scope, zval *expr, zval *varResult, zval *context) const
	{
		// a truthy chain containing a nullsafe narrows its receivers not-null
		// regardless of the call's own narrowability - the old-world truthy
		// default routed through create()'s nullsafe fan
		zv::Val nullsafeFan = zv::Val::null();
		bool truthy;
		if (UNEXPECTED(!pt_type_specifier_context_truthy(Z_OBJ_P(context), truthy))) return zv::Val();
		bool falsey = false;
		if (truthy && UNEXPECTED(!pt_type_specifier_context_falsey(Z_OBJ_P(context), falsey))) return zv::Val();
		if (truthy && !falsey) {
			zv::Val currentStorage = pt_mutating_scope_get_current_expression_result_storage(Z_OBJ_P(scope));
			if (UNEXPECTED(currentStorage.isUndef())) return zv::Val();
			zv::Val result = zv::Val::null();
			if (!currentStorage.isNull()) {
				result = pt_expression_result_storage_find(currentStorage.raw(), expr);
				if (UNEXPECTED(result.isUndef())) return zv::Val();
			}
			if (!result.isNull()) {
				zv::Val falseyType = pt_static_type_factory_falsey();
				if (UNEXPECTED(falseyType.isUndef())) return zv::Val();
				zend_object *falseContext = pt_type_specifier_context_create_false();
				if (UNEXPECTED(falseContext == NULL)) return zv::Val();
				zval falseContextZv;
				ZVAL_OBJ(&falseContextZv, falseContext);
				nullsafeFan = pt_default_narrowing_helper_create_nullsafe_receiver_only_types(OBJ_PROP_NUM(self, slots::defaultNarrowingHelper), scope, expr, result.raw(), falseyType.raw(), &falseContextZv);
				if (UNEXPECTED(nullsafeFan.isUndef())) return zv::Val();
			}
		}

		bool narrowable;
		if (UNEXPECTED(!isMethodCallNarrowable(scope, expr, varResult, narrowable))) return zv::Val();
		if (!narrowable) {
			zv::Val empty = pt_specified_types_new();
			if (UNEXPECTED(empty.isUndef())) return zv::Val();
			zv::Val base = pt_specified_types_set_root_expr(Z_OBJ_P(empty.raw()), expr);
			if (UNEXPECTED(base.isUndef())) return zv::Val();
			if (nullsafeFan.isNull()) return base;
			zv::Val united = pt_specified_types_union_with(Z_OBJ_P(base.raw()), nullsafeFan.raw());
			if (UNEXPECTED(united.isUndef())) return zv::Val();
			return pt_specified_types_set_root_expr(Z_OBJ_P(united.raw()), expr);
		}

		zv::Val defaultTypes = pt_default_narrowing_helper_specify_default_types(OBJ_PROP_NUM(self, slots::defaultNarrowingHelper), expr, context);
		if (UNEXPECTED(defaultTypes.isUndef())) return zv::Val();
		if (nullsafeFan.isNull()) return defaultTypes;
		zv::Val united = pt_specified_types_union_with(Z_OBJ_P(defaultTypes.raw()), nullsafeFan.raw());
		if (UNEXPECTED(united.isUndef())) return zv::Val();
		return pt_specified_types_set_root_expr(Z_OBJ_P(united.raw()), expr);
	}

	/* Mirrors isMethodCallNarrowable(); false = pending exception */
	[[nodiscard]] bool isMethodCallNarrowable(zval *scope, zval *expr, zval *varResult, bool &out) const
	{
		zval *name = exprName(expr);
		if (UNEXPECTED(name == NULL)) return false;
		int nameIsIdentifier = isIdentifier(name);
		if (UNEXPECTED(nameIsIdentifier < 0)) return false;
		if (!nameIsIdentifier) {
			out = true;
			return true;
		}

		bool nativeTypesPromoted;
		if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(scope), nativeTypesPromoted))) return false;
		zv::Val calledOnType = pt_expression_result_get_type_on_scope(varResult, scope, nativeTypesPromoted);
		if (UNEXPECTED(calledOnType.isUndef())) return false;
		/* $expr->name->toString() */
		zval *methodName = identifierName(name);
		if (UNEXPECTED(methodName == NULL)) return false;
		zv::Val methodReflection = pt_mutating_scope_get_method_reflection(Z_OBJ_P(scope), calledOnType.raw(), Z_STR_P(methodName));
		if (UNEXPECTED(methodReflection.isUndef())) return false;
		if (methodReflection.isNull()) {
			out = false;
			return true;
		}

		zend_long hasSideEffects = reflectionTrinary(methodReflection.raw(), PT_MR_HAS_SIDE_EFFECTS);
		if (UNEXPECTED(hasSideEffects < 0)) return false;
		if (hasSideEffects == PT_TRI_YES) {
			out = false;
			return true;
		}

		out = zend_is_true(OBJ_PROP_NUM(self, slots::rememberPossiblyImpureFunctionValues)) || hasSideEffects == PT_TRI_NO;
		return true;
	}

private:
	zend_object *self;

	/* $resolveMethod($methodName, $methodCall) of resolveReturnType() */
	zv::Val resolveMethod(zval *reflectionScope, bool nativeTypesPromoted, zval *calledOnType, zval *preResolvedAcceptor, zval *argsResult, zval *methodName, zval *methodCall) const
	{
		if (nativeTypesPromoted) {
			zv::Val methodReflection = pt_mutating_scope_get_method_reflection(Z_OBJ_P(reflectionScope), calledOnType, Z_STR_P(methodName));
			if (UNEXPECTED(methodReflection.isUndef())) return zv::Val();
			if (methodReflection.isNull()) return pt_type_new_error_type();

			zv::Val variants = pt_extended_method_reflection_call(methodReflection.raw(), PT_MR_GET_VARIANTS);
			if (UNEXPECTED(variants.isUndef())) return zv::Val();
			zv::Val acceptor = combineAcceptors(variants.raw());
			if (UNEXPECTED(acceptor.isUndef())) return zv::Val();
			return acceptorNativeReturnType(acceptor.raw());
		}

		zv::Val type = pt_method_call_return_type_helper_method_call_return_type(OBJ_PROP_NUM(self, slots::methodCallReturnTypeHelper), reflectionScope, calledOnType, methodName, methodCall, preResolvedAcceptor, argsResult);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		if (type.isNull()) return pt_type_new_error_type();
		return type;
	}

	/* the extensions loop of specifyTypes() inside its try: the first
	 * supporting extension's answer, null when none supports the call */
	zv::Val specifyTypesByExtensions(zval *methodClassReflection, zval *methodReflection, zval *normalizedExpr, zval *scope, zval *context) const
	{
		zv::Val className = pt_class_reflection_get_name(Z_OBJ_P(methodClassReflection));
		if (UNEXPECTED(className.isUndef())) return zv::Val();
		zv::Val extensions = pt_type_specifier_get_method_type_specifying_extensions_for_class(Z_OBJ_P(OBJ_PROP_NUM(self, slots::typeSpecifier)), className.raw());
		if (UNEXPECTED(extensions.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(extensions.raw()) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(extensions.raw()));
			if (UNEXPECTED(EG(exception))) return zv::Val();
			return zv::Val::null();
		}
		for (auto entry : zv::TableRef(Z_ARRVAL_P(extensions.raw()))) {
			zval *extension = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(extension) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function isMethodSupported() on %s", zend_zval_value_name(extension));
				return zv::Val();
			}
			bool supported;
			if (UNEXPECTED(!extensionIsMethodSupported(extension, methodReflection, normalizedExpr, context, supported))) return zv::Val();
			if (!supported) continue;

			return extensionSpecifyTypes(extension, methodReflection, normalizedExpr, scope, context);
		}
		return zv::Val::null();
	}

	/* the purity gate of processCalledMethod(): $scope->isInClass() && the
	 * class is the method's declaring class && the function is __construct
	 * && the receiver is $this; false = pending exception */
	[[nodiscard]] static bool shouldProcessCalledMethod(zval *scope, zval *methodReflection, zval *calledOnType, bool &out)
	{
		out = false;
		bool inClass;
		if (UNEXPECTED(!pt_scope_is_in_class(Z_OBJ_P(scope), inClass))) return false;
		if (!inClass) return true;
		zv::Val classReflection = pt_scope_get_class_reflection(Z_OBJ_P(scope));
		if (UNEXPECTED(classReflection.isUndef())) return false;
		if (UNEXPECTED(!classReflection.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function getName() on %s", zend_zval_value_name(classReflection.raw()));
			return false;
		}
		zv::Val className = pt_class_reflection_get_name(Z_OBJ_P(classReflection.raw()));
		if (UNEXPECTED(className.isUndef())) return false;
		zv::Val declaringClass = pt_extended_method_reflection_call(methodReflection, PT_MR_GET_DECLARING_CLASS);
		if (UNEXPECTED(declaringClass.isUndef())) return false;
		zv::Val declaringClassName = pt_class_reflection_get_name(Z_OBJ_P(declaringClass.raw()));
		if (UNEXPECTED(declaringClassName.isUndef())) return false;
		if (!zend_is_identical(className.raw(), declaringClassName.raw())) return true;

		zv::Val functionName = pt_mutating_scope_get_function_name(Z_OBJ_P(scope));
		if (UNEXPECTED(functionName.isUndef())) return false;
		if (functionName.isNull()) return true;
		zv::Val lowerFunctionName = pt_mutating_scope_get_function_name(Z_OBJ_P(scope));
		if (UNEXPECTED(lowerFunctionName.isUndef())) return false;
		if (UNEXPECTED(!lowerFunctionName.ref().isString())) {
			zend_type_error("strtolower(): Argument #1 ($string) must be of type string, %s given", zend_zval_value_name(lowerFunctionName.raw()));
			return false;
		}
		if (!zend_string_equals_literal_ci(Z_STR_P(lowerFunctionName.raw()), "__construct")) return true;

		zv::Val thisType = pt_type_utils_find_this_type(calledOnType);
		if (UNEXPECTED(thisType.isUndef())) return false;
		out = !thisType.isNull();
		return true;
	}

	/* static fn (bool $nativeTypesPromoted): Type => new NeverType(true) */
	static void earlyTerminatingTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) captures;
		(void) argv;
		if (UNEXPECTED(argc < 1)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\ExprHandler\\MethodCallHandler::{closure}(), %u passed and exactly 1 expected", argc);
			return;
		}
		zval never;
		if (UNEXPECTED(!pt_never_type_new(&never, true))) return;
		ZVAL_COPY_VALUE(return_value, &never);
	}

	/* fn (bool $nativeTypesPromoted): Type => $this->resolveReturnType($beforeScope,
	 * $nativeTypesPromoted, $expr, $varResult, $nameResult, $nativeTypesPromoted ?
	 * null : $resolvedParametersAcceptor, $argsResult) — captures: $this,
	 * $beforeScope, $expr, $varResult, $nameResult, $resolvedParametersAcceptor,
	 * $argsResult */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 1)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\ExprHandler\\MethodCallHandler::{closure}(), %u passed and exactly 1 expected", argc);
			return;
		}
		bool nativeTypesPromoted = zend_is_true(&argv[0]);
		zv::Val type = MethodCallHandler(Z_OBJ(captures[0])).resolveReturnType(&captures[1], nativeTypesPromoted, &captures[2], &captures[3], &captures[4], nativeTypesPromoted ? NULL : &captures[5], &captures[6]);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* fn (TypeSpecifierContext $specifyContext, bool $nativeTypesPromoted): SpecifiedTypes
	 * => $this->specifyTypes($nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain()
	 * : $beforeScope, $expr, $normalizedExpr, $varResult, $resolvedParametersAcceptor,
	 * $walkMethodReflection, $specifyContext, $argsResult) — captures: $this,
	 * $beforeScope, $expr, $normalizedExpr, $varResult, $resolvedParametersAcceptor,
	 * $walkMethodReflection, $argsResult */
	static void specifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 2)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\ExprHandler\\MethodCallHandler::{closure}(), %u passed and exactly 2 expected", argc);
			return;
		}
		zv::Val promotedScope;
		zval *scope = &captures[1];
		if (zend_is_true(&argv[1])) {
			promotedScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ(captures[1]));
			if (UNEXPECTED(promotedScope.isUndef())) return;
			scope = promotedScope.raw();
		}
		zv::Val specifiedTypes = MethodCallHandler(Z_OBJ(captures[0])).specifyTypes(scope, &captures[2], &captures[3], &captures[4], &captures[5], &captures[6], &argv[0], &captures[7]);
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}

	/* function (Type $type, TypeSpecifierContext $createContext, bool
	 * $nativeTypesPromoted) use ($expr, $varResult, $beforeScope): SpecifiedTypes
	 * — captures: $this, $expr, $varResult, $beforeScope */
	static void createTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 3)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\ExprHandler\\MethodCallHandler::{closure}(), %u passed and exactly 3 expected", argc);
			return;
		}
		MethodCallHandler handler(Z_OBJ(captures[0]));
		zval *expr = &captures[1];
		zval *varResult = &captures[2];
		zv::Val promotedScope;
		zval *s = &captures[3];
		if (zend_is_true(&argv[2])) {
			promotedScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ(captures[3]));
			if (UNEXPECTED(promotedScope.isUndef())) return;
			s = promotedScope.raw();
		}
		bool narrowable;
		if (UNEXPECTED(!handler.isMethodCallNarrowable(s, expr, varResult, narrowable))) return;
		// !narrowable: the call's value is not remembered, but a nullsafe
		// receiver chain still narrows not-null. narrowable: delegate with this
		// call's own stored result (looked up at ask time, never captured) so a
		// nullsafe receiver chain fans "not null" through the containsNullsafe
		// state - the FromResultState variant skips the createTypesCallback
		// consult that would re-enter this closure
		zv::Val resultStorage = pt_mutating_scope_get_current_expression_result_storage(Z_OBJ_P(s));
		if (UNEXPECTED(resultStorage.isUndef())) return;
		zv::Val storedResult = zv::Val::null();
		if (!resultStorage.isNull()) {
			storedResult = pt_expression_result_storage_find(resultStorage.raw(), expr);
			if (UNEXPECTED(storedResult.isUndef())) return;
		}
		zval *defaultNarrowingHelper = OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::defaultNarrowingHelper);
		zv::Val specifiedTypes = narrowable
			? pt_default_narrowing_helper_create_subject_types_from_result_state(defaultNarrowingHelper, s, expr, storedResult.raw(), &argv[0], &argv[1])
			: pt_default_narrowing_helper_create_nullsafe_receiver_only_types(defaultNarrowingHelper, s, expr, storedResult.raw(), &argv[0], &argv[1]);
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}

	/* static fn (Type $type) => TemplateTypeHelper::resolveTemplateTypes($type,
	 * $resolvedParametersAcceptor->getResolvedTemplateTypeMap(), ...,
	 * TemplateTypeVariance::createInvariant()) — captures:
	 * $resolvedParametersAcceptor */
	static void resolveAssertTypeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		resolveAssertType(captures, argc, argv, return_value, "PHPStan\\Analyser\\ExprHandler\\MethodCallHandler::{closure}");
	}
};

} // namespace phpstanturbo

using phpstanturbo::MethodCallHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_method_call_handler()
{
	pt_mch_method_call = zend_string_init_interned(PT_LC("methodCall"), 1);
	pt_mch_unknown_method = zend_string_init_interned(PT_LC("call to unknown method"), 1);

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\MethodCallHandler");
	ptdecl::MethodCallHandler::declareClass(cls);
	ptdecl::MethodCallHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *calledMethodProcessor, *methodCallReturnTypeHelper, *methodThrowPointHelper, *reflectionProvider, *expressionResultFactory, *typeSpecifier, *defaultNarrowingHelper, *storagePrimer, *earlyTerminatingHelper, *argumentsHandler;
		bool rememberPossiblyImpureFunctionValues;
		ZEND_PARSE_PARAMETERS_START(11, 11)
			Z_PARAM_OBJECT(calledMethodProcessor)
			Z_PARAM_OBJECT(methodCallReturnTypeHelper)
			Z_PARAM_OBJECT(methodThrowPointHelper)
			Z_PARAM_OBJECT(reflectionProvider)
			Z_PARAM_BOOL(rememberPossiblyImpureFunctionValues)
			Z_PARAM_OBJECT(expressionResultFactory)
			Z_PARAM_OBJECT(typeSpecifier)
			Z_PARAM_OBJECT(defaultNarrowingHelper)
			Z_PARAM_OBJECT(storagePrimer)
			Z_PARAM_OBJECT(earlyTerminatingHelper)
			Z_PARAM_OBJECT(argumentsHandler)
		ZEND_PARSE_PARAMETERS_END();
		zval *services[10] = {calledMethodProcessor, methodCallReturnTypeHelper, methodThrowPointHelper, reflectionProvider, expressionResultFactory, typeSpecifier, defaultNarrowingHelper, storagePrimer, earlyTerminatingHelper, argumentsHandler};
		MethodCallHandler(Z_OBJ_P(ZEND_THIS)).construct(services, rememberPossiblyImpureFunctionValues);
	});

	cls.method<&MethodCallHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(MethodCallHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.method(sigs::resolveReturnType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *reflectionScope, *expr, *varResult, *nameResult, *preResolvedAcceptor, *argsResult;
		bool nativeTypesPromoted;
		ZEND_PARSE_PARAMETERS_START(7, 7)
			Z_PARAM_OBJECT(reflectionScope)
			Z_PARAM_BOOL(nativeTypesPromoted)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(varResult)
			Z_PARAM_OBJECT_OR_NULL(nameResult)
			Z_PARAM_OBJECT_OR_NULL(preResolvedAcceptor)
			Z_PARAM_OBJECT_OR_NULL(argsResult)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(MethodCallHandler(Z_OBJ_P(ZEND_THIS)).resolveReturnType(reflectionScope, nativeTypesPromoted, expr, varResult, nameResult, preResolvedAcceptor, argsResult));
	});

	cls.method(sigs::specifyTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr, *normalizedExpr, *varResult, *resolvedParametersAcceptor, *walkMethodReflection, *context, *argsResult = NULL;
		ZEND_PARSE_PARAMETERS_START(7, 8)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(normalizedExpr)
			Z_PARAM_OBJECT(varResult)
			Z_PARAM_OBJECT_OR_NULL(resolvedParametersAcceptor)
			Z_PARAM_OBJECT_OR_NULL(walkMethodReflection)
			Z_PARAM_OBJECT(context)
			Z_PARAM_OPTIONAL
			Z_PARAM_OBJECT_OR_NULL(argsResult)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(MethodCallHandler(Z_OBJ_P(ZEND_THIS)).specifyTypes(scope, expr, normalizedExpr, varResult, resolvedParametersAcceptor, walkMethodReflection, context, argsResult));
	});

	cls.method(sigs::defaultMethodCallNarrowing, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr, *varResult, *context;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, scope, expr, varResult, context)) RETURN_THROWS();
		PT_RETURN_VAL(MethodCallHandler(Z_OBJ_P(ZEND_THIS)).defaultMethodCallNarrowing(scope, expr, varResult, context));
	});

	cls.method(sigs::isMethodCallNarrowable, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr, *varResult;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, scope, expr, varResult)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!MethodCallHandler(Z_OBJ_P(ZEND_THIS)).isMethodCallNarrowable(scope, expr, varResult, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.shadow(&pt_ce_method_call_handler);
	pt_expr_handler_entry_register(&pt_ce_method_call_handler, &MethodCallHandler::processExprEntry);
}

/* }}} */
