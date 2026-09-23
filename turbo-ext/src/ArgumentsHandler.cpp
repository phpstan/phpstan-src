/*
 * PHPStanTurbo\ArgumentsHandler — native implementation of
 * PHPStan\Analyser\ArgumentsHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's exact
 * arginfo (the nine #[AutowiredExtensions] collections and the
 * #[AutowiredParameter] bool), the state lives in the twin's property slots
 * (generated declarations). processArgs() and processDroppedArgs() — called
 * by the call handlers — are exported as pt_arguments_handler_process_args() /
 * pt_arguments_handler_process_dropped_args() (Engine.h conventions).
 *
 * The twin's locals that never leave the method (the processing order, the
 * gathered argument types by index, the deferred invalidations) are C++
 * state; the arrays handed to PHP collaborators or stored in the result
 * ($gatheredTypes, $argResults, $byRefArguments, the throw/impure points)
 * are PHP arrays built exactly as the twin builds them. The twin's closures
 * are native closures: the ArgsResult's `static fn () => new MixedType()` and
 * the four getters handed to ParametersAcceptorSelector::applyIntrinsicArgOverrides()
 * (\Closure-typed, so wrapped by pt_native_closure_to_closure()).
 *
 * MutatingScope, ExpressionResult, ExpressionResultStorage, ExpressionContext,
 * InternalThrowPoint, ImpurePoint, ArgsResult, SpecifiedTypes, TypeCombinator,
 * TypeUtils, NodeScopeResolver, ClosureProcessor and its results,
 * ClosureTypeResolver, ClosureParameterResolver, ClosureHandler, AssignHandler,
 * ParametersAcceptorSelector and the Type kernel are called through their direct
 * entries; the collaborators that stay PHP for now (TemplateArgumentObserver,
 * the reflections) through the cached sites in the block below, one helper
 * each. The extensions stay PHP for good and are called by name.
 */

#include "support.h"
#include "generated/ArgumentsHandler.h"
#include "generated/SimpleImpurePoint.h"

namespace slots = ptdecl::ArgumentsHandler::slot;
namespace sigs = ptdecl::ArgumentsHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "ParameterValues.h"
#include "AcceptorValues.h"

#include <new>

zend_class_entry *pt_ce_arguments_handler = nullptr;

/* the twin's closure names, for the engine's messages */
#define PT_AH_CLASS "PHPStan\\Analyser\\ArgumentsHandler"
#if PHP_VERSION_ID >= 80400
#define PT_AH_CLOSURE(method, line) PT_AH_CLASS "::{closure:" PT_AH_CLASS "::" method "():" line "}"
#else
/* PHP 8.3 names a closure by its namespace alone */
#define PT_AH_CLOSURE(method, line) PT_AH_CLASS "::PHPStan\\Analyser\\{closure}"
#endif

namespace {

/* persistent interned literals, created at registration */
zend_string *pt_ah_original_arg = nullptr;
zend_string *pt_ah_contains_closure = nullptr;
zend_string *pt_ah_start_token_pos = nullptr;
zend_string *pt_ah_start_line = nullptr;
zend_string *pt_ah_this = nullptr;
zend_string *pt_ah_construct = nullptr;

/* {{{ generic reads and calls */

/* $object->method(...$argv) through a cached site, with the Error PHP raises
 * for a member call on a non-object; UNDEF = pending exception */
zv::Val callOn(pt_method_site &site, zval *object, const char *lcname, size_t len, const char *name, uint32_t argc, zval *argv)
{
	if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", name, zend_zval_value_name(object));
		return zv::Val();
	}
	return pt_call_method_cached(site, Z_OBJ_P(object), lcname, len, argc, argv);
}

/* $object->method(...$argv) on a polymorphic PHP receiver (an extension) */
zv::Val callByName(zval *object, const char *lcname, size_t len, const char *name, uint32_t argc, zval *argv)
{
	if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", name, zend_zval_value_name(object));
		return zv::Val();
	}
	return pt_type_call(Z_OBJ_P(object), lcname, len, argc, argv);
}

/* the property read the engine does for $object->name when the fast path
 * does not apply: the warning and null for a non-object, the object's read
 * handler otherwise (its warning / Error); NULL = pending exception */
zend_never_inline zval *readPropertySlow(zval *object, const char *name, size_t len, zv::Val &hold)
{
	if (Z_TYPE_P(object) != IS_OBJECT) {
		zend_error(E_WARNING, "Attempt to read property \"%s\" on %s", name, zend_zval_value_name(object));
		if (UNEXPECTED(EG(exception))) return NULL;
		return &EG(uninitialized_zval);
	}
	zval rv;
	ZVAL_UNDEF(&rv);
	zval *value = zend_read_property(Z_OBJCE_P(object), Z_OBJ_P(object), name, len, 0, &rv);
	if (UNEXPECTED(EG(exception))) {
		zval_ptr_dtor(&rv);
		return NULL;
	}
	if (value == &rv) {
		hold = zv::Val::adopt(rv);
		return hold.raw();
	}
	ZVAL_DEREF(value);
	return value;
}

/* $object->name — the declared slot through the site (dereferenced), the
 * engine's read otherwise; borrowed (or kept alive in hold), NULL = pending
 * exception */
inline zval *readProperty(pt_property_site &site, zval *object, const char *name, size_t len, zv::Val &hold)
{
	if (EXPECTED(Z_TYPE_P(object) == IS_OBJECT)) {
		zval *slot = pt_property_cached(site, Z_OBJ_P(object), name, len);
		if (EXPECTED(slot != NULL)) {
			ZVAL_DEREF(slot);
			if (EXPECTED(Z_TYPE_P(slot) != IS_UNDEF)) return slot;
		}
	}
	return readPropertySlow(object, name, len, hold);
}

/* $value instanceof <class-map class>; false = pending exception */
inline bool isA(zval *value, int classIdx, bool &out)
{
	if (Z_TYPE_P(value) != IS_OBJECT) {
		out = false;
		return true;
	}
	zend_class_entry *ce = pt_class(classIdx);
	if (UNEXPECTED(ce == NULL)) return false;
	out = instanceof_function(Z_OBJCE_P(value), ce);
	return true;
}

/* an array a collaborator's declared `array` return type guarantees — the
 * TypeError PHP's array functions raise for anything else; false = pending
 * exception */
inline bool requireArray(zval *value, const char *what)
{
	if (EXPECTED(Z_TYPE_P(value) == IS_ARRAY)) return true;
	zend_type_error("%s must be of type array, %s given", what, zend_zval_value_name(value));
	return false;
}

/* a TrinaryLogic result's PT_TRI_* value; -1 = pending exception */
inline zend_long trinaryOf(zv::Val &result)
{
	if (UNEXPECTED(result.isUndef())) return -1;
	return pt_type_trinary_value(result.raw());
}

/* array_last($array) — the last element (borrowed) or null */
inline zval *arrayLast(HashTable *table)
{
	if (zend_hash_num_elements(table) == 0) return &EG(uninitialized_zval);
	uint32_t pos = table->nNumUsed;
	/* walk back over the holes to the last used slot */
	while (pos > 0) {
		pos--;
		zval *slot = HT_IS_PACKED(table) ? &table->arPacked[pos] : &table->arData[pos].val;
		if (Z_TYPE_P(slot) != IS_UNDEF) return slot;
	}
	return &EG(uninitialized_zval);
}

/* isset($array[$key]) for an integer key — the value (borrowed) or NULL */
inline zval *issetIndex(HashTable *table, zend_ulong key)
{
	zval *value = zend_hash_index_find(table, key);
	if (value == NULL) return NULL;
	ZVAL_DEREF(value);
	return Z_TYPE_P(value) == IS_NULL ? NULL : value;
}

/* $target = array_merge($target, $source): string keys overwrite, integer
 * keys append; an empty target takes over a list source as-is (the value
 * array_merge() returns is the same) */
bool arrayMerge(zv::Arr &target, zval *source)
{
	if (UNEXPECTED(Z_TYPE_P(source) != IS_ARRAY)) {
		zend_type_error("array_merge(): Argument #2 must be of type array, %s given", zend_zval_value_name(source));
		return false;
	}
	HashTable *src = Z_ARRVAL_P(source);
	if (zend_hash_num_elements(src) == 0) return true;
	if (zend_hash_num_elements(target.table()) == 0 && zend_array_is_list(src)) {
		target = zv::Arr::copyOfTable(src);
		return true;
	}
	target.separate();
	HashTable *dest = target.table();
	zend_string *key;
	zval *entry;
	ZEND_HASH_FOREACH_STR_KEY_VAL(src, key, entry) {
		zval *value = entry;
		if (Z_ISREF_P(value) && Z_REFCOUNT_P(value) == 1) {
			value = Z_REFVAL_P(value);
		}
		Z_TRY_ADDREF_P(value);
		if (key != NULL) {
			zend_hash_update(dest, key, value);
		} else {
			zend_hash_next_index_insert_new(dest, value);
		}
	} ZEND_HASH_FOREACH_END();
	return true;
}

/* }}} */

/* {{{ node reads */

pt_property_site pt_ah_arg_value_site;
pt_property_site pt_ah_arg_name_site;
pt_property_site pt_ah_arg_unpack_site;
pt_property_site pt_ah_identifier_name_site;
pt_property_site pt_ah_closure_static_site;
pt_property_site pt_ah_closure_uses_site;
pt_property_site pt_ah_closure_use_var_site;
pt_property_site pt_ah_variable_name_site;
pt_property_site pt_ah_array_dim_fetch_dim_site;
pt_property_site pt_ah_new_class_site;
pt_method_site pt_ah_identifier_to_string_site;

/* $arg->value / ->name / ->unpack */
inline zval *argValue(zval *arg, zv::Val &hold)
{
	return readProperty(pt_ah_arg_value_site, arg, PT_LC("value"), hold);
}

inline zval *argName(zval *arg, zv::Val &hold)
{
	return readProperty(pt_ah_arg_name_site, arg, PT_LC("name"), hold);
}

inline bool argUnpack(zval *arg, bool &out)
{
	zv::Val hold;
	zval *unpack = readProperty(pt_ah_arg_unpack_site, arg, PT_LC("unpack"), hold);
	if (UNEXPECTED(unpack == NULL)) return false;
	out = zend_is_true(unpack);
	return true;
}

/* $identifier->toString(): the $name slot of a php-parser Identifier (the
 * method returns it), the method otherwise; UNDEF = pending exception */
zv::Val identifierToString(zval *identifier)
{
	if (EXPECTED(Z_TYPE_P(identifier) == IS_OBJECT)) {
		zend_class_entry *identifierCe = pt_class(PT_CLASS_IDENTIFIER);
		if (UNEXPECTED(identifierCe == NULL)) return zv::Val();
		zend_class_entry *varLikeCe = Z_OBJCE_P(identifier) == identifierCe ? identifierCe : pt_class(PT_CLASS_VAR_LIKE_IDENTIFIER);
		if (EXPECTED(Z_OBJCE_P(identifier) == identifierCe || Z_OBJCE_P(identifier) == varLikeCe)) {
			zv::Val hold;
			zval *name = readProperty(pt_ah_identifier_name_site, identifier, PT_LC("name"), hold);
			if (UNEXPECTED(name == NULL)) return zv::Val();
			return zv::Val::copyOf(zv::Ref(name));
		}
	}
	return callOn(pt_ah_identifier_to_string_site, identifier, PT_LC("tostring"), "toString", 0, NULL);
}

/* $node->getAttribute($key) of a php-parser node (borrowed, NULL when absent) */
inline zval *nodeAttribute(zval *node, zend_string *key)
{
	zval *value = pt_node_attribute(Z_OBJ_P(node), key);
	if (value != NULL) {
		ZVAL_DEREF(value);
	}
	return value;
}

/* $arg->getAttribute(ArgumentsNormalizer::ORIGINAL_ARG_ATTRIBUTE) ?? $arg (borrowed) */
inline zval *originalArgOf(zval *arg)
{
	if (UNEXPECTED(Z_TYPE_P(arg) != IS_OBJECT)) return arg;
	zval *original = nodeAttribute(arg, pt_ah_original_arg);
	return original != NULL && Z_TYPE_P(original) != IS_NULL ? original : arg;
}

/* $value instanceof Expr\Closure / Expr\ArrowFunction; false = pending exception */
inline bool closureKind(zval *value, bool &isClosure, bool &isArrowFunction)
{
	isClosure = false;
	isArrowFunction = false;
	if (Z_TYPE_P(value) != IS_OBJECT) return true;
	zend_class_entry *closureCe = pt_class(PT_CLASS_CLOSURE_EXPR);
	if (UNEXPECTED(closureCe == NULL)) return false;
	if (instanceof_function(Z_OBJCE_P(value), closureCe)) {
		isClosure = true;
		return true;
	}
	zend_class_entry *arrowCe = pt_class(PT_CLASS_ARROW_FUNCTION);
	if (UNEXPECTED(arrowCe == NULL)) return false;
	isArrowFunction = instanceof_function(Z_OBJCE_P(value), arrowCe);
	return true;
}

/* $callLike->getArgs() (pt_call_like_args(): the `args` slot,
 * or the method's result) as an owned value; UNDEF = pending exception */
zv::Val callArgs(zval *callLike)
{
	zv::Val hold;
	zval *args = pt_call_like_args(Z_OBJ_P(callLike), hold);
	if (UNEXPECTED(args == NULL)) return zv::Val();
	if (!hold.isUndef()) return hold;
	return zv::Val::copyOf(zv::Ref(args));
}

/* }}} */

/* {{{ the PHP collaborators (one site each; switch to their direct entries
 * once they are ported) */

/* NodeScopeResolver (direct entries) */

inline zv::Val nsrLookForSetAllowedUndefinedExpressions(zval *nodeScopeResolver, zval *scope, zval *expr)
{
	return pt_node_scope_resolver_look_for_set_allowed_undefined_expressions(nodeScopeResolver, scope, expr);
}

inline zv::Val nsrLookForUnsetAllowedUndefinedExpressions(zval *nodeScopeResolver, zval *scope, zval *expr)
{
	return pt_node_scope_resolver_look_for_unset_allowed_undefined_expressions(nodeScopeResolver, scope, expr);
}

inline bool nsrCallNodeCallback(zval *nodeScopeResolver, zval *nodeCallback, zval *node, zval *scope, zval *storage)
{
	return pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, node, scope, storage);
}

inline bool nsrIsReturningStoredExpressionResults(zval *nodeScopeResolver, bool &out)
{
	return pt_node_scope_resolver_is_returning_stored_expression_results(nodeScopeResolver, out);
}

inline bool nsrIsConsumingStoredExpressionResults(zval *nodeScopeResolver, bool &out)
{
	return pt_node_scope_resolver_is_consuming_stored_expression_results(nodeScopeResolver, out);
}

inline bool nsrStoreExpressionResult(zval *nodeScopeResolver, zval *storage, zval *expr, zval *result)
{
	return pt_node_scope_resolver_store_expression_result(nodeScopeResolver, storage, expr, result);
}

inline bool nsrCallNodeCallbackWithExpression(zval *nodeScopeResolver, zval *nodeCallback, zval *expr, zval *scope, zval *storage, zval *context)
{
	return pt_node_scope_resolver_call_node_callback_with_expression(nodeScopeResolver, nodeCallback, expr, scope, storage, context);
}

inline zv::Val nsrProcessExprNode(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
{
	return pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
}

inline zv::Val nsrObservingTemplateArgumentFrame(zval *nodeScopeResolver, zval *scope)
{
	return pt_node_scope_resolver_observing_template_argument_frame(nodeScopeResolver, scope);
}

inline zv::Val nsrReadTypeOfMaybeStored(zval *nodeScopeResolver, zval *expr, zval *scope)
{
	return pt_node_scope_resolver_read_type_of_maybe_stored(nodeScopeResolver, expr, scope);
}

/* ClosureTypeResolver / ClosureParameterResolver (direct entries) */

/* $closureTypeResolver->getClosureType($scope, $expr, $shallow, $storage) */
inline zv::Val closureTypeResolverGetClosureType(zval *closureTypeResolver, zval *scope, zval *expr, bool shallow, zval *storage)
{
	return pt_closure_type_resolver_get_closure_type(closureTypeResolver, scope, expr, shallow, storage);
}

/* $closureTypeResolver->buildClosureTypeForClosure(...) with its ten arguments */
inline zv::Val closureTypeResolverBuildForClosure(zval *closureTypeResolver, zval *argv)
{
	return pt_closure_type_resolver_build_closure_type_for_closure(closureTypeResolver, &argv[0], &argv[1], &argv[2], &argv[3], &argv[4], &argv[5], &argv[6], &argv[7], zend_is_true(&argv[8]), &argv[9]);
}

/* $closureTypeResolver->buildClosureTypeForArrowFunction(...) with its eight arguments */
inline zv::Val closureTypeResolverBuildForArrowFunction(zval *closureTypeResolver, zval *argv)
{
	return pt_closure_type_resolver_build_closure_type_for_arrow_function(closureTypeResolver, &argv[0], &argv[1], &argv[2], &argv[3], &argv[4], &argv[5], zend_is_true(&argv[6]), &argv[7]);
}

/* $closureParameterResolver->resolveCallableTypeForScope($expr, $scope) */
inline zv::Val closureParameterResolverResolveCallableTypeForScope(zval *closureParameterResolver, zval *expr, zval *scope)
{
	return pt_closure_parameter_resolver_resolve_callable_type_for_scope(closureParameterResolver, expr, scope);
}

/* ClosureProcessor and its results (direct entries; the result getters are
 * the slot readers of AnalyserValues.h) */

/* $closureProcessor->processClosureNode($nodeScopeResolver, $stmt, $expr, $scope, $storage, $nodeCallback, $context, $passedToType, $nativePassedToType) */
inline zv::Val closureProcessorProcessClosureNode(zval *closureProcessor, zval *argv)
{
	return pt_closure_processor_process_closure_node(closureProcessor, &argv[0], &argv[1], &argv[2], &argv[3], &argv[4], &argv[5], &argv[6], &argv[7], &argv[8]);
}

/* $closureProcessor->processArrowFunctionNode($nodeScopeResolver, $stmt, $expr, $scope, $storage, $nodeCallback, $passedToType, $nativePassedToType, $context) */
inline zv::Val closureProcessorProcessArrowFunctionNode(zval *closureProcessor, zval *argv)
{
	return pt_closure_processor_process_arrow_function_node(closureProcessor, &argv[0], &argv[1], &argv[2], &argv[3], &argv[4], &argv[5], &argv[6], &argv[7], &argv[8]);
}

/* $closureProcessor->processImmediatelyCalledCallable($scope, $invalidateExpressions, $uses) */
inline zv::Val closureProcessorProcessImmediatelyCalledCallable(zval *closureProcessor, zval *scope, zval *invalidateExpressions, zval *uses)
{
	return pt_closure_processor_process_immediately_called_callable(closureProcessor, scope, invalidateExpressions, uses);
}

/* $closureResult->applyByRefUseScope($scope) */
inline zv::Val processClosureResultApplyByRefUseScope(zval *closureResult, zval *scope)
{
	return pt_process_closure_result_apply_by_ref_use_scope(closureResult, scope);
}

/* the getters of ProcessClosureResult / ProcessArrowFunctionResult (borrowed,
 * or kept alive in hold); NULL = pending exception */
#define PT_AH_RESULT_GETTER(fn, reader) \
	inline zval *fn(zval *result, zv::Val &hold) \
	{ \
		return reader(result, hold); \
	}
PT_AH_RESULT_GETTER(closureResultScope, pt_process_closure_result_scope)
PT_AH_RESULT_GETTER(closureResultThrowPoints, pt_process_closure_result_throw_points)
PT_AH_RESULT_GETTER(closureResultImpurePoints, pt_process_closure_result_impure_points)
PT_AH_RESULT_GETTER(closureResultInvalidateExpressions, pt_process_closure_result_invalidate_expressions)
PT_AH_RESULT_GETTER(closureResultGatheredReturnStatements, pt_process_closure_result_gathered_return_statements)
PT_AH_RESULT_GETTER(closureResultGatheredYieldStatements, pt_process_closure_result_gathered_yield_statements)
PT_AH_RESULT_GETTER(closureResultExecutionEnds, pt_process_closure_result_execution_ends)
PT_AH_RESULT_GETTER(closureResultClosureTypeImpurePoints, pt_process_closure_result_closure_type_impure_points)
PT_AH_RESULT_GETTER(arrowResultExpressionResult, pt_process_arrow_function_result_expression_result)
PT_AH_RESULT_GETTER(arrowResultArrowFunctionScope, pt_process_arrow_function_result_arrow_function_scope)
PT_AH_RESULT_GETTER(arrowResultClosureTypeThrowPoints, pt_process_arrow_function_result_closure_type_throw_points)
PT_AH_RESULT_GETTER(arrowResultClosureTypeImpurePoints, pt_process_arrow_function_result_closure_type_impure_points)
PT_AH_RESULT_GETTER(arrowResultInvalidateExpressions, pt_process_arrow_function_result_invalidate_expressions)
#undef PT_AH_RESULT_GETTER

/* ClosureHandler::getVariableFlow($expr) */
inline zv::Val closureHandlerGetVariableFlow(zval *expr)
{
	return pt_closure_handler_get_variable_flow(expr);
}

/* $assignHandler->processVirtualAssign($nodeScopeResolver, $scope, $storage, $stmt, $var, $assignedExpr, $nodeCallback) */
zv::Val assignHandlerProcessVirtualAssign(zval *assignHandler, zval *nodeScopeResolver, zval *scope, zval *storage, zval *stmt, zval *var, zval *assignedExpr, zval *nodeCallback)
{
	if (UNEXPECTED(Z_TYPE_P(assignHandler) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function processVirtualAssign() on %s", zend_zval_value_name(assignHandler));
		return zv::Val();
	}
	return pt_assign_handler_process_virtual_assign(assignHandler, nodeScopeResolver, scope, storage, stmt, var, assignedExpr, nodeCallback, NULL);
}

/* TemplateArgumentObserver */

/* $templateArgumentObserver->collectArgument($parameterType, $argumentType, $isPure) */
zv::Val templateArgumentObserverCollectArgument(zval *observer, zval *parameterType, zval *argumentType, bool isPure)
{
	return pt_template_argument_observer_collect_argument(observer, parameterType, argumentType, isPure);
}

/* $templateArgumentObserver->collectCall($site, $acceptor, $argumentTypes, $classTemplates) */
zv::Val templateArgumentObserverCollectCall(zval *observer, zval *site, zval *acceptor, zval *argumentTypes, zval *classTemplates)
{
	return pt_template_argument_observer_collect_call(observer, site, acceptor, argumentTypes, classTemplates);
}

/* ParametersAcceptorSelector (direct entries) */

/* ParametersAcceptorSelector::hasAcceptorTemplateOrLateResolvableType($acceptor); false = pending exception */
inline bool selectorHasAcceptorTemplateOrLateResolvableType(zval *acceptor, bool &out)
{
	return pt_parameters_acceptor_selector_has_acceptor_template_or_late_resolvable_type(acceptor, out);
}

/* ParametersAcceptorSelector::hasAcceptorTemplateOrLateResolvableParameterType($acceptor); false = pending exception */
inline bool selectorHasAcceptorTemplateOrLateResolvableParameterType(zval *acceptor, bool &out)
{
	return pt_parameters_acceptor_selector_has_acceptor_template_or_late_resolvable_parameter_type(acceptor, out);
}

/* ParametersAcceptorSelector::selectFromTypes($types, $parametersAcceptors, $unpack) */
inline zv::Val selectorSelectFromTypes(zval *types, zval *parametersAcceptors, bool unpack)
{
	return pt_parameters_acceptor_selector_select_from_types(types, parametersAcceptors, unpack);
}

/* the reflections: parameters, their PassedByReference, the acceptors and
 * the callee */
pt_method_site pt_ah_callee_is_builtin_site;

/* the parameter getters: pt_parameter_reflection_call() (ParameterValues.h —
 * the value classes' slots, the native parameter reflections' bodies, a
 * cached site per member otherwise) */

/* $parameter->getName() */
zv::Val parameterGetName(zval *parameter)
{
	return pt_parameter_reflection_call(parameter, PT_PR_GET_NAME);
}

/* the PT_PASSED_BY_REFERENCE_* mode of $parameter->passedByReference(); -1 =
 * pending exception */
zend_long parameterPassedByReferenceMode(zval *parameter)
{
	zval *passedByReference = pt_dummy_parameter_slot(parameter, ptdecl::DummyParameter::slot::passedByReference);
	if (EXPECTED(passedByReference != NULL)) return pt_passed_by_reference_mode(passedByReference);
	zv::Val returned = pt_parameter_reflection_call(parameter, PT_PR_PASSED_BY_REFERENCE);
	if (UNEXPECTED(returned.isUndef())) return -1;
	return pt_passed_by_reference_mode(returned.raw());
}

/* $parameter->passedByReference()->createsNewVariable(); false = pending exception */
bool parameterCreatesNewVariable(zval *parameter, bool &out)
{
	zend_long mode = parameterPassedByReferenceMode(parameter);
	if (UNEXPECTED(mode < 0)) return false;
	out = mode == PT_PASSED_BY_REFERENCE_CREATES_NEW_VARIABLE;
	return true;
}

/* $parameter->passedByReference()->no(); false = pending exception */
bool parameterPassedByReferenceNo(zval *parameter, bool &out)
{
	zend_long mode = parameterPassedByReferenceMode(parameter);
	if (UNEXPECTED(mode < 0)) return false;
	out = mode == PT_PASSED_BY_REFERENCE_NO;
	return true;
}

/* $parameter->getType() */
zv::Val parameterGetType(zval *parameter)
{
	return pt_parameter_reflection_call(parameter, PT_PR_GET_TYPE);
}

/* $parameter->getNativeType() */
zv::Val parameterGetNativeType(zval *parameter)
{
	return pt_parameter_reflection_call(parameter, PT_PR_GET_NATIVE_TYPE);
}

/* $parameter->isImmediatelyInvokedCallable() (the PT_TRI_* value); -1 = pending exception */
zend_long parameterIsImmediatelyInvokedCallable(zval *parameter)
{
	zval *immediately = pt_extended_dummy_parameter_slot(parameter, ptdecl::ExtendedDummyParameter::slot::immediatelyInvokedCallable);
	if (EXPECTED(immediately != NULL)) return pt_type_trinary_value(immediately);
	zv::Val result = pt_parameter_reflection_call(parameter, PT_PR_IS_IMMEDIATELY_INVOKED_CALLABLE);
	return trinaryOf(result);
}

/* $parameter->getOutType() */
zv::Val parameterGetOutType(zval *parameter)
{
	return pt_parameter_reflection_call(parameter, PT_PR_GET_OUT_TYPE);
}

/* $parameter->getClosureThisType() */
zv::Val parameterGetClosureThisType(zval *parameter)
{
	return pt_parameter_reflection_call(parameter, PT_PR_GET_CLOSURE_THIS_TYPE);
}

/* the acceptor getters: pt_parameters_acceptor_call() (AcceptorValues.h) */

/* $acceptor->getParameters() */
zv::Val acceptorGetParameters(zval *acceptor)
{
	return pt_parameters_acceptor_call(acceptor, PT_PA_GET_PARAMETERS);
}

/* $acceptor->isVariadic(); false = pending exception */
bool acceptorIsVariadic(zval *acceptor, bool &out)
{
	return pt_parameters_acceptor_bool(acceptor, PT_PA_IS_VARIADIC, out);
}

/* $acceptor->getReturnType() */
zv::Val acceptorGetReturnType(zval *acceptor)
{
	return pt_parameters_acceptor_call(acceptor, PT_PA_GET_RETURN_TYPE);
}

/* $acceptor->getOriginalParametersAcceptor() / ->getResolvedTemplateTypeMap() / ->getCallSiteVarianceMap() */
zv::Val acceptorGetOriginalParametersAcceptor(zval *acceptor)
{
	return pt_parameters_acceptor_call(acceptor, PT_PA_GET_ORIGINAL_PARAMETERS_ACCEPTOR);
}

zv::Val acceptorGetResolvedTemplateTypeMap(zval *acceptor)
{
	return pt_parameters_acceptor_call(acceptor, PT_PA_GET_RESOLVED_TEMPLATE_TYPE_MAP);
}

zv::Val acceptorGetCallSiteVarianceMap(zval *acceptor)
{
	return pt_parameters_acceptor_call(acceptor, PT_PA_GET_CALL_SITE_VARIANCE_MAP);
}

/* $functionReflection->isBuiltin() / $classReflection->isBuiltin() (truthiness); false = pending exception */
bool calleeIsBuiltin(zval *reflection, bool &out)
{
	zv::Val result = callOn(pt_ah_callee_is_builtin_site, reflection, PT_LC("isbuiltin"), "isBuiltin", 0, NULL);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

/* $calleeReflection-><member>() through the method reflection entry (the
 * native body of a ResolvedMethodReflection / ChangedTypeMethodReflection,
 * a cached site otherwise), with the Error of a member call on a non-object */
zv::Val calleeCall(zval *reflection, pt_method_reflection_member member, const char *name)
{
	if (UNEXPECTED(Z_TYPE_P(reflection) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", name, zend_zval_value_name(reflection));
		return zv::Val();
	}
	return pt_extended_method_reflection_call(reflection, member);
}

/* $methodReflection->getDeclaringClass() */
zv::Val calleeGetDeclaringClass(zval *reflection)
{
	return calleeCall(reflection, PT_MR_GET_DECLARING_CLASS, "getDeclaringClass");
}

/* $methodReflection->getDeclaringClass()->isBuiltin(); false = pending exception */
bool calleeDeclaringClassIsBuiltin(zval *reflection, bool &out)
{
	zv::Val declaringClass = calleeGetDeclaringClass(reflection);
	if (UNEXPECTED(declaringClass.isUndef())) return false;
	if (UNEXPECTED(Z_TYPE_P(declaringClass.raw()) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function isBuiltin() on %s", zend_zval_value_name(declaringClass.raw()));
		return false;
	}
	return pt_class_reflection_is_builtin(Z_OBJ_P(declaringClass.raw()), out);
}

/* $calleeReflection->hasSideEffects() / ->isPure() (the PT_TRI_* values); -1 = pending exception */
zend_long calleeHasSideEffects(zval *reflection)
{
	zv::Val result = calleeCall(reflection, PT_MR_HAS_SIDE_EFFECTS, "hasSideEffects");
	return trinaryOf(result);
}

zend_long calleeIsPure(zval *reflection)
{
	zv::Val result = calleeCall(reflection, PT_MR_IS_PURE, "isPure");
	return trinaryOf(result);
}

/* $methodReflection->getVariants() / ->getNamedArgumentsVariants() */
zv::Val calleeGetVariants(zval *reflection)
{
	return calleeCall(reflection, PT_MR_GET_VARIANTS, "getVariants");
}

zv::Val calleeGetNamedArgumentsVariants(zval *reflection)
{
	return calleeCall(reflection, PT_MR_GET_NAMED_ARGUMENTS_VARIANTS, "getNamedArgumentsVariants");
}

/* the callables of an argument's type: CallableParametersAcceptor and the
 * throw / impure points it carries (PHP classes, polymorphic) */
pt_method_site pt_ah_simple_throw_point_is_explicit_site;
pt_method_site pt_ah_simple_throw_point_get_type_site;
pt_method_site pt_ah_simple_throw_point_can_contain_any_throwable_site;
pt_property_site pt_ah_invalidate_expr_node_expr_site;

/* $impurePoint->getIdentifier() / ->getDescription() / ->isCertain(): the
 * slot of the native SimpleImpurePoint (its getters return them), the
 * method by name otherwise */
zv::Val simpleImpurePointRead(zval *impurePoint, uint32_t slotIndex, const char *lcname, size_t len, const char *name)
{
	if (EXPECTED(Z_TYPE_P(impurePoint) == IS_OBJECT && Z_OBJCE_P(impurePoint) == pt_ce_simple_impure_point)) {
		zval *value = OBJ_PROP_NUM(Z_OBJ_P(impurePoint), slotIndex);
		if (EXPECTED(Z_TYPE_P(value) != IS_UNDEF)) return zv::Val::copyOf(zv::Ref(value));
	}
	return callByName(impurePoint, lcname, len, name, 0, NULL);
}

/* new InvalidateExprNode($expr) / new NoopNodeCallback() */
zv::Val newInvalidateExprNode(zval *expr)
{
	return pt_type_new(PT_CLASS_INVALIDATE_EXPR_NODE, 1, expr);
}

/* new NativeTypeExpr($phpdocType, $nativeType) */
zv::Val newNativeTypeExpr(zval *phpdocType, zval *nativeType)
{
	zv::Args argv{phpdocType, nativeType};
	return pt_type_new(PT_CLASS_NATIVE_TYPE_EXPR, 2, argv);
}

zv::Val newNoopNodeCallback()
{
	return pt_type_new(PT_CLASS_NOOP_NODE_CALLBACK, 0, NULL);
}

/* $invalidateExprNode->getExpr() — the final node's promoted $expr (borrowed,
 * or kept alive in hold); NULL = pending exception */
inline zval *invalidateExprNodeExpr(zval *node, zv::Val &hold)
{
	return readProperty(pt_ah_invalidate_expr_node_expr_site, node, PT_LC("expr"), hold);
}

/* }}} */

/* $array[$key] of an array read the twin spells without a guard: the value
 * (borrowed), or null after the engine's "Undefined array key" warning;
 * NULL = pending exception */
zval *readIndex(HashTable *table, zend_ulong key)
{
	zval *value = zend_hash_index_find(table, key);
	if (EXPECTED(value != NULL)) {
		ZVAL_DEREF(value);
		return value;
	}
	zend_error(E_WARNING, "Undefined array key " ZEND_ULONG_FMT, key);
	if (UNEXPECTED(EG(exception))) return NULL;
	return &EG(uninitialized_zval);
}

/* $array[$key] = $value for an integer key or a string key (symtable
 * semantics, as a PHP array write converts numeric strings) */
void setKey(zv::Arr &array, zend_string *stringKey, zend_ulong intKey, zval *value)
{
	array.separate();
	Z_TRY_ADDREF_P(value);
	if (stringKey != NULL) {
		zend_symtable_update(array.table(), stringKey, value);
	} else {
		zend_hash_index_update(array.table(), intKey, value);
	}
}

/* isset($array[$key]) — the value or NULL */
zval *issetKey(zv::Arr &array, zend_string *stringKey, zend_ulong intKey)
{
	zval *value = stringKey != NULL ? zend_symtable_find(array.table(), stringKey) : zend_hash_index_find(array.table(), intKey);
	if (value == NULL) return NULL;
	ZVAL_DEREF(value);
	return Z_TYPE_P(value) == IS_NULL ? NULL : value;
}

/* the ArgumentCountError of a closure called with too few arguments */
void tooFewArguments(uint32_t argc, uint32_t expected)
{
	zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\ArgumentsHandler::{closure}(), %u passed and exactly %u expected", argc, expected);
}

/* NodeFinder's filter `$node instanceof Expr\Closure || $node instanceof
 * Expr\ArrowFunction` for pt_find_first_recursive() */
struct ClosureFindCtx : pt_find_ctx
{
	zend_class_entry *closureCe;
	zend_class_entry *arrowFunctionCe;
};

bool closureMatcher(zend_object *node, void *ctx)
{
	ClosureFindCtx *find = static_cast<ClosureFindCtx *>(static_cast<pt_find_ctx *>(ctx));
	return instanceof_function(node->ce, find->closureCe) || instanceof_function(node->ce, find->arrowFunctionCe);
}

/* the filter `$expr instanceof Variable && $expr->name === 'this'` */
bool thisVariableMatcher(zend_object *node, void *ctx)
{
	pt_find_ctx *find = static_cast<pt_find_ctx *>(ctx);
	if (!instanceof_function(node->ce, find->target_ce)) return false;
	zval *name = pt_property_cached(pt_ah_variable_name_site, node, PT_LC("name"));
	if (name == NULL) return false;
	ZVAL_DEREF(name);
	return Z_TYPE_P(name) == IS_STRING && zend_string_equals(Z_STR_P(name), pt_ah_this);
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ArgumentsHandler; UNDEF = pending exception. */
class ArgumentsHandler
{
public:
	explicit ArgumentsHandler(zend_object *self) : self(self) {}

	/* Mirrors __construct(): the promoted properties, in the twin's order */
	static void construct(zend_object *object, zval *argv, bool implicitThrows)
	{
		for (uint32_t i = 0; i < slots::implicitThrows; i++) {
			writeSlot(object, i, zv::Val::copyOf(zv::Ref(&argv[i])));
		}
		writeSlot(object, slots::implicitThrows, zv::Val::boolean(implicitThrows));
		for (uint32_t i = slots::assignHandler; i <= slots::initializerExprTypeResolver; i++) {
			writeSlot(object, i, zv::Val::copyOf(zv::Ref(&argv[i])));
		}
	}

private:
	zend_object *self;

	zval *slot(uint32_t index) const { return OBJ_PROP_NUM(self, index); }

	/* the `$getType = function (Expr $inner) use (&$getType, $nodeScopeResolver,
	 * $scope, $initializerContext): Type` of gatherArrayArgTypeSkeleton() —
	 * InitializerExprTypeResolver calls it synchronously, over this frame */
	struct SkeletonFrame
	{
		zend_object *self;
		zval *nodeScopeResolver;
		zval *scope;
		zval *initializerContext;
	};

	static zv::Val skeletonType(void *data, zval *inner)
	{
		SkeletonFrame *frame = static_cast<SkeletonFrame *>(data);
		ZVAL_DEREF(inner);
		if (UNEXPECTED(Z_TYPE_P(inner) != IS_OBJECT)) {
			zend_type_error(PT_AH_CLOSURE("gatherArrayArgTypeSkeleton", "977") "(): Argument #1 ($inner) must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(inner));
			return zv::Val();
		}
		ArgumentsHandler handler(frame->self);
		bool isClosure, isArrowFunction;
		if (UNEXPECTED(!closureKind(inner, isClosure, isArrowFunction))) return zv::Val();
		if (isClosure || isArrowFunction) {
			return pt_closure_type_resolver_get_declared_closure_type(handler.slot(slots::closureTypeResolver), frame->scope, inner);
		}
		zend_class_entry *arrayExpr = pt_class(PT_CLASS_ARRAY_EXPR);
		if (UNEXPECTED(arrayExpr == NULL)) return zv::Val();
		if (instanceof_function(Z_OBJCE_P(inner), arrayExpr)) {
			pt_ietr_get_type getTypeCallback{&skeletonType, frame, &skeletonTypeCallable};
			return pt_initializer_expr_type_resolver_get_array_type(handler.slot(slots::initializerExprTypeResolver), inner, getTypeCallback);
		}
		zv::Val stateType = pt_node_scope_resolver_find_scope_state_type(frame->nodeScopeResolver, inner, frame->scope);
		if (UNEXPECTED(stateType.isUndef())) return zv::Val();
		if (!stateType.isNull()) return stateType;
		return pt_initializer_expr_type_resolver_get_type(handler.slot(slots::initializerExprTypeResolver), inner, frame->initializerContext);
	}

	/* the callback as a PHP callable that outlives the call: the closure
	 * over copies of what the frame points at */
	static zv::Val skeletonTypeCallable(void *data)
	{
		SkeletonFrame *frame = static_cast<SkeletonFrame *>(data);
		zval self;
		ZVAL_OBJ(&self, frame->self);
		return pt_native_closure(&skeletonTypeBody, &self, frame->nodeScopeResolver, frame->scope, frame->initializerContext);
	}

	/* the same closure called from PHP — captures: $this, $nodeScopeResolver,
	 * $scope, $initializerContext */
	static void skeletonTypeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 1)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function " PT_AH_CLOSURE("gatherArrayArgTypeSkeleton", "977") "(), %u passed and exactly 1 expected", argc);
			return;
		}
		SkeletonFrame frame{Z_OBJ(captures[0]), &captures[1], &captures[2], &captures[3]};
		zv::Val type = skeletonType(&frame, &argv[0]);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	static void writeSlot(zend_object *object, uint32_t index, zv::Val value)
	{
		zv::ObjRef(object).propAtWrite(index, std::move(value));
		Z_PROP_FLAG_P(OBJ_PROP_NUM(object, index)) = 0; /* no longer IS_PROP_UNINIT */
	}

	/* Mirrors addGatheredArgType(); false = pending exception */
	static bool addGatheredArgType(zv::Arr &types, bool &unpack, bool &hasName, zval *originalArg, zend_ulong i, zval *type)
	{
		zv::Val nameHold;
		zval *name = argName(originalArg, nameHold);
		if (UNEXPECTED(name == NULL)) return false;
		zv::Val index;
		if (Z_TYPE_P(name) != IS_NULL) {
			index = identifierToString(name);
			if (UNEXPECTED(index.isUndef())) return false;
			if (UNEXPECTED(!index.ref().isString())) {
				zend_type_error("Illegal offset type");
				return false;
			}
			hasName = true;
		}
		zend_string *indexKey = index.isUndef() ? NULL : Z_STR_P(index.raw());

		bool isUnpack = false;
		if (UNEXPECTED(!argUnpack(originalArg, isUnpack))) return false;
		if (!isUnpack) {
			setKey(types, indexKey, i, type);
			return true;
		}

		unpack = true;
		zv::Val constantArrays = pt_type_op(Z_OBJ_P(type), PT_OP_GET_CONSTANT_ARRAYS, 0, NULL);
		if (UNEXPECTED(constantArrays.isUndef())) return false;
		if (UNEXPECTED(!requireArray(constantArrays.raw(), "count(): Argument #1 ($value)"))) return false;
		if (zend_hash_num_elements(Z_ARRVAL_P(constantArrays.raw())) == 0) {
			zv::Val iterableValueType = pt_type_op(Z_OBJ_P(type), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
			if (UNEXPECTED(iterableValueType.isUndef())) return false;
			setKey(types, indexKey, i, iterableValueType.raw());
			return true;
		}

		for (zv::ArrayEntry entry : zv::ArrRef(constantArrays.raw())) {
			zval *constantArray = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(constantArray) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function getValueTypes() on %s", zend_zval_value_name(constantArray));
				return false;
			}
			zv::Val values = pt_type_op(Z_OBJ_P(constantArray), PT_OP_GET_VALUE_TYPES, 0, NULL);
			if (UNEXPECTED(values.isUndef())) return false;
			zv::Val keyTypes = pt_type_op(Z_OBJ_P(constantArray), PT_OP_GET_KEY_TYPES, 0, NULL);
			if (UNEXPECTED(keyTypes.isUndef())) return false;
			if (UNEXPECTED(!requireArray(values.raw(), "getValueTypes()") || !requireArray(keyTypes.raw(), "foreach() argument"))) return false;
			for (zv::ArrayEntry keyEntry : zv::ArrRef(keyTypes.raw())) {
				zend_ulong j = keyEntry.indexKey();
				zval *valueType = readIndex(Z_ARRVAL_P(values.raw()), j);
				if (UNEXPECTED(valueType == NULL)) return false;
				zval *keyType = keyEntry.value().deref().raw();
				if (UNEXPECTED(Z_TYPE_P(keyType) != IS_OBJECT)) {
					zend_throw_error(NULL, "Call to a member function getValue() on %s", zend_zval_value_name(keyType));
					return false;
				}
				zv::Val valueIndex = pt_type_op(Z_OBJ_P(keyType), PT_OP_GET_VALUE, 0, NULL);
				if (UNEXPECTED(valueIndex.isUndef())) return false;
				zend_string *valueStringKey = NULL;
				zend_ulong valueIntKey = 0;
				if (valueIndex.ref().isString()) {
					hasName = true;
					valueStringKey = Z_STR_P(valueIndex.raw());
				} else {
					valueIntKey = i + j;
				}
				zval *existing = issetKey(types, valueStringKey, valueIntKey);
				if (existing != NULL) {
					zv::Args unionArgs{existing, valueType};
					zv::Val unionType = pt_type_combinator_union(2, unionArgs);
					if (UNEXPECTED(unionType.isUndef())) return false;
					setKey(types, valueStringKey, valueIntKey, unionType.raw());
				} else {
					setKey(types, valueStringKey, valueIntKey, valueType);
				}
			}
		}
		return true;
	}

	/* Mirrors argConsumesResolvedParameterType(); false = pending exception */
	static bool argConsumesResolvedParameterType(zval *value, bool &out)
	{
		bool isClosure, isArrowFunction;
		if (UNEXPECTED(!closureKind(value, isClosure, isArrowFunction))) return false;
		if (isClosure || isArrowFunction) {
			out = true;
			return true;
		}

		// cached on the node - args are re-processed across convergence passes
		zval *cached = nodeAttribute(value, pt_ah_contains_closure);
		if (cached != NULL && Z_TYPE_P(cached) != IS_NULL) {
			if (UNEXPECTED(Z_TYPE_P(cached) != IS_TRUE && Z_TYPE_P(cached) != IS_FALSE)) {
				zend_type_error("PHPStan\\Analyser\\ArgumentsHandler::argConsumesResolvedParameterType(): Return value must be of type bool, %s returned", zend_zval_value_name(cached));
				return false;
			}
			out = Z_TYPE_P(cached) == IS_TRUE;
			return true;
		}

		ClosureFindCtx ctx{};
		ctx.closureCe = pt_class(PT_CLASS_CLOSURE_EXPR);
		ctx.arrowFunctionCe = pt_class(PT_CLASS_ARROW_FUNCTION);
		if (UNEXPECTED(ctx.closureCe == NULL || ctx.arrowFunctionCe == NULL)) return false;
		zend_object *found = pt_find_first_recursive(Z_OBJ_P(value), closureMatcher, static_cast<pt_find_ctx *>(&ctx));
		if (UNEXPECTED(ctx.failed || EG(exception))) return false;
		out = found != NULL;
		zval contains;
		ZVAL_BOOL(&contains, out);
		pt_node_set_attribute(Z_OBJ_P(value), pt_ah_contains_closure, &contains);
		return true;
	}

	/* Mirrors gatherArrayArgTypeSkeleton() (private): a structural stand-in for
	 * an array literal argument that holds closures, built without walking
	 * anything - nested array literals recurse, a closure / arrow function
	 * contributes its declared signature, every other key/value is priced by
	 * the scope state it is tracked as, falling back to the constant-expression
	 * resolver. UNDEF = pending exception */
	zv::Val gatherArrayArgTypeSkeleton(zval *nodeScopeResolver, zval *expr, zval *scope) const
	{
		zv::Val initializerContext = pt_initializer_expr_context_from_scope(scope);
		if (UNEXPECTED(initializerContext.isUndef())) return zv::Val();
		SkeletonFrame frame{self, nodeScopeResolver, scope, initializerContext.raw()};
		pt_ietr_get_type getTypeCallback{&skeletonType, &frame, &skeletonTypeCallable};
		return pt_initializer_expr_type_resolver_get_array_type(slot(slots::initializerExprTypeResolver), expr, getTypeCallback);
	}

	/* Mirrors gatherClosureArgType() */
	zv::Val gatherClosureArgType(HashTable *parametersAcceptors, zend_ulong i, zval *closureExpr, zval *scope) const
	{
		zv::Val rawParametersHold;
		zval *rawParameter = NULL;
		if (zend_hash_num_elements(parametersAcceptors) == 1) {
			zval *acceptor = readIndex(parametersAcceptors, 0);
			if (UNEXPECTED(acceptor == NULL)) return zv::Val();
			rawParametersHold = acceptorGetParameters(acceptor);
			if (UNEXPECTED(rawParametersHold.isUndef())) return zv::Val();
			if (UNEXPECTED(!requireArray(rawParametersHold.raw(), "getParameters()"))) return zv::Val();
			HashTable *rawParameters = Z_ARRVAL_P(rawParametersHold.raw());
			rawParameter = issetIndex(rawParameters, i);
			if (rawParameter == NULL && zend_hash_num_elements(rawParameters) > 0) {
				bool isVariadic = false;
				acceptor = readIndex(parametersAcceptors, 0);
				if (UNEXPECTED(acceptor == NULL || !acceptorIsVariadic(acceptor, isVariadic))) return zv::Val();
				if (isVariadic) {
					rawParameter = arrayLast(rawParameters);
					ZVAL_DEREF(rawParameter);
				}
			}
		}

		zv::Val pushedScope;
		if (rawParameter != NULL && Z_TYPE_P(rawParameter) != IS_NULL) {
			zval null;
			ZVAL_NULL(&null);
			pushedScope = pt_mutating_scope_push_in_function_call(Z_OBJ_P(scope), &null, rawParameter, false);
			if (UNEXPECTED(pushedScope.isUndef())) return zv::Val();
			scope = pushedScope.raw();
		}

		return closureParameterResolverResolveCallableTypeForScope(slot(slots::closureParameterResolver), closureExpr, scope);
	}

	/* Mirrors selectArgsAcceptor() */
	static zv::Val selectArgsAcceptor(zval *types, zval *parametersAcceptors, zval *namedArgumentsVariants, bool hasName, bool unpack)
	{
		return hasName && Z_TYPE_P(namedArgumentsVariants) != IS_NULL
			? selectorSelectFromTypes(types, namedArgumentsVariants, unpack)
			: selectorSelectFromTypes(types, parametersAcceptors, unpack);
	}

	/* static fn (Expr $e): Type => $nodeScopeResolver->readTypeOfMaybeStored($e, $scope)
	 * — captures: $nodeScopeResolver, $scope */
	static void typeGetterBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 1)) {
			tooFewArguments(argc, 1);
			return;
		}
		zv::Val type = nsrReadTypeOfMaybeStored(&captures[0], &argv[0], &captures[1]);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* static fn (Expr $e): Type => $nodeScopeResolver->readTypeOfMaybeStored($e,
	 * $scope->doNotTreatPhpDocTypesAsCertain()) — captures: $nodeScopeResolver, $scope */
	static void nativeTypeGetterBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 1)) {
			tooFewArguments(argc, 1);
			return;
		}
		zv::Val nativeScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ(captures[1]));
		if (UNEXPECTED(nativeScope.isUndef())) return;
		zv::Val type = nsrReadTypeOfMaybeStored(&captures[0], &argv[0], nativeScope.raw());
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* static fn (Type $t): Type => $scope->getIterableValueType($t) — captures: $scope */
	static void iterableValueTypeGetterBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 1)) {
			tooFewArguments(argc, 1);
			return;
		}
		zv::Val type = pt_mutating_scope_get_iterable_value_type(Z_OBJ(captures[0]), &argv[0]);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* static fn (Type $t): Type => $scope->getIterableKeyType($t) — captures: $scope */
	static void iterableKeyTypeGetterBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 1)) {
			tooFewArguments(argc, 1);
			return;
		}
		zv::Val type = pt_mutating_scope_get_iterable_key_type(Z_OBJ(captures[0]), &argv[0]);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* Mirrors selectArgsMetadataAcceptor() */
	static zv::Val selectArgsMetadataAcceptor(zval *nodeScopeResolver, zval *args, zval *gatheredTypes, zval *parametersAcceptors, zval *namedArgumentsVariants, bool hasName, bool unpack, zval *scope)
	{
		/* the native entry takes the holders as they are (the public method's
		 * \Closure parameter types do not apply to it) */
		zv::Val typeGetter = pt_native_closure(&typeGetterBody, nodeScopeResolver, scope);
		zv::Val nativeTypeGetter = pt_native_closure(&nativeTypeGetterBody, nodeScopeResolver, scope);
		zv::Val iterableValueTypeGetter = pt_native_closure(&iterableValueTypeGetterBody, scope);
		zv::Val iterableKeyTypeGetter = pt_native_closure(&iterableKeyTypeGetterBody, scope);
		zv::Val overridden = pt_parameters_acceptor_selector_apply_intrinsic_arg_overrides(args, parametersAcceptors, namedArgumentsVariants, scope, typeGetter.raw(), nativeTypeGetter.raw(), iterableValueTypeGetter.raw(), iterableKeyTypeGetter.raw());
		if (UNEXPECTED(overridden.isUndef())) return zv::Val();

		return selectArgsAcceptor(gatheredTypes, overridden.raw(), namedArgumentsVariants, hasName, unpack);
	}

	/* Mirrors callCallbackImmediately() ($parameter / $parameterType NULL for
	 * null); false = pending exception */
	static bool callCallbackImmediately(zval *parameter, zval *parameterType, zval *calleeReflection, bool &out)
	{
		bool parameterCallableTypeFound = false;
		if (parameterType != NULL) {
			bool isFunction = false;
			if (UNEXPECTED(!isA(calleeReflection, PT_CLASS_FUNCTION_REFLECTION, isFunction))) return false;
			if (isFunction) {
				zv::Val parameterCallableType = pt_type_utils_find_callable_type(parameterType);
				if (UNEXPECTED(parameterCallableType.isUndef())) return false;
				parameterCallableTypeFound = !parameterCallableType.isNull();
			}
		}

		bool isExtended = false;
		if (parameter != NULL && UNEXPECTED(!isA(parameter, PT_CLASS_EXTENDED_PARAMETER_REFLECTION, isExtended))) return false;
		if (isExtended) {
			zend_long parameterCallImmediately = parameterIsImmediatelyInvokedCallable(parameter);
			if (UNEXPECTED(parameterCallImmediately < 0)) return false;
			out = parameterCallImmediately == PT_TRI_MAYBE ? parameterCallableTypeFound : parameterCallImmediately == PT_TRI_YES;
		} else {
			out = parameterCallableTypeFound;
		}
		return true;
	}

	/* Mirrors shouldInvalidateCallbackExpressions() ($parameter NULL for null);
	 * false = pending exception */
	static bool shouldInvalidateCallbackExpressions(zval *parameter, bool &out)
	{
		bool isExtended = false;
		if (parameter != NULL && UNEXPECTED(!isA(parameter, PT_CLASS_EXTENDED_PARAMETER_REFLECTION, isExtended))) return false;
		if (isExtended) {
			zend_long immediately = parameterIsImmediatelyInvokedCallable(parameter);
			if (UNEXPECTED(immediately < 0)) return false;
			out = immediately != PT_TRI_NO;
			return true;
		}
		out = true;
		return true;
	}

	/* $this->{$collection}->getAll(); UNDEF = pending exception */
	zv::Val extensionsOf(uint32_t collectionSlot) const
	{
		zval *collection = slot(collectionSlot);
		if (UNEXPECTED(Z_TYPE_P(collection) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getAll() on %s", zend_zval_value_name(collection));
			return zv::Val();
		}
		zv::Val extensions = pt_extensions_collection_get_all(Z_OBJ_P(collection));
		if (UNEXPECTED(extensions.isUndef())) return zv::Val();
		if (UNEXPECTED(!requireArray(extensions.raw(), "foreach() argument"))) return zv::Val();
		return extensions;
	}

	/* the first extension of a collection whose $supported($callee,
	 * $parameter) answers truthy, asked for $get($callee, $call, $parameter,
	 * $scope): its answer, or `found` false when none supports it */
	zv::Val firstSupportedExtensionType(uint32_t collectionSlot, const char *supportedLc, size_t supportedLen, const char *supportedName, const char *getLc, size_t getLen, const char *getName, zval *calleeReflection, zval *call, zval *parameter, zval *scope, bool &found) const
	{
		found = false;
		zv::Val extensions = extensionsOf(collectionSlot);
		if (UNEXPECTED(extensions.isUndef())) return zv::Val();
		for (zv::ArrayEntry entry : zv::ArrRef(extensions.raw())) {
			zval *extension = entry.value().deref().raw();
			zv::Args supportedArgs{calleeReflection, parameter};
			zv::Val supported = callByName(extension, supportedLc, supportedLen, supportedName, 2, supportedArgs);
			if (UNEXPECTED(supported.isUndef())) return zv::Val();
			if (!zend_is_true(supported.raw())) continue;
			found = true;
			zv::Args getArgs{calleeReflection, call, parameter, scope};
			return callByName(extension, getLc, getLen, getName, 4, getArgs);
		}
		return zv::Val::null();
	}

	/* Mirrors getParameterTypeFromParameterClosureTypeExtension() (the type or null) */
	zv::Val getParameterTypeFromParameterClosureTypeExtension(zval *callLike, zval *calleeReflection, zval *parameter, zval *scope) const
	{
		bool found = false;
		bool isFuncCall = false, isFunction = false, isMethod = false;
		if (UNEXPECTED(!isA(callLike, PT_CLASS_FUNC_CALL, isFuncCall))) return zv::Val();
		if (isFuncCall && UNEXPECTED(!isA(calleeReflection, PT_CLASS_FUNCTION_REFLECTION, isFunction))) return zv::Val();
		if (isFuncCall && isFunction) {
			return firstSupportedExtensionType(slots::functionParameterClosureTypeExtensions, PT_LC("isfunctionsupported"), "isFunctionSupported", PT_LC("gettypefromfunctioncall"), "getTypeFromFunctionCall", calleeReflection, callLike, parameter, scope, found);
		}
		if (UNEXPECTED(!isA(calleeReflection, PT_CLASS_METHOD_REFLECTION, isMethod))) return zv::Val();
		if (!isMethod) return zv::Val::null();

		bool isStaticCall = false, isNew = false, isMethodCall = false;
		if (UNEXPECTED(!isA(callLike, PT_CLASS_STATIC_CALL, isStaticCall))) return zv::Val();
		if (isStaticCall) {
			return firstSupportedExtensionType(slots::staticMethodParameterClosureTypeExtensions, PT_LC("isstaticmethodsupported"), "isStaticMethodSupported", PT_LC("gettypefromstaticmethodcall"), "getTypeFromStaticMethodCall", calleeReflection, callLike, parameter, scope, found);
		}
		if (UNEXPECTED(!isA(callLike, PT_CLASS_NEW, isNew))) return zv::Val();
		if (isNew) {
			zv::Val classHold;
			zval *newClass = readProperty(pt_ah_new_class_site, callLike, PT_LC("class"), classHold);
			if (UNEXPECTED(newClass == NULL)) return zv::Val();
			bool isName = false;
			if (UNEXPECTED(!isA(newClass, PT_CLASS_NAME, isName))) return zv::Val();
			if (isName) {
				zval constructName;
				ZVAL_STR(&constructName, pt_ah_construct);
				zv::Val identifier = pt_name_node_new(PT_CLASS_IDENTIFIER, &constructName);
				if (UNEXPECTED(identifier.isUndef())) return zv::Val();
				zv::Val args = callArgs(callLike);
				if (UNEXPECTED(args.isUndef())) return zv::Val();
				zv::Args staticCallArgs{newClass, identifier.raw(), args.raw()};
				zv::Val staticCall = pt_type_new(PT_CLASS_STATIC_CALL, 3, staticCallArgs);
				if (UNEXPECTED(staticCall.isUndef())) return zv::Val();
				return firstSupportedExtensionType(slots::staticMethodParameterClosureTypeExtensions, PT_LC("isstaticmethodsupported"), "isStaticMethodSupported", PT_LC("gettypefromstaticmethodcall"), "getTypeFromStaticMethodCall", calleeReflection, staticCall.raw(), parameter, scope, found);
			}
		}
		if (isNew) return zv::Val::null();
		if (UNEXPECTED(!isA(callLike, PT_CLASS_METHOD_CALL, isMethodCall))) return zv::Val();
		if (isMethodCall) {
			return firstSupportedExtensionType(slots::methodParameterClosureTypeExtensions, PT_LC("ismethodsupported"), "isMethodSupported", PT_LC("gettypefrommethodcall"), "getTypeFromMethodCall", calleeReflection, callLike, parameter, scope, found);
		}
		return zv::Val::null();
	}

	/* the out types every supporting extension of a collection resolves,
	 * appended to $paramOutTypes; false = pending exception */
	bool collectParameterOutTypes(uint32_t collectionSlot, const char *supportedLc, size_t supportedLen, const char *supportedName, const char *getLc, size_t getLen, const char *getName, zval *calleeReflection, zval *callLike, zval *parameter, zval *scope, zv::Arr &paramOutTypes) const
	{
		zv::Val extensions = extensionsOf(collectionSlot);
		if (UNEXPECTED(extensions.isUndef())) return false;
		for (zv::ArrayEntry entry : zv::ArrRef(extensions.raw())) {
			zval *extension = entry.value().deref().raw();
			zv::Args supportedArgs{calleeReflection, parameter};
			zv::Val supported = callByName(extension, supportedLc, supportedLen, supportedName, 2, supportedArgs);
			if (UNEXPECTED(supported.isUndef())) return false;
			if (!zend_is_true(supported.raw())) continue;

			zv::Args getArgs{calleeReflection, callLike, parameter, scope};
			zv::Val resolvedType = callByName(extension, getLc, getLen, getName, 4, getArgs);
			if (UNEXPECTED(resolvedType.isUndef())) return false;
			if (resolvedType.isNull()) continue;
			paramOutTypes.push(std::move(resolvedType));
		}
		return true;
	}

	/* Mirrors getParameterOutExtensionsType() (the type or null) */
	zv::Val getParameterOutExtensionsType(zval *callLike, zval *calleeReflection, zval *currentParameter, zval *scope) const
	{
		zv::Arr paramOutTypes = zv::Arr::empty();
		bool isFuncCall = false, isFunction = false, isMethodCall = false, isMethod = false, isStaticCall = false;
		if (UNEXPECTED(!isA(callLike, PT_CLASS_FUNC_CALL, isFuncCall))) return zv::Val();
		if (isFuncCall && UNEXPECTED(!isA(calleeReflection, PT_CLASS_FUNCTION_REFLECTION, isFunction))) return zv::Val();
		if (isFuncCall && isFunction) {
			if (UNEXPECTED(!collectParameterOutTypes(slots::functionParameterOutTypeExtensions, PT_LC("isfunctionsupported"), "isFunctionSupported", PT_LC("getparameterouttypefromfunctioncall"), "getParameterOutTypeFromFunctionCall", calleeReflection, callLike, currentParameter, scope, paramOutTypes))) return zv::Val();
		} else {
			if (UNEXPECTED(!isA(callLike, PT_CLASS_METHOD_CALL, isMethodCall))) return zv::Val();
			if (isMethodCall && UNEXPECTED(!isA(calleeReflection, PT_CLASS_METHOD_REFLECTION, isMethod))) return zv::Val();
			if (isMethodCall && isMethod) {
				if (UNEXPECTED(!collectParameterOutTypes(slots::methodParameterOutTypeExtensions, PT_LC("ismethodsupported"), "isMethodSupported", PT_LC("getparameterouttypefrommethodcall"), "getParameterOutTypeFromMethodCall", calleeReflection, callLike, currentParameter, scope, paramOutTypes))) return zv::Val();
			} else {
				if (UNEXPECTED(!isA(callLike, PT_CLASS_STATIC_CALL, isStaticCall))) return zv::Val();
				if (isStaticCall && UNEXPECTED(!isA(calleeReflection, PT_CLASS_METHOD_REFLECTION, isMethod))) return zv::Val();
				if (isStaticCall && isMethod) {
					if (UNEXPECTED(!collectParameterOutTypes(slots::staticMethodParameterOutTypeExtensions, PT_LC("isstaticmethodsupported"), "isStaticMethodSupported", PT_LC("getparameterouttypefromstaticmethodcall"), "getParameterOutTypeFromStaticMethodCall", calleeReflection, callLike, currentParameter, scope, paramOutTypes))) return zv::Val();
				}
			}
		}

		uint32_t count = zend_hash_num_elements(paramOutTypes.table());
		if (count == 1) return zv::Val::copyOf(zv::Ref(zend_hash_index_find(paramOutTypes.table(), 0)));
		if (count > 1) return pt_type_combinator_union(count, paramOutTypes.table()->arPacked);
		return zv::Val::null();
	}

	/* the first non-null closure $this type of a collection's supporting
	 * extensions; `found` true when one answered */
	zv::Val firstClosureThisType(uint32_t collectionSlot, const char *supportedLc, size_t supportedLen, const char *supportedName, const char *getLc, size_t getLen, const char *getName, zval *calleeReflection, zval *call, zval *parameter, zval *scope, bool &found) const
	{
		found = false;
		zv::Val extensions = extensionsOf(collectionSlot);
		if (UNEXPECTED(extensions.isUndef())) return zv::Val();
		for (zv::ArrayEntry entry : zv::ArrRef(extensions.raw())) {
			zval *extension = entry.value().deref().raw();
			zv::Args supportedArgs{calleeReflection, parameter};
			zv::Val supported = callByName(extension, supportedLc, supportedLen, supportedName, 2, supportedArgs);
			if (UNEXPECTED(supported.isUndef())) return zv::Val();
			if (!zend_is_true(supported.raw())) continue;
			zv::Args getArgs{calleeReflection, call, parameter, scope};
			zv::Val type = callByName(extension, getLc, getLen, getName, 4, getArgs);
			if (UNEXPECTED(type.isUndef())) return zv::Val();
			if (!type.isNull()) {
				found = true;
				return type;
			}
		}
		return zv::Val::null();
	}

	/* Mirrors resolveClosureThisType() (the type or null) */
	zv::Val resolveClosureThisType(zval *call, zval *calleeReflection, zval *parameter, zval *scope) const
	{
		bool found = false;
		zv::Val type;
		bool isFuncCall = false, isFunction = false, isStaticCall = false, isMethodCall = false, isMethod = false;
		if (UNEXPECTED(!isA(call, PT_CLASS_FUNC_CALL, isFuncCall))) return zv::Val();
		if (isFuncCall && UNEXPECTED(!isA(calleeReflection, PT_CLASS_FUNCTION_REFLECTION, isFunction))) return zv::Val();
		if (isFuncCall && isFunction) {
			type = firstClosureThisType(slots::functionParameterClosureThisExtensions, PT_LC("isfunctionsupported"), "isFunctionSupported", PT_LC("getclosurethistypefromfunctioncall"), "getClosureThisTypeFromFunctionCall", calleeReflection, call, parameter, scope, found);
		} else {
			if (UNEXPECTED(!isA(call, PT_CLASS_STATIC_CALL, isStaticCall))) return zv::Val();
			if (isStaticCall && UNEXPECTED(!isA(calleeReflection, PT_CLASS_METHOD_REFLECTION, isMethod))) return zv::Val();
			if (isStaticCall && isMethod) {
				type = firstClosureThisType(slots::staticMethodParameterClosureThisExtensions, PT_LC("isstaticmethodsupported"), "isStaticMethodSupported", PT_LC("getclosurethistypefromstaticmethodcall"), "getClosureThisTypeFromStaticMethodCall", calleeReflection, call, parameter, scope, found);
			} else {
				if (UNEXPECTED(!isA(call, PT_CLASS_METHOD_CALL, isMethodCall))) return zv::Val();
				if (isMethodCall && UNEXPECTED(!isA(calleeReflection, PT_CLASS_METHOD_REFLECTION, isMethod))) return zv::Val();
				if (isMethodCall && isMethod) {
					type = firstClosureThisType(slots::methodParameterClosureThisExtensions, PT_LC("ismethodsupported"), "isMethodSupported", PT_LC("getclosurethistypefrommethodcall"), "getClosureThisTypeFromMethodCall", calleeReflection, call, parameter, scope, found);
				}
			}
		}
		if (UNEXPECTED(EG(exception))) return zv::Val();
		if (found) return type;

		bool isExtended = false;
		if (UNEXPECTED(!isA(parameter, PT_CLASS_EXTENDED_PARAMETER_REFLECTION, isExtended))) return zv::Val();
		if (isExtended) return parameterGetClosureThisType(parameter);
		return zv::Val::null();
	}

	/* Mirrors findOriginalParameterType() (the type or null) */
	static zv::Val findOriginalParameterType(zval *acceptor, zval *parameter)
	{
		bool isResolved = false;
		if (UNEXPECTED(!isA(acceptor, PT_CLASS_RESOLVED_FUNCTION_VARIANT, isResolved))) return zv::Val();
		if (!isResolved) return parameterGetType(parameter);

		zv::Val originalAcceptor = acceptorGetOriginalParametersAcceptor(acceptor);
		if (UNEXPECTED(originalAcceptor.isUndef())) return zv::Val();
		zv::Val originalParameters = acceptorGetParameters(originalAcceptor.raw());
		if (UNEXPECTED(originalParameters.isUndef())) return zv::Val();
		zv::Val resolvedParameters = acceptorGetParameters(acceptor);
		if (UNEXPECTED(resolvedParameters.isUndef())) return zv::Val();
		if (UNEXPECTED(!requireArray(resolvedParameters.raw(), "foreach() argument") || !requireArray(originalParameters.raw(), "getParameters()"))) return zv::Val();
		for (zv::ArrayEntry entry : zv::ArrRef(resolvedParameters.raw())) {
			zval *resolvedParameter = entry.value().deref().raw();
			if (Z_TYPE_P(resolvedParameter) != IS_OBJECT || Z_OBJ_P(resolvedParameter) != Z_OBJ_P(parameter)) continue;
			zend_string *stringKey = entry.stringKeyOrNull();
			zval *originalParameter = stringKey != NULL ? zend_symtable_find(Z_ARRVAL_P(originalParameters.raw()), stringKey) : zend_hash_index_find(Z_ARRVAL_P(originalParameters.raw()), entry.indexKey());
			if (originalParameter != NULL) {
				ZVAL_DEREF(originalParameter);
			}
			if (originalParameter == NULL || Z_TYPE_P(originalParameter) == IS_NULL) return zv::Val::null();
			zv::Val originalType = parameterGetType(originalParameter);
			if (UNEXPECTED(originalType.isUndef())) return zv::Val();
			zv::Val hasTemplate = pt_type_op(Z_OBJ_P(originalType.raw()), PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, 0, NULL);
			if (UNEXPECTED(hasTemplate.isUndef())) return zv::Val();
			if (!zend_is_true(hasTemplate.raw())) return originalType;

			zv::Val resolvedMap = acceptorGetResolvedTemplateTypeMap(acceptor);
			if (UNEXPECTED(resolvedMap.isUndef())) return zv::Val();
			zv::Val resolvedTypes = callByName(resolvedMap.raw(), PT_LC("gettypes"), "getTypes", 0, NULL);
			if (UNEXPECTED(resolvedTypes.isUndef())) return zv::Val();
			if (UNEXPECTED(!requireArray(resolvedTypes.raw(), "foreach() argument"))) return zv::Val();
			zv::Arr decided = zv::Arr::empty();
			for (zv::ArrayEntry typeEntry : zv::ArrRef(resolvedTypes.raw())) {
				zval *type = typeEntry.value().deref().raw();
				if (Z_TYPE_P(type) == IS_OBJECT && instanceof_function(Z_OBJCE_P(type), pt_ce_error_type)) continue;
				setKey(decided, typeEntry.stringKeyOrNull(), typeEntry.indexKey(), type);
			}
			zval decidedMap;
			if (UNEXPECTED(!pt_template_type_map_new(&decidedMap, decided.raw()))) return zv::Val();
			zv::Val decidedMapHold = zv::Val::adopt(decidedMap);
			zv::Val callSiteVarianceMap = acceptorGetCallSiteVarianceMap(acceptor);
			if (UNEXPECTED(callSiteVarianceMap.isUndef())) return zv::Val();
			zval *contravariant = pt_template_type_variance_singleton(PT_TEMPLATE_TYPE_VARIANCE_CONTRAVARIANT);
			if (UNEXPECTED(contravariant == NULL)) return zv::Val();

			return pt_type_template_type_helper_resolve_template_types(originalType.raw(), decidedMapHold.raw(), callSiteVarianceMap.raw(), contravariant, false);
		}

		return zv::Val::null();
	}

	/* Mirrors readArgResult(): the captured result (borrowed); NULL = pending exception */
	static zval *readArgResult(HashTable *argResults, zval *argValue)
	{
		zval *result = zend_hash_index_find(argResults, Z_OBJ_HANDLE_P(argValue));
		if (EXPECTED(result != NULL && Z_TYPE_P(result) != IS_NULL)) return result;
		zval *startLine = nodeAttribute(argValue, pt_ah_start_line);
		zend_long line = startLine != NULL && Z_TYPE_P(startLine) != IS_NULL ? zval_get_long(startLine) : -1;
		zend_string *message = zend_strpprintf(0, "%s on line " ZEND_LONG_FMT " has no captured ExpressionResult - it was not processed as an argument by processArgs().", ZSTR_VAL(Z_OBJCE_P(argValue)->name), line);
		zval messageZv;
		ZVAL_STR(&messageZv, message);
		zv::Val exception = pt_type_new(PT_CLASS_SHOULD_NOT_HAPPEN, 1, &messageZv);
		zend_string_release(message);
		if (UNEXPECTED(exception.isUndef())) return NULL;
		zval raw = exception.take();
		zend_throw_exception_object(&raw);
		return NULL;
	}

	/* {{{ processArgs() state */

	/* one argument of $args, in the array's order */
	struct ArgRecord
	{
		zend_ulong key;
		zval *arg; /* borrowed from $args */
		zval gathered; /* $gatheredArgTypeByIndex[$i], UNDEF = unset */
		/* the usort comparator's inputs */
		bool sortIsClosure;
		bool sortHasOriginal;
		zend_long sortStartTokenPos;
	};

	/* the records and the processing order (positions into records) */
	struct ArgRecords
	{
		uint32_t count;
		ArgRecord *records;
		uint32_t *order;
	};

	/* the per-argument locals the branches share — in the Walk block, reset per
	 * argument, so the frames on the nested-walk path hold none of them */
	struct ArgLocals
	{
		zval *argMetadataAcceptor = NULL; /* borrowed (NULL for null) */
		zv::Val argMetadataAcceptorHold;
		bool assignByReference = false;
		bool lookForUnset = false;
		zval *parameter = NULL; /* borrowed from parameters */
		zv::Val parameters;
		zv::Val parameterType; /* UNDEF = null */
		zv::Val parameterNativeType; /* UNDEF = null */
		zv::Val scopeToPass;
		zv::Val originalScope;
		zv::Val valueHold;
		zv::Val argContext;
		zv::Val exprResult;
		bool enterExpressionAssignForByRef = false;

		/* back to the defaults in place (no temporary on the C stack) */
		zend_never_inline void reset()
		{
			argMetadataAcceptor = NULL;
			argMetadataAcceptorHold.release();
			assignByReference = false;
			lookForUnset = false;
			parameter = NULL;
			parameters.release();
			parameterType.release();
			parameterNativeType.release();
			scopeToPass.release();
			originalScope.release();
			valueHold.release();
			argContext.release();
			exprResult.release();
			enterExpressionAssignForByRef = false;
		}
	};

	/* the twin's locals shared by the argument loop and what follows it */
	struct Walk
	{
		zval *nodeScopeResolver;
		zval *stmt;
		zval *calleeReflection; /* IS_NULL for null */
		zval *nakedMethodReflection; /* NULL for null */
		zval *parametersAcceptors;
		zval *namedArgumentsVariants; /* IS_NULL for null */
		zval *callLike;
		zval *storage;
		zval *nodeCallback;
		zval *context;
		zval *closureBindScopeFactory; /* NULL for null */
		zval *args;
		zv::Val argsHold;
		ArgRecords records;
		zval *metadataAcceptor; /* NULL for null */
		bool typeDrivenAcceptorSelection;
		bool argMetadataIsTypeDriven;
		zv::Val scope;
		zv::Arr gatheredTypes = zv::Arr::empty();
		bool gatheredUnpack = false;
		bool gatheredHasName = false;
		bool hasYield = false;
		bool isAlwaysTerminating = false;
		zv::Arr throwPoints = zv::Arr::empty();
		zv::Arr impurePoints = zv::Arr::empty();
		zv::Arr deferredInvalidateExpressions = zv::Arr::empty();
		zv::Arr deferredUses = zv::Arr::empty();
		zv::Arr deferredByRefClosureResults = zv::Arr::empty();
		zv::Arr argResults = zv::Arr::empty();
		zv::Arr byRefArguments = zv::Arr::empty();
		zv::Val countStableMetadataAcceptor; /* UNDEF = null */
		ArgLocals current;
	};

	/* the walk state off the C stack — processArgs()'s frame stays on it
	 * through every nested walk: one emalloc for the Walk and the argument
	 * records, released with them */
	class WalkHolder
	{
	public:
		WalkHolder() = default;
		WalkHolder(const WalkHolder &) = delete;
		WalkHolder &operator=(const WalkHolder &) = delete;

		void allocate(uint32_t count)
		{
			void *memory = safe_emalloc(count, sizeof(ArgRecord) + sizeof(uint32_t), sizeof(Walk));
			walk = new (memory) Walk();
			walk->records.count = count;
			walk->records.records = reinterpret_cast<ArgRecord *>(static_cast<char *>(memory) + sizeof(Walk));
			walk->records.order = reinterpret_cast<uint32_t *>(static_cast<char *>(memory) + sizeof(Walk) + count * sizeof(ArgRecord));
			for (uint32_t i = 0; i < count; i++) {
				ZVAL_UNDEF(&walk->records.records[i].gathered);
			}
		}

		~WalkHolder()
		{
			if (walk == NULL) return;
			for (uint32_t i = 0; i < walk->records.count; i++) {
				zval_ptr_dtor(&walk->records.records[i].gathered);
			}
			walk->~Walk();
			efree(walk);
		}

		Walk *walk = NULL;
	};

	/* the usort() comparator: closures after non-closures, then the
	 * normalized-only arguments after the original ones, by index / start
	 * token position (a strict order together with the stable fallback on
	 * the original position, so any stable sort agrees with usort()) */
	static bool sortsBefore(const ArgRecord &a, uint32_t aPosition, const ArgRecord &b, uint32_t bPosition)
	{
		if (a.sortIsClosure != b.sortIsClosure) return !a.sortIsClosure;
		int result;
		if (!a.sortHasOriginal && !b.sortHasOriginal) {
			result = a.key < b.key ? -1 : (a.key > b.key ? 1 : 0);
		} else if (!a.sortHasOriginal) {
			result = 1;
		} else if (!b.sortHasOriginal) {
			result = -1;
		} else {
			result = a.sortStartTokenPos < b.sortStartTokenPos ? -1 : (a.sortStartTokenPos > b.sortStartTokenPos ? 1 : 0);
		}
		if (result != 0) return result < 0;
		return aPosition < bPosition;
	}

	/* $processingOrder = array_keys($args) sorted by the comparator (an
	 * insertion sort — argument lists are short); false = pending exception */
	zend_never_inline static bool buildProcessingOrder(ArgRecords &records, HashTable *args)
	{
		uint32_t position = 0;
		for (zv::ArrayEntry entry : zv::TableRef(args)) {
			if (UNEXPECTED(entry.stringKeyOrNull() != NULL)) {
				zend_type_error("PHPStan\\Analyser\\ArgumentsHandler::{closure}(): Argument #1 ($a) must be of type int, string given");
				return false;
			}
			ArgRecord &record = records.records[position];
			record.key = entry.indexKey();
			record.arg = entry.value().deref().raw();
			records.order[position] = position;
			position++;
		}
		if (records.count < 2) return true;

		for (uint32_t i = 0; i < records.count; i++) {
			ArgRecord &record = records.records[i];
			if (UNEXPECTED(Z_TYPE_P(record.arg) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function getAttribute() on %s", zend_zval_value_name(record.arg));
				return false;
			}
			zval *original = nodeAttribute(record.arg, pt_ah_original_arg);
			record.sortHasOriginal = original != NULL && Z_TYPE_P(original) != IS_NULL;
			zv::Val valueHold;
			zval *value = argValue(record.sortHasOriginal ? original : record.arg, valueHold);
			if (UNEXPECTED(value == NULL)) return false;
			bool isClosure, isArrowFunction;
			if (UNEXPECTED(!closureKind(value, isClosure, isArrowFunction))) return false;
			record.sortIsClosure = isClosure || isArrowFunction;
			record.sortStartTokenPos = -1;
			if (record.sortHasOriginal) {
				if (UNEXPECTED(Z_TYPE_P(original) != IS_OBJECT)) {
					zend_throw_error(NULL, "Call to a member function getStartTokenPos() on %s", zend_zval_value_name(original));
					return false;
				}
				zval *startTokenPos = nodeAttribute(original, pt_ah_start_token_pos);
				if (startTokenPos != NULL && Z_TYPE_P(startTokenPos) != IS_NULL) {
					record.sortStartTokenPos = zval_get_long(startTokenPos);
				}
			}
		}

		uint32_t *order = records.order;
		for (uint32_t i = 1; i < records.count; i++) {
			uint32_t current = order[i];
			uint32_t j = i;
			while (j > 0 && sortsBefore(records.records[current], current, records.records[order[j - 1]], order[j - 1])) {
				order[j] = order[j - 1];
				j--;
			}
			order[j] = current;
		}
		return true;
	}

	/* the metadata acceptor selected over every argument's gathered type
	 * (allMixed: over mixed for all of them), padded with mixed */
	zend_never_inline zv::Val paddedMetadataAcceptor(Walk &w, bool allMixed) const
	{
		zv::Arr paddedTypes = zv::Arr::empty();
		bool paddedUnpack = false;
		bool paddedHasName = false;
		for (uint32_t j = 0; j < w.records.count; j++) {
			ArgRecord &record = w.records.records[j];
			zval *paddedOriginalArg = originalArgOf(record.arg);
			zv::Val mixed;
			zval *type;
			if (!allMixed && Z_TYPE(record.gathered) != IS_UNDEF && Z_TYPE(record.gathered) != IS_NULL) {
				type = &record.gathered;
			} else {
				mixed = pt_type_new_mixed_type();
				if (UNEXPECTED(mixed.isUndef())) return zv::Val();
				type = mixed.raw();
			}
			if (UNEXPECTED(!addGatheredArgType(paddedTypes, paddedUnpack, paddedHasName, paddedOriginalArg, record.key, type))) return zv::Val();
		}
		return selectArgsMetadataAcceptor(w.nodeScopeResolver, w.args, paddedTypes.raw(), w.parametersAcceptors, w.namedArgumentsVariants, paddedHasName, paddedUnpack, w.scope.raw());
	}

	/* $argResults[spl_object_id($value)] = $result */
	static void storeArgResult(Walk &w, zval *value, zval *result)
	{
		w.argResults.arrRef().setIndex(Z_OBJ_HANDLE_P(value), zv::Ref(result));
	}

	/* the throw points of a closure body mapped onto the call argument
	 * (array_map() over InternalThrowPoints, keys kept) and merged into
	 * $throwPoints; false = pending exception */
	static bool mergeMappedInternalThrowPoints(Walk &w, zval *throwPoints, zval *scope, zval *value)
	{
		if (UNEXPECTED(!requireArray(throwPoints, "array_map(): Argument #2 ($array)"))) return false;
		zv::Arr mapped = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(throwPoints)));
		for (zv::ArrayEntry entry : zv::ArrRef(throwPoints)) {
			zval *throwPoint = entry.value().deref().raw();
			bool isExplicit = false;
			if (UNEXPECTED(!pt_internal_throw_point_is_explicit(throwPoint, isExplicit))) return false;
			zv::Val mappedPoint;
			if (isExplicit) {
				zv::Val typeHold;
				zval *type = pt_internal_throw_point_type(throwPoint, typeHold);
				if (UNEXPECTED(type == NULL)) return false;
				bool canContainAnyThrowable = false;
				if (UNEXPECTED(!pt_internal_throw_point_can_contain_any_throwable(throwPoint, canContainAnyThrowable))) return false;
				mappedPoint = pt_internal_throw_point_create_explicit(scope, type, value, canContainAnyThrowable);
			} else {
				mappedPoint = pt_internal_throw_point_create_implicit(scope, value);
			}
			if (UNEXPECTED(mappedPoint.isUndef())) return false;
			setKey(mapped, entry.stringKeyOrNull(), entry.indexKey(), mappedPoint.raw());
		}
		return arrayMerge(w.throwPoints, mapped.raw());
	}

	/* the stored-result shortcut of a closure / arrow function argument on an
	 * on-demand re-walk: the stored result, or a priced one stored now; UNDEF
	 * with no exception = walk it; `failed` on a pending exception */
	zend_never_inline zv::Val storedClosureArgResult(Walk &w, zval *value, zval *scopeToPass, bool &failed) const
	{
		failed = true;
		bool returning = false, consuming = false;
		if (UNEXPECTED(!nsrIsReturningStoredExpressionResults(w.nodeScopeResolver, returning))) return zv::Val();
		if (!returning && UNEXPECTED(!nsrIsConsumingStoredExpressionResults(w.nodeScopeResolver, consuming))) return zv::Val();
		failed = false;
		if (!returning && !consuming) return zv::Val();

		failed = true;
		zv::Val stored = pt_expression_result_storage_find(w.storage, value);
		if (UNEXPECTED(stored.isUndef())) return zv::Val();
		if (!stored.isNull()) {
			failed = false;
			return stored;
		}
		bool returningAgain = false;
		if (UNEXPECTED(!nsrIsReturningStoredExpressionResults(w.nodeScopeResolver, returningAgain))) return zv::Val();
		if (!returningAgain) {
			failed = false;
			return zv::Val();
		}
		zval *closureTypeResolver = slot(slots::closureTypeResolver);
		zv::Val type = closureTypeResolverGetClosureType(closureTypeResolver, scopeToPass, value, false, w.storage);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(scopeToPass) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function doNotTreatPhpDocTypesAsCertain() on %s", zend_zval_value_name(scopeToPass));
			return zv::Val();
		}
		zv::Val nativeScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scopeToPass));
		if (UNEXPECTED(nativeScope.isUndef())) return zv::Val();
		zv::Val nativeType = closureTypeResolverGetClosureType(closureTypeResolver, nativeScope.raw(), value, false, w.storage);
		if (UNEXPECTED(nativeType.isUndef())) return zv::Val();
		zv::Val specifyTypesCallback = pt_specified_types_empty_specify_callback();
		if (UNEXPECTED(specifyTypesCallback.isUndef())) return zv::Val();
		pt_expression_result_args args(scopeToPass, scopeToPass, value, false, false, NULL, NULL, NULL, specifyTypesCallback.raw());
		args.withType(type.raw()).withNativeType(nativeType.raw());
		stored = pt_expression_result_create(slot(slots::expressionResultFactory), args);
		if (UNEXPECTED(stored.isUndef())) return zv::Val();
		if (UNEXPECTED(!nsrStoreExpressionResult(w.nodeScopeResolver, w.storage, value, stored.raw()))) return zv::Val();
		failed = false;
		return stored;
	}

	/* the closure $this binding and the parameter-type extensions ahead of a
	 * closure / arrow function body walk; restoreThisScope receives the scope
	 * before the binding (UNDEF when none); false = pending exception */
	zend_never_inline bool prepareClosureArgScope(Walk &w, zval *value, ArgLocals &a, zv::Val *restoreThisScope) const
	{
		bool isExtended = false;
		if (a.parameter != NULL && UNEXPECTED(!isA(a.parameter, PT_CLASS_EXTENDED_PARAMETER_REFLECTION, isExtended))) return false;
		if (w.closureBindScopeFactory == NULL && isExtended) {
			zv::Val staticHold;
			zval *isStatic = readProperty(pt_ah_closure_static_site, value, PT_LC("static"), staticHold);
			if (UNEXPECTED(isStatic == NULL)) return false;
			if (!zend_is_true(isStatic)) {
				zv::Val closureThisType = resolveClosureThisType(w.callLike, w.calleeReflection, a.parameter, a.scopeToPass.raw());
				if (UNEXPECTED(closureThisType.isUndef())) return false;
				if (!closureThisType.isNull()) {
					if (restoreThisScope != NULL) {
						*restoreThisScope = zv::Val::copyOf(zv::Ref(a.scopeToPass.raw()));
					}
					if (UNEXPECTED(Z_TYPE_P(a.scopeToPass.raw()) != IS_OBJECT)) {
						zend_throw_error(NULL, "Call to a member function assignVariable() on %s", zend_zval_value_name(a.scopeToPass.raw()));
						return false;
					}
					zv::Val objectWithoutClassType = pt_type_new_object_without_class_type();
					if (UNEXPECTED(objectWithoutClassType.isUndef())) return false;
					zv::Val assigned = pt_mutating_scope_assign_variable(Z_OBJ_P(a.scopeToPass.raw()), pt_ah_this, closureThisType.raw(), objectWithoutClassType.raw(), pt_trinary_singleton(PT_TRI_YES));
					if (UNEXPECTED(assigned.isUndef())) return false;
					zv::Val classNames = pt_type_op(Z_OBJ_P(closureThisType.raw()), PT_OP_GET_OBJECT_CLASS_NAMES, 0, NULL);
					if (UNEXPECTED(classNames.isUndef())) return false;
					zv::Val bound = pt_mutating_scope_with_closure_bind_scope_classes(Z_OBJ_P(assigned.raw()), classNames.raw());
					if (UNEXPECTED(bound.isUndef())) return false;
					a.scopeToPass = std::move(bound);
				}
			}
		}

		if (a.parameter != NULL) {
			zv::Val overwritingParameterType = getParameterTypeFromParameterClosureTypeExtension(w.callLike, w.calleeReflection, a.parameter, a.scopeToPass.raw());
			if (UNEXPECTED(overwritingParameterType.isUndef())) return false;
			if (!overwritingParameterType.isNull()) {
				a.parameterType = std::move(overwritingParameterType);

				// resolve the native flavour through the same extension on the
				// natively-promoted scope, so the closure parameters keep
				// their native precision too
				if (UNEXPECTED(Z_TYPE_P(a.scopeToPass.raw()) != IS_OBJECT)) {
					zend_throw_error(NULL, "Call to a member function doNotTreatPhpDocTypesAsCertain() on %s", zend_zval_value_name(a.scopeToPass.raw()));
					return false;
				}
				zv::Val nativeScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(a.scopeToPass.raw()));
				if (UNEXPECTED(nativeScope.isUndef())) return false;
				zv::Val overwritingParameterNativeType = getParameterTypeFromParameterClosureTypeExtension(w.callLike, w.calleeReflection, a.parameter, nativeScope.raw());
				if (UNEXPECTED(overwritingParameterNativeType.isUndef())) return false;
				if (!overwritingParameterNativeType.isNull()) {
					a.parameterNativeType = std::move(overwritingParameterNativeType);
				}
			}
		}
		return true;
	}

	/* $invalidateExpressions without the nodes mentioning $this, as a list */
	static zv::Val withoutThisInvalidations(zval *invalidateExpressions)
	{
		if (UNEXPECTED(!requireArray(invalidateExpressions, "foreach() argument"))) return zv::Val();
		pt_find_ctx ctx{};
		ctx.target_ce = pt_class(PT_CLASS_VARIABLE);
		if (UNEXPECTED(ctx.target_ce == NULL)) return zv::Val();
		zv::Arr kept = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(invalidateExpressions)));
		for (zv::ArrayEntry entry : zv::ArrRef(invalidateExpressions)) {
			zval *invalidateExprNode = entry.value().deref().raw();
			zv::Val exprHold;
			zval *expr = invalidateExprNodeExpr(invalidateExprNode, exprHold);
			if (UNEXPECTED(expr == NULL)) return zv::Val();
			zend_object *foundThis = Z_TYPE_P(expr) == IS_OBJECT ? pt_find_first_recursive(Z_OBJ_P(expr), thisVariableMatcher, &ctx) : NULL;
			if (UNEXPECTED(ctx.failed || EG(exception))) return zv::Val();
			if (foundThis != NULL) continue;
			kept.push(zv::Ref(entry.value().raw()));
		}
		return zv::Val(std::move(kept));
	}

	/* the Expr\Closure branch of the argument loop; false = pending exception */
	zend_never_inline bool processClosureArg(Walk &w, zval *value, ArgLocals &a) const
	{
		bool failed = false;
		zv::Val stored = storedClosureArgResult(w, value, a.scopeToPass.raw(), failed);
		if (UNEXPECTED(failed)) return false;
		if (!stored.isUndef()) {
			storeArgResult(w, value, stored.raw());
			return true;
		}

		zv::Val restoreThisScope;
		if (UNEXPECTED(!prepareClosureArgScope(w, value, a, &restoreThisScope))) return false;

		zval null;
		ZVAL_NULL(&null);
		zv::Args processArgv{w.nodeScopeResolver, w.stmt, value, a.scopeToPass.raw(), w.storage, w.nodeCallback, w.context, a.parameterType.isUndef() ? &null : a.parameterType.raw(), a.parameterNativeType.isUndef() ? &null : a.parameterNativeType.raw()};
		zv::Val closureResult = closureProcessorProcessClosureNode(slot(slots::closureProcessor), processArgv);
		if (UNEXPECTED(closureResult.isUndef())) return false;
		bool immediately = false;
		if (UNEXPECTED(!callCallbackImmediately(a.parameter, a.parameterType.isUndef() ? NULL : a.parameterType.raw(), w.calleeReflection, immediately))) return false;
		if (immediately) {
			zv::Val throwPointsHold, impurePointsHold;
			zval *throwPoints = closureResultThrowPoints(closureResult.raw(), throwPointsHold);
			if (UNEXPECTED(throwPoints == NULL || !mergeMappedInternalThrowPoints(w, throwPoints, w.scope.raw(), value))) return false;
			zval *impurePoints = closureResultImpurePoints(closureResult.raw(), impurePointsHold);
			if (UNEXPECTED(impurePoints == NULL || !arrayMerge(w.impurePoints, impurePoints))) return false;
		}

		zv::Val resultScopeHold;
		zval *resultScope = closureResultScope(closureResult.raw(), resultScopeHold);
		if (UNEXPECTED(resultScope == NULL)) return false;
		zv::Val variableFlow = closureHandlerGetVariableFlow(value);
		if (UNEXPECTED(variableFlow.isUndef())) return false;
		zv::Val types[2];
		for (int native = 0; native < 2; native++) {
			zv::Val returnsHold, yieldsHold, endsHold, throwPointsHold, impurePointsHold, invalidateHold;
			zval *returns = closureResultGatheredReturnStatements(closureResult.raw(), returnsHold);
			zval *yields = returns != NULL ? closureResultGatheredYieldStatements(closureResult.raw(), yieldsHold) : NULL;
			zval *ends = yields != NULL ? closureResultExecutionEnds(closureResult.raw(), endsHold) : NULL;
			zval *throwPoints = ends != NULL ? closureResultThrowPoints(closureResult.raw(), throwPointsHold) : NULL;
			zval *impurePoints = throwPoints != NULL ? closureResultClosureTypeImpurePoints(closureResult.raw(), impurePointsHold) : NULL;
			zval *invalidate = impurePoints != NULL ? closureResultInvalidateExpressions(closureResult.raw(), invalidateHold) : NULL;
			if (UNEXPECTED(invalidate == NULL)) return false;
			zv::Args buildArgv{a.scopeToPass.raw(), value, returns, yields, ends, throwPoints, impurePoints, invalidate, native == 1, w.storage};
			types[native] = closureTypeResolverBuildForClosure(slot(slots::closureTypeResolver), buildArgv);
			if (UNEXPECTED(types[native].isUndef())) return false;
		}
		zv::Val specifyTypesCallback = pt_specified_types_empty_specify_callback();
		if (UNEXPECTED(specifyTypesCallback.isUndef())) return false;
		pt_expression_result_args storedArgs(resultScope, a.scopeToPass.raw(), value, false, false, NULL, NULL, NULL, specifyTypesCallback.raw());
		storedArgs.withVariableFlow(variableFlow.raw()).withType(types[0].raw()).withNativeType(types[1].raw());
		zv::Val storedClosureResult = pt_expression_result_create(slot(slots::expressionResultFactory), storedArgs);
		if (UNEXPECTED(storedClosureResult.isUndef())) return false;
		if (UNEXPECTED(!nsrStoreExpressionResult(w.nodeScopeResolver, w.storage, value, storedClosureResult.raw()))) return false;
		// the closure node's own callback fires after its result is
		// stored, mirroring processExprNodeInternal()
		if (UNEXPECTED(!nsrCallNodeCallbackWithExpression(w.nodeScopeResolver, w.nodeCallback, value, a.scopeToPass.raw(), w.storage, w.context))) return false;
		storeArgResult(w, value, storedClosureResult.raw());

		zv::Arr uses = zv::Arr::empty();
		zv::Val usesHold;
		zval *closureUses = readProperty(pt_ah_closure_uses_site, value, PT_LC("uses"), usesHold);
		if (UNEXPECTED(closureUses == NULL || !requireArray(closureUses, "foreach() argument"))) return false;
		for (zv::ArrayEntry entry : zv::ArrRef(closureUses)) {
			zv::Val varHold, nameHold;
			zval *var = readProperty(pt_ah_closure_use_var_site, entry.value().deref().raw(), PT_LC("var"), varHold);
			if (UNEXPECTED(var == NULL)) return false;
			zval *name = readProperty(pt_ah_variable_name_site, var, PT_LC("name"), nameHold);
			if (UNEXPECTED(name == NULL)) return false;
			if (Z_TYPE_P(name) != IS_STRING) continue;
			uses.push(zv::Ref(name));
		}

		zv::Val scopeHold;
		zval *closureScope = closureResultScope(closureResult.raw(), scopeHold);
		if (UNEXPECTED(closureScope == NULL)) return false;
		w.scope = zv::Val::copyOf(zv::Ref(closureScope));
		w.deferredByRefClosureResults.push(zv::Ref(closureResult.raw()));
		// Prefer the invalidate expressions collected on the ClosureType -
		// they also cover writes the closure's own body walk observed
		zv::Val closureExprType = pt_expression_result_get_type(storedClosureResult.raw());
		if (UNEXPECTED(closureExprType.isUndef())) return false;
		zv::Val invalidateExpressions;
		if (closureExprType.ref().isObject() && instanceof_function(Z_OBJCE_P(closureExprType.raw()), pt_ce_closure_type)) {
			invalidateExpressions = pt_type_call(Z_OBJ_P(closureExprType.raw()), PT_LC("getinvalidateexpressions"), 0, NULL);
		} else {
			zv::Val invalidateHold;
			zval *invalidate = closureResultInvalidateExpressions(closureResult.raw(), invalidateHold);
			if (invalidate != NULL) {
				invalidateExpressions = zv::Val::copyOf(zv::Ref(invalidate));
			}
		}
		if (UNEXPECTED(invalidateExpressions.isUndef())) return false;
		if (!restoreThisScope.isUndef()) {
			invalidateExpressions = withoutThisInvalidations(invalidateExpressions.raw());
			if (UNEXPECTED(invalidateExpressions.isUndef())) return false;
			zv::Val restored = pt_mutating_scope_restore_this(Z_OBJ_P(w.scope.raw()), restoreThisScope.raw());
			if (UNEXPECTED(restored.isUndef())) return false;
			w.scope = std::move(restored);
		}

		bool invalidateCallback = false;
		if (UNEXPECTED(!shouldInvalidateCallbackExpressions(a.parameter, invalidateCallback))) return false;
		if (invalidateCallback) {
			w.deferredInvalidateExpressions.push(std::move(invalidateExpressions));
			w.deferredUses.push(zv::Val(std::move(uses)));
		}
		return true;
	}

	/* the Expr\ArrowFunction branch of the argument loop; false = pending exception */
	zend_never_inline bool processArrowFunctionArg(Walk &w, zval *value, ArgLocals &a) const
	{
		bool failed = false;
		zv::Val stored = storedClosureArgResult(w, value, a.scopeToPass.raw(), failed);
		if (UNEXPECTED(failed)) return false;
		if (!stored.isUndef()) {
			storeArgResult(w, value, stored.raw());
		} else {
			if (UNEXPECTED(!prepareClosureArgScope(w, value, a, NULL))) return false;

			zval null;
			ZVAL_NULL(&null);
			zv::Args processArgv{w.nodeScopeResolver, w.stmt, value, a.scopeToPass.raw(), w.storage, w.nodeCallback, a.parameterType.isUndef() ? &null : a.parameterType.raw(), a.parameterNativeType.isUndef() ? &null : a.parameterNativeType.raw(), w.context};
			zv::Val arrowFunctionResult = closureProcessorProcessArrowFunctionNode(slot(slots::closureProcessor), processArgv);
			if (UNEXPECTED(arrowFunctionResult.isUndef())) return false;
			zv::Val exprResultHold;
			zval *exprResult = arrowResultExpressionResult(arrowFunctionResult.raw(), exprResultHold);
			if (UNEXPECTED(exprResult == NULL)) return false;
			bool immediately = false;
			if (UNEXPECTED(!callCallbackImmediately(a.parameter, a.parameterType.isUndef() ? NULL : a.parameterType.raw(), w.calleeReflection, immediately))) return false;
			if (immediately) {
				zv::Val throwPointsHold, impurePointsHold;
				zval *throwPoints = pt_expression_result_throw_points(exprResult, throwPointsHold);
				if (UNEXPECTED(throwPoints == NULL || !mergeMappedInternalThrowPoints(w, throwPoints, w.scope.raw(), value))) return false;
				zval *impurePoints = pt_expression_result_impure_points(exprResult, impurePointsHold);
				if (UNEXPECTED(impurePoints == NULL || !arrayMerge(w.impurePoints, impurePoints))) return false;
			}
			zv::Val arrowScopeHold;
			zval *arrowFunctionScope = arrowResultArrowFunctionScope(arrowFunctionResult.raw(), arrowScopeHold);
			if (UNEXPECTED(arrowFunctionScope == NULL)) return false;
			// both flavours are built from the single body walk
			zv::Val types[2];
			for (int native = 0; native < 2; native++) {
				zv::Val throwPointsHold, impurePointsHold, invalidateHold;
				zval *throwPoints = arrowResultClosureTypeThrowPoints(arrowFunctionResult.raw(), throwPointsHold);
				zval *impurePoints = throwPoints != NULL ? arrowResultClosureTypeImpurePoints(arrowFunctionResult.raw(), impurePointsHold) : NULL;
				zval *invalidate = impurePoints != NULL ? arrowResultInvalidateExpressions(arrowFunctionResult.raw(), invalidateHold) : NULL;
				if (UNEXPECTED(invalidate == NULL)) return false;
				zv::Args buildArgv{a.scopeToPass.raw(), value, arrowFunctionScope, throwPoints, impurePoints, invalidate, native == 1, w.storage};
				types[native] = closureTypeResolverBuildForArrowFunction(slot(slots::closureTypeResolver), buildArgv);
				if (UNEXPECTED(types[native].isUndef())) return false;
			}
			zv::Val scopeHold, throwPointsHold, impurePointsHold;
			zval *resultScope = pt_expression_result_scope(exprResult, scopeHold);
			if (UNEXPECTED(resultScope == NULL)) return false;
			zv::Val variableFlow = pt_expression_result_variable_flow(exprResult);
			if (UNEXPECTED(variableFlow.isUndef())) return false;
			bool hasYield = false, isAlwaysTerminating = false;
			if (UNEXPECTED(!pt_expression_result_has_yield(exprResult, hasYield) || !pt_expression_result_is_always_terminating(exprResult, isAlwaysTerminating))) return false;
			zval *throwPoints = pt_expression_result_throw_points(exprResult, throwPointsHold);
			zval *impurePoints = throwPoints != NULL ? pt_expression_result_impure_points(exprResult, impurePointsHold) : NULL;
			if (UNEXPECTED(impurePoints == NULL)) return false;
			zv::Val specifyTypesCallback = pt_specified_types_empty_specify_callback();
			if (UNEXPECTED(specifyTypesCallback.isUndef())) return false;
			pt_expression_result_args storedArgs(resultScope, a.scopeToPass.raw(), value, hasYield, isAlwaysTerminating, throwPoints, impurePoints, NULL, specifyTypesCallback.raw());
			storedArgs.withVariableFlow(variableFlow.raw()).withType(types[0].raw()).withNativeType(types[1].raw());
			zv::Val storedArrowResult = pt_expression_result_create(slot(slots::expressionResultFactory), storedArgs);
			if (UNEXPECTED(storedArrowResult.isUndef())) return false;
			if (UNEXPECTED(!nsrStoreExpressionResult(w.nodeScopeResolver, w.storage, value, storedArrowResult.raw()))) return false;
			if (UNEXPECTED(!nsrCallNodeCallbackWithExpression(w.nodeScopeResolver, w.nodeCallback, value, a.scopeToPass.raw(), w.storage, w.context))) return false;
			storeArgResult(w, value, storedArrowResult.raw());
			bool invalidateCallback = false;
			if (UNEXPECTED(!shouldInvalidateCallbackExpressions(a.parameter, invalidateCallback))) return false;
			if (invalidateCallback) {
				zv::Val invalidateExpressions = callByName(types[0].raw(), PT_LC("getinvalidateexpressions"), "getInvalidateExpressions", 0, NULL);
				if (UNEXPECTED(invalidateExpressions.isUndef())) return false;
				zv::Val usedVariables = callByName(types[0].raw(), PT_LC("getusedvariables"), "getUsedVariables", 0, NULL);
				if (UNEXPECTED(usedVariables.isUndef())) return false;
				w.deferredInvalidateExpressions.push(std::move(invalidateExpressions));
				w.deferredUses.push(std::move(usedVariables));
			}
		}

		zval *argResult = zend_hash_index_find(w.argResults.table(), Z_OBJ_HANDLE_P(value));
		if (argResult == NULL) {
			zend_error(E_WARNING, "Undefined array key %u", Z_OBJ_HANDLE_P(value));
			if (UNEXPECTED(EG(exception))) return false;
			zend_throw_error(NULL, "Call to a member function getScope() on null");
			return false;
		}
		zv::Val argScopeHold;
		zval *argScope = pt_expression_result_scope(argResult, argScopeHold);
		if (UNEXPECTED(argScope == NULL)) return false;
		zv::Val constraints = pt_mutating_scope_get_template_argument_constraints(Z_OBJ_P(argScope));
		if (UNEXPECTED(constraints.isUndef())) return false;
		zv::Val constrained = pt_mutating_scope_add_template_argument_constraints(Z_OBJ_P(w.scope.raw()), constraints.raw());
		if (UNEXPECTED(constrained.isUndef())) return false;
		w.scope = std::move(constrained);
		return true;
	}

	/* the callable-argument bookkeeping of a non-closure argument whose type
	 * is callable with a single acceptor; false = pending exception */
	zend_never_inline bool processCallableArg(Walk &w, zval *value, zval *acceptor, bool invalidateCallback, bool immediately) const
	{
		if (invalidateCallback) {
			zv::Val invalidateExpressions = callByName(acceptor, PT_LC("getinvalidateexpressions"), "getInvalidateExpressions", 0, NULL);
			if (UNEXPECTED(invalidateExpressions.isUndef())) return false;
			zv::Val usedVariables = callByName(acceptor, PT_LC("getusedvariables"), "getUsedVariables", 0, NULL);
			if (UNEXPECTED(usedVariables.isUndef())) return false;
			w.deferredInvalidateExpressions.push(std::move(invalidateExpressions));
			w.deferredUses.push(std::move(usedVariables));
		}
		if (!immediately) return true;

		static pt_method_site throwPointsSite, impurePointsSite;
		zv::Val simpleThrowPoints = callOn(throwPointsSite, acceptor, PT_LC("getthrowpoints"), "getThrowPoints", 0, NULL);
		if (UNEXPECTED(simpleThrowPoints.isUndef() || !requireArray(simpleThrowPoints.raw(), "array_map(): Argument #2 ($array)"))) return false;
		zval *scope = w.scope.raw();
		zv::Arr callableThrowPoints = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(simpleThrowPoints.raw())));
		for (zv::ArrayEntry entry : zv::ArrRef(simpleThrowPoints.raw())) {
			zval *throwPoint = entry.value().deref().raw();
			zv::Val isExplicit = callOn(pt_ah_simple_throw_point_is_explicit_site, throwPoint, PT_LC("isexplicit"), "isExplicit", 0, NULL);
			if (UNEXPECTED(isExplicit.isUndef())) return false;
			zv::Val mapped;
			if (zend_is_true(isExplicit.raw())) {
				zv::Val type = callOn(pt_ah_simple_throw_point_get_type_site, throwPoint, PT_LC("gettype"), "getType", 0, NULL);
				if (UNEXPECTED(type.isUndef())) return false;
				zv::Val canContainAnyThrowable = callOn(pt_ah_simple_throw_point_can_contain_any_throwable_site, throwPoint, PT_LC("cancontainanythrowable"), "canContainAnyThrowable", 0, NULL);
				if (UNEXPECTED(canContainAnyThrowable.isUndef())) return false;
				mapped = pt_internal_throw_point_create_explicit(scope, type.raw(), value, zend_is_true(canContainAnyThrowable.raw()));
			} else {
				mapped = pt_internal_throw_point_create_implicit(scope, value);
			}
			if (UNEXPECTED(mapped.isUndef())) return false;
			setKey(callableThrowPoints, entry.stringKeyOrNull(), entry.indexKey(), mapped.raw());
		}
		if (!zend_is_true(slot(slots::implicitThrows))) {
			zv::Arr explicitOnly = zv::Arr::create(zend_hash_num_elements(callableThrowPoints.table()));
			for (zv::ArrayEntry entry : zv::TableRef(callableThrowPoints.table())) {
				bool isExplicit = false;
				if (UNEXPECTED(!pt_internal_throw_point_is_explicit(entry.value().raw(), isExplicit))) return false;
				if (isExplicit) {
					explicitOnly.push(zv::Ref(entry.value().raw()));
				}
			}
			callableThrowPoints = std::move(explicitOnly);
		}
		if (UNEXPECTED(!arrayMerge(w.throwPoints, callableThrowPoints.raw()))) return false;

		zv::Val simpleImpurePoints = callOn(impurePointsSite, acceptor, PT_LC("getimpurepoints"), "getImpurePoints", 0, NULL);
		if (UNEXPECTED(simpleImpurePoints.isUndef() || !requireArray(simpleImpurePoints.raw(), "array_map(): Argument #2 ($array)"))) return false;
		zv::Arr mappedImpurePoints = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(simpleImpurePoints.raw())));
		for (zv::ArrayEntry entry : zv::ArrRef(simpleImpurePoints.raw())) {
			zval *impurePoint = entry.value().deref().raw();
			zv::Val identifier = simpleImpurePointRead(impurePoint, ptdecl::SimpleImpurePoint::slot::identifier, PT_LC("getidentifier"), "getIdentifier");
			if (UNEXPECTED(identifier.isUndef())) return false;
			zv::Val description = simpleImpurePointRead(impurePoint, ptdecl::SimpleImpurePoint::slot::description, PT_LC("getdescription"), "getDescription");
			if (UNEXPECTED(description.isUndef())) return false;
			zv::Val certain = simpleImpurePointRead(impurePoint, ptdecl::SimpleImpurePoint::slot::certain, PT_LC("iscertain"), "isCertain");
			if (UNEXPECTED(certain.isUndef())) return false;
			if (UNEXPECTED(!identifier.ref().isString() || !description.ref().isString())) {
				zend_type_error("PHPStan\\Analyser\\ImpurePoint::__construct(): Argument #%d must be of type string, %s given", identifier.ref().isString() ? 4 : 3, zend_zval_value_name(identifier.ref().isString() ? description.raw() : identifier.raw()));
				return false;
			}
			zv::Val mapped = pt_impure_point_new(scope, value, Z_STR_P(identifier.raw()), Z_STR_P(description.raw()), zend_is_true(certain.raw()));
			if (UNEXPECTED(mapped.isUndef())) return false;
			setKey(mappedImpurePoints, entry.stringKeyOrNull(), entry.indexKey(), mapped.raw());
		}
		return arrayMerge(w.impurePoints, mappedImpurePoints.raw());
	}

	/* The argument loop re-enters the walk (processExprNode(), the closure
	 * body walks) once per nesting level of a call inside a call argument, so
	 * the frames on that path — processArgs(), processArg() and
	 * processOtherArg() — keep only what must survive the nested walk; every
	 * step before or after it lives in a never-inlined helper whose locals are
	 * gone by the time the walk recurses. */

	/* the post-walk half of the plain-expression branch; false = pending exception */
	zend_never_inline bool completeOtherArg(Walk &w, ArgRecord &record, zval *value, ArgLocals &a, zval *exprResult, bool enterExpressionAssignForByRef) const
	{
		storeArgResult(w, value, exprResult);
		zv::Val exprType = pt_expression_result_get_type(exprResult);
		if (UNEXPECTED(exprType.isUndef())) return false;
		{
			zv::Val throwPointsHold, impurePointsHold, scopeHold;
			zval *throwPoints = pt_expression_result_throw_points(exprResult, throwPointsHold);
			if (UNEXPECTED(throwPoints == NULL || !arrayMerge(w.throwPoints, throwPoints))) return false;
			zval *impurePoints = pt_expression_result_impure_points(exprResult, impurePointsHold);
			if (UNEXPECTED(impurePoints == NULL || !arrayMerge(w.impurePoints, impurePoints))) return false;
			if (!w.isAlwaysTerminating && UNEXPECTED(!pt_expression_result_is_always_terminating(exprResult, w.isAlwaysTerminating))) return false;
			zval *resultScope = pt_expression_result_scope(exprResult, scopeHold);
			if (UNEXPECTED(resultScope == NULL)) return false;
			w.scope = zv::Val::copyOf(zv::Ref(resultScope));
		}
		if (enterExpressionAssignForByRef) {
			zv::Val exited = pt_mutating_scope_exit_expression_assign(Z_OBJ_P(w.scope.raw()), Z_OBJ_P(value));
			if (UNEXPECTED(exited.isUndef())) return false;
			w.scope = std::move(exited);
		}
		if (!w.hasYield && UNEXPECTED(!pt_expression_result_has_yield(exprResult, w.hasYield))) return false;

		// only callable objects (closures) carry expressions to invalidate - asking
		// isCallable() of other arguments reflects the classes named by callable-like
		// strings and arrays, so it is skipped when nothing would come of it
		bool invalidateCallback = false;
		if (UNEXPECTED(!shouldInvalidateCallbackExpressions(a.parameter, invalidateCallback))) return false;
		if (invalidateCallback) {
			if (UNEXPECTED(!exprType.ref().isObject())) {
				zend_throw_error(NULL, "Call to a member function isObject() on %s", zend_zval_value_name(exprType.raw()));
				return false;
			}
			zend_long isObject = pt_type_call_trinary(Z_OBJ_P(exprType.raw()), PT_LC("isobject"), 0, NULL);
			if (UNEXPECTED(isObject < 0)) return false;
			invalidateCallback = isObject != PT_TRI_NO;
		}
		bool immediately = false;
		if (UNEXPECTED(!callCallbackImmediately(a.parameter, a.parameterType.isUndef() ? NULL : a.parameterType.raw(), w.calleeReflection, immediately))) return false;
		if (invalidateCallback || immediately) {
			if (UNEXPECTED(!exprType.ref().isObject())) {
				zend_throw_error(NULL, "Call to a member function isCallable() on %s", zend_zval_value_name(exprType.raw()));
				return false;
			}
			zend_long isCallable = pt_type_op_trinary(Z_OBJ_P(exprType.raw()), PT_OP_IS_CALLABLE, 0, NULL);
			if (UNEXPECTED(isCallable < 0)) return false;
			if (isCallable == PT_TRI_YES) {
				zv::Val acceptors = pt_type_call(Z_OBJ_P(exprType.raw()), PT_LC("getcallableparametersacceptors"), 1, w.scope.raw());
				if (UNEXPECTED(acceptors.isUndef() || !requireArray(acceptors.raw(), "count(): Argument #1 ($value)"))) return false;
				if (zend_hash_num_elements(Z_ARRVAL_P(acceptors.raw())) == 1) {
					zval *acceptor = readIndex(Z_ARRVAL_P(acceptors.raw()), 0);
					if (UNEXPECTED(acceptor == NULL || !processCallableArg(w, value, acceptor, invalidateCallback, immediately))) return false;
				}
			}
		}

		zv::Val gathered = pt_expression_result_get_type(exprResult);
		if (UNEXPECTED(gathered.isUndef())) return false;
		zval_ptr_dtor(&record.gathered);
		record.gathered = gathered.take();
		if (UNEXPECTED(!addGatheredArgType(w.gatheredTypes, w.gatheredUnpack, w.gatheredHasName, originalArgOf(record.arg), record.key, &record.gathered))) return false;
		zv::Val templateArgumentFrame = nsrObservingTemplateArgumentFrame(w.nodeScopeResolver, w.scope.raw());
		if (UNEXPECTED(templateArgumentFrame.isUndef())) return false;
		if (!templateArgumentFrame.isNull() && a.parameter != NULL) {
			// the metadata acceptor is resolved against the arguments gathered
			// before this one: observe the declared parameter type
			zv::Val parameterType = findOriginalParameterType(a.argMetadataAcceptor, a.parameter);
			if (UNEXPECTED(parameterType.isUndef())) return false;
			if (parameterType.isNull()) {
				parameterType = parameterGetType(a.parameter);
				if (UNEXPECTED(parameterType.isUndef())) return false;
			}
			bool isPure = false;
			bool isFunction = false, isExtendedMethod = false;
			if (UNEXPECTED(!isA(w.calleeReflection, PT_CLASS_FUNCTION_REFLECTION, isFunction))) return false;
			if (!isFunction && UNEXPECTED(!isA(w.calleeReflection, PT_CLASS_EXTENDED_METHOD_REFLECTION, isExtendedMethod))) return false;
			if (isFunction || isExtendedMethod) {
				zend_long pure = calleeIsPure(w.calleeReflection);
				if (UNEXPECTED(pure < 0)) return false;
				isPure = pure == PT_TRI_YES;
			}
			zv::Val constraints = templateArgumentObserverCollectArgument(slot(slots::templateArgumentObserver), parameterType.raw(), &record.gathered, isPure);
			if (UNEXPECTED(constraints.isUndef())) return false;
			zv::Val constrained = pt_mutating_scope_add_template_argument_constraints(Z_OBJ_P(w.scope.raw()), constraints.raw());
			if (UNEXPECTED(constrained.isUndef())) return false;
			w.scope = std::move(constrained);
		}
		return true;
	}

	/* the pre-walk half of the plain-expression branch: the by-ref dim-fetch
	 * assign entry and the argument's context; false = pending exception */
	zend_never_inline bool prepareOtherArg(Walk &w, ArgRecord &record, zval *value, ArgLocals &a, bool &enterExpressionAssignForByRef, zv::Val &argContext) const
	{
		enterExpressionAssignForByRef = false;
		if (a.assignByReference) {
			bool isDimFetch = false;
			if (UNEXPECTED(!isA(value, PT_CLASS_ARRAY_DIM_FETCH, isDimFetch))) return false;
			if (isDimFetch) {
				zv::Val dimHold;
				zval *dim = readProperty(pt_ah_array_dim_fetch_dim_site, value, PT_LC("dim"), dimHold);
				if (UNEXPECTED(dim == NULL)) return false;
				enterExpressionAssignForByRef = Z_TYPE_P(dim) == IS_NULL;
			}
		}
		if (enterExpressionAssignForByRef) {
			zv::Val entered = pt_mutating_scope_enter_expression_assign(Z_OBJ_P(a.scopeToPass.raw()), Z_OBJ_P(value), true);
			if (UNEXPECTED(entered.isUndef())) return false;
			a.scopeToPass = std::move(entered);
		}
		argContext = pt_expression_context_enter_deep(w.context);
		if (UNEXPECTED(argContext.isUndef())) return false;
		bool unpack = false;
		if (UNEXPECTED(!argUnpack(record.arg, unpack))) return false;
		if (!unpack) {
			bool isArray = false;
			if (UNEXPECTED(!isA(value, PT_CLASS_ARRAY_EXPR, isArray))) return false;
			if (isArray) {
				zv::Val passed = pt_expression_context_enter_passed_to_type(argContext.raw(), a.parameterType.isUndef() ? NULL : a.parameterType.raw(), a.parameterNativeType.isUndef() ? NULL : a.parameterNativeType.raw());
				if (UNEXPECTED(passed.isUndef())) return false;
				argContext = std::move(passed);
			}
		}
		return true;
	}

	/* the plain-expression branch of the argument loop; false = pending exception */
	bool processOtherArg(Walk &w, ArgRecord &record, zval *value, ArgLocals &a) const
	{
		if (UNEXPECTED(!prepareOtherArg(w, record, value, a, a.enterExpressionAssignForByRef, a.argContext))) return false;
		a.exprResult = nsrProcessExprNode(w.nodeScopeResolver, w.stmt, value, a.scopeToPass.raw(), w.storage, w.nodeCallback, a.argContext.raw());
		if (UNEXPECTED(a.exprResult.isUndef())) return false;
		return completeOtherArg(w, record, value, a, a.exprResult.raw(), a.enterExpressionAssignForByRef);
	}

	/* the closure-type gather ahead of a closure / arrow function argument's
	 * metadata; false = pending exception */
	zend_never_inline bool gatherClosureArg(Walk &w, ArgRecord &record, zval *value) const
	{
		// gather the closure/arrow type for the final resolved acceptor on
		// the evolving scope, before the body is processed
		zval *originalArgForGather = originalArgOf(record.arg);
		zv::Val gathered = w.typeDrivenAcceptorSelection
			? gatherClosureArgType(Z_ARRVAL_P(w.parametersAcceptors), record.key, value, w.scope.raw())
			: closureTypeResolverGetClosureType(slot(slots::closureTypeResolver), w.scope.raw(), value, true, w.storage);
		if (UNEXPECTED(gathered.isUndef())) return false;
		zval_ptr_dtor(&record.gathered);
		record.gathered = gathered.take();
		return addGatheredArgType(w.gatheredTypes, w.gatheredUnpack, w.gatheredHasName, originalArgForGather, record.key, &record.gathered);
	}

	/* everything of one iteration ahead of the branches: the metadata
	 * acceptor, the matched parameter, the by-ref bookkeeping, the
	 * in-function-call push, the argument's node callback and the scope to
	 * pass; false = pending exception */
	zend_never_inline bool prepareArg(Walk &w, ArgRecord &record, zval *value, bool isClosureLike, ArgLocals &a, zv::Val &originalScope) const
	{
		zval *arg = record.arg;
		zend_ulong i = record.key;
		a.argMetadataAcceptor = w.metadataAcceptor;
		if (w.metadataAcceptor != NULL && w.argMetadataIsTypeDriven) {
			bool consumes = false;
			if (UNEXPECTED(!argConsumesResolvedParameterType(value, consumes))) return false;
			if (consumes) {
				// resolve the acceptor for this argument from the args gathered so
				// far, padded to the full argument count with mixed
				a.argMetadataAcceptorHold = paddedMetadataAcceptor(w, false);
				if (UNEXPECTED(a.argMetadataAcceptorHold.isUndef())) return false;
				a.argMetadataAcceptor = a.argMetadataAcceptorHold.raw();
			} else {
				// one all-mixed count-stable selection serves every argument not
				// consuming the resolved parameter type
				if (w.countStableMetadataAcceptor.isUndef()) {
					w.countStableMetadataAcceptor = paddedMetadataAcceptor(w, true);
					if (UNEXPECTED(w.countStableMetadataAcceptor.isUndef())) return false;
				}
				a.argMetadataAcceptor = w.countStableMetadataAcceptor.raw();
			}
		}

		if (a.argMetadataAcceptor != NULL) {
			a.parameters = acceptorGetParameters(a.argMetadataAcceptor);
			if (UNEXPECTED(a.parameters.isUndef() || !requireArray(a.parameters.raw(), "getParameters()"))) return false;
			HashTable *parameters = Z_ARRVAL_P(a.parameters.raw());
			zval *matchedParameter = NULL;
			zv::Val nameHold;
			zval *name = argName(arg, nameHold);
			if (UNEXPECTED(name == NULL)) return false;
			if (Z_TYPE_P(name) != IS_NULL) {
				for (zv::ArrayEntry entry : zv::TableRef(parameters)) {
					zval *p = entry.value().deref().raw();
					zv::Val parameterName = parameterGetName(p);
					if (UNEXPECTED(parameterName.isUndef())) return false;
					zv::Val argNameString = identifierToString(name);
					if (UNEXPECTED(argNameString.isUndef())) return false;
					if (parameterName.ref().isString() && argNameString.ref().isString() && zend_string_equals(Z_STR_P(parameterName.raw()), Z_STR_P(argNameString.raw()))) {
						matchedParameter = p;
						break;
					}
				}
			} else {
				matchedParameter = issetIndex(parameters, i);
			}

			if (matchedParameter == NULL && zend_hash_num_elements(parameters) > 0) {
				bool isVariadic = false;
				if (UNEXPECTED(!acceptorIsVariadic(a.argMetadataAcceptor, isVariadic))) return false;
				if (isVariadic) {
					matchedParameter = arrayLast(parameters);
					ZVAL_DEREF(matchedParameter);
				}
			}
			if (matchedParameter != NULL) {
				if (UNEXPECTED(!parameterCreatesNewVariable(matchedParameter, a.assignByReference))) return false;
				a.parameterType = parameterGetType(matchedParameter);
				if (UNEXPECTED(a.parameterType.isUndef())) return false;
				bool isExtended = false;
				if (UNEXPECTED(!isA(matchedParameter, PT_CLASS_EXTENDED_PARAMETER_REFLECTION, isExtended))) return false;
				if (isExtended) {
					a.parameterNativeType = parameterGetNativeType(matchedParameter);
					if (UNEXPECTED(a.parameterNativeType.isUndef())) return false;
				}
				a.parameter = matchedParameter;
			}
		}

		if (a.parameter != NULL) {
			bool no = false;
			if (UNEXPECTED(!parameterPassedByReferenceNo(a.parameter, no))) return false;
			if (!no) {
				zval trueZv;
				ZVAL_TRUE(&trueZv);
				w.byRefArguments.arrRef().setIndex(Z_OBJ_HANDLE_P(value), zv::Ref(&trueZv));
			}
		}
		if (a.assignByReference) {
			bool isBuiltin = false;
			bool isFunction = false;
			if (UNEXPECTED(!isA(w.calleeReflection, PT_CLASS_FUNCTION_REFLECTION, isFunction))) return false;
			if (isFunction && UNEXPECTED(!calleeIsBuiltin(w.calleeReflection, isBuiltin))) return false;
			if (!isBuiltin) {
				bool isExtendedMethod = false;
				if (UNEXPECTED(!isA(w.calleeReflection, PT_CLASS_EXTENDED_METHOD_REFLECTION, isExtendedMethod))) return false;
				if (isExtendedMethod && UNEXPECTED(!calleeDeclaringClassIsBuiltin(w.calleeReflection, isBuiltin))) return false;
			}
			bool nullableNative = a.parameterNativeType.isUndef();
			if (!isBuiltin && !nullableNative) {
				zend_long isNull = pt_type_op_trinary(Z_OBJ_P(a.parameterNativeType.raw()), PT_OP_IS_NULL, 0, NULL);
				if (UNEXPECTED(isNull < 0)) return false;
				nullableNative = isNull != PT_TRI_NO;
			}
			if (isBuiltin || nullableNative) {
				zv::Val scope = nsrLookForSetAllowedUndefinedExpressions(w.nodeScopeResolver, w.scope.raw(), value);
				if (UNEXPECTED(scope.isUndef())) return false;
				w.scope = std::move(scope);
				a.lookForUnset = true;
			}
		}

		zval *originalArg = originalArgOf(arg);
		zval null;
		ZVAL_NULL(&null);
		if (Z_TYPE_P(w.calleeReflection) != IS_NULL) {
			zv::Val originalValueHold;
			zval *originalValue = argValue(originalArg, originalValueHold);
			if (UNEXPECTED(originalValue == NULL)) return false;
			bool originalIsClosure, originalIsArrowFunction;
			if (UNEXPECTED(!closureKind(originalValue, originalIsClosure, originalIsArrowFunction))) return false;
			zv::Val pushed = pt_mutating_scope_push_in_function_call(Z_OBJ_P(w.scope.raw()), w.calleeReflection, a.parameter != NULL ? a.parameter : &null, !originalIsClosure && !originalIsArrowFunction);
			if (UNEXPECTED(pushed.isUndef())) return false;
			w.scope = std::move(pushed);
		}

		if (UNEXPECTED(!nsrCallNodeCallback(w.nodeScopeResolver, w.nodeCallback, originalArg, w.scope.raw(), w.storage))) return false;

		originalScope = zv::Val::copyOf(zv::Ref(w.scope.raw()));
		a.scopeToPass = zv::Val::copyOf(zv::Ref(w.scope.raw()));
		if (i == 0 && w.closureBindScopeFactory != NULL && isClosureLike) {
			a.scopeToPass = pt_type_call_callable(w.closureBindScopeFactory, 1, w.scope.raw());
			if (UNEXPECTED(a.scopeToPass.isUndef())) return false;
		}
		return true;
	}

	/* everything of one iteration after the branches; false = pending exception */
	zend_never_inline bool finishArg(Walk &w, ArgRecord &record, zval *value, ArgLocals &a, zval *originalScope) const
	{
		if (a.assignByReference && a.lookForUnset) {
			zv::Val scope = nsrLookForUnsetAllowedUndefinedExpressions(w.nodeScopeResolver, w.scope.raw(), value);
			if (UNEXPECTED(scope.isUndef())) return false;
			w.scope = std::move(scope);
		}

		if (Z_TYPE_P(w.calleeReflection) != IS_NULL) {
			zv::Val popped = pt_mutating_scope_pop_in_function_call(Z_OBJ_P(w.scope.raw()));
			if (UNEXPECTED(popped.isUndef())) return false;
			w.scope = std::move(popped);
		}

		if (record.key != 0 || w.closureBindScopeFactory == NULL) return true;

		zv::Val restored = pt_mutating_scope_restore_original_scope_after_closure_bind(Z_OBJ_P(w.scope.raw()), originalScope);
		if (UNEXPECTED(restored.isUndef())) return false;
		w.scope = std::move(restored);
		return true;
	}

	/* one iteration of the argument loop; false = pending exception */
	bool processArg(Walk &w, ArgRecord &record) const
	{
		ArgLocals &a = w.current;
		a.reset();
		zval *value = argValue(record.arg, a.valueHold);
		if (UNEXPECTED(value == NULL)) return false;
		if (UNEXPECTED(Z_TYPE_P(value) != IS_OBJECT)) {
			/* Arg::$value is a typed Expr property — only a foreign argument
			 * object gets here; the twin would fail on its first use */
			zend_type_error("PhpParser\\Node\\Arg::$value must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(value));
			return false;
		}
		bool isClosure, isArrowFunction;
		if (UNEXPECTED(!closureKind(value, isClosure, isArrowFunction))) return false;
		if (isClosure || isArrowFunction) {
			if (UNEXPECTED(!gatherClosureArg(w, record, value))) return false;
		} else if (w.argMetadataIsTypeDriven) {
			/* && !$arg->unpack && $arg->value instanceof Expr\Array_
			 * && $this->argConsumesResolvedParameterType($arg->value) */
			bool unpack;
			if (UNEXPECTED(!argUnpack(record.arg, unpack))) return false;
			if (!unpack) {
				bool isArray = false;
				zend_class_entry *arrayExpr = pt_class(PT_CLASS_ARRAY_EXPR);
				if (UNEXPECTED(arrayExpr == NULL)) return false;
				isArray = instanceof_function(Z_OBJCE_P(value), arrayExpr);
				bool consumes = false;
				if (isArray && UNEXPECTED(!argConsumesResolvedParameterType(value, consumes))) return false;
				if (isArray && consumes) {
					// An array literal holding closures decides the templates of its own
					// parameter through its keys and its non-closure values - and those
					// very templates type the closures nested in it. Pin a skeleton of the
					// array so the per-argument resolution below sees them; the walk that
					// follows overwrites it with the real type.
					zv::Val skeleton = gatherArrayArgTypeSkeleton(w.nodeScopeResolver, value, w.scope.raw());
					if (UNEXPECTED(skeleton.isUndef())) return false;
					zval_ptr_dtor(&record.gathered);
					record.gathered = skeleton.take();
				}
			}
		}

		if (UNEXPECTED(!prepareArg(w, record, value, isClosure || isArrowFunction, a, a.originalScope))) return false;

		if (isClosure) {
			if (UNEXPECTED(!processClosureArg(w, value, a))) return false;
		} else if (isArrowFunction) {
			if (UNEXPECTED(!processArrowFunctionArg(w, value, a))) return false;
		} else {
			if (UNEXPECTED(!processOtherArg(w, record, value, a))) return false;
		}

		return finishArg(w, record, value, a, a.originalScope.raw());
	}

	/* the callee-side effects on one argument after the loop: the by-ref OUT
	 * writeback or the invalidation of an object / resource argument;
	 * false = pending exception */
	zend_never_inline bool writebackArg(Walk &w, ArgRecord &record, zval *writebackAcceptor, HashTable *writebackParameters) const
	{
		zend_ulong i = record.key;
		bool assignByReference = false;
		zval *currentParameter = issetIndex(writebackParameters, i);
		if (currentParameter == NULL && zend_hash_num_elements(writebackParameters) > 0) {
			bool isVariadic = false;
			if (UNEXPECTED(!acceptorIsVariadic(writebackAcceptor, isVariadic))) return false;
			if (isVariadic) {
				currentParameter = arrayLast(writebackParameters);
				ZVAL_DEREF(currentParameter);
			}
		}
		if (currentParameter != NULL && Z_TYPE_P(currentParameter) != IS_NULL && UNEXPECTED(!parameterCreatesNewVariable(currentParameter, assignByReference))) return false;

		zv::Val valueHold;
		zval *argValueZv = argValue(record.arg, valueHold);
		if (UNEXPECTED(argValueZv == NULL)) return false;

		if (assignByReference) {
			bool isVariable = false;
			if (UNEXPECTED(!isA(argValueZv, PT_CLASS_VARIABLE, isVariable))) return false;
			bool isThis = false;
			if (isVariable) {
				zv::Val nameHold;
				zval *name = readProperty(pt_ah_variable_name_site, argValueZv, PT_LC("name"), nameHold);
				if (UNEXPECTED(name == NULL)) return false;
				isThis = Z_TYPE_P(name) == IS_STRING && zend_string_equals(Z_STR_P(name), pt_ah_this);
			}
			if (isThis) return true;

			zv::Val byRefType = getParameterOutExtensionsType(w.callLike, w.calleeReflection, currentParameter, w.scope.raw());
			if (UNEXPECTED(byRefType.isUndef())) return false;
			if (byRefType.isNull()) {
				bool resolved = false;
				bool isExtended = false;
				if (UNEXPECTED(!isA(currentParameter, PT_CLASS_EXTENDED_PARAMETER_REFLECTION, isExtended))) return false;
				if (isExtended) {
					zv::Val outType = parameterGetOutType(currentParameter);
					if (UNEXPECTED(outType.isUndef())) return false;
					if (!outType.isNull()) {
						byRefType = parameterGetOutType(currentParameter);
						if (UNEXPECTED(byRefType.isUndef())) return false;
						resolved = true;
					}
				}
				if (!resolved) {
					bool isMethod = false, isFunction = false, builtin = false;
					if (UNEXPECTED(!isA(w.calleeReflection, PT_CLASS_METHOD_REFLECTION, isMethod))) return false;
					if (isMethod && UNEXPECTED(!calleeDeclaringClassIsBuiltin(w.calleeReflection, builtin))) return false;
					if (isMethod && !builtin) {
						byRefType = parameterGetType(currentParameter);
						resolved = true;
					} else {
						if (UNEXPECTED(!isA(w.calleeReflection, PT_CLASS_FUNCTION_REFLECTION, isFunction))) return false;
						builtin = false;
						if (isFunction && UNEXPECTED(!calleeIsBuiltin(w.calleeReflection, builtin))) return false;
						if (isFunction && !builtin) {
							byRefType = parameterGetType(currentParameter);
							resolved = true;
						}
					}
					if (!resolved) {
						byRefType = pt_type_new_mixed_type();
					}
					if (UNEXPECTED(byRefType.isUndef())) return false;
				}
			}

			// what the call writes back is described by PHPDoc (@param, @param-out,
			// a parameter-out extension) - natively only the parameter's own
			// type declaration is guaranteed
			bool currentIsExtended = false;
			if (UNEXPECTED(!isA(currentParameter, PT_CLASS_EXTENDED_PARAMETER_REFLECTION, currentIsExtended))) return false;
			zv::Val byRefNativeType = currentIsExtended ? parameterGetNativeType(currentParameter) : zv::Val::copyOf(byRefType.ref());
			if (UNEXPECTED(byRefNativeType.isUndef())) return false;

			zv::Val typeExpr = newNativeTypeExpr(byRefType.raw(), byRefNativeType.raw());
			if (UNEXPECTED(typeExpr.isUndef())) return false;
			zv::Val assignResult = assignHandlerProcessVirtualAssign(slot(slots::assignHandler), w.nodeScopeResolver, w.scope.raw(), w.storage, w.stmt, argValueZv, typeExpr.raw(), w.nodeCallback);
			if (UNEXPECTED(assignResult.isUndef())) return false;
			zv::Val assignScopeHold;
			zval *assignScope = pt_expression_result_scope(assignResult.raw(), assignScopeHold);
			if (UNEXPECTED(assignScope == NULL)) return false;
			w.scope = zv::Val::copyOf(zv::Ref(assignScope));
			zv::Val scope = nsrLookForUnsetAllowedUndefinedExpressions(w.nodeScopeResolver, w.scope.raw(), argValueZv);
			if (UNEXPECTED(scope.isUndef())) return false;
			w.scope = std::move(scope);
			return true;
		}

		if (Z_TYPE_P(w.calleeReflection) == IS_NULL) return true;
		zend_long hasSideEffects = calleeHasSideEffects(w.calleeReflection);
		if (UNEXPECTED(hasSideEffects < 0)) return false;
		if (hasSideEffects != PT_TRI_YES) return true;

		zval *argResult = readArgResult(w.argResults.table(), argValueZv);
		if (UNEXPECTED(argResult == NULL)) return false;
		zv::Val argType = pt_expression_result_get_type_on_scope(argResult, w.scope.raw(), false);
		if (UNEXPECTED(argType.isUndef())) return false;
		if (UNEXPECTED(!argType.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function isObject() on %s", zend_zval_value_name(argType.raw()));
			return false;
		}
		zend_long isObject = pt_type_call_trinary(Z_OBJ_P(argType.raw()), PT_LC("isobject"), 0, NULL);
		if (UNEXPECTED(isObject < 0)) return false;
		bool invalidate = false;
		if (isObject != PT_TRI_NO) {
			zv::Val nakedReturnType;
			if (w.nakedMethodReflection != NULL) {
				zv::Val variants = calleeGetVariants(w.nakedMethodReflection);
				if (UNEXPECTED(variants.isUndef())) return false;
				zv::Val namedVariants = calleeGetNamedArgumentsVariants(w.nakedMethodReflection);
				if (UNEXPECTED(namedVariants.isUndef())) return false;
				zv::Val nakedParametersAcceptor = selectArgsAcceptor(w.gatheredTypes.raw(), variants.raw(), namedVariants.raw(), w.gatheredHasName, w.gatheredUnpack);
				if (UNEXPECTED(nakedParametersAcceptor.isUndef())) return false;
				nakedReturnType = acceptorGetReturnType(nakedParametersAcceptor.raw());
				if (UNEXPECTED(nakedReturnType.isUndef())) return false;
			}
			invalidate = nakedReturnType.isNull();
			if (!invalidate) {
				zv::Val declaringClass = calleeGetDeclaringClass(w.nakedMethodReflection);
				if (UNEXPECTED(declaringClass.isUndef())) return false;
				zval thisType;
				if (UNEXPECTED(!pt_this_type_new(&thisType, declaringClass.raw()))) return false;
				zv::Val thisTypeHold = zv::Val::adopt(thisType);
				zv::Val isSuperType = pt_type_op(Z_OBJ_P(thisTypeHold.raw()), PT_OP_IS_SUPER_TYPE_OF, 1, nakedReturnType.raw());
				if (UNEXPECTED(isSuperType.isUndef())) return false;
				zend_long isSuperTypeValue = pt_type_result_trinary(isSuperType.raw());
				if (UNEXPECTED(isSuperTypeValue < 0)) return false;
				invalidate = isSuperTypeValue != PT_TRI_YES;
			}
			if (!invalidate) {
				zend_long isPure = calleeIsPure(w.nakedMethodReflection);
				if (UNEXPECTED(isPure < 0)) return false;
				invalidate = isPure == PT_TRI_NO;
			}
		} else {
			zval resourceType;
			if (UNEXPECTED(!pt_resource_type_new(&resourceType))) return false;
			zv::Val resourceTypeHold = zv::Val::adopt(resourceType);
			zv::Val isSuperType = pt_type_op(Z_OBJ_P(resourceTypeHold.raw()), PT_OP_IS_SUPER_TYPE_OF, 1, argType.raw());
			if (UNEXPECTED(isSuperType.isUndef())) return false;
			zend_long isSuperTypeValue = pt_type_result_trinary(isSuperType.raw());
			if (UNEXPECTED(isSuperTypeValue < 0)) return false;
			invalidate = isSuperTypeValue != PT_TRI_NO;
		}
		if (!invalidate) return true;

		zv::Val invalidateNode = newInvalidateExprNode(argValueZv);
		if (UNEXPECTED(invalidateNode.isUndef())) return false;
		if (UNEXPECTED(!nsrCallNodeCallback(w.nodeScopeResolver, w.nodeCallback, invalidateNode.raw(), w.scope.raw(), w.storage))) return false;
		zv::Val invalidated = pt_mutating_scope_invalidate_expression(Z_OBJ_P(w.scope.raw()), argValueZv, true);
		if (UNEXPECTED(invalidated.isUndef())) return false;
		w.scope = std::move(invalidated);
		return true;
	}

	/* static fn () => new MixedType() */
	static void mixedTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) captures;
		(void) argc;
		(void) argv;
		zv::Val type = pt_type_new_mixed_type();
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* }}} */

public:
	/* Mirrors processArgs() ($nakedMethodReflection / $namedArgumentsVariants /
	 * $closureBindScopeFactory NULL or IS_NULL for null, $calleeReflection
	 * IS_NULL for null; everything borrowed) */
	zv::Val processArgs(zval *nodeScopeResolver, zval *stmt, zval *calleeReflection, zval *nakedMethodReflection, zval *parametersAcceptors, zval *namedArgumentsVariants, zval *callLike, zval *scope, zval *storage, zval *nodeCallback, zval *context, zval *closureBindScopeFactory) const
	{
		zval null;
		ZVAL_NULL(&null);
		WalkHolder holder;
		if (UNEXPECTED(!startArgs(holder, &null, nodeScopeResolver, stmt, calleeReflection, nakedMethodReflection, parametersAcceptors, namedArgumentsVariants, callLike, scope, storage, nodeCallback, context, closureBindScopeFactory))) return zv::Val();
		Walk &w = *holder.walk;
		/* the argument walks recurse into the call's nested expressions: on a
		 * fresh C stack segment when the current one runs low (Engine.h) */
		bool ok = true;
		pt_engine_with_stack([&]() {
			for (uint32_t k = 0; k < w.records.count; k++) {
				if (UNEXPECTED(!processArg(w, w.records.records[w.records.order[k]]))) {
					ok = false;
					return;
				}
			}
		});
		if (UNEXPECTED(!ok)) return zv::Val();

		return finishArgs(w, nodeScopeResolver, parametersAcceptors, callLike);
	}

private:
	/* everything ahead of the argument loop: the arguments, the metadata
	 * acceptor and whether its selection is type-driven, the processing
	 * order; false = pending exception */
	zend_never_inline bool startArgs(WalkHolder &holder, zval *null, zval *nodeScopeResolver, zval *stmt, zval *calleeReflection, zval *nakedMethodReflection, zval *parametersAcceptors, zval *namedArgumentsVariants, zval *callLike, zval *scope, zval *storage, zval *nodeCallback, zval *context, zval *closureBindScopeFactory) const
	{
		zv::Val argsVal = callArgs(callLike);
		if (UNEXPECTED(argsVal.isUndef() || !requireArray(argsVal.raw(), "getArgs()"))) return false;
		holder.allocate(zend_hash_num_elements(Z_ARRVAL_P(argsVal.raw())));

		Walk &w = *holder.walk;
		w.nodeScopeResolver = nodeScopeResolver;
		w.stmt = stmt;
		w.calleeReflection = calleeReflection != NULL ? calleeReflection : null;
		w.nakedMethodReflection = nakedMethodReflection != NULL && Z_TYPE_P(nakedMethodReflection) != IS_NULL ? nakedMethodReflection : NULL;
		w.parametersAcceptors = parametersAcceptors;
		w.namedArgumentsVariants = namedArgumentsVariants != NULL ? namedArgumentsVariants : null;
		w.callLike = callLike;
		w.storage = storage;
		w.nodeCallback = nodeCallback;
		w.context = context;
		w.closureBindScopeFactory = closureBindScopeFactory != NULL && Z_TYPE_P(closureBindScopeFactory) != IS_NULL ? closureBindScopeFactory : NULL;
		w.argsHold = std::move(argsVal);
		w.args = w.argsHold.raw();
		w.scope = zv::Val::copyOf(zv::Ref(scope));

		HashTable *acceptors = Z_ARRVAL_P(parametersAcceptors);
		uint32_t acceptorCount = zend_hash_num_elements(acceptors);
		w.metadataAcceptor = zend_hash_index_find(acceptors, 0);
		if (w.metadataAcceptor != NULL) {
			ZVAL_DEREF(w.metadataAcceptor);
			if (Z_TYPE_P(w.metadataAcceptor) == IS_NULL) {
				w.metadataAcceptor = NULL;
			}
		}

		// whether selecting an acceptor is type-driven at all
		w.typeDrivenAcceptorSelection = acceptorCount > 1 || Z_TYPE_P(w.namedArgumentsVariants) != IS_NULL;
		if (!w.typeDrivenAcceptorSelection && w.metadataAcceptor != NULL && UNEXPECTED(!selectorHasAcceptorTemplateOrLateResolvableType(w.metadataAcceptor, w.typeDrivenAcceptorSelection))) return false;
		bool hasTemplateParameterType = false;
		if (w.metadataAcceptor != NULL && UNEXPECTED(!selectorHasAcceptorTemplateOrLateResolvableParameterType(w.metadataAcceptor, hasTemplateParameterType))) return false;
		w.argMetadataIsTypeDriven = acceptorCount > 1 || hasTemplateParameterType;

		return buildProcessingOrder(w.records, Z_ARRVAL_P(w.args));
	}

	/* everything after the argument loop: the deferred invalidations and
	 * by-ref closure scopes, the resolved acceptor and its template
	 * observation, the by-ref writeback and the result (never inlined into
	 * processArgs(), whose frame stays on the stack through the nested
	 * walks) */
	zend_never_inline zv::Val finishArgs(Walk &w, zval *nodeScopeResolver, zval *parametersAcceptors, zval *callLike) const
	{
		uint32_t acceptorCount = zend_hash_num_elements(Z_ARRVAL_P(parametersAcceptors));
		zval *closureProcessor = slot(slots::closureProcessor);
		uint32_t deferredCount = zend_hash_num_elements(w.deferredInvalidateExpressions.table());
		for (uint32_t k = 0; k < deferredCount; k++) {
			zv::Val processed = closureProcessorProcessImmediatelyCalledCallable(closureProcessor, w.scope.raw(), zend_hash_index_find(w.deferredInvalidateExpressions.table(), k), zend_hash_index_find(w.deferredUses.table(), k));
			if (UNEXPECTED(processed.isUndef())) return zv::Val();
			w.scope = std::move(processed);
		}
		for (zv::ArrayEntry entry : zv::TableRef(w.deferredByRefClosureResults.table())) {
			zv::Val applied = processClosureResultApplyByRefUseScope(entry.value().raw(), w.scope.raw());
			if (UNEXPECTED(applied.isUndef())) return zv::Val();
			w.scope = std::move(applied);
		}

		// the arg types gathered on the evolving scope select (and
		// generic-resolve) the acceptor that drives the call's return type
		zv::Val resolvedAcceptorHold;
		zval *resolvedAcceptor = NULL;
		if (acceptorCount != 0) {
			if (w.typeDrivenAcceptorSelection) {
				resolvedAcceptorHold = selectArgsMetadataAcceptor(nodeScopeResolver, w.args, w.gatheredTypes.raw(), parametersAcceptors, w.namedArgumentsVariants, w.gatheredHasName, w.gatheredUnpack, w.scope.raw());
				if (UNEXPECTED(resolvedAcceptorHold.isUndef())) return zv::Val();
				resolvedAcceptor = resolvedAcceptorHold.raw();
			} else {
				resolvedAcceptor = w.metadataAcceptor;
			}
		}

		if (resolvedAcceptor != NULL) {
			zv::Val frame = nsrObservingTemplateArgumentFrame(nodeScopeResolver, w.scope.raw());
			if (UNEXPECTED(frame.isUndef())) return zv::Val();
			if (!frame.isNull()) {
				zv::Val classTemplates = zv::Val::null();
				bool isNew = false, isMethod = false;
				if (UNEXPECTED(!isA(callLike, PT_CLASS_NEW, isNew))) return zv::Val();
				if (isNew && UNEXPECTED(!isA(w.calleeReflection, PT_CLASS_METHOD_REFLECTION, isMethod))) return zv::Val();
				if (isNew && isMethod) {
					zv::Val declaringClass = calleeGetDeclaringClass(w.calleeReflection);
					if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
					classTemplates = callByName(declaringClass.raw(), PT_LC("gettemplatetypemap"), "getTemplateTypeMap", 0, NULL);
					if (UNEXPECTED(classTemplates.isUndef())) return zv::Val();
				}
				zv::Val constraints = templateArgumentObserverCollectCall(slot(slots::templateArgumentObserver), callLike, resolvedAcceptor, w.gatheredTypes.raw(), classTemplates.raw());
				if (UNEXPECTED(constraints.isUndef())) return zv::Val();
				zv::Val constrained = pt_mutating_scope_add_template_argument_constraints(Z_OBJ_P(w.scope.raw()), constraints.raw());
				if (UNEXPECTED(constrained.isUndef())) return zv::Val();
				w.scope = std::move(constrained);
			}
		}

		// the by-ref OUT writeback reads the metadata acceptor, or the resolved
		// one when the metadata selection is type-driven
		zval *writebackAcceptor = w.metadataAcceptor;
		if (w.metadataAcceptor != NULL && w.argMetadataIsTypeDriven) {
			writebackAcceptor = resolvedAcceptor;
		}
		if (writebackAcceptor != NULL) {
			zv::Val writebackParameters = acceptorGetParameters(writebackAcceptor);
			if (UNEXPECTED(writebackParameters.isUndef() || !requireArray(writebackParameters.raw(), "getParameters()"))) return zv::Val();
			for (uint32_t k = 0; k < w.records.count; k++) {
				if (UNEXPECTED(!writebackArg(w, w.records.records[k], writebackAcceptor, Z_ARRVAL_P(writebackParameters.raw())))) return zv::Val();
			}
		}

		// not storing this, it's scope after processing all args
		zv::Val typeCallback = pt_native_closure(&mixedTypeCallbackBody);
		zv::Val specifyTypesCallback = pt_specified_types_empty_specify_callback();
		if (UNEXPECTED(specifyTypesCallback.isUndef())) return zv::Val();
		pt_expression_result_args resultArgs(w.scope.raw(), w.scope.raw(), callLike, w.hasYield, w.isAlwaysTerminating, w.throwPoints.raw(), w.impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		zv::Val expressionResult = pt_expression_result_create(slot(slots::expressionResultFactory), resultArgs);
		if (UNEXPECTED(expressionResult.isUndef())) return zv::Val();

		return pt_args_result_new(expressionResult.raw(), resolvedAcceptor, w.argResults.raw(), w.byRefArguments.raw());
	}

public:
	/* Mirrors processDroppedArgs(); false = pending exception */
	bool processDroppedArgs(zval *nodeScopeResolver, zval *stmt, zval *originalCall, zval *normalizedCall, zval *scope, zval *storage, zval *context) const
	{
		if (Z_OBJ_P(originalCall) == Z_OBJ_P(normalizedCall)) return true;

		zv::Val normalizedArgs = callArgs(normalizedCall);
		if (UNEXPECTED(normalizedArgs.isUndef() || !requireArray(normalizedArgs.raw(), "foreach() argument"))) return false;
		zv::ScratchTable keptValueIds(zend_hash_num_elements(Z_ARRVAL_P(normalizedArgs.raw())));
		for (zv::ArrayEntry entry : zv::ArrRef(normalizedArgs.raw())) {
			zv::Val valueHold;
			zval *value = argValue(entry.value().deref().raw(), valueHold);
			if (UNEXPECTED(value == NULL)) return false;
			if (UNEXPECTED(Z_TYPE_P(value) != IS_OBJECT)) {
				zend_type_error("spl_object_id(): Argument #1 ($obj) must be of type object, %s given", zend_zval_value_name(value));
				return false;
			}
			zend_hash_index_add_empty_element(keptValueIds.table(), Z_OBJ_HANDLE_P(value));
		}

		zv::Val originalArgs = callArgs(originalCall);
		if (UNEXPECTED(originalArgs.isUndef() || !requireArray(originalArgs.raw(), "foreach() argument"))) return false;
		for (zv::ArrayEntry entry : zv::ArrRef(originalArgs.raw())) {
			zv::Val valueHold;
			zval *value = argValue(entry.value().deref().raw(), valueHold);
			if (UNEXPECTED(value == NULL)) return false;
			if (UNEXPECTED(Z_TYPE_P(value) != IS_OBJECT)) {
				zend_type_error("spl_object_id(): Argument #1 ($obj) must be of type object, %s given", zend_zval_value_name(value));
				return false;
			}
			if (zend_hash_index_exists(keptValueIds.table(), Z_OBJ_HANDLE_P(value))) continue;

			zv::Val noopNodeCallback = newNoopNodeCallback();
			if (UNEXPECTED(noopNodeCallback.isUndef())) return false;
			zv::Val deepContext = pt_expression_context_enter_deep(context);
			if (UNEXPECTED(deepContext.isUndef())) return false;
			zv::Val argContext = pt_expression_context_without_template_argument_resolution(deepContext.raw());
			if (UNEXPECTED(argContext.isUndef())) return false;
			zv::Val result = nsrProcessExprNode(nodeScopeResolver, stmt, value, scope, storage, noopNodeCallback.raw(), argContext.raw());
			if (UNEXPECTED(result.isUndef())) return false;
		}
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::ArgumentsHandler;

zv::Val pt_arguments_handler_process_args(zval *handler, zval *nodeScopeResolver, zval *stmt, zval *calleeReflection, zval *nakedMethodReflection, zval *parametersAcceptors, zval *namedArgumentsVariants, zval *callLike, zval *scope, zval *storage, zval *nodeCallback, zval *context, zval *closureBindScopeFactory)
{
	if (EXPECTED(Z_OBJCE_P(handler) == pt_ce_arguments_handler && Z_TYPE_P(parametersAcceptors) == IS_ARRAY && (namedArgumentsVariants == NULL || Z_TYPE_P(namedArgumentsVariants) == IS_ARRAY || Z_TYPE_P(namedArgumentsVariants) == IS_NULL) && Z_TYPE_P(callLike) == IS_OBJECT)) return ArgumentsHandler(Z_OBJ_P(handler)).processArgs(nodeScopeResolver, stmt, calleeReflection, nakedMethodReflection, parametersAcceptors, namedArgumentsVariants, callLike, scope, storage, nodeCallback, context, closureBindScopeFactory);
	zval null;
	ZVAL_NULL(&null);
	zv::Args argv{nodeScopeResolver, stmt, calleeReflection != NULL ? calleeReflection : &null, nakedMethodReflection != NULL ? nakedMethodReflection : &null, parametersAcceptors, namedArgumentsVariants != NULL ? namedArgumentsVariants : &null, callLike, scope, storage, nodeCallback, context, closureBindScopeFactory != NULL ? closureBindScopeFactory : &null};
	return pt_type_call(Z_OBJ_P(handler), PT_LC("processargs"), 12, argv);
}

bool pt_arguments_handler_process_dropped_args(zval *handler, zval *nodeScopeResolver, zval *stmt, zval *originalCall, zval *normalizedCall, zval *scope, zval *storage, zval *context)
{
	if (EXPECTED(Z_OBJCE_P(handler) == pt_ce_arguments_handler)) return ArgumentsHandler(Z_OBJ_P(handler)).processDroppedArgs(nodeScopeResolver, stmt, originalCall, normalizedCall, scope, storage, context);
	zv::Args argv{nodeScopeResolver, stmt, originalCall, normalizedCall, scope, storage, context};
	return !pt_type_call(Z_OBJ_P(handler), PT_LC("processdroppedargs"), 7, argv).isUndef();
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_arguments_handler()
{
	pt_ah_original_arg = zend_string_init_interned(PT_LC("originalArg"), 1);
	pt_ah_contains_closure = zend_string_init_interned(PT_LC("phpstanArgContainsClosure"), 1);
	pt_ah_start_token_pos = zend_string_init_interned(PT_LC("startTokenPos"), 1);
	pt_ah_start_line = zend_string_init_interned(PT_LC("startLine"), 1);
	pt_ah_this = zend_string_init_interned(PT_LC("this"), 1);
	pt_ah_construct = zend_string_init_interned(PT_LC("__construct"), 1);

	reg::Class cls("PHPStan\\Analyser\\ArgumentsHandler");
	ptdecl::ArgumentsHandler::declareClass(cls);
	ptdecl::ArgumentsHandler::declareProperties(cls);

	/* the DI service's constructor: the generated arginfo names the twin's
	 * parameter classes exactly (README rule 6) */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *objects[17];
		bool implicitThrows;
		ZEND_PARSE_PARAMETERS_START(17, 17)
			Z_PARAM_OBJECT(objects[0])
			Z_PARAM_OBJECT(objects[1])
			Z_PARAM_OBJECT(objects[2])
			Z_PARAM_OBJECT(objects[3])
			Z_PARAM_OBJECT(objects[4])
			Z_PARAM_OBJECT(objects[5])
			Z_PARAM_OBJECT(objects[6])
			Z_PARAM_OBJECT(objects[7])
			Z_PARAM_OBJECT(objects[8])
			Z_PARAM_OBJECT(objects[9])
			Z_PARAM_OBJECT(objects[10])
			Z_PARAM_OBJECT(objects[11])
			Z_PARAM_BOOL(implicitThrows)
			Z_PARAM_OBJECT(objects[13])
			Z_PARAM_OBJECT(objects[14])
			Z_PARAM_OBJECT(objects[15])
			Z_PARAM_OBJECT(objects[16])
		ZEND_PARSE_PARAMETERS_END();
		zval argv[17];
		for (uint32_t i = 0; i < 17; i++) {
			if (i == slots::implicitThrows) {
				ZVAL_BOOL(&argv[i], implicitThrows);
			} else {
				ZVAL_COPY_VALUE(&argv[i], objects[i]);
			}
		}
		ArgumentsHandler::construct(Z_OBJ_P(ZEND_THIS), argv, implicitThrows);
	});

	cls.method(sigs::processArgs, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *stmt, *calleeReflection, *nakedMethodReflection, *parametersAcceptors, *namedArgumentsVariants, *callLike, *scope, *storage, *nodeCallback, *context, *closureBindScopeFactory = NULL;
		ZEND_PARSE_PARAMETERS_START(11, 12)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_ZVAL(calleeReflection)
			Z_PARAM_OBJECT_OR_NULL(nakedMethodReflection)
			Z_PARAM_ARRAY(parametersAcceptors)
			Z_PARAM_ARRAY_OR_NULL(namedArgumentsVariants)
			Z_PARAM_OBJECT(callLike)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(context)
			Z_PARAM_OPTIONAL
			Z_PARAM_ZVAL(closureBindScopeFactory)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(ArgumentsHandler(Z_OBJ_P(ZEND_THIS)).processArgs(nodeScopeResolver, stmt, calleeReflection, nakedMethodReflection, parametersAcceptors, namedArgumentsVariants, callLike, scope, storage, nodeCallback, context, closureBindScopeFactory));
	});

	cls.method(sigs::processDroppedArgs, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *stmt, *originalCall, *normalizedCall, *scope, *storage, *context;
		ZEND_PARSE_PARAMETERS_START(7, 7)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(originalCall)
			Z_PARAM_OBJECT(normalizedCall)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!ArgumentsHandler(Z_OBJ_P(ZEND_THIS)).processDroppedArgs(nodeScopeResolver, stmt, originalCall, normalizedCall, scope, storage, context))) RETURN_THROWS();
	});

	cls.shadow(&pt_ce_arguments_handler);
}

/* }}} */
