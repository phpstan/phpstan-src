/*
 * PHPStanTurbo\AssignHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\AssignHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry; the public methods other handlers call — prepareTarget(),
 * applyWrite(), processVirtualAssign() — are exported as
 * pt_assign_handler_prepare_target() / _apply_write() /
 * _process_virtual_assign() (Engine.h conventions). The twin's closures are
 * native closures capturing exactly what the PHP closures capture.
 *
 * MutatingScope, ExpressionResult, ExpressionResultStorage, ExpressionContext,
 * VariableFlow, VariableFlowBuilder, SpecifiedTypes, TypeSpecifierContext,
 * the holders, ExprPrinter, VariableHandler and the Type family are called
 * through their direct entries; the collaborators that stay PHP for now
 * (NodeScopeResolver, the helpers and the other handlers, PreparedAssignTarget,
 * AssignTargetWalkMode, InternalThrowPoint, the reflections) through the
 * helpers in the "PHP collaborators" block below, one per called method, so
 * each switches to a direct entry in one place when its port lands.
 */

#include "support.h"
#include "generated/AssignHandler.h"
#include "generated/PreparedAssignTarget.h"

namespace slots = ptdecl::AssignHandler::slot;
namespace sigs = ptdecl::AssignHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "AcceptorValues.h"

#include <cstring>
#include <utility>

zend_class_entry *pt_ce_assign_handler = nullptr;

/* the twin's private constants */
#define PT_AH_TERNARY_ARM_EXCLUDED_VALUES_LIMIT 3
#define PT_AH_DERIVED_CONDITIONAL_EXPRESSIONS_LIMIT 16
#define PT_AH_ARRAY_DIM_FETCH_WRITE_DEPTH_LIMIT 5

/* VariableWrite::KIND_ASSIGN / KIND_READ_MODIFY_WRITE */
#define PT_AH_KIND_ASSIGN 1
#define PT_AH_KIND_READ_MODIFY_WRITE 2

/* {{{ propagation macros: every producer returns UNDEF (or false) with an
 * exception pending, which the enclosing zv::Val-returning body forwards */

#define AH_VAL(name, expr) \
	zv::Val name = (expr); \
	if (UNEXPECTED(name.isUndef())) return zv::Val()

#define AH_SET(name, expr) \
	do { \
		zv::Val ah_tmp_ = (expr); \
		if (UNEXPECTED(ah_tmp_.isUndef())) return zv::Val(); \
		(name) = std::move(ah_tmp_); \
	} while (0)

#define AH_OK(expr) \
	do { \
		if (UNEXPECTED(!(expr))) return zv::Val(); \
	} while (0)

/* the same for bodies returning bool (false = pending exception) */
#define AH_VALB(name, expr) \
	zv::Val name = (expr); \
	if (UNEXPECTED(name.isUndef())) return false

#define AH_SETB(name, expr) \
	do { \
		zv::Val ah_tmp_ = (expr); \
		if (UNEXPECTED(ah_tmp_.isUndef())) return false; \
		(name) = std::move(ah_tmp_); \
	} while (0)

#define AH_OKB(expr) \
	do { \
		if (UNEXPECTED(!(expr))) return false; \
	} while (0)

/* }}} */

namespace {

/* {{{ node access */

/* $value instanceof <class-map class> */
inline bool ahIs(zval *value, int classIdx)
{
	if (Z_TYPE_P(value) != IS_OBJECT) return false;
	/* pt_class() caches the entry (autoloading it once) where
	 * pt_class_loaded() would repeat the name lookup while the class is not
	 * declared yet; loading an AST or virtual node class has no observable
	 * effect */
	zend_class_entry *ce = pt_class(classIdx);
	return ce != NULL && instanceof_function(Z_OBJCE_P(value), ce);
}

/* $value instanceof <shadowing class> */
inline bool ahIsCe(zval *value, zend_class_entry *ce)
{
	return Z_TYPE_P(value) == IS_OBJECT && ce != NULL && instanceof_function(Z_OBJCE_P(value), ce);
}

zval pt_ah_undef_zval;

/* a declared property of a node / value object (dereferenced; an UNDEF zval
 * when the value is no object or its class declares no such property) */
zval *ahPropResolve(pt_property_site &site, zval *object, const char *name, size_t len)
{
	if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) {
		ZVAL_UNDEF(&pt_ah_undef_zval);
		return &pt_ah_undef_zval;
	}
	zval *slot = pt_property_cached(site, Z_OBJ_P(object), name, len);
	if (UNEXPECTED(slot == NULL)) {
		ZVAL_UNDEF(&pt_ah_undef_zval);
		return &pt_ah_undef_zval;
	}
	ZVAL_DEREF(slot);
	return slot;
}

/* $object->name through a property site of its own (one per use) */
#define AH_PROP(object, name) ([](zval *ah_object_) -> zval * { static pt_property_site ah_site_; return ahPropResolve(ah_site_, ah_object_, PT_LC(#name)); }(object))

/* the string name of a Variable node, NULL when it is an Expr */
inline zend_string *ahVariableName(zval *variable)
{
	zval *name = AH_PROP(variable, name);
	return Z_TYPE_P(name) == IS_STRING ? Z_STR_P(name) : NULL;
}

/* $expr instanceof Variable && is_string($expr->name) */
inline zend_string *ahStringVariableName(zval *expr)
{
	if (!ahIs(expr, PT_CLASS_VARIABLE)) return NULL;
	return ahVariableName(expr);
}

/* $call->getArgs() of a CallLike (its raw args) — the table or NULL */
inline HashTable *ahArgs(zval *call)
{
	zval *args = AH_PROP(call, args);
	return Z_TYPE_P(args) == IS_ARRAY ? Z_ARRVAL_P(args) : NULL;
}

inline uint32_t ahArgCount(zval *call)
{
	HashTable *args = ahArgs(call);
	return args != NULL ? zend_hash_num_elements(args) : 0;
}

/* $call->getArgs()[$index]->value (an UNDEF zval when absent) */
inline zval *ahArgValue(zval *call, zend_ulong index)
{
	HashTable *args = ahArgs(call);
	zval *arg = args != NULL ? zend_hash_index_find(args, index) : NULL;
	if (arg == NULL) {
		ZVAL_UNDEF(&pt_ah_undef_zval);
		return &pt_ah_undef_zval;
	}
	ZVAL_DEREF(arg);
	return AH_PROP(arg, value);
}

/* $funcCall->name instanceof Name && $funcCall->name->toLowerString() ===
 * $literal (strtolower() is ASCII-only, as the _ci comparison) */
inline bool ahNameIs(zval *name, const char *literal, size_t len)
{
	if (!ahIs(name, PT_CLASS_NAME)) return false;
	zval *string = AH_PROP(name, name);
	return Z_TYPE_P(string) == IS_STRING && zend_binary_strcasecmp(Z_STRVAL_P(string), Z_STRLEN_P(string), literal, len) == 0;
}

/* spl_object_id() of a node */
inline zend_ulong ahObjectId(zval *object)
{
	return (zend_ulong) Z_OBJ_HANDLE_P(object);
}

/* }}} */

/* {{{ values */

/* the PT_TRI_* value of a TrinaryLogic, IsSuperTypeOfResult or
 * AcceptsResult (their yes()/maybe()/no()); -1 = pending exception */
zend_long ahTri(zval *value)
{
	if (EXPECTED(Z_TYPE_P(value) == IS_OBJECT)) {
		zend_object *object = Z_OBJ_P(value);
		if (EXPECTED(object->ce == pt_ce_trinary)) return pt_trinary_value(object);
		if (object->ce == pt_ce_is_super_type_of_result || object->ce == pt_ce_accepts_result) return pt_result_value(object);
		zv::Val yes = pt_type_call(object, PT_LC("yes"), 0, NULL);
		if (UNEXPECTED(yes.isUndef())) return -1;
		if (zend_is_true(yes.raw())) return PT_TRI_YES;
		zv::Val no = pt_type_call(object, PT_LC("no"), 0, NULL);
		if (UNEXPECTED(no.isUndef())) return -1;
		return zend_is_true(no.raw()) ? PT_TRI_NO : PT_TRI_MAYBE;
	}
	zend_throw_error(NULL, "Call to a member function yes() on %s", zend_zval_value_name(value));
	return -1;
}

/* a borrowed TrinaryLogic singleton */
inline zval *ahTrinary(zend_long value)
{
	return pt_trinary_singleton(value);
}

/* $type->method(...$argv) on a Type (the op's direct entry when the class
 * registered it); UNDEF = pending exception */
inline zv::Val ahTypeOp(zval *type, pt_type_op_id op, uint32_t argc, zval *argv)
{
	if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", pt_type_op_infos[op].lcname, zend_zval_value_name(type));
		return zv::Val();
	}
	return pt_type_op(Z_OBJ_P(type), op, argc, argv);
}

/* the PT_TRI_* value of a trinary-returning op; -1 = pending exception */
inline zend_long ahTypeOpTri(zval *type, pt_type_op_id op, uint32_t argc, zval *argv)
{
	zv::Val result = ahTypeOp(type, op, argc, argv);
	if (UNEXPECTED(result.isUndef())) return -1;
	return ahTri(result.raw());
}

/* $object->method(...$argv) by name */
inline zv::Val ahCall(zval *object, const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", lcname, zend_zval_value_name(object));
		return zv::Val();
	}
	return pt_type_call(Z_OBJ_P(object), lcname, len, argc, argv);
}

/* the PT_TRI_* value of a trinary-returning method by name; -1 = pending
 * exception */
inline zend_long ahCallTri(zval *object, const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	zv::Val result = ahCall(object, lcname, len, argc, argv);
	if (UNEXPECTED(result.isUndef())) return -1;
	return ahTri(result.raw());
}

/* $a->equals($b); false = pending exception */
[[nodiscard]] inline bool ahEquals(zval *a, zval *b, bool &out)
{
	zv::Val result = ahTypeOp(a, PT_OP_EQUALS, 1, b);
	if (UNEXPECTED(result.isUndef())) return false;
	out = Z_TYPE_P(result.raw()) == IS_TRUE;
	return true;
}

/* $into = array_merge($into, $more) */
[[nodiscard]] bool ahMerge(zv::Val &into, zval *more)
{
	if (UNEXPECTED(Z_TYPE_P(more) != IS_ARRAY)) {
		zend_type_error("array_merge(): Argument #2 must be of type array, %s given", zend_zval_value_name(more));
		return false;
	}
	HashTable *intoTable = Z_ARRVAL_P(into.raw());
	HashTable *moreTable = Z_ARRVAL_P(more);
	bool intoIsList = HT_IS_PACKED(intoTable) && HT_IS_WITHOUT_HOLES(intoTable);
	if (zend_hash_num_elements(moreTable) == 0 && (intoIsList || zend_hash_num_elements(intoTable) == 0)) {
		if (zend_hash_num_elements(intoTable) == 0) into = zv::Val(zv::Arr::empty());
		return true;
	}
	if (zend_hash_num_elements(intoTable) == 0 && HT_IS_PACKED(moreTable) && HT_IS_WITHOUT_HOLES(moreTable)) {
		into = zv::Val::copyOf(zv::Ref(more));
		return true;
	}
	zv::Arr merged = zv::Arr::create(zend_hash_num_elements(intoTable) + zend_hash_num_elements(moreTable));
	for (HashTable *table : { intoTable, moreTable }) {
		for (auto entry : zv::TableRef(table)) {
			if (entry.hasStringKey()) {
				merged.set(entry.stringKey(), zv::Val::copyOf(entry.value()));
			} else {
				merged.push(entry.value());
			}
		}
	}
	into = zv::Val(std::move(merged));
	return true;
}

/* $list[] = $value on an owned array value */
inline void ahPush(zv::Val &list, zv::Val value)
{
	zval *raw = list.raw();
	SEPARATE_ARRAY(raw);
	zval v = value.take();
	zend_hash_next_index_insert(Z_ARRVAL_P(raw), &v);
}

inline void ahPushRef(zv::Val &list, zval *value)
{
	zval *raw = list.raw();
	SEPARATE_ARRAY(raw);
	Z_TRY_ADDREF_P(value);
	zend_hash_next_index_insert(Z_ARRVAL_P(raw), value);
}

/* $table[$index] = $value on an owned array value */
inline void ahSetIndex(zv::Val &table, zend_ulong index, zval *value)
{
	zval *raw = table.raw();
	SEPARATE_ARRAY(raw);
	Z_TRY_ADDREF_P(value);
	zend_hash_index_update(Z_ARRVAL_P(raw), index, value);
}

/* $table[$key] = $value (symtable semantics) on an owned array value */
inline void ahSetKey(zv::Val &table, zend_string *key, zval *value)
{
	zval *raw = table.raw();
	SEPARATE_ARRAY(raw);
	Z_TRY_ADDREF_P(value);
	zend_symtable_update(Z_ARRVAL_P(raw), key, value);
}

/* [$a, $b] */
inline zv::Val ahPair(zval *a, zval *b)
{
	zv::Arr pair = zv::Arr::create(2);
	pair.push(zv::Ref(a));
	pair.push(zv::Ref(b));
	return zv::Val(std::move(pair));
}

inline zv::Val ahEmptyArray()
{
	return zv::Val(zv::Arr::empty());
}

inline zv::Val ahNull()
{
	return zv::Val::null();
}

/* $scope->hasVariableType($name)->no() ? new ErrorType() : $scope->getVariableType($name) */
zv::Val ahVariableTypeOrError(zval *scope, zend_string *name)
{
	AH_VAL(has, pt_mutating_scope_has_variable_type(Z_OBJ_P(scope), name));
	zend_long hasValue = ahTri(has.raw());
	if (UNEXPECTED(hasValue < 0)) return zv::Val();
	if (hasValue == PT_TRI_NO) return pt_type_new_error_type();
	return pt_mutating_scope_get_variable_type(Z_OBJ_P(scope), name);
}

/* $scope->nativeTypesPromoted; false = pending exception */
[[nodiscard]] inline bool ahPromoted(zval *scope, bool &out)
{
	return pt_mutating_scope_native_types_promoted(Z_OBJ_P(scope), out);
}

/* $result->getTypeOnScope($scope, $scope->nativeTypesPromoted) */
zv::Val ahTypeOnScope(zval *result, zval *scope)
{
	bool promoted;
	AH_OK(ahPromoted(scope, promoted));
	return pt_expression_result_get_type_on_scope(result, scope, promoted);
}

/* $nativeTypesPromoted ? $scope->doNotTreatPhpDocTypesAsCertain() : $scope */
zv::Val ahFlavourScope(zval *scope, bool nativeTypesPromoted)
{
	if (nativeTypesPromoted) return pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scope));
	return zv::Val::copyOf(zv::Ref(scope));
}

/* $write->getId() of a VariableWrite: the slot of the PHP class, the method
 * otherwise */
zv::Val ahVariableWriteId(zval *write)
{
	bool error = false;
	const pt_variable_write_slots *writeSlots = pt_variable_write_slots_of(Z_OBJ_P(write), error);
	if (writeSlots != NULL) return zv::Val::copyOf(zv::ObjRef(write).propAtOffset(writeSlots->id));
	if (UNEXPECTED(error)) return zv::Val();
	return pt_type_call(Z_OBJ_P(write), PT_LC("getid"), 0, NULL);
}

/* }}} */

/* $result->getScope() / ->getThrowPoints() / ->getImpurePoints() as owned
 * values (the borrowed AnalyserValues.h readers, copied) */
inline zv::Val ahResultRead(zval *value, zv::Val &hold)
{
	if (UNEXPECTED(value == NULL)) return zv::Val();
	if (!hold.isUndef()) return std::move(hold);
	return zv::Val::copyOf(zv::Ref(value));
}

inline zv::Val ahResultScope(zval *result)
{
	zv::Val hold;
	zval *value = pt_expression_result_scope(result, hold);
	return ahResultRead(value, hold);
}

inline zv::Val ahResultThrowPoints(zval *result)
{
	zv::Val hold;
	zval *value = pt_expression_result_throw_points(result, hold);
	return ahResultRead(value, hold);
}

inline zv::Val ahResultImpurePoints(zval *result)
{
	zv::Val hold;
	zval *value = pt_expression_result_impure_points(result, hold);
	return ahResultRead(value, hold);
}

/* {{{ the collaborators — the native ones through their direct entries, the
 * PHP ones through one cached method site each (switch them to direct entries
 * once they are ported) */

/* $nodeScopeResolver->processExprNode($stmt, $expr, $scope, $storage, $nodeCallback, $context) */
zv::Val nsrProcessExprNode(zval *nsr, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
{
	return pt_node_scope_resolver_process_expr_node(nsr, stmt, expr, scope, storage, nodeCallback, context);
}

/* $nodeScopeResolver->callNodeCallback($nodeCallback, $node, $scope, $storage); false = pending exception */
[[nodiscard]] bool nsrCallNodeCallback(zval *nsr, zval *nodeCallback, zval *node, zval *scope, zval *storage)
{
	return pt_node_scope_resolver_call_node_callback(nsr, nodeCallback, node, scope, storage);
}

/* $nodeScopeResolver->storeExpressionResult($storage, $expr, $result); false = pending exception */
[[nodiscard]] bool nsrStoreExpressionResult(zval *nsr, zval *storage, zval *expr, zval *result)
{
	return pt_node_scope_resolver_store_expression_result(nsr, storage, expr, result);
}

/* $nodeScopeResolver->getAssignedVariables($expr) */
zv::Val nsrGetAssignedVariables(zval *nsr, zval *expr)
{
	return pt_node_scope_resolver_get_assigned_variables(nsr, expr);
}

/* $nodeScopeResolver->observingTemplateArgumentFrame($scope) */
zv::Val nsrObservingTemplateArgumentFrame(zval *nsr, zval *scope)
{
	return pt_node_scope_resolver_observing_template_argument_frame(nsr, scope);
}

/* $nodeScopeResolver->lookForSetAllowedUndefinedExpressions($scope, $expr) */
zv::Val nsrLookForSetAllowedUndefinedExpressions(zval *nsr, zval *scope, zval *expr)
{
	return pt_node_scope_resolver_look_for_set_allowed_undefined_expressions(nsr, scope, expr);
}

/* $nodeScopeResolver->readStoredResult($expr, $storage) */
zv::Val nsrReadStoredResult(zval *nsr, zval *expr, zval *storage)
{
	return pt_node_scope_resolver_read_stored_result(nsr, expr, storage);
}

/* $nodeScopeResolver->readTypeOfMaybeStored($expr, $scope) */
zv::Val nsrReadTypeOfMaybeStored(zval *nsr, zval *expr, zval *scope)
{
	return pt_node_scope_resolver_read_type_of_maybe_stored(nsr, expr, scope);
}

/* $nodeScopeResolver->findScopeStateType($expr, $scope) */
zv::Val nsrFindScopeStateType(zval *nsr, zval *expr, zval *scope)
{
	return pt_node_scope_resolver_find_scope_state_type(nsr, expr, scope);
}

/* $nodeScopeResolver->processSyntheticOnDemand($expr, $scope) */
zv::Val nsrProcessSyntheticOnDemand(zval *nsr, zval *expr, zval *scope)
{
	return pt_node_scope_resolver_process_synthetic_on_demand(nsr, expr, scope);
}

/* $nodeScopeResolver->readScopeStateOrSyntheticType($expr, $scope) */
zv::Val nsrReadScopeStateOrSyntheticType(zval *nsr, zval *expr, zval *scope)
{
	return pt_node_scope_resolver_read_scope_state_or_synthetic_type(nsr, expr, scope);
}

/* $nodeScopeResolver->processExprOnDemand($expr, $scope, $storage) */
zv::Val nsrProcessExprOnDemand(zval *nsr, zval *expr, zval *scope, zval *storage)
{
	return pt_node_scope_resolver_process_expr_on_demand(nsr, expr, scope, storage);
}

/* $nonNullabilityHelper->ensureNonNullability($scope, $expr)->getScope() */
zv::Val nnhEnsureNonNullabilityScope(zval *helper, zval *scope, zval *expr)
{
	static pt_method_site scopeSite;
	AH_VAL(result, pt_non_nullability_helper_ensure_non_nullability(helper, scope, expr));
	return pt_call_method_cached(scopeSite, Z_OBJ_P(result.raw()), PT_LC("getscope"), 0, NULL);
}

/* $arrayDimFetchHandler->composeResult($nodeScopeResolver, $stmt, $expr, $dimResult, $varResult, $storage, $context, $beforeScope) */
zv::Val adfhComposeResult(zval *handler, zval *nsr, zval *stmt, zval *expr, zval *dimResult, zval *varResult, zval *storage, zval *context, zval *beforeScope)
{
	return pt_array_dim_fetch_handler_compose_result(handler, nsr, stmt, expr, dimResult, varResult, storage, context, beforeScope);
}

/* $propertyFetchHandler->composeResult($nodeScopeResolver, $expr, $varResult, $nameResult, $scopeBeforeVar, $beforeScope) */
zv::Val pfhComposeResult(zval *handler, zval *nsr, zval *expr, zval *varResult, zval *nameResult, zval *scopeBeforeVar, zval *beforeScope)
{
	return pt_property_fetch_handler_compose_result(handler, nsr, expr, varResult, nameResult, scopeBeforeVar, beforeScope);
}

/* $staticPropertyFetchHandler->composeResult($expr, $classResult, $nameResult, $beforeScope) */
zv::Val spfhComposeResult(zval *handler, zval *expr, zval *classResult, zval *nameResult, zval *beforeScope)
{
	return pt_static_property_fetch_handler_compose_result(handler, expr, classResult, nameResult, beforeScope);
}

/* $defaultNarrowingHelper->createSubjectTypes($s, $subject, $subjectResult, $type, $context) */
zv::Val dnhCreateSubjectTypes(zval *helper, zval *scope, zval *subject, zval *subjectResult, zval *type, zval *context)
{
	return pt_default_narrowing_helper_create_subject_types(helper, scope, subject, subjectResult, type, context);
}

/* $defaultNarrowingHelper->specifyDefaultTypes($expr, $context) */
zv::Val dnhSpecifyDefaultTypes(zval *helper, zval *expr, zval *context)
{
	return pt_default_narrowing_helper_specify_default_types(helper, expr, context);
}

/* $defaultNarrowingHelper->specifyTypesForNode($scope, $node, $context) */
zv::Val dnhSpecifyTypesForNode(zval *helper, zval *scope, zval *node, zval *context)
{
	return pt_default_narrowing_helper_specify_types_for_node(helper, scope, node, context);
}

/* $defaultNarrowingHelper->captureChainResults($node, $storage, $chainResults) — $chainResults by reference; false = pending exception */
[[nodiscard]] bool dnhCaptureChainResults(zval *helper, zval *node, zval *storage, zv::Val &chainResults)
{
	zval reference;
	ZVAL_NEW_REF(&reference, chainResults.raw());
	ZVAL_UNDEF(chainResults.raw());
	bool ok = pt_default_narrowing_helper_capture_chain_results(helper, node, storage, &reference);
	chainResults = zv::Val::copyOf(zv::Ref(Z_REFVAL(reference)));
	zval_ptr_dtor(&reference);
	return ok;
}

/* $identicalNarrowingHelper->captureFirstArgResult($side, $storage) */
zv::Val inhCaptureFirstArgResult(zval *helper, zval *side, zval *storage)
{
	return pt_identical_narrowing_helper_capture_first_arg_result(helper, side, storage);
}

/* $identicalNarrowingHelper->specifyIdenticalAgainstType($subject, $subjectResult, $constantExpr, $constantType, $context, $evaluationScope, $subjectArgResult, $identicalTypeCallback) */
zv::Val inhSpecifyIdenticalAgainstType(zval *helper, zval *subject, zval *subjectResult, zval *constantExpr, zval *constantType, zval *context, zval *evaluationScope, zval *subjectArgResult, zval *identicalTypeCallback)
{
	return pt_identical_narrowing_helper_specify_identical_against_type(helper, subject, subjectResult, constantExpr, constantType, context, evaluationScope, subjectArgResult, identicalTypeCallback);
}

/* $ternaryHandler->getCapturedResults($expr) */
zv::Val thGetCapturedResults(zval *handler, zval *expr)
{
	return pt_ternary_handler_get_captured_results(handler, expr);
}

/* $matchHandler->getCapturedArmScopesAndTypes($expr) (MatchHandler.cpp) */
zv::Val mhGetCapturedArmScopesAndTypes(zval *handler, zval *expr)
{
	return pt_match_handler_get_captured_arm_scopes_and_types(handler, expr);
}

/* $varAnnotationProcessor->processVarAnnotation($scope, $variableNames, $stmt, $changed) — $changed by reference */
zv::Val vapProcessVarAnnotation(zval *processor, zval *scope, zval *variableNames, zval *stmt, bool &changed)
{
	return pt_var_annotation_processor_process_var_annotation(processor, scope, variableNames, stmt, &changed);
}

/* $arrayUnpackingHelper->getImplicitIndexCount($type) */
zv::Val auhGetImplicitIndexCount(zval *helper, zval *type)
{
	static pt_method_site site;
	return pt_call_method_cached(site, Z_OBJ_P(helper), PT_LC("getimplicitindexcount"), 1, type);
}

/* $statementsHandler->processStmtVarAnnotation($nodeScopeResolver, $scope, $storage, $stmt, $defaultExpr, $nodeCallback) */
zv::Val shProcessStmtVarAnnotation(zval *handler, zval *nsr, zval *scope, zval *storage, zval *stmt, zval *defaultExpr, zval *nodeCallback)
{
	return pt_statements_handler_process_stmt_var_annotation(handler, nsr, scope, storage, stmt, defaultExpr, nodeCallback);
}

/* $templateArgumentObserver->collectSend($declared, $actual) */
zv::Val taoCollectSend(zval *observer, zval *declared, zval *actual)
{
	return pt_template_argument_observer_collect_send(observer, declared, actual);
}

/* $templateArgumentObserver->collectArgument($parameterType, $argumentType) */
zv::Val taoCollectArgument(zval *observer, zval *parameterType, zval *argumentType)
{
	return pt_template_argument_observer_collect_argument(observer, parameterType, argumentType, false);
}

/* TemplateArgumentConstraints::createEmpty() */
zv::Val tacCreateEmpty()
{
	return pt_template_argument_constraints_create_empty();
}

/* $constraints->merge($other) */
zv::Val tacMerge(zval *constraints, zval *other)
{
	return pt_template_argument_constraints_merge(constraints, other);
}

/* $methodThrowPointHelper->getThrowPointsForCallOnType($scope, $context, $calledOnType, $methodCall) */
zv::Val mtphGetThrowPointsForCallOnType(zval *helper, zval *scope, zval *context, zval *calledOnType, zval *methodCall)
{
	return pt_method_throw_point_helper_get_throw_points_for_call_on_type(helper, scope, context, calledOnType, methodCall);
}

/* $propertyHookThrowPointsResolver->getThrowPointsFromPropertyHook($scope, $propertyFetch, $propertyReflection, 'set') */
zv::Val phtprGetThrowPointsFromSetHook(zval *resolver, zval *scope, zval *propertyFetch, zval *propertyReflection)
{
	zv::Str hookType = zv::Str::adopt(zend_string_init(PT_LC("set"), 0));
	return pt_property_hook_throw_points_resolver_get_throw_points_from_property_hook(resolver, scope, propertyFetch, propertyReflection, hookType.get());
}

/* $propertyReflectionFinder->findPropertyReflectionFromNodeWithHolderType($propertyFetch, $propertyHolderType, $scope) */
zv::Val prfFindPropertyReflectionFromNodeWithHolderType(zval *finder, zval *propertyFetch, zval *propertyHolderType, zval *scope)
{
	static pt_method_site site;
	zv::Args argv{propertyFetch, propertyHolderType, scope};
	return pt_call_method_cached(site, Z_OBJ_P(finder), PT_LC("findpropertyreflectionfromnodewithholdertype"), 3, argv);
}

/* $virtualExprResultHelper->createTypeExprResult($scope, $expr) */
zv::Val vehCreateTypeExprResult(zval *helper, zval *scope, zval *expr)
{
	return pt_virtual_expr_result_helper_create_type_expr_result(helper, scope, expr);
}

/* $phpVersion->supportsPropertyHooks(); false = pending exception */
[[nodiscard]] bool phpVersionSupportsPropertyHooks(zval *phpVersion, bool &out)
{
	return pt_php_version_supports_property_hooks(phpVersion, out);
}

/* VirtualAssignNodeCallback::create($nodeCallback) */
zv::Val vancCreate(zval *nodeCallback)
{
	static pt_method_site site;
	return pt_call_static_cached(site, PT_CLASS_VIRTUAL_ASSIGN_NODE_CALLBACK, PT_LC("create"), 1, nodeCallback);
}

/* InternalThrowPoint::createImplicit($scope, $node) */
zv::Val itpCreateImplicit(zval *scope, zval *node)
{
	return pt_internal_throw_point_create_implicit(scope, node);
}

/* InternalThrowPoint::createExplicit($scope, $type, $node, $canContainAnyThrowable) */
zv::Val itpCreateExplicit(zval *scope, zval *type, zval *node, bool canContainAnyThrowable)
{
	return pt_internal_throw_point_create_explicit(scope, type, node, canContainAnyThrowable);
}

/* AssignTargetWalkMode::assign() / virtualAssign() */
zv::Val walkModeAssign()
{
	return pt_assign_target_walk_mode_new(true, false, false);
}

zv::Val walkModeVirtualAssign()
{
	return pt_assign_target_walk_mode_new(false, false, false);
}

/* $mode->enterExpressionAssign() / producesTargetReadResult() /
 * issetSemanticsForRead() (the slots of the native class; a foreign mode's
 * getter cannot fail in a way the twin would observe) */
inline bool walkModeEnterExpressionAssign(zval *mode)
{
	bool out = false;
	(void) pt_assign_target_walk_mode_enter_expression_assign(mode, out);
	return out;
}

inline bool walkModeProducesTargetReadResult(zval *mode)
{
	bool out = false;
	(void) pt_assign_target_walk_mode_produces_target_read_result(mode, out);
	return out;
}

inline bool walkModeIssetSemanticsForRead(zval *mode)
{
	bool out = false;
	(void) pt_assign_target_walk_mode_isset_semantics_for_read(mode, out);
	return out;
}

/* new NoopNodeCallback() */
zv::Val newNoopNodeCallback()
{
	return pt_type_new(PT_CLASS_NOOP_NODE_CALLBACK, 0, NULL);
}

/* (new NodeFinder())->findInstanceOf([$node], Variable::class) */
zv::Val nodeFinderFindVariables(zval *node)
{
	static pt_method_site site;
	AH_VAL(finder, pt_type_new(PT_CLASS_NODE_FINDER, 0, NULL));
	zend_class_entry *variableCe = pt_class(PT_CLASS_VARIABLE);
	if (UNEXPECTED(variableCe == NULL)) return zv::Val();
	zv::Arr nodes = zv::Arr::create(1);
	nodes.push(zv::Ref(node));
	zv::Val className = zv::Val::string(variableCe->name);
	zv::Args argv{nodes.raw(), className.raw()};
	return pt_call_method_cached(site, Z_OBJ_P(finder.raw()), PT_LC("findinstanceof"), 2, argv);
}

/* {{{ PreparedAssignTarget — the final class's constructor and its private
 * slots (read by name through one property site each: the declared order of
 * the PHP twin and of a later native port agree, but the offsets are
 * resolved rather than assumed) */

struct PreparedTarget
{
	const char *kind;
	size_t kindLen;
	zval *var;
	zval *assignedExpr;
	zval *beforeScope;
	zval *scope;
	bool enterExpressionAssign;
	bool isAssignOp;
	bool hasYield;
	zval *throwPoints;
	zval *impurePoints;
	bool isAlwaysTerminating;
	zval *rootVar = NULL;
	zval *varResult = NULL;
	zval *dimFetchStack = NULL;
	zval *assignedPropertyExpr = NULL;
	zval *offsetTypes = NULL;
	zval *offsetNativeTypes = NULL;
	zval *existingOffsetTypes = NULL;
	zval *existingOffsetNativeTypes = NULL;
	zval *offsetSetTargetResult = NULL;
	zval *objectResult = NULL;
	zval *propertyName = NULL;
	zval *propertyHolderType = NULL;
	zval *targetReadResult = NULL;
	zval *targetChainResults = NULL;
	zval *variableNameResult = NULL;
};

/* new PreparedAssignTarget(...) */
zv::Val preparedAssignTargetNew(const PreparedTarget &t)
{
	zval null = {};
	ZVAL_NULL(&null);
	zval emptyArray;
	ZVAL_EMPTY_ARRAY(&emptyArray);
	auto orNull = [&](zval *value) { return value != NULL ? value : &null; };
	zv::Val kind = zv::Val::string(t.kind, t.kindLen);
	zv::Args argv{
		kind.raw(), t.var, t.assignedExpr, t.beforeScope, t.scope, t.enterExpressionAssign, t.isAssignOp, t.hasYield, t.throwPoints, t.impurePoints, t.isAlwaysTerminating,
		orNull(t.rootVar), orNull(t.varResult), orNull(t.dimFetchStack), orNull(t.assignedPropertyExpr), orNull(t.offsetTypes), orNull(t.offsetNativeTypes), orNull(t.existingOffsetTypes), orNull(t.existingOffsetNativeTypes), orNull(t.offsetSetTargetResult), orNull(t.objectResult), orNull(t.propertyName), orNull(t.propertyHolderType), orNull(t.targetReadResult), t.targetChainResults != NULL ? t.targetChainResults : &emptyArray, orNull(t.variableNameResult),
	};
	return pt_prepared_assign_target_new(26, argv);
}

/* the getters: the borrowed slots of the native class (its private
 * properties by name for any other object — never in a live walk) */
inline zval *ahTargetSlot(zval *target, uint32_t slot, pt_property_site &site, const char *name, size_t len)
{
	if (EXPECTED(Z_TYPE_P(target) == IS_OBJECT && Z_OBJCE_P(target) == pt_ce_prepared_assign_target)) return OBJ_PROP_NUM(Z_OBJ_P(target), slot);
	return ahPropResolve(site, target, name, len);
}

#define AH_TARGET(target, name) ([](zval *ah_target_) -> zval * { static pt_property_site ah_site_; return ahTargetSlot(ah_target_, ptdecl::PreparedAssignTarget::slot::name, ah_site_, PT_LC(#name)); }(target))

/* a getter that throws ShouldNotHappenException on null; NULL = pending exception */
inline zval *ahRequire(zval *value)
{
	if (UNEXPECTED(Z_TYPE_P(value) == IS_NULL || Z_TYPE_P(value) == IS_UNDEF)) {
		pt_throw_should_not_happen();
		return NULL;
	}
	return value;
}

/* }}} */

/* {{{ node and value constructors */

/* new Variable($name) */
zv::Val newVariable(zend_string *name)
{
	zv::Args argv{name};
	return pt_type_new(PT_CLASS_VARIABLE, 1, argv);
}

/* new ArrayDimFetch($var, $dim) */
zv::Val newArrayDimFetch(zval *var, zval *dim)
{
	zv::Args argv{var, dim};
	return pt_type_new(PT_CLASS_ARRAY_DIM_FETCH, 2, argv);
}

/* new TypeExpr($type) */
zv::Val newTypeExpr(zval *type)
{
	return pt_type_new(PT_CLASS_TYPE_EXPR, 1, type);
}

/* new NativeTypeExpr($phpdocType, $nativeType) */
zv::Val newNativeTypeExpr(zval *phpdocType, zval *nativeType)
{
	zv::Args argv{phpdocType, nativeType};
	return pt_type_new(PT_CLASS_NATIVE_TYPE_EXPR, 2, argv);
}

/* new SetExistingOffsetValueTypeExpr($var, $dim, $value) / new SetOffsetValueTypeExpr($var, $dim, $value) */
zv::Val newSetExistingOffsetValueTypeExpr(zval *var, zval *dim, zval *value)
{
	zv::Args argv{var, dim, value};
	return pt_type_new(PT_CLASS_SET_EXISTING_OFFSET_VALUE_TYPE_EXPR, 3, argv);
}

zv::Val newSetOffsetValueTypeExpr(zval *var, zval *dim, zval *value)
{
	zv::Args argv{var, dim, value};
	return pt_type_new(PT_CLASS_SET_OFFSET_VALUE_TYPE_EXPR, 3, argv);
}

/* new IntertwinedVariableByReferenceWithExpr($variableName, $expr, $assignedExpr) */
zv::Val newIntertwined(zend_string *variableName, zval *expr, zval *assignedExpr)
{
	zv::Args argv{variableName, expr, assignedExpr};
	return pt_type_new(PT_CLASS_INTERTWINED_VAR, 3, argv);
}

/* new MethodCall($var, $name) */
zv::Val newMethodCall(zval *var, const char *name, size_t len)
{
	zv::Val nameValue = zv::Val::string(name, len);
	zv::Args argv{var, nameValue.raw()};
	return pt_type_new(PT_CLASS_METHOD_CALL, 2, argv);
}

/* new VariableAssignNode($variable, $assignedExpr) */
zv::Val newVariableAssignNode(zval *variable, zval *assignedExpr)
{
	zv::Args argv{variable, assignedExpr};
	return pt_type_new(PT_CLASS_VARIABLE_ASSIGN_NODE, 2, argv);
}

/* new PropertyAssignNode($propertyFetch, $assignedExpr, $assignOp) */
zv::Val newPropertyAssignNode(zval *propertyFetch, zval *assignedExpr, bool assignOp)
{
	zv::Args argv{propertyFetch, assignedExpr, assignOp};
	return pt_type_new(PT_CLASS_PROPERTY_ASSIGN_NODE, 3, argv);
}

/* new ImpurePoint($scope, $node, $identifier, $description, $certain) */
zv::Val newImpurePoint(zval *scope, zval *node, zval *identifier, zval *description, bool certain)
{
	return pt_impure_point_new(scope, node, Z_STR_P(identifier), Z_STR_P(description), certain);
}

/* new ObjectType($className) */
zv::Val newObjectType(const char *className, size_t len)
{
	zv::Val name = zv::Val::string(className, len);
	return pt_type_new_object_type(name.raw());
}

/* new ConstantArrayType([], []) */
zv::Val newEmptyConstantArrayType()
{
	zval out;
	zval keys;
	zval values;
	ZVAL_EMPTY_ARRAY(&keys);
	ZVAL_EMPTY_ARRAY(&values);
	if (UNEXPECTED(!pt_constant_array_type_new(&out, &keys, &values))) return zv::Val();
	return zv::Val::adopt(out);
}

/* new NonEmptyArrayType() / new AccessoryArrayListType() / new NeverType() / new NullType() / new BooleanType() / new IntegerType() */
zv::Val newNonEmptyArrayType()
{
	zval out;
	if (UNEXPECTED(!pt_non_empty_array_type_new(&out))) return zv::Val();
	return zv::Val::adopt(out);
}

zv::Val newAccessoryArrayListType()
{
	zval out;
	if (UNEXPECTED(!pt_accessory_array_list_type_new(&out))) return zv::Val();
	return zv::Val::adopt(out);
}

zv::Val newNeverType()
{
	zval out;
	if (UNEXPECTED(!pt_never_type_new(&out))) return zv::Val();
	return zv::Val::adopt(out);
}

zv::Val newNullType()
{
	zval out;
	if (UNEXPECTED(!pt_null_type_new(&out))) return zv::Val();
	return zv::Val::adopt(out);
}

zv::Val newBooleanType()
{
	zval out;
	if (UNEXPECTED(!pt_boolean_type_new(&out))) return zv::Val();
	return zv::Val::adopt(out);
}

zv::Val newIntegerType()
{
	zval out;
	if (UNEXPECTED(!pt_integer_type_new(&out))) return zv::Val();
	return zv::Val::adopt(out);
}

zv::Val newConstantBooleanType(bool value)
{
	zval out;
	if (UNEXPECTED(!pt_constant_boolean_type_new(&out, value))) return zv::Val();
	return zv::Val::adopt(out);
}

zv::Val newConstantIntegerType(zend_long value)
{
	zval out;
	if (UNEXPECTED(!pt_constant_integer_type_new(&out, value))) return zv::Val();
	return zv::Val::adopt(out);
}

zv::Val newConstantStringType(zend_string *value)
{
	zval out;
	if (UNEXPECTED(!pt_constant_string_type_new(&out, value))) return zv::Val();
	return zv::Val::adopt(out);
}

zv::Val newHasOffsetValueType(zval *offsetType, zval *valueType)
{
	zval out;
	if (UNEXPECTED(!pt_has_offset_value_type_new(&out, offsetType, valueType))) return zv::Val();
	return zv::Val::adopt(out);
}

/* ExpressionTypeHolder::createYes($expr, $type) / new ExpressionTypeHolder($expr, $type, $certainty) */
zv::Val newExpressionTypeHolder(zval *expr, zval *type, zend_long certainty)
{
	zval holder;
	pt_holder_create(&holder, expr, type, certainty);
	return zv::Val::adopt(holder);
}

/* new ConditionalExpressionHolder($conditionExpressionTypeHolders, $typeHolder) */
zv::Val newConditionalExpressionHolder(zval *conditions, zval *typeHolder)
{
	if (UNEXPECTED(Z_TYPE_P(conditions) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL_P(conditions)) == 0)) {
		pt_throw_should_not_happen();
		return zv::Val();
	}
	zval raw;
	object_init_ex(&raw, pt_ce_cond_expr_holder);
	zv::Val holder = zv::Val::adopt(raw);
	zv::ObjRef object(holder.ref().asObject());
	object.propAtWrite(PT_CEH_PROP_CONDS, zv::Val::copyOf(zv::Ref(conditions)));
	object.propAtWrite(PT_CEH_PROP_TYPEHOLDER, zv::Val::copyOf(zv::Ref(typeHolder)));
	return holder;
}

/* $holder->getKey() of a ConditionalExpressionHolder; NULL = pending exception */
zend_string *conditionalHolderKey(zval *holder)
{
	if (EXPECTED(Z_OBJCE_P(holder) == pt_ce_cond_expr_holder)) {
		zv::ObjRef object(Z_OBJ_P(holder));
		return pt_ceh_key_build(object.propAt(PT_CEH_PROP_CONDS).deref().asArrayTable(), object.propAt(PT_CEH_PROP_TYPEHOLDER).deref().raw());
	}
	zv::Val key = pt_type_call(Z_OBJ_P(holder), PT_LC("getkey"), 0, NULL);
	if (UNEXPECTED(key.isUndef())) return NULL;
	return zval_get_string(key.raw());
}

/* $this->exprPrinter->printExpr($expr); NULL = pending exception */
zend_string *printExpr(zval *exprPrinter, zval *expr)
{
	return pt_expr_printer_print(exprPrinter, Z_OBJ_P(expr));
}

/* }}} */

/* }}} */

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\AssignHandler; UNDEF = pending
 * exception. The members follow the twin's order (twin line numbers in the
 * member comments). */
class AssignHandler
{
public:
	explicit AssignHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *argv)
	{
		zv::ObjRef object(self);
		for (uint32_t i = 0; i < 20; i++) {
			object.propAtWrite(i, zv::Val::copyOf(zv::Ref(&argv[i])));
		}
	}

	/* (twin 144) false = pending exception */
	[[nodiscard]] bool supports(zval *expr, bool &out) const
	{
		zend_class_entry *assignCe = pt_class(PT_CLASS_ASSIGN_EXPR);
		zend_class_entry *assignRefCe = pt_class(PT_CLASS_ASSIGN_REF_EXPR);
		if (UNEXPECTED(assignCe == NULL || assignRefCe == NULL)) return false;
		out = instanceof_function(Z_OBJCE_P(expr), assignCe) || instanceof_function(Z_OBJCE_P(expr), assignRefCe);
		return true;
	}

	/* the state processExpr() carries across the walk of the assigned value */
	struct ValueWalk
	{
		zv::Val target;
		zv::Val valueBeforeScope;
		zv::Val valueScope;
		zv::Val valueImpurePoints;
		zv::Val valueFlowWrite;
		zv::Val valueContext;
	};

	/* (twin 149) — split in three so the frame on the recursion path (a
	 * nested assignment in the assigned value re-enters processExpr() through
	 * NodeScopeResolver::processExprNode()) holds only the walk state: the
	 * target and value-context preparation and the write after the walk run
	 * in their own frames */
	zv::Val processExpr(zval *nsr, zval *stmt, zval *expr, zval *scopeArg, zval *storage, zval *nodeCallback, zval *context)
	{
		ValueWalk walk;
		AH_OK(prepareValueWalk(nsr, stmt, expr, scopeArg, storage, nodeCallback, context, walk));
		AH_VAL(assignedExprResult, nsrProcessExprNode(nsr, stmt, AH_PROP(expr, expr), walk.valueScope.raw(), storage, nodeCallback, walk.valueContext.raw()));
		return finishProcessExpr(nsr, stmt, expr, scopeArg, storage, nodeCallback, context, walk, assignedExprResult.raw());
	}

	/* processExpr() up to the walk of the assigned value; false = pending
	 * exception */
	[[nodiscard]] zend_never_inline bool prepareValueWalk(zval *nsr, zval *stmt, zval *expr, zval *scopeArg, zval *storage, zval *nodeCallback, zval *context, ValueWalk &walk)
	{
		zval *exprVar = AH_PROP(expr, var);
		zval *exprExpr = AH_PROP(expr, expr);
		AH_VALB(mode, walkModeAssign());
		AH_SETB(walk.target, prepareTarget(nsr, scopeArg, storage, stmt, exprVar, exprExpr, nodeCallback, context, mode.raw()));

		zv::Val &valueBeforeScope = walk.valueBeforeScope;
		zv::Val &valueScope = walk.valueScope;
		zv::Val &valueImpurePoints = walk.valueImpurePoints;
		valueBeforeScope = zv::Val::copyOf(zv::Ref(AH_TARGET(walk.target.raw(), scope)));
		valueScope = zv::Val::copyOf(valueBeforeScope.ref());
		valueImpurePoints = ahEmptyArray();
		bool isAssignRef = ahIs(expr, PT_CLASS_ASSIGN_REF_EXPR);
		bool isAssign = ahIs(expr, PT_CLASS_ASSIGN_EXPR);
		if (isAssignRef) {
			zval *referencedExpr = exprExpr;
			while (ahIs(referencedExpr, PT_CLASS_ARRAY_DIM_FETCH)) {
				referencedExpr = AH_PROP(referencedExpr, var);
			}

			if (ahIs(referencedExpr, PT_CLASS_PROPERTY_FETCH) || ahIs(referencedExpr, PT_CLASS_STATIC_PROPERTY_FETCH)) {
				zv::Val identifier = zv::Val::string(PT_LC("propertyAssignByRef"));
				zv::Val description = zv::Val::string(PT_LC("property assignment by reference"));
				AH_VALB(point, newImpurePoint(valueScope.raw(), expr, identifier.raw(), description.raw(), false));
				ahPush(valueImpurePoints, std::move(point));
			}

			AH_SETB(valueScope, pt_mutating_scope_enter_expression_assign(Z_OBJ_P(valueScope.raw()), Z_OBJ_P(exprExpr), true));
		}

		zv::Val &valueContext = walk.valueContext;
		valueContext = zv::Val::copyOf(zv::Ref(context));
		zend_string *varName = ahStringVariableName(exprVar);
		if (varName != NULL) {
			AH_SETB(valueContext, pt_expression_context_enter_right_side_assign(valueContext.raw(), varName, exprExpr));
		}

		zv::Val &valueFlowWrite = walk.valueFlowWrite;
		valueFlowWrite = ahNull();
		if (isAssign) {
			AH_SETB(valueFlowWrite, pt_variable_flow_builder_write_site(exprVar, PT_AH_KIND_ASSIGN, valueScope.raw(), storage));
		}
		AH_SETB(valueContext, pt_expression_context_enter_deep(valueContext.raw()));
		if (!valueFlowWrite.isNull()) {
			AH_SETB(valueContext, pt_expression_context_enter_value_flow(valueContext.raw(), valueFlowWrite.raw(), true));
		}

		return true;
	}

	/* processExpr() after the walk of the assigned value */
	zend_never_inline zv::Val finishProcessExpr(zval *nsr, zval *stmt, zval *expr, zval *scopeArg, zval *storage, zval *nodeCallback, zval *context, ValueWalk &walk, zval *assignedExprResultArg)
	{
		zval *beforeScope = scopeArg;
		zval *exprVar = AH_PROP(expr, var);
		zval *exprExpr = AH_PROP(expr, expr);
		bool isAssignRef = ahIs(expr, PT_CLASS_ASSIGN_REF_EXPR);
		bool isAssign = ahIs(expr, PT_CLASS_ASSIGN_EXPR);
		zend_string *varName = ahStringVariableName(exprVar);
		zv::Val assignedExprResult = zv::Val::copyOf(zv::Ref(assignedExprResultArg));
		zv::Val &target = walk.target;
		zv::Val &valueBeforeScope = walk.valueBeforeScope;
		zv::Val &valueScope = walk.valueScope;
		zv::Val &valueImpurePoints = walk.valueImpurePoints;
		zv::Val &valueFlowWrite = walk.valueFlowWrite;
		{
			AH_VAL(points, ahResultImpurePoints(assignedExprResult.raw()));
			AH_OK(ahMerge(valueImpurePoints, points.raw()));
		}
		AH_SET(valueScope, ahResultScope(assignedExprResult.raw()));

		if (isAssignRef) {
			AH_SET(valueScope, pt_mutating_scope_exit_expression_assign(Z_OBJ_P(valueScope.raw()), Z_OBJ_P(exprExpr)));
		}

		zv::Val result;
		{
			bool hasYield;
			bool isAlwaysTerminating;
			AH_OK(pt_expression_result_has_yield(assignedExprResult.raw(), hasYield));
			AH_OK(pt_expression_result_is_always_terminating(assignedExprResult.raw(), isAlwaysTerminating));
			AH_VAL(throwPoints, ahResultThrowPoints(assignedExprResult.raw()));
			zv::Val typeCallback = pt_native_closure(&resultTypeCallbackBody, assignedExprResult.raw());
			AH_VAL(specifyTypesCallback, pt_specified_types_empty_specify_callback());
			pt_expression_result_args args(valueScope.raw(), valueBeforeScope.raw(), exprExpr, hasYield, isAlwaysTerminating, throwPoints.raw(), valueImpurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
			AH_VAL(valueResult, pt_expression_result_create(factory(), args));
			AH_SET(result, applyWrite(nsr, target.raw(), valueResult.raw(), assignedExprResult.raw(), stmt, storage, nodeCallback, context));
		}
		AH_VAL(scope, ahResultScope(result.raw()));

		zend_string *refName = isAssignRef && varName != NULL ? ahStringVariableName(exprExpr) : NULL;
		if (refName != NULL) {
			// a plain variable read is scope state - no result or walk needed
			AH_VAL(type, ahVariableTypeOrError(scope.raw(), varName));
			AH_VAL(nativeScope, pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scope.raw())));
			AH_VAL(nativeType, ahVariableTypeOrError(nativeScope.raw(), varName));

			// When $varName is assigned, update $refName
			{
				AH_VAL(refVariable, newVariable(refName));
				AH_VAL(varVariable, newVariable(varName));
				AH_VAL(intertwined, newIntertwined(varName, refVariable.raw(), varVariable.raw()));
				AH_SET(scope, pt_mutating_scope_assign_expression(Z_OBJ_P(scope.raw()), Z_OBJ_P(intertwined.raw()), type.raw(), nativeType.raw()));
			}

			// When $refName is assigned, update $varName
			{
				AH_VAL(varVariable, newVariable(varName));
				AH_VAL(refVariable, newVariable(refName));
				AH_VAL(intertwined, newIntertwined(refName, varVariable.raw(), refVariable.raw()));
				AH_SET(scope, pt_mutating_scope_assign_expression(Z_OBJ_P(scope.raw()), Z_OBJ_P(intertwined.raw()), type.raw(), nativeType.raw()));
			}
		}

		AH_VAL(vars, nsrGetAssignedVariables(nsr, exprVar));
		if (Z_TYPE_P(vars.raw()) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(vars.raw())) > 0) {
			bool varChangedScope = false;
			AH_SET(scope, vapProcessVarAnnotation(prop(slots::varAnnotationProcessor), scope.raw(), vars.raw(), stmt, varChangedScope));
			if (!varChangedScope) {
				zval null;
				ZVAL_NULL(&null);
				AH_SET(scope, shProcessStmtVarAnnotation(prop(slots::statementsHandler), nsr, scope.raw(), storage, stmt, &null, nodeCallback));
			} else {
				// the @var tag is a declared type the assigned value flows into
				AH_VAL(templateArgumentFrame, nsrObservingTemplateArgumentFrame(nsr, scope.raw()));
				if (!templateArgumentFrame.isNull()) {
					for (auto entry : zv::TableRef(Z_ARRVAL_P(vars.raw()))) {
						zval *var = entry.value().deref().raw();
						zend_string *name = Z_TYPE_P(var) == IS_STRING ? Z_STR_P(var) : NULL;
						if (UNEXPECTED(name == NULL)) {
							zend_type_error("PHPStan\\Analyser\\MutatingScope::hasVariableType(): Argument #1 ($variableName) must be of type string, %s given", zend_zval_value_name(var));
							return zv::Val();
						}
						AH_VAL(has, pt_mutating_scope_has_variable_type(Z_OBJ_P(scope.raw()), name));
						zend_long hasValue = ahTri(has.raw());
						AH_OK(hasValue >= 0);
						if (hasValue == PT_TRI_NO) continue;
						AH_VAL(variableType, pt_mutating_scope_get_variable_type(Z_OBJ_P(scope.raw()), name));
						AH_VAL(assignedType, pt_expression_result_get_type(assignedExprResult.raw()));
						AH_VAL(constraints, taoCollectSend(prop(slots::templateArgumentObserver), variableType.raw(), assignedType.raw()));
						AH_SET(scope, pt_mutating_scope_add_template_argument_constraints(Z_OBJ_P(scope.raw()), constraints.raw()));
					}
				}
			}
		}

		zv::Val redundantType = ahNull();
		if (isAssign) {
			AH_SET(redundantType, redundant(assignedExprResult.raw(), exprVar, storage));
		}
		zv::Val variableFlow;
		{
			AH_VAL(targetReadFlow, pt_variable_flow_builder_target_read(exprVar, storage, false, NULL));
			AH_VAL(assignedFlow, pt_expression_result_variable_flow(assignedExprResult.raw()));
			zv::Val inputsFlow = ahNull();
			if (!valueFlowWrite.isNull()) {
				bool consumed;
				AH_OK(pt_expression_context_is_value_consumed(context, consumed));
				if (consumed) {
					AH_VAL(writeId, ahVariableWriteId(valueFlowWrite.raw()));
					AH_VAL(valueFlowTarget, pt_expression_context_get_value_flow_target(context));
					zv::Val targetId = ahNull();
					if (!valueFlowTarget.isNull()) {
						AH_SET(targetId, ahVariableWriteId(valueFlowTarget.raw()));
					}
					AH_SET(inputsFlow, pt_variable_flow_inputs(zval_get_long(writeId.raw()), targetId.raw()));
				}
			}
			AH_VAL(targetWriteFlow, pt_variable_flow_builder_target_write(exprVar, PT_AH_KIND_ASSIGN, scope.raw(), storage, redundantType.raw()));
			zv::Args flows{targetReadFlow.raw(), assignedFlow.raw(), inputsFlow.raw(), targetWriteFlow.raw()};
			AH_SET(variableFlow, pt_variable_flow_sequence(4, flows));
		}
		if (isAssign && ahIs(exprExpr, PT_CLASS_ARRAY_EXPR) && hasArrayReference(exprExpr)) {
			AH_VAL(escape, pt_variable_flow_builder_escape_root(exprVar));
			zv::Args flows{variableFlow.raw(), escape.raw()};
			AH_SET(variableFlow, pt_variable_flow_sequence(2, flows));
		}
		if (isAssignRef) {
			AH_VAL(escapeVar, pt_variable_flow_builder_escape_root(exprVar));
			AH_VAL(escapeExpr, pt_variable_flow_builder_escape_root(exprExpr));
			zv::Args flows{variableFlow.raw(), escapeVar.raw(), escapeExpr.raw()};
			AH_SET(variableFlow, pt_variable_flow_sequence(3, flows));
		}

		bool hasYield;
		bool isAlwaysTerminating;
		AH_OK(pt_expression_result_has_yield(result.raw(), hasYield));
		AH_OK(pt_expression_result_is_always_terminating(result.raw(), isAlwaysTerminating));
		AH_VAL(throwPoints, ahResultThrowPoints(result.raw()));
		AH_VAL(impurePoints, ahResultImpurePoints(result.raw()));
		zv::Val typeCallback = pt_native_closure(&resultTypeCallbackBody, assignedExprResult.raw());
		zv::Val specifyTypesCallback;
		if (isAssign) {
			AH_SET(specifyTypesCallback, createSpecifyTypesCallback(expr, assignedExprResult.raw(), beforeScope, storage));
		} else {
			specifyTypesCallback = pt_native_closure(&defaultSpecifyTypesCallbackBody, self, expr);
		}
		zv::Val createTypesCallback = ahNull();
		if (isAssign) {
			createTypesCallback = pt_native_closure(&createTypesCallbackBody, self, expr, assignedExprResult.raw(), beforeScope);
		}
		pt_expression_result_args args(scope.raw(), beforeScope, expr, hasYield, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw()).withCreateTypesCallback(createTypesCallback.raw());
		return pt_expression_result_create(factory(), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return AssignHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

	/* (twin 556) $assignedExprResult NULL for null */
	zv::Val processVirtualAssign(zval *nsr, zval *scope, zval *storage, zval *stmt, zval *var, zval *assignedExpr, zval *nodeCallback, zval *assignedExprResultArg)
	{
		// work off an available result for the assigned expr: passed by the
		// caller, or fabricated from a type-carrying virtual node - threaded
		// straight into applyWrite() so its reads compose instead of falling
		// back to on-demand pricing of the type, the truthy/falsey narrowing,
		// and the synthetic sentinel comparisons
		zv::Val assignedExprResult = assignedExprResultArg != NULL && Z_TYPE_P(assignedExprResultArg) != IS_NULL ? zv::Val::copyOf(zv::Ref(assignedExprResultArg)) : ahNull();
		if (assignedExprResult.isNull() && (ahIs(assignedExpr, PT_CLASS_TYPE_EXPR) || ahIs(assignedExpr, PT_CLASS_NATIVE_TYPE_EXPR))) {
			AH_VAL(stored, pt_expression_result_storage_find(storage, assignedExpr));
			if (stored.isNull()) {
				AH_SET(assignedExprResult, vehCreateTypeExprResult(prop(slots::virtualExprResultHelper), scope, assignedExpr));
			}
		}

		AH_VAL(virtualAssignNodeCallback, vancCreate(nodeCallback));
		AH_VAL(targetContext, pt_expression_context_create_deep());
		AH_VAL(mode, walkModeVirtualAssign());
		AH_VAL(target, prepareTarget(nsr, scope, storage, stmt, var, assignedExpr, virtualAssignNodeCallback.raw(), targetContext.raw(), mode.raw()));

		zval *targetScope = AH_TARGET(target.raw(), scope);
		zv::Val typeCallback = pt_native_closure(&mixedTypeCallbackBody);
		AH_VAL(specifyTypesCallback, pt_specified_types_empty_specify_callback());
		pt_expression_result_args args(targetScope, targetScope, assignedExpr, false, false, NULL, NULL, typeCallback.raw(), specifyTypesCallback.raw());
		AH_VAL(valueResult, pt_expression_result_create(factory(), args));
		AH_VAL(writeContext, pt_expression_context_create_deep());
		return applyWrite(nsr, target.raw(), valueResult.raw(), assignedExprResult.isNull() ? NULL : assignedExprResult.raw(), stmt, storage, virtualAssignNodeCallback.raw(), writeContext.raw());
	}

	/* (twin 615) */
	zv::Val prepareTarget(zval *nsr, zval *scope, zval *storage, zval *stmt, zval *var, zval *assignedExpr, zval *nodeCallback, zval *context, zval *mode)
	{
		// The raw target's node callback fires after the walk below composed and
		// stored the target's read result, with the scope captured at entry -
		// a synchronously invoked rule (the plain resolver, PHP < 8.1) then
		// answers its asks from the storage instead of re-walking on demand,
		// same as NodeScopeResolver::processExprNodeInternal().
		AH_VAL(prepared, doPrepareTarget(nsr, scope, storage, stmt, var, assignedExpr, nodeCallback, context, mode));
		zv::Val callbackScope = zv::Val::copyOf(zv::Ref(scope));
		if (walkModeEnterExpressionAssign(mode)) {
			AH_SET(callbackScope, pt_mutating_scope_enter_expression_assign(Z_OBJ_P(scope), Z_OBJ_P(var), true));
		}
		AH_OK(nsrCallNodeCallback(nsr, nodeCallback, var, callbackScope.raw(), storage));

		return prepared;
	}

	/* (twin 641) */
	zv::Val doPrepareTarget(zval *nsr, zval *scopeArg, zval *storage, zval *stmt, zval *varArg, zval *assignedExpr, zval *nodeCallback, zval *context, zval *mode)
	{
		bool enterExpressionAssign = walkModeEnterExpressionAssign(mode);
		zv::Val targetReadResult = ahNull();
		zv::Val targetChainResults = ahEmptyArray();
		zval *beforeScope = scopeArg;
		zv::Val scope = zv::Val::copyOf(zv::Ref(scopeArg));
		bool hasYield = false;
		zv::Val throwPoints = ahEmptyArray();
		zv::Val impurePoints = ahEmptyArray();
		bool isAlwaysTerminating = false;
		bool isAssignOp = ahIs(assignedExpr, PT_CLASS_ASSIGN_OP_EXPR) && !enterExpressionAssign;
		zval *var = varArg;

		if (ahIs(var, PT_CLASS_VARIABLE)) {
			zv::Val variableNameResult = ahNull();
			if (walkModeProducesTargetReadResult(mode)) {
				// `$lvalue OP= ...` reads the old value of `$lvalue`; the write walk
				// processes a Variable target only as an assignment target, never as
				// a read. The read result is composed here without a walk - the
				// ??= read with isset() semantics (mirroring CoalesceHandler's
				// left-side processing, with the isset descriptor - bug-13623).
				zval *name = AH_PROP(var, name);
				if (Z_TYPE_P(name) != IS_STRING) {
					// `$$name OP= ...` evaluates the name before reading the old
					// value: walk it once here, the write flow consumes the result
					AH_VAL(nameContext, pt_expression_context_without_value_flow(context));
					AH_SET(variableNameResult, nsrProcessExprNode(nsr, stmt, name, scope.raw(), storage, nodeCallback, nameContext.raw()));
					AH_OK(pt_expression_result_has_yield(variableNameResult.raw(), hasYield));
					AH_SET(throwPoints, ahResultThrowPoints(variableNameResult.raw()));
					AH_SET(impurePoints, ahResultImpurePoints(variableNameResult.raw()));
					AH_OK(pt_expression_result_is_always_terminating(variableNameResult.raw(), isAlwaysTerminating));
					AH_SET(scope, ahResultScope(variableNameResult.raw()));
				}
				zv::Val readScope = zv::Val::copyOf(scope.ref());
				if (walkModeIssetSemanticsForRead(mode)) {
					AH_VAL(ensuredScope, nnhEnsureNonNullabilityScope(prop(slots::nonNullabilityHelper), scope.raw(), var));
					AH_SET(readScope, nsrLookForSetAllowedUndefinedExpressions(nsr, ensuredScope.raw(), var));
				}
				AH_SET(targetReadResult, pt_variable_handler_compose_result(prop(slots::variableHandler), nsr, var, variableNameResult.isNull() ? NULL : variableNameResult.raw(), storage, readScope.raw(), NULL));
				if (walkModeIssetSemanticsForRead(mode)) {
					ahSetIndex(targetChainResults, ahObjectId(var), targetReadResult.raw());
				}
			}

			PreparedTarget t{PT_LC("variable"), var, assignedExpr, beforeScope, scope.raw(), enterExpressionAssign, isAssignOp, hasYield, throwPoints.raw(), impurePoints.raw(), isAlwaysTerminating};
			t.targetReadResult = targetReadResult.raw();
			t.targetChainResults = targetChainResults.raw();
			t.variableNameResult = variableNameResult.raw();
			return preparedAssignTargetNew(t);
		}

		if (ahIs(var, PT_CLASS_ARRAY_DIM_FETCH)) {
			zv::Val dimFetchStack = ahEmptyArray();
			zval *originalVar = var;
			zv::Val scopeBeforeTargetWalk = zv::Val::copyOf(scope.ref());
			while (ahIs(var, PT_CLASS_ARRAY_DIM_FETCH)) {
				ahPushRef(dimFetchStack, var);
				var = AH_PROP(var, var);
			}

			// 1. eval root expr
			// The root is read to obtain the container that receives the offset write, so a
			// property root must resolve to its readable type (not its writable one) even
			// though it sits on the left-hand side of the assignment.
			if (enterExpressionAssign) {
				AH_SET(scope, pt_mutating_scope_enter_expression_assign(Z_OBJ_P(scope.raw()), Z_OBJ_P(var), false));
			}
			AH_VAL(rootContext, pt_expression_context_enter_deep(context));
			AH_VAL(varResult, nsrProcessExprNode(nsr, stmt, var, scope.raw(), storage, nodeCallback, rootContext.raw()));
			AH_OK(pt_expression_result_has_yield(varResult.raw(), hasYield));
			AH_SET(throwPoints, ahResultThrowPoints(varResult.raw()));
			AH_SET(impurePoints, ahResultImpurePoints(varResult.raw()));
			AH_OK(pt_expression_result_is_always_terminating(varResult.raw(), isAlwaysTerminating));
			AH_SET(scope, ahResultScope(varResult.raw()));
			if (enterExpressionAssign) {
				AH_SET(scope, pt_mutating_scope_exit_expression_assign(Z_OBJ_P(scope.raw()), Z_OBJ_P(var)));
			}

			// 1b. build the write chain (Set*OffsetValueTypeExpr nesting) after
			// the root walk, so a property base's holder and current types are
			// read from its stored result instead of pricing the unwalked fetch
			zv::Val assignedPropertyExpr = zv::Val::copyOf(zv::Ref(assignedExpr));
			zval *chainVar = originalVar;
			while (ahIs(chainVar, PT_CLASS_ARRAY_DIM_FETCH)) {
				zval *chainVarVar = AH_PROP(chainVar, var);
				zval *chainVarDim = AH_PROP(chainVar, dim);
				zv::Val varForSetOffsetValue = zv::Val::copyOf(zv::Ref(chainVarVar));
				if (ahIs(chainVarVar, PT_CLASS_PROPERTY_FETCH) || ahIs(chainVarVar, PT_CLASS_STATIC_PROPERTY_FETCH)) {
					AH_VAL(originalPropertyType, getOriginalPropertyType(nsr, chainVarVar, scope.raw()));
					AH_SET(varForSetOffsetValue, newTypeExpr(originalPropertyType.raw()));
				}

				bool existing = false;
				if (chainVar == originalVar && Z_TYPE_P(chainVarDim) != IS_NULL) {
										zend_long hasValue = pt_mutating_scope_has_expression_type(Z_OBJ_P(scopeBeforeTargetWalk.raw()), chainVar);
					AH_OK(hasValue >= 0);
					existing = hasValue == PT_TRI_YES;
				}
				if (existing) {
					AH_SET(assignedPropertyExpr, newSetExistingOffsetValueTypeExpr(varForSetOffsetValue.raw(), chainVarDim, assignedPropertyExpr.raw()));
				} else {
					AH_SET(assignedPropertyExpr, newSetOffsetValueTypeExpr(varForSetOffsetValue.raw(), chainVarDim, assignedPropertyExpr.raw()));
				}
				chainVar = chainVarVar;
			}

			// 2. eval dimensions
			zv::Val offsetTypes = ahEmptyArray();
			zv::Val offsetNativeTypes = ahEmptyArray();
			zv::Val dimResults = ahEmptyArray();
			zv::Val deferredDimFetchResults = ahEmptyArray();
			{
				HashTable *stack = Z_ARRVAL_P(dimFetchStack.raw());
				uint32_t count = zend_hash_num_elements(stack);
				zv::Arr reversed = zv::Arr::create(count);
				for (uint32_t i = count; i > 0; i--) {
					reversed.push(zv::Ref(zend_hash_index_find(stack, i - 1)));
				}
				dimFetchStack = zv::Val(std::move(reversed));
			}
			zend_ulong lastDimKey = zend_hash_num_elements(Z_ARRVAL_P(dimFetchStack.raw())) - 1;
			zv::Val previousLinkResult = zv::Val::copyOf(varResult.ref());
			zval null;
			ZVAL_NULL(&null);
			for (auto entry : zv::TableRef(Z_ARRVAL_P(dimFetchStack.raw()))) {
				zend_ulong key = entry.indexKey();
				zval *dimFetch = entry.value().raw();
				zval *dimExpr = AH_PROP(dimFetch, dim);
				zv::Val callbackScope = zv::Val::copyOf(scope.ref());

				if (Z_TYPE_P(dimExpr) == IS_NULL) {
					ahPush(offsetTypes, ahPair(&null, dimFetch));
					ahPush(offsetNativeTypes, ahPair(&null, dimFetch));
					ahSetIndex(dimResults, key, &null);
					zv::Val typeCallback = pt_native_closure(&neverTypeCallbackBody);
					AH_VAL(specifyTypesCallback, pt_specified_types_empty_specify_callback());
					pt_expression_result_args args(scope.raw(), scope.raw(), dimFetch, false, false, NULL, NULL, typeCallback.raw(), specifyTypesCallback.raw());
					AH_VAL(fabricatedResult, pt_expression_result_create(factory(), args));
					ahPush(deferredDimFetchResults, ahPair(dimFetch, fabricatedResult.raw()));
					previousLinkResult = std::move(fabricatedResult);
				} else {
					if (enterExpressionAssign) {
						AH_VAL(discarded, pt_mutating_scope_enter_expression_assign(Z_OBJ_P(scope.raw()), Z_OBJ_P(dimExpr), true));
					}
					// process the dimension first, then consume its ExpressionResult
					// (single-pass inside-out) rather than reading it before processExprNode()
					AH_VAL(dimContext, pt_expression_context_enter_deep(context));
					AH_VAL(result, nsrProcessExprNode(nsr, stmt, dimExpr, scope.raw(), storage, nodeCallback, dimContext.raw()));
					ahSetIndex(dimResults, key, result.raw());
					{
						AH_VAL(type, pt_expression_result_get_type(result.raw()));
						ahPush(offsetTypes, ahPair(type.raw(), dimFetch));
					}
					{
						AH_VAL(nativeType, pt_expression_result_get_native_type(result.raw()));
						ahPush(offsetNativeTypes, ahPair(nativeType.raw(), dimFetch));
					}
					if (!hasYield) {
						AH_OK(pt_expression_result_has_yield(result.raw(), hasYield));
					}
					{
						AH_VAL(points, ahResultThrowPoints(result.raw()));
						AH_OK(ahMerge(throwPoints, points.raw()));
					}

					zv::Val typeCallback = pt_native_closure(&dimLinkTypeCallbackBody, previousLinkResult.raw(), result.raw(), scope.raw());
					AH_VAL(specifyTypesCallback, pt_specified_types_empty_specify_callback());
					pt_expression_result_args args(scope.raw(), scope.raw(), dimFetch, false, false, NULL, NULL, typeCallback.raw(), specifyTypesCallback.raw());
					AH_VAL(fabricatedResult, pt_expression_result_create(factory(), args));
					ahPush(deferredDimFetchResults, ahPair(dimFetch, fabricatedResult.raw()));
					previousLinkResult = std::move(fabricatedResult);
					AH_SET(scope, ahResultScope(result.raw()));

					if (enterExpressionAssign) {
						AH_SET(scope, pt_mutating_scope_exit_expression_assign(Z_OBJ_P(scope.raw()), Z_OBJ_P(dimExpr)));
					}
				}

				// The whole target's callback fires in prepareTarget() after the
				// walk; an intermediate link's fires here, after its dimension was
				// processed and its write-flavoured result stored, so callback-side
				// asks answer from the storage with the link's entry scope.
				if (key == lastDimKey) continue;

				AH_OK(nsrStoreExpressionResult(nsr, storage, dimFetch, previousLinkResult.raw()));
				if (enterExpressionAssign) {
					AH_SET(callbackScope, pt_mutating_scope_enter_expression_assign(Z_OBJ_P(callbackScope.raw()), Z_OBJ_P(dimFetch), true));
				}
				AH_OK(nsrCallNodeCallback(nsr, nodeCallback, dimFetch, callbackScope.raw(), storage));
			}

			HashTable *deferred = Z_ARRVAL_P(deferredDimFetchResults.raw());
			uint32_t deferredCount = zend_hash_num_elements(deferred);
			if (walkModeIssetSemanticsForRead(mode)) {
				// `$lvalue ??= ...` reads the chain with isset() semantics. The root
				// and dimensions were just walked, so each chain link's read is
				// composed from their results - no re-walk. The reads carry the isset
				// descriptor (bug-13623) and are stored, which is what parked rule
				// asks observe; the write-flavoured results below then replace them
				// in storage, exactly as before.
				AH_VAL(ensuredScope, nnhEnsureNonNullabilityScope(prop(slots::nonNullabilityHelper), scope.raw(), originalVar));
				AH_VAL(readScope, nsrLookForSetAllowedUndefinedExpressions(nsr, ensuredScope.raw(), originalVar));
				zv::Val levelReadResult = zv::Val::copyOf(varResult.ref());
				for (auto entry : zv::TableRef(Z_ARRVAL_P(dimFetchStack.raw()))) {
					zend_ulong key = entry.indexKey();
					zval *dimFetch = entry.value().raw();
					zval *dimResult = zend_hash_index_find(Z_ARRVAL_P(dimResults.raw()), key);
					AH_SET(levelReadResult, adfhComposeResult(prop(slots::arrayDimFetchHandler), nsr, stmt, dimFetch, dimResult, levelReadResult.raw(), storage, context, readScope.raw()));
					AH_OK(nsrStoreExpressionResult(nsr, storage, dimFetch, levelReadResult.raw()));
					ahSetIndex(targetChainResults, ahObjectId(dimFetch), levelReadResult.raw());
					zval *dim = AH_PROP(dimFetch, dim);
					if (Z_TYPE_P(dim) == IS_NULL || Z_TYPE_P(dimResult) == IS_NULL) continue;

					ahSetIndex(targetChainResults, ahObjectId(dim), dimResult);
				}
				targetReadResult = std::move(levelReadResult);
				// the root (and, when it is itself a fetch chain, its links) was
				// stored by its own walk above
				AH_OK(dnhCaptureChainResults(prop(slots::defaultNarrowingHelper), var, storage, targetChainResults));
			} else if (walkModeProducesTargetReadResult(mode)) {
				// `$lvalue OP= ...`: the value the target reads is the write-flavoured
				// result of the whole chain, fabricated above
				zval *last = zend_hash_index_find(deferred, deferredCount - 1);
				targetReadResult = zv::Val::copyOf(zv::Ref(zend_hash_index_find(Z_ARRVAL_P(last), 1)));
			}
			for (auto entry : zv::TableRef(deferred)) {
				HashTable *pair = Z_ARRVAL_P(entry.value().raw());
				AH_OK(nsrStoreExpressionResult(nsr, storage, zend_hash_index_find(pair, 0), zend_hash_index_find(pair, 1)));
			}
			// the chain link the write's ArrayAccess::offsetSet would be invoked on:
			// the second-outermost link, or the root for a single-dimension target
			zval *offsetSetTargetResult = deferredCount >= 2
				? zend_hash_index_find(Z_ARRVAL_P(zend_hash_index_find(deferred, deferredCount - 2)), 1)
				: varResult.raw();

			PreparedTarget t{PT_LC("arrayDimFetch"), originalVar, assignedExpr, beforeScope, scope.raw(), enterExpressionAssign, isAssignOp, hasYield, throwPoints.raw(), impurePoints.raw(), isAlwaysTerminating};
			t.rootVar = var;
			t.varResult = varResult.raw();
			t.dimFetchStack = dimFetchStack.raw();
			t.assignedPropertyExpr = assignedPropertyExpr.raw();
			t.offsetTypes = offsetTypes.raw();
			t.offsetNativeTypes = offsetNativeTypes.raw();
			t.offsetSetTargetResult = offsetSetTargetResult;
			t.targetReadResult = targetReadResult.raw();
			t.targetChainResults = targetChainResults.raw();
			return preparedAssignTargetNew(t);
		}

		if (ahIs(var, PT_CLASS_PROPERTY_FETCH)) {
			zv::Val scopeBeforeVar = zv::Val::copyOf(scope.ref());
			AH_VAL(objectContext, pt_expression_context_without_value_flow(context));
			AH_VAL(objectResult, nsrProcessExprNode(nsr, stmt, AH_PROP(var, var), scope.raw(), storage, nodeCallback, objectContext.raw()));
			AH_OK(pt_expression_result_has_yield(objectResult.raw(), hasYield));
			AH_SET(throwPoints, ahResultThrowPoints(objectResult.raw()));
			AH_SET(impurePoints, ahResultImpurePoints(objectResult.raw()));
			AH_OK(pt_expression_result_is_always_terminating(objectResult.raw(), isAlwaysTerminating));
			AH_SET(scope, ahResultScope(objectResult.raw()));

			zv::Val propertyName = ahNull();
			zv::Val propertyNameResult = ahNull();
			zval *name = AH_PROP(var, name);
			if (ahIs(name, PT_CLASS_IDENTIFIER)) {
				propertyName = zv::Val::copyOf(zv::Ref(AH_PROP(name, name)));
			} else {
				AH_VAL(nameContext, pt_expression_context_without_value_flow(context));
				AH_SET(propertyNameResult, nsrProcessExprNode(nsr, stmt, name, scope.raw(), storage, nodeCallback, nameContext.raw()));
				if (!hasYield) {
					AH_OK(pt_expression_result_has_yield(propertyNameResult.raw(), hasYield));
				}
				{
					AH_VAL(points, ahResultThrowPoints(propertyNameResult.raw()));
					AH_OK(ahMerge(throwPoints, points.raw()));
				}
				{
					AH_VAL(points, ahResultImpurePoints(propertyNameResult.raw()));
					AH_OK(ahMerge(impurePoints, points.raw()));
				}
				if (!isAlwaysTerminating) {
					AH_OK(pt_expression_result_is_always_terminating(propertyNameResult.raw(), isAlwaysTerminating));
				}
				AH_SET(scope, ahResultScope(propertyNameResult.raw()));
			}

			zv::Val scopeBeforeAssignEval = zv::Val::copyOf(scope.ref());
			if (walkModeIssetSemanticsForRead(mode)) {
				// `$lvalue ??= ...` reads the property with isset() semantics: the
				// read is composed from the just-walked receiver and name results -
				// no re-walk - and carries the isset descriptor (bug-13623). Stored
				// so parked rule asks observe the read flavour, exactly as they
				// observed the former pre-read's store.
				AH_VAL(ensuredScope, nnhEnsureNonNullabilityScope(prop(slots::nonNullabilityHelper), scope.raw(), var));
				AH_VAL(readScope, nsrLookForSetAllowedUndefinedExpressions(nsr, ensuredScope.raw(), var));
				AH_SET(targetReadResult, pfhComposeResult(prop(slots::propertyFetchHandler), nsr, var, objectResult.raw(), propertyNameResult.raw(), scopeBeforeVar.raw(), readScope.raw()));
				AH_OK(nsrStoreExpressionResult(nsr, storage, var, targetReadResult.raw()));
				AH_OK(dnhCaptureChainResults(prop(slots::defaultNarrowingHelper), var, storage, targetChainResults));
			}
			// The raw target fetch was emitted to node callbacks at the top of
			// prepareTarget() but the assign flow never processes it as a
			// read. Compose and store it once here from the receiver's and
			// name's results, so askers parked on it (DependencyResolver,
			// property rules) resume with its pre-assign type.
			AH_VAL(parkedReadResult, pfhComposeResult(prop(slots::propertyFetchHandler), nsr, var, objectResult.raw(), propertyNameResult.raw(), scopeBeforeVar.raw(), scopeBeforeAssignEval.raw()));
			AH_OK(nsrStoreExpressionResult(nsr, storage, var, parkedReadResult.raw()));
			if (walkModeProducesTargetReadResult(mode) && !walkModeIssetSemanticsForRead(mode)) {
				targetReadResult = std::move(parkedReadResult);
			}

			PreparedTarget t{PT_LC("propertyFetch"), var, assignedExpr, beforeScope, scope.raw(), enterExpressionAssign, isAssignOp, hasYield, throwPoints.raw(), impurePoints.raw(), isAlwaysTerminating};
			t.objectResult = objectResult.raw();
			t.propertyName = propertyName.raw();
			t.targetReadResult = targetReadResult.raw();
			t.targetChainResults = targetChainResults.raw();
			return preparedAssignTargetNew(t);
		}

		if (ahIs(var, PT_CLASS_STATIC_PROPERTY_FETCH)) {
			zv::Val classResult = ahNull();
			zv::Val propertyHolderType;
			zval *varClass = AH_PROP(var, class);
			if (ahIs(varClass, PT_CLASS_NAME)) {
				AH_SET(propertyHolderType, pt_mutating_scope_resolve_type_by_name(Z_OBJ_P(scope.raw()), Z_OBJ_P(varClass)));
			} else {
				AH_VAL(classContext, pt_expression_context_without_value_flow(context));
				AH_SET(classResult, nsrProcessExprNode(nsr, stmt, varClass, scope.raw(), storage, nodeCallback, classContext.raw()));
				AH_SET(propertyHolderType, pt_expression_result_get_type(classResult.raw()));
			}

			zv::Val propertyName = ahNull();
			zv::Val propertyNameResult = ahNull();
			zval *name = AH_PROP(var, name);
			if (ahIs(name, PT_CLASS_IDENTIFIER)) {
				propertyName = zv::Val::copyOf(zv::Ref(AH_PROP(name, name)));
			} else {
				AH_VAL(nameContext, pt_expression_context_without_value_flow(context));
				AH_SET(propertyNameResult, nsrProcessExprNode(nsr, stmt, name, scope.raw(), storage, nodeCallback, nameContext.raw()));
				AH_OK(pt_expression_result_has_yield(propertyNameResult.raw(), hasYield));
				AH_SET(throwPoints, ahResultThrowPoints(propertyNameResult.raw()));
				AH_SET(impurePoints, ahResultImpurePoints(propertyNameResult.raw()));
				AH_OK(pt_expression_result_is_always_terminating(propertyNameResult.raw(), isAlwaysTerminating));
				AH_SET(scope, ahResultScope(propertyNameResult.raw()));
			}

			zv::Val scopeBeforeAssignEval = zv::Val::copyOf(scope.ref());
			if (walkModeIssetSemanticsForRead(mode)) {
				// Same as the PropertyFetch branch above: the ??= read is composed
				// from the just-walked class/name results on the isset-semantics
				// scope - no re-walk.
				AH_VAL(ensuredScope, nnhEnsureNonNullabilityScope(prop(slots::nonNullabilityHelper), scope.raw(), var));
				AH_VAL(readScope, nsrLookForSetAllowedUndefinedExpressions(nsr, ensuredScope.raw(), var));
				AH_SET(targetReadResult, spfhComposeResult(prop(slots::staticPropertyFetchHandler), var, classResult.raw(), propertyNameResult.raw(), readScope.raw()));
				AH_OK(nsrStoreExpressionResult(nsr, storage, var, targetReadResult.raw()));
				AH_OK(dnhCaptureChainResults(prop(slots::defaultNarrowingHelper), var, storage, targetChainResults));
			}
			// Same as the PropertyFetch branch above: the emitted target fetch
			// needs a stored result for parked askers.
			AH_VAL(parkedReadResult, spfhComposeResult(prop(slots::staticPropertyFetchHandler), var, classResult.raw(), propertyNameResult.raw(), scopeBeforeAssignEval.raw()));
			AH_OK(nsrStoreExpressionResult(nsr, storage, var, parkedReadResult.raw()));
			if (walkModeProducesTargetReadResult(mode) && !walkModeIssetSemanticsForRead(mode)) {
				targetReadResult = std::move(parkedReadResult);
			}

			PreparedTarget t{PT_LC("staticPropertyFetch"), var, assignedExpr, beforeScope, scope.raw(), enterExpressionAssign, isAssignOp, hasYield, throwPoints.raw(), impurePoints.raw(), isAlwaysTerminating};
			t.propertyName = propertyName.raw();
			t.propertyHolderType = propertyHolderType.raw();
			t.targetReadResult = targetReadResult.raw();
			t.targetChainResults = targetChainResults.raw();
			return preparedAssignTargetNew(t);
		}

		if (ahIs(var, PT_CLASS_LIST_EXPR)) {
			PreparedTarget t{PT_LC("list"), var, assignedExpr, beforeScope, scope.raw(), enterExpressionAssign, isAssignOp, hasYield, throwPoints.raw(), impurePoints.raw(), isAlwaysTerminating};
			return preparedAssignTargetNew(t);
		}

		if (ahIs(var, PT_CLASS_EXISTING_ARRAY_DIM_FETCH)) {
			zval *originalVar = var;
			zv::Val dimFetchStack = ahEmptyArray();
			zv::Val assignedPropertyExpr = zv::Val::copyOf(zv::Ref(assignedExpr));
			while (ahIs(var, PT_CLASS_EXISTING_ARRAY_DIM_FETCH)) {
				zval *varVar = AH_PROP(var, var);
				zval *varDim = AH_PROP(var, dim);
				zv::Val varForSetOffsetValue = zv::Val::copyOf(zv::Ref(varVar));
				if (ahIs(varVar, PT_CLASS_PROPERTY_FETCH) || ahIs(varVar, PT_CLASS_STATIC_PROPERTY_FETCH)) {
					AH_VAL(originalPropertyType, getOriginalPropertyType(nsr, varVar, scope.raw()));
					AH_SET(varForSetOffsetValue, newTypeExpr(originalPropertyType.raw()));
				}
				AH_SET(assignedPropertyExpr, newSetExistingOffsetValueTypeExpr(varForSetOffsetValue.raw(), varDim, assignedPropertyExpr.raw()));
				ahPushRef(dimFetchStack, var);
				var = varVar;
			}

			// the chain links reference the original, already-processed AST nodes
			// (see the Unset_ handling) - read their stored results, no walk
			AH_VAL(varResult, nsrReadStoredResult(nsr, var, storage));

			zv::Val offsetTypes = ahEmptyArray();
			zv::Val offsetNativeTypes = ahEmptyArray();
			HashTable *stack = Z_ARRVAL_P(dimFetchStack.raw());
			for (uint32_t i = zend_hash_num_elements(stack); i > 0; i--) {
				zval *dimFetch = zend_hash_index_find(stack, i - 1);
				AH_VAL(dimResult, nsrReadStoredResult(nsr, AH_PROP(dimFetch, dim), storage));
				{
					AH_VAL(type, pt_expression_result_get_type(dimResult.raw()));
					ahPush(offsetTypes, ahPair(type.raw(), dimFetch));
				}
				{
					AH_VAL(nativeType, pt_expression_result_get_native_type(dimResult.raw()));
					ahPush(offsetNativeTypes, ahPair(nativeType.raw(), dimFetch));
				}
			}

			PreparedTarget t{PT_LC("existingArrayDimFetch"), originalVar, assignedExpr, beforeScope, scope.raw(), enterExpressionAssign, isAssignOp, hasYield, throwPoints.raw(), impurePoints.raw(), isAlwaysTerminating};
			t.rootVar = var;
			t.varResult = varResult.raw();
			t.assignedPropertyExpr = assignedPropertyExpr.raw();
			t.existingOffsetTypes = offsetTypes.raw();
			t.existingOffsetNativeTypes = offsetNativeTypes.raw();
			return preparedAssignTargetNew(t);
		}

		AH_VAL(fallbackContext, pt_expression_context_without_value_flow(context));
		AH_VAL(varResult, nsrProcessExprNode(nsr, stmt, var, scope.raw(), storage, nodeCallback, fallbackContext.raw()));
		AH_OK(pt_expression_result_has_yield(varResult.raw(), hasYield));
		{
			AH_VAL(points, ahResultThrowPoints(varResult.raw()));
			AH_OK(ahMerge(throwPoints, points.raw()));
		}
		{
			AH_VAL(points, ahResultImpurePoints(varResult.raw()));
			AH_OK(ahMerge(impurePoints, points.raw()));
		}
		AH_OK(pt_expression_result_is_always_terminating(varResult.raw(), isAlwaysTerminating));
		AH_SET(scope, ahResultScope(varResult.raw()));

		if (walkModeProducesTargetReadResult(mode)) {
			// a synthetic op=/??= target (e.g. InvalidBinaryOperationRule's
			// TypeExpr-operand clone priced on demand): the walk above already
			// priced the target as a read - its result is the read
			targetReadResult = zv::Val::copyOf(varResult.ref());
			if (walkModeIssetSemanticsForRead(mode)) {
				ahSetIndex(targetChainResults, ahObjectId(var), varResult.raw());
			}
		}

		PreparedTarget t{PT_LC("fallback"), var, assignedExpr, beforeScope, scope.raw(), enterExpressionAssign, isAssignOp, hasYield, throwPoints.raw(), impurePoints.raw(), isAlwaysTerminating};
		t.targetReadResult = targetReadResult.raw();
		t.targetChainResults = targetChainResults.raw();
		return preparedAssignTargetNew(t);
	}

	/* (twin 1144) $assignedValueResult NULL for null */
	zv::Val applyWrite(zval *nsr, zval *target, zval *valueResult, zval *assignedValueResult, zval *stmt, zval *storage, zval *nodeCallback, zval *context)
	{
		if (assignedValueResult != NULL && Z_TYPE_P(assignedValueResult) == IS_NULL) assignedValueResult = NULL;

		zval *kind = AH_TARGET(target, kind);
		zval *var = AH_TARGET(target, var);
		zval *assignedExprSlot = AH_TARGET(target, assignedExpr);
		zval *beforeScope = AH_TARGET(target, beforeScope);
		zv::Val scope = zv::Val::copyOf(zv::Ref(AH_TARGET(target, scope)));
		bool enterExpressionAssign = Z_TYPE_P(AH_TARGET(target, enterExpressionAssign)) == IS_TRUE;
		bool isAssignOp = Z_TYPE_P(AH_TARGET(target, isAssignOp)) == IS_TRUE;
		bool hasYield = Z_TYPE_P(AH_TARGET(target, hasYield)) == IS_TRUE;
		zv::Val throwPoints = zv::Val::copyOf(zv::Ref(AH_TARGET(target, throwPoints)));
		zv::Val impurePoints = zv::Val::copyOf(zv::Ref(AH_TARGET(target, impurePoints)));
		bool isAlwaysTerminating = Z_TYPE_P(AH_TARGET(target, isAlwaysTerminating)) == IS_TRUE;
		zv::Val assignedExpr = zv::Val::copyOf(zv::Ref(assignedExprSlot));
		zv::Val resultVar = zv::Val::copyOf(zv::Ref(var));

		if (UNEXPECTED(Z_TYPE_P(kind) != IS_STRING)) {
			zend_throw_error(NULL, "phpstan_turbo: PreparedAssignTarget::$kind is not a string");
			return zv::Val();
		}
		zend_string *kindString = Z_STR_P(kind);

		if (zend_string_equals_literal(kindString, "variable")) {
			AH_OK(applyWriteVariable(nsr, target, valueResult, assignedValueResult, stmt, storage, nodeCallback, context, var, assignedExpr, scope, hasYield, throwPoints, impurePoints, isAlwaysTerminating));
		} else if (zend_string_equals_literal(kindString, "arrayDimFetch")) {
			AH_OK(applyWriteArrayDimFetch(nsr, target, valueResult, assignedValueResult, storage, nodeCallback, context, var, assignedExpr.raw(), scope, isAssignOp, hasYield, throwPoints, impurePoints, isAlwaysTerminating, resultVar));
		} else if (zend_string_equals_literal(kindString, "propertyFetch")) {
			AH_OK(applyWritePropertyFetch(nsr, target, valueResult, assignedValueResult, storage, nodeCallback, context, var, assignedExpr.raw(), scope, enterExpressionAssign, isAssignOp, hasYield, throwPoints, impurePoints, isAlwaysTerminating));
		} else if (zend_string_equals_literal(kindString, "staticPropertyFetch")) {
			AH_OK(applyWriteStaticPropertyFetch(nsr, target, valueResult, assignedValueResult, storage, nodeCallback, var, assignedExpr.raw(), scope, isAssignOp, hasYield, throwPoints, impurePoints, isAlwaysTerminating));
		} else if (zend_string_equals_literal(kindString, "list")) {
			AH_OK(applyWriteList(nsr, valueResult, assignedValueResult, stmt, storage, nodeCallback, context, var, assignedExpr.raw(), scope, enterExpressionAssign, hasYield, throwPoints, impurePoints, isAlwaysTerminating));
		} else if (zend_string_equals_literal(kindString, "existingArrayDimFetch")) {
			AH_OK(applyWriteExistingArrayDimFetch(nsr, target, assignedValueResult, storage, nodeCallback, assignedExpr.raw(), scope, isAssignOp, resultVar));
		} else {
			AH_OK(pt_expression_result_has_yield_or(valueResult, hasYield));
			{
				AH_VAL(points, ahResultThrowPoints(valueResult));
				AH_OK(ahMerge(throwPoints, points.raw()));
			}
			{
				AH_VAL(points, ahResultImpurePoints(valueResult));
				AH_OK(ahMerge(impurePoints, points.raw()));
			}
			AH_OK(pt_expression_result_is_always_terminating_or(valueResult, isAlwaysTerminating));
			AH_SET(scope, ahResultScope(valueResult));
		}

		// stored where prepareTarget/applyWrite are called
		zv::Val typeCallback = pt_native_closure(&mixedTypeCallbackBody);
		AH_VAL(specifyTypesCallback, pt_specified_types_empty_specify_callback());
		pt_expression_result_args args(scope.raw(), beforeScope, resultVar.raw(), hasYield, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		return pt_expression_result_create(factory(), args);
	}

private:
	zend_object *self;

	zval *prop(uint32_t slot) const { return OBJ_PROP_NUM(self, slot); }
	zval *factory() const { return prop(slots::expressionResultFactory); }

	/* $hasYield = $hasYield || $result->hasYield() */
	[[nodiscard]] static bool pt_expression_result_has_yield_or(zval *result, bool &flag)
	{
		if (flag) return true;
		return pt_expression_result_has_yield(result, flag);
	}

	/* $isAlwaysTerminating = $isAlwaysTerminating || $result->isAlwaysTerminating() */
	[[nodiscard]] static bool pt_expression_result_is_always_terminating_or(zval *result, bool &flag)
	{
		if (flag) return true;
		return pt_expression_result_is_always_terminating(result, flag);
	}

	/* {{{ applyWrite() — the KIND_VARIABLE branch (twin 1166) */

	[[nodiscard]] bool applyWriteVariable(zval *nsr, zval *target, zval *valueResult, zval *assignedValueResult, zval *stmt, zval *storage, zval *nodeCallback, zval *context, zval *var, zv::Val &assignedExpr, zv::Val &scope, bool &hasYield, zv::Val &throwPoints, zv::Val &impurePoints, bool &isAlwaysTerminating)
	{
		if (UNEXPECTED(!ahIs(var, PT_CLASS_VARIABLE))) {
			pt_throw_should_not_happen();
			return false;
		}
		zval *result = valueResult;
		AH_OKB(pt_expression_result_has_yield(result, hasYield));
		AH_SETB(throwPoints, ahResultThrowPoints(result));
		AH_SETB(impurePoints, ahResultImpurePoints(result));
		AH_OKB(pt_expression_result_is_always_terminating(result, isAlwaysTerminating));
		zv::Val scopeBeforeAssignEval = zv::Val::copyOf(scope.ref());
		AH_SETB(scope, ahResultScope(result));
		zend_string *varName = ahVariableName(var);
		if (varName != NULL) {
			if (pt_is_superglobal_name(varName)) {
				zv::Val identifier = zv::Val::string(PT_LC("superglobal"));
				zv::Val description = zv::Val::string(PT_LC("assign to superglobal variable"));
				AH_VALB(point, newImpurePoint(scopeBeforeAssignEval.raw(), var, identifier.raw(), description.raw(), true));
				ahPush(impurePoints, std::move(point));
			}
			assignedExpr = zv::Val::copyOf(zv::Ref(unwrapAssign(assignedExpr.raw())));
			// the caller-passed value result; a nested assign chain's value is the
			// innermost assigned expression, whose result comes from the storage
			// the walk just wrote into (the one read this method cannot avoid)
			zv::Val storedAssignedExprResult;
			if (Z_OBJ_P(assignedExpr.raw()) == Z_OBJ_P(AH_TARGET(target, assignedExpr)) && assignedValueResult != NULL) {
				storedAssignedExprResult = zv::Val::copyOf(zv::Ref(assignedValueResult));
			} else {
				AH_SETB(storedAssignedExprResult, pt_expression_result_storage_find(storage, assignedExpr.raw()));
			}
			zval *stored = storedAssignedExprResult.isNull() ? NULL : storedAssignedExprResult.raw();
			AH_VALB(type, readAssignedValueType(nsr, stored, assignedExpr.raw(), scopeBeforeAssignEval.raw()));

			zv::Val conditionalExpressions = ahEmptyArray();
			if (ahIs(assignedExpr.raw(), PT_CLASS_TERNARY_EXPR)) {
				AH_OKB(ternaryConditionalExpressions(nsr, stmt, storage, varName, conditionalExpressions, assignedExpr.raw(), scope.raw(), impurePoints.raw(), stored));
			}

			if (ahIs(assignedExpr.raw(), PT_CLASS_MATCH)) {
				AH_VALB(matchExpressions, processMatchForConditionalExpressionsAfterAssign(scopeBeforeAssignEval.raw(), varName, assignedExpr.raw()));
				mergeConditionalExpressions(conditionalExpressions, matchExpressions.raw());
			}

			AH_VALB(assignedArgResult, inhCaptureFirstArgResult(prop(slots::identicalNarrowingHelper), assignedExpr.raw(), storage));

			if (ahIs(assignedExpr.raw(), PT_CLASS_FUNC_CALL)) {
				AH_OKB(processInArrayForConditionalExpressionsAfterAssign(nsr, scopeBeforeAssignEval.raw(), varName, conditionalExpressions, assignedExpr.raw(), type.raw(), impurePoints.raw()));
			}

			AH_OKB(processDerivedConditionalExpressionsAfterAssign(nsr, scopeBeforeAssignEval.raw(), varName, conditionalExpressions, assignedExpr.raw(), type.raw(), impurePoints.raw()));

			AH_VALB(truthyType, pt_type_combinator_call(PT_LC("removefalsey"), 1, type.raw()));
			// Value comparison, not identity: remove() happens to hand back the very same
			// instance when it removes nothing, but that is not part of its contract — the
			// falsey loop below already compares with equals(). The identity check is only
			// a fast path (equals() has no such shortcut, and no-op removal is the common
			// case here).
			bool truthyChanged = false;
			if (Z_OBJ_P(truthyType.raw()) != Z_OBJ_P(type.raw())) {
				bool equal;
				AH_OKB(ahEquals(truthyType.raw(), type.raw(), equal));
				truthyChanged = !equal;
			}
			if (truthyChanged) {
				zend_object *truthyContext = pt_type_specifier_context_create_truthy();
				AH_OKB(truthyContext != NULL);
				zval truthyContextZv;
				ZVAL_OBJ(&truthyContextZv, truthyContext);
				zv::Val truthySpecifiedTypes;
				if (stored != NULL) {
					AH_SETB(truthySpecifiedTypes, pt_expression_result_get_specified_types_for_scope(stored, scope.raw(), &truthyContextZv));
				} else {
					AH_SETB(truthySpecifiedTypes, dnhSpecifyTypesForNode(prop(slots::defaultNarrowingHelper), scope.raw(), assignedExpr.raw(), &truthyContextZv));
				}
				AH_OKB(processSureTypesForConditionalExpressionsAfterAssign(nsr, scope.raw(), storage, varName, conditionalExpressions, truthySpecifiedTypes.raw(), truthyType.raw(), impurePoints.raw(), assignedExpr.raw(), stored));
				AH_OKB(processSureNotTypesForConditionalExpressionsAfterAssign(nsr, scope.raw(), storage, varName, conditionalExpressions, truthySpecifiedTypes.raw(), truthyType.raw(), impurePoints.raw(), assignedExpr.raw(), stored));

				AH_VALB(falseyStatic, pt_static_type_factory_falsey());
				zv::Args intersectArgs{type.raw(), falseyStatic.raw()};
				AH_VALB(falseyType, pt_type_combinator_intersect(2, intersectArgs));
				zend_object *falseyContext = pt_type_specifier_context_create_falsey();
				AH_OKB(falseyContext != NULL);
				zval falseyContextZv;
				ZVAL_OBJ(&falseyContextZv, falseyContext);
				zv::Val falseySpecifiedTypes;
				if (stored != NULL) {
					AH_SETB(falseySpecifiedTypes, pt_expression_result_get_specified_types_for_scope(stored, scope.raw(), &falseyContextZv));
				} else {
					AH_SETB(falseySpecifiedTypes, dnhSpecifyTypesForNode(prop(slots::defaultNarrowingHelper), scope.raw(), assignedExpr.raw(), &falseyContextZv));
				}
				AH_OKB(processSureTypesForConditionalExpressionsAfterAssign(nsr, scope.raw(), storage, varName, conditionalExpressions, falseySpecifiedTypes.raw(), falseyType.raw(), impurePoints.raw(), assignedExpr.raw(), stored));
				AH_OKB(processSureNotTypesForConditionalExpressionsAfterAssign(nsr, scope.raw(), storage, varName, conditionalExpressions, falseySpecifiedTypes.raw(), falseyType.raw(), impurePoints.raw(), assignedExpr.raw(), stored));
			}

			for (int falseyIndex = 0; falseyIndex < 7; falseyIndex++) {
				zval falseyScalar;
				switch (falseyIndex) {
					case 0: ZVAL_NULL(&falseyScalar); break;
					case 1: ZVAL_FALSE(&falseyScalar); break;
					case 2: ZVAL_LONG(&falseyScalar, 0); break;
					case 3: ZVAL_DOUBLE(&falseyScalar, 0.0); break;
					case 4: ZVAL_EMPTY_STRING(&falseyScalar); break;
					case 5: ZVAL_CHAR(&falseyScalar, '0'); break;
					default: ZVAL_EMPTY_ARRAY(&falseyScalar); break;
				}
				AH_VALB(falseyType, pt_constant_type_helper_get_type_from_value(&falseyScalar));
				AH_VALB(withoutFalseyType, pt_type_combinator_remove(type.raw(), falseyType.raw()));
				bool equal;
				AH_OKB(ahEquals(withoutFalseyType.raw(), type.raw(), equal));
				if (equal) continue;
				AH_OKB(ahEquals(withoutFalseyType.raw(), truthyType.raw(), equal));
				if (equal) continue;

				zv::Val astNode;
				switch (falseyIndex) {
					case 0: AH_SETB(astNode, newConstFetch(PT_LC("null"))); break;
					case 1: AH_SETB(astNode, newConstFetch(PT_LC("false"))); break;
					case 2: AH_SETB(astNode, pt_type_new(PT_CLASS_SCALAR_INT, 1, &falseyScalar)); break;
					case 3: AH_SETB(astNode, pt_type_new(PT_CLASS_SCALAR_FLOAT, 1, &falseyScalar)); break;
					case 4:
					case 5: AH_SETB(astNode, pt_type_new(PT_CLASS_SCALAR_STRING, 1, &falseyScalar)); break;
					default: AH_SETB(astNode, pt_type_new(PT_CLASS_ARRAY_EXPR, 1, &falseyScalar)); break;
				}

				// the identical verdict of "assigned expr vs the sentinel":
				// the loop guarantees the sentinel is a possible value, so
				// only always-the-sentinel is decided
				zv::Val identicalTypeCallback = pt_native_closure(&identicalFalseyTypeCallbackBody, type.raw(), falseyType.raw());

				zv::Val notIdenticalSpecifiedTypes = ahNull();
				if (stored != NULL) {
					zend_object *falseContext = pt_type_specifier_context_create_false();
					AH_OKB(falseContext != NULL);
					zval falseContextZv;
					ZVAL_OBJ(&falseContextZv, falseContext);
					AH_SETB(notIdenticalSpecifiedTypes, inhSpecifyIdenticalAgainstType(prop(slots::identicalNarrowingHelper), assignedExpr.raw(), stored, astNode.raw(), falseyType.raw(), &falseContextZv, scope.raw(), assignedArgResult.raw(), identicalTypeCallback.raw()));
				}
				if (notIdenticalSpecifiedTypes.isNull()) {
					zv::Args nodeArgs{assignedExpr.raw(), astNode.raw()};
					AH_VALB(notIdentical, pt_type_new(PT_CLASS_BINARY_OP_NOT_IDENTICAL, 2, nodeArgs));
					zend_object *trueContext = pt_type_specifier_context_create_true();
					AH_OKB(trueContext != NULL);
					zval trueContextZv;
					ZVAL_OBJ(&trueContextZv, trueContext);
					AH_SETB(notIdenticalSpecifiedTypes, dnhSpecifyTypesForNode(prop(slots::defaultNarrowingHelper), scope.raw(), notIdentical.raw(), &trueContextZv));
				}
				AH_OKB(processSureTypesForConditionalExpressionsAfterAssign(nsr, scope.raw(), storage, varName, conditionalExpressions, notIdenticalSpecifiedTypes.raw(), withoutFalseyType.raw(), impurePoints.raw(), assignedExpr.raw(), stored));
				AH_OKB(processSureNotTypesForConditionalExpressionsAfterAssign(nsr, scope.raw(), storage, varName, conditionalExpressions, notIdenticalSpecifiedTypes.raw(), withoutFalseyType.raw(), impurePoints.raw(), assignedExpr.raw(), stored));

				zv::Val identicalSpecifiedTypes = ahNull();
				if (stored != NULL) {
					zend_object *trueContext = pt_type_specifier_context_create_true();
					AH_OKB(trueContext != NULL);
					zval trueContextZv;
					ZVAL_OBJ(&trueContextZv, trueContext);
					AH_SETB(identicalSpecifiedTypes, inhSpecifyIdenticalAgainstType(prop(slots::identicalNarrowingHelper), assignedExpr.raw(), stored, astNode.raw(), falseyType.raw(), &trueContextZv, scope.raw(), assignedArgResult.raw(), identicalTypeCallback.raw()));
				}
				if (identicalSpecifiedTypes.isNull()) {
					zv::Args nodeArgs{assignedExpr.raw(), astNode.raw()};
					AH_VALB(identical, pt_type_new(PT_CLASS_IDENTICAL_EXPR, 2, nodeArgs));
					zend_object *trueContext = pt_type_specifier_context_create_true();
					AH_OKB(trueContext != NULL);
					zval trueContextZv;
					ZVAL_OBJ(&trueContextZv, trueContext);
					AH_SETB(identicalSpecifiedTypes, dnhSpecifyTypesForNode(prop(slots::defaultNarrowingHelper), scope.raw(), identical.raw(), &trueContextZv));
				}
				AH_OKB(processSureTypesForConditionalExpressionsAfterAssign(nsr, scope.raw(), storage, varName, conditionalExpressions, identicalSpecifiedTypes.raw(), falseyType.raw(), impurePoints.raw(), assignedExpr.raw(), stored));
				AH_OKB(processSureNotTypesForConditionalExpressionsAfterAssign(nsr, scope.raw(), storage, varName, conditionalExpressions, identicalSpecifiedTypes.raw(), falseyType.raw(), impurePoints.raw(), assignedExpr.raw(), stored));
			}

			{
				AH_VALB(assignNode, newVariableAssignNode(var, assignedExpr.raw()));
				AH_OKB(nsrCallNodeCallback(nsr, nodeCallback, assignNode.raw(), scopeBeforeAssignEval.raw(), storage));
			}

			AH_VALB(nativeScope, pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scope.raw())));
			AH_VALB(nativeType, readAssignedValueType(nsr, stored, assignedExpr.raw(), nativeScope.raw()));
			AH_SETB(scope, pt_mutating_scope_assign_variable(Z_OBJ_P(scope.raw()), varName, type.raw(), nativeType.raw(), ahTrinary(PT_TRI_YES)));
			for (auto entry : zv::TableRef(Z_ARRVAL_P(conditionalExpressions.raw()))) {
				zval *holders = entry.value().raw();
				zend_string *exprString = entry.stringKeyOrNull();
				if (exprString != NULL) {
					AH_SETB(scope, pt_mutating_scope_add_conditional_expressions(Z_OBJ_P(scope.raw()), exprString, Z_ARRVAL_P(holders)));
				} else {
					zv::Str key = zv::Str::adopt(zend_long_to_str((zend_long) entry.indexKey()));
					AH_SETB(scope, pt_mutating_scope_add_conditional_expressions(Z_OBJ_P(scope.raw()), key.get(), Z_ARRVAL_P(holders)));
				}
			}

			if (ahIs(assignedExpr.raw(), PT_CLASS_ARRAY_EXPR)) {
				AH_VALB(rootVariable, newVariable(varName));
				AH_SETB(scope, processArrayByRefItems(nsr, scope.raw(), storage, varName, assignedExpr.raw(), rootVariable.raw()));
			}
		} else {
			zv::Val nameExprResult = zv::Val::copyOf(zv::Ref(AH_TARGET(target, variableNameResult)));
			if (nameExprResult.isNull()) {
				// Read-modify-write targets already evaluated the dynamic name in prepareTarget().
				AH_VALB(nameContext, pt_expression_context_without_value_flow(context));
				AH_SETB(nameExprResult, nsrProcessExprNode(nsr, stmt, AH_PROP(var, name), scope.raw(), storage, nodeCallback, nameContext.raw()));
				AH_OKB(pt_expression_result_has_yield_or(nameExprResult.raw(), hasYield));
				{
					AH_VALB(points, ahResultThrowPoints(nameExprResult.raw()));
					AH_OKB(ahMerge(throwPoints, points.raw()));
				}
				{
					AH_VALB(points, ahResultImpurePoints(nameExprResult.raw()));
					AH_OKB(ahMerge(impurePoints, points.raw()));
				}
				AH_OKB(pt_expression_result_is_always_terminating_or(nameExprResult.raw(), isAlwaysTerminating));
				AH_SETB(scope, ahResultScope(nameExprResult.raw()));
			}
			zv::Val storedAssignedExprResult;
			if (assignedValueResult != NULL) {
				storedAssignedExprResult = zv::Val::copyOf(zv::Ref(assignedValueResult));
			} else {
				AH_SETB(storedAssignedExprResult, pt_expression_result_storage_find(storage, assignedExpr.raw()));
			}
			zval *stored = storedAssignedExprResult.isNull() ? NULL : storedAssignedExprResult.raw();
			AH_VALB(valueType, readAssignedValueType(nsr, stored, assignedExpr.raw(), scopeBeforeAssignEval.raw()));
			AH_VALB(nativeScopeBeforeAssignEval, pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scopeBeforeAssignEval.raw())));
			AH_VALB(nativeValueType, readAssignedValueType(nsr, stored, assignedExpr.raw(), nativeScopeBeforeAssignEval.raw()));
			AH_SETB(scope, assignDynamicVariable(scope.raw(), nameExprResult.raw(), valueType.raw(), nativeValueType.raw()));
		}

		return true;
	}

	/* the Ternary part of the KIND_VARIABLE branch (twin 1191-1243) */
	[[nodiscard]] bool ternaryConditionalExpressions(zval *nsr, zval *stmt, zval *storage, zend_string *varName, zv::Val &conditionalExpressions, zval *assignedExpr, zval *scope, zval *impurePoints, zval *stored)
	{
		// a short ternary's truthy arm is its condition
		zval *ifExpr = AH_PROP(assignedExpr, if);
		if (Z_TYPE_P(ifExpr) == IS_NULL) {
			ifExpr = AH_PROP(assignedExpr, cond);
		}
		zval *elseExpr = AH_PROP(assignedExpr, else);
		zend_object *truthyContext = pt_type_specifier_context_create_truthy();
		AH_OKB(truthyContext != NULL);
		zend_object *falseyContext = pt_type_specifier_context_create_falsey();
		AH_OKB(falseyContext != NULL);
		zval truthyContextZv;
		ZVAL_OBJ(&truthyContextZv, truthyContext);
		zval falseyContextZv;
		ZVAL_OBJ(&falseyContextZv, falseyContext);

		// the walk already evaluated the arms on the cond-filtered
		// scopes - read the captured results instead of re-walking
		AH_VALB(capturedTernary, thGetCapturedResults(prop(slots::ternaryHandler), assignedExpr));
		zv::Val condScope;
		zv::Val truthySpecifiedTypes;
		zv::Val falseySpecifiedTypes;
		zv::Val truthyType;
		zv::Val falseyType;
		zv::Val truthyScope;
		zv::Val falsyScope;
		if (!capturedTernary.isNull()) {
			HashTable *captured = Z_ARRVAL_P(capturedTernary.raw());
			zval *ternaryCondResult = zend_hash_index_find(captured, 0);
			zval *ternaryIfResult = zend_hash_index_find(captured, 1);
			zval *ternaryElseResult = zend_hash_index_find(captured, 2);
			AH_SETB(condScope, ahResultScope(ternaryCondResult));
			AH_SETB(truthySpecifiedTypes, pt_expression_result_get_specified_types_for_scope(ternaryCondResult, condScope.raw(), &truthyContextZv));
			AH_SETB(falseySpecifiedTypes, pt_expression_result_get_specified_types_for_scope(ternaryCondResult, condScope.raw(), &falseyContextZv));
			AH_SETB(truthyType, pt_expression_result_get_type(ternaryIfResult));
			AH_SETB(falseyType, pt_expression_result_get_type(ternaryElseResult));
			// the arm scopes the value-implied narrowings below are read on -
			// the captured path never needed them, the unwalked one builds
			// the same pair to re-price the arms
			AH_SETB(truthyScope, pt_mutating_scope_apply_specified_types(Z_OBJ_P(condScope.raw()), truthySpecifiedTypes.raw()));
			AH_SETB(falsyScope, pt_mutating_scope_apply_specified_types(Z_OBJ_P(condScope.raw()), falseySpecifiedTypes.raw()));
		} else {
			zval *cond = AH_PROP(assignedExpr, cond);
			{
				AH_VALB(duplicate, pt_expression_result_storage_duplicate(storage));
				AH_VALB(noop, newNoopNodeCallback());
				AH_VALB(condContext, pt_expression_context_create_deep(false));
				AH_VALB(condResult, nsrProcessExprNode(nsr, stmt, cond, scope, duplicate.raw(), noop.raw(), condContext.raw()));
				AH_SETB(condScope, ahResultScope(condResult.raw()));
			}
			AH_SETB(truthySpecifiedTypes, dnhSpecifyTypesForNode(prop(slots::defaultNarrowingHelper), condScope.raw(), cond, &truthyContextZv));
			AH_SETB(falseySpecifiedTypes, dnhSpecifyTypesForNode(prop(slots::defaultNarrowingHelper), condScope.raw(), cond, &falseyContextZv));
			AH_SETB(truthyScope, pt_mutating_scope_apply_specified_types(Z_OBJ_P(condScope.raw()), truthySpecifiedTypes.raw()));
			AH_SETB(falsyScope, pt_mutating_scope_apply_specified_types(Z_OBJ_P(condScope.raw()), falseySpecifiedTypes.raw()));
			// the arms of this unwalked ternary are re-priced on the
			// narrowed cond scopes - scope state answers plain reads,
			// anything else is priced on demand
			AH_SETB(truthyType, scopeStateOrSyntheticType(nsr, ifExpr, truthyScope.raw()));
			AH_SETB(falseyType, scopeStateOrSyntheticType(nsr, elseExpr, falsyScope.raw()));
		}

		// The variable can prove an arm was taken even when the arm types overlap:
		// the part of an arm's type not producible by the other arm implies that
		// arm's condition outcome. With fully disjoint arms both remainders are
		// the full arm types.
		AH_VALB(truthyRemainder, pt_type_combinator_remove(truthyType.raw(), falseyType.raw()));
		zend_long truthyDisjoint = ahTypeOpTri(falseyType.raw(), PT_OP_IS_SUPER_TYPE_OF, 1, truthyRemainder.raw());
		AH_OKB(truthyDisjoint >= 0);
		if (truthyDisjoint == PT_TRI_NO) {
			AH_OKB(processSureTypesForConditionalExpressionsAfterAssign(nsr, condScope.raw(), storage, varName, conditionalExpressions, truthySpecifiedTypes.raw(), truthyRemainder.raw(), impurePoints, assignedExpr, stored));
			AH_OKB(processSureNotTypesForConditionalExpressionsAfterAssign(nsr, condScope.raw(), storage, varName, conditionalExpressions, truthySpecifiedTypes.raw(), truthyRemainder.raw(), impurePoints, assignedExpr, stored));
			AH_OKB(processTernaryArmValueImpliedTypesAfterAssign(nsr, truthyScope.raw(), storage, varName, conditionalExpressions, ifExpr, truthyRemainder.raw(), falseyType.raw(), impurePoints, assignedExpr, stored));
		}
		AH_VALB(falseyRemainder, pt_type_combinator_remove(falseyType.raw(), truthyType.raw()));
		zend_long falseyDisjoint = ahTypeOpTri(truthyType.raw(), PT_OP_IS_SUPER_TYPE_OF, 1, falseyRemainder.raw());
		AH_OKB(falseyDisjoint >= 0);
		if (falseyDisjoint == PT_TRI_NO) {
			AH_OKB(processSureTypesForConditionalExpressionsAfterAssign(nsr, condScope.raw(), storage, varName, conditionalExpressions, falseySpecifiedTypes.raw(), falseyRemainder.raw(), impurePoints, assignedExpr, stored));
			AH_OKB(processSureNotTypesForConditionalExpressionsAfterAssign(nsr, condScope.raw(), storage, varName, conditionalExpressions, falseySpecifiedTypes.raw(), falseyRemainder.raw(), impurePoints, assignedExpr, stored));
			AH_OKB(processTernaryArmValueImpliedTypesAfterAssign(nsr, falsyScope.raw(), storage, varName, conditionalExpressions, elseExpr, falseyRemainder.raw(), truthyType.raw(), impurePoints, assignedExpr, stored));
		}

		return true;
	}

	/* $nodeScopeResolver->findScopeStateType($expr, $scope) ?? $nodeScopeResolver->processSyntheticOnDemand($expr, $scope)->getTypeOnScope($scope, $scope->nativeTypesPromoted) */
	static zv::Val scopeStateOrSyntheticType(zval *nsr, zval *expr, zval *scope)
	{
		AH_VAL(stateType, nsrFindScopeStateType(nsr, expr, scope));
		if (!stateType.isNull()) return stateType;
		AH_VAL(result, nsrProcessSyntheticOnDemand(nsr, expr, scope));
		return ahTypeOnScope(result.raw(), scope);
	}

	/* new ConstFetch(new Name($name)) */
	static zv::Val newConstFetch(const char *name, size_t len)
	{
		zv::Val nameString = zv::Val::string(name, len);
		AH_VAL(nameNode, pt_name_node_new(PT_CLASS_NAME, nameString.raw()));
		return pt_type_new(PT_CLASS_CONST_FETCH, 1, nameNode.raw());
	}

	/* }}} */

	/* (twin 1871) $assignedValueResult NULL for null */
	static zv::Val readAssignedValueType(zval *nsr, zval *assignedValueResult, zval *assignedExpr, zval *scope)
	{
		if (assignedValueResult != NULL) return ahTypeOnScope(assignedValueResult, scope);

		return scopeStateOrSyntheticType(nsr, assignedExpr, scope);
	}

	/* (twin 1942) */
	static zv::Val assignDynamicVariable(zval *scopeArg, zval *nameResult, zval *valueType, zval *nativeValueType)
	{
		AH_VAL(rawNameType, pt_expression_result_get_type(nameResult));
		AH_VAL(nameType, ahCall(rawNameType.raw(), PT_LC("tostring"), 0, NULL));
		AH_VAL(rawNativeNameType, pt_expression_result_get_native_type(nameResult));
		AH_VAL(nativeNameType, ahCall(rawNativeNameType.raw(), PT_LC("tostring"), 0, NULL));
		zv::Val names = ahEmptyArray();
		{
			AH_VAL(constantStrings, ahCall(nameType.raw(), PT_LC("getconstantstrings"), 0, NULL));
			for (auto entry : zv::TableRef(Z_ARRVAL_P(constantStrings.raw()))) {
				zval *name = entry.value().deref().raw();
				AH_VAL(value, ahTypeOp(name, PT_OP_GET_VALUE, 0, NULL));
				if (Z_TYPE_P(value.raw()) == IS_LONG) {
					ahSetIndex(names, (zend_ulong) Z_LVAL_P(value.raw()), name);
				} else {
					zv::Str key = zv::Str::adopt(zval_get_string(value.raw()));
					ahSetKey(names, key.get(), name);
				}
			}
		}
		{
			AH_VAL(defined, pt_mutating_scope_get_defined_variables(Z_OBJ_P(scopeArg)));
			AH_VAL(maybeDefined, pt_mutating_scope_get_maybe_defined_variables(Z_OBJ_P(scopeArg)));
			zv::Val all = zv::Val::copyOf(defined.ref());
			AH_OK(ahMerge(all, maybeDefined.raw()));
			for (auto entry : zv::TableRef(Z_ARRVAL_P(all.raw()))) {
				zval *name = entry.value().deref().raw();
				zend_string *nameString = Z_TYPE_P(name) == IS_STRING ? Z_STR_P(name) : NULL;
				zv::Str converted;
				if (nameString == NULL) {
					converted = zv::Str::adopt(zval_get_string(name));
					nameString = converted.get();
				}
				AH_VAL(nameStringType, newConstantStringType(nameString));
				zend_long isSuper = ahTypeOpTri(nameType.raw(), PT_OP_IS_SUPER_TYPE_OF, 1, nameStringType.raw());
				AH_OK(isSuper >= 0);
				if (isSuper == PT_TRI_NO) continue;
				ahSetKey(names, nameString, nameStringType.raw());
			}
		}

		zval *beforeScope = scopeArg;
		zv::Val scope = zv::Val::copyOf(zv::Ref(scopeArg));
		for (auto entry : zv::TableRef(Z_ARRVAL_P(names.raw()))) {
			zval *nameStringType = entry.value().raw();
			AH_VAL(nameValue, ahTypeOp(nameStringType, PT_OP_GET_VALUE, 0, NULL));
			if (UNEXPECTED(Z_TYPE_P(nameValue.raw()) != IS_STRING)) {
				zend_type_error("PHPStan\\Analyser\\MutatingScope::hasVariableType(): Argument #1 ($variableName) must be of type string, %s given", zend_zval_value_name(nameValue.raw()));
				return zv::Val();
			}
			zend_string *name = Z_STR_P(nameValue.raw());
			if (zend_string_equals_literal(name, "this")) continue;
			AH_VAL(certainty, pt_mutating_scope_has_variable_type(Z_OBJ_P(beforeScope), name));
			zend_long certaintyValue = ahTri(certainty.raw());
			AH_OK(certaintyValue >= 0);
			zv::Val type = zv::Val::copyOf(zv::Ref(valueType));
			zv::Val nativeType = zv::Val::copyOf(zv::Ref(nativeValueType));
			if (certaintyValue != PT_TRI_NO) {
				bool equal;
				AH_OK(ahEquals(nameType.raw(), nameStringType, equal));
				if (!equal) {
					AH_VAL(variableType, pt_mutating_scope_get_variable_type(Z_OBJ_P(beforeScope), name));
					zv::Args unionArgs{variableType.raw(), type.raw()};
					AH_SET(type, pt_type_combinator_union(2, unionArgs));
				}
				AH_OK(ahEquals(nativeNameType.raw(), nameStringType, equal));
				if (!equal) {
					AH_VAL(nativeBeforeScope, pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(beforeScope)));
					AH_VAL(variableType, pt_mutating_scope_get_variable_type(Z_OBJ_P(nativeBeforeScope.raw()), name));
					zv::Args unionArgs{variableType.raw(), nativeType.raw()};
					AH_SET(nativeType, pt_type_combinator_union(2, unionArgs));
				}
			}
			bool exact;
			AH_OK(ahEquals(nameType.raw(), nameStringType, exact));
			zval *newCertainty = exact ? ahTrinary(PT_TRI_YES) : ahTrinary(pt_trinary_or(certaintyValue, PT_TRI_MAYBE));
			AH_SET(scope, pt_mutating_scope_assign_variable(Z_OBJ_P(scope.raw()), name, type.raw(), nativeType.raw(), newCertainty));
		}
		return scope;
	}

	/* (twin 1980) */
	static zval *unwrapAssign(zval *expr)
	{
		while (ahIs(expr, PT_CLASS_ASSIGN_EXPR)) {
			expr = AH_PROP(expr, expr);
		}

		return expr;
	}

	/* {{{ the conditional expressions of a variable assignment */

	/* (twin 1994) */
	[[nodiscard]] bool processSureTypesForConditionalExpressionsAfterAssign(zval *nsr, zval *scope, zval *storage, zend_string *variableName, zv::Val &conditionalExpressions, zval *specifiedTypes, zval *variableType, zval *rhsImpurePoints, zval *assignedExpr, zval *assignedValueResult)
	{
		return processSpecifiedTypesForConditionalExpressionsAfterAssign(nsr, scope, storage, variableName, conditionalExpressions, specifiedTypes, variableType, rhsImpurePoints, assignedExpr, assignedValueResult, true);
	}

	/* (twin 2036) */
	[[nodiscard]] bool processSureNotTypesForConditionalExpressionsAfterAssign(zval *nsr, zval *scope, zval *storage, zend_string *variableName, zv::Val &conditionalExpressions, zval *specifiedTypes, zval *variableType, zval *rhsImpurePoints, zval *assignedExpr, zval *assignedValueResult)
	{
		return processSpecifiedTypesForConditionalExpressionsAfterAssign(nsr, scope, storage, variableName, conditionalExpressions, specifiedTypes, variableType, rhsImpurePoints, assignedExpr, assignedValueResult, false);
	}

	/* the shared body of the two above: the sure types (intersected, isset
	 * holders of maybe certainty) or the sure-not types (removed, isset
	 * holders of no certainty over NeverType) */
	[[nodiscard]] bool processSpecifiedTypesForConditionalExpressionsAfterAssign(zval *nsr, zval *scope, zval *storage, zend_string *variableName, zv::Val &conditionalExpressions, zval *specifiedTypes, zval *variableType, zval *rhsImpurePoints, zval *assignedExpr, zval *assignedValueResult, bool sure)
	{
		if (UNEXPECTED(Z_TYPE_P(specifiedTypes) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function %s() on %s", sure ? "getSureTypes" : "getSureNotTypes", zend_zval_value_name(specifiedTypes));
			return false;
		}
		AH_VALB(types, sure ? pt_specified_types_get_sure_types(Z_OBJ_P(specifiedTypes)) : pt_specified_types_get_sure_not_types(Z_OBJ_P(specifiedTypes)));
		if (Z_TYPE_P(types.raw()) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL_P(types.raw())) == 0) return true;

		for (auto entry : zv::TableRef(Z_ARRVAL_P(types.raw()))) {
			zval *pair = entry.value().deref().raw();
			zval *expr = zend_hash_index_find(Z_ARRVAL_P(pair), 0);
			zval *exprType = zend_hash_index_find(Z_ARRVAL_P(pair), 1);
			ZVAL_DEREF(expr);
			ZVAL_DEREF(exprType);
			bool safe;
			AH_OKB(isExprSafeToProjectThroughVariable(expr, variableName, rhsImpurePoints, assignedExpr, safe));
			if (!safe) continue;

			if (ahIs(expr, PT_CLASS_ISSET_EXPR)) {
				zval *innerExpr = AH_PROP(expr, expr);
				zv::Str innerExprString = zv::Str::adopt(printExpr(prop(slots::exprPrinter), innerExpr));
				AH_OKB(!innerExprString.isNull());
				if (sure) {
					AH_VALB(holderType, currentTypeForConditionalHolder(nsr, scope, storage, innerExpr, assignedExpr, assignedValueResult));
					AH_OKB(addConditionalExpressionHolder(conditionalExpressions, variableName, variableType, innerExpr, innerExprString.get(), holderType.raw(), PT_TRI_MAYBE));
				} else {
					AH_VALB(never, newNeverType());
					AH_OKB(addConditionalExpressionHolder(conditionalExpressions, variableName, variableType, innerExpr, innerExprString.get(), never.raw(), PT_TRI_NO));
				}
				continue;
			}

			zend_string *stringKey = entry.stringKeyOrNull();
			zv::Str exprString = stringKey != NULL ? zv::Str::copyOf(stringKey) : zv::Str::adopt(zend_long_to_str((zend_long) entry.indexKey()));

			AH_VALB(currentType, currentTypeForConditionalHolder(nsr, scope, storage, expr, assignedExpr, assignedValueResult));
			zv::Val holderType;
			if (sure) {
				zv::Args intersectArgs{currentType.raw(), exprType};
				AH_SETB(holderType, pt_type_combinator_intersect(2, intersectArgs));
			} else {
				AH_SETB(holderType, pt_type_combinator_remove(currentType.raw(), exprType));
			}
			AH_OKB(addConditionalExpressionHolder(conditionalExpressions, variableName, variableType, expr, exprString.get(), holderType.raw(), PT_TRI_YES));
		}

		return true;
	}

	/* (twin 2081) */
	static zv::Val currentTypeForConditionalHolder(zval *nsr, zval *scope, zval *storage, zval *expr, zval *assignedExpr, zval *assignedValueResult)
	{
		// A by-ref write lands in the variable's tracked type, so read it from the
		// scope state (getVariableType is null-safe for superglobals/undefined too).
		// Method calls and other non-variable holder exprs have no by-ref hazard and
		// keep reading their stored result.
		zend_string *name = ahStringVariableName(expr);
		if (name != NULL) {
			AH_VAL(has, pt_mutating_scope_has_variable_type(Z_OBJ_P(scope), name));
			zend_long hasValue = ahTri(has.raw());
			AH_OK(hasValue >= 0);
			if (hasValue == PT_TRI_YES) return pt_mutating_scope_get_variable_type(Z_OBJ_P(scope), name);
		}

		// the assigned expression's own result is threaded in by the caller - its
		// processing is still in flight, so an on-demand walk would re-enter it
		if (Z_OBJ_P(expr) == Z_OBJ_P(assignedExpr) && assignedValueResult != NULL) return ahTypeOnScope(assignedValueResult, scope);

		// holder exprs are usually subexpressions of the walked condition - read
		// them from the walk's own storage (the scope's storage stack misses it
		// on loop-convergence passes); synthetic terms narrowing extensions built
		// (@phpstan-assert property fetches etc.) answer from scope state or a walk
		AH_VAL(storedResult, pt_expression_result_storage_find(storage, expr));
		if (!storedResult.isNull()) return ahTypeOnScope(storedResult.raw(), scope);

		return nsrReadScopeStateOrSyntheticType(nsr, expr, scope);
	}

	/* (twin 2120) */
	[[nodiscard]] bool processTernaryArmValueImpliedTypesAfterAssign(zval *nsr, zval *armScope, zval *storage, zend_string *variableName, zv::Val &conditionalExpressions, zval *armExpr, zval *remainderType, zval *otherArmType, zval *rhsImpurePoints, zval *assignedExpr, zval *assignedValueResult)
	{
		AH_VALB(otherArmFiniteTypes, ahCall(otherArmType, PT_LC("getfinitetypes"), 0, NULL));
		uint32_t count = zend_hash_num_elements(Z_ARRVAL_P(otherArmFiniteTypes.raw()));
		if (count == 0 || count > PT_AH_TERNARY_ARM_EXCLUDED_VALUES_LIMIT) return true;

		for (auto entry : zv::TableRef(Z_ARRVAL_P(otherArmFiniteTypes.raw()))) {
			zval *finiteType = entry.value().deref().raw();
			zend_long isSuper = ahTypeOpTri(remainderType, PT_OP_IS_SUPER_TYPE_OF, 1, finiteType);
			AH_OKB(isSuper >= 0);
			if (isSuper != PT_TRI_NO) continue;

			AH_VALB(typeExpr, newTypeExpr(finiteType));
			zv::Args nodeArgs{armExpr, typeExpr.raw()};
			AH_VALB(notIdentical, pt_type_new(PT_CLASS_BINARY_OP_NOT_IDENTICAL, 2, nodeArgs));
			zend_object *trueContext = pt_type_specifier_context_create_true();
			AH_OKB(trueContext != NULL);
			zval trueContextZv;
			ZVAL_OBJ(&trueContextZv, trueContext);
			AH_VALB(specifiedTypes, dnhSpecifyTypesForNode(prop(slots::defaultNarrowingHelper), armScope, notIdentical.raw(), &trueContextZv));
			AH_OKB(processSureTypesForConditionalExpressionsAfterAssign(nsr, armScope, storage, variableName, conditionalExpressions, specifiedTypes.raw(), remainderType, rhsImpurePoints, assignedExpr, assignedValueResult));
			AH_OKB(processSureNotTypesForConditionalExpressionsAfterAssign(nsr, armScope, storage, variableName, conditionalExpressions, specifiedTypes.raw(), remainderType, rhsImpurePoints, assignedExpr, assignedValueResult));
		}

		return true;
	}

	/* $conditionalExpressions[$key1][$key2] = $value (symtable keys; the
	 * inner array created when absent) */
	static void nestedSet(zv::Val &conditionalExpressions, zend_string *key1, zend_string *key2, zval *value)
	{
		zval *outer = conditionalExpressions.raw();
		SEPARATE_ARRAY(outer);
		zval *inner = zend_symtable_find(Z_ARRVAL_P(outer), key1);
		if (inner == NULL) {
			zval emptyArray;
			ZVAL_EMPTY_ARRAY(&emptyArray);
			inner = zend_symtable_update(Z_ARRVAL_P(outer), key1, &emptyArray);
		}
		SEPARATE_ARRAY(inner);
		Z_TRY_ADDREF_P(value);
		zend_symtable_update(Z_ARRVAL_P(inner), key2, value);
	}

	/* (twin 2160) */
	[[nodiscard]] static bool addConditionalExpressionHolder(zv::Val &conditionalExpressions, zend_string *variableName, zval *variableType, zval *holderExpr, zend_string *holderExprString, zval *holderType, zend_long holderCertainty)
	{
		{
			zval *outer = conditionalExpressions.raw();
			if (zend_symtable_find(Z_ARRVAL_P(outer), holderExprString) == NULL) {
				SEPARATE_ARRAY(outer);
				zval emptyArray;
				ZVAL_EMPTY_ARRAY(&emptyArray);
				zend_symtable_update(Z_ARRVAL_P(outer), holderExprString, &emptyArray);
			}
		}

		AH_VALB(variable, newVariable(variableName));
		AH_VALB(conditionHolder, newExpressionTypeHolder(variable.raw(), variableType, PT_TRI_YES));
		zv::Str conditionKey = zv::Str::adopt(zend_string_concat2(PT_LC("$"), ZSTR_VAL(variableName), ZSTR_LEN(variableName)));
		zv::Val conditions = ahEmptyArray();
		ahSetKey(conditions, conditionKey.get(), conditionHolder.raw());
		AH_VALB(typeHolder, newExpressionTypeHolder(holderExpr, holderType, holderCertainty));
		AH_VALB(holder, newConditionalExpressionHolder(conditions.raw(), typeHolder.raw()));
		zv::Str holderKey = zv::Str::adopt(conditionalHolderKey(holder.raw()));
		AH_OKB(!holderKey.isNull());
		nestedSet(conditionalExpressions, holderExprString, holderKey.get(), holder.raw());

		return true;
	}

	/* (twin 2191) */
	static void mergeConditionalExpressions(zv::Val &conditionalExpressions, zval *newConditionalExpressions)
	{
		for (auto entry : zv::TableRef(Z_ARRVAL_P(newConditionalExpressions))) {
			zval *holders = entry.value().deref().raw();
			for (auto holderEntry : zv::TableRef(Z_ARRVAL_P(holders))) {
				zval *outer = conditionalExpressions.raw();
				SEPARATE_ARRAY(outer);
				zval *inner = entry.stringKeyOrNull() != NULL ? zend_hash_find(Z_ARRVAL_P(outer), entry.stringKey()) : zend_hash_index_find(Z_ARRVAL_P(outer), entry.indexKey());
				if (inner == NULL) {
					zval emptyArray;
					ZVAL_EMPTY_ARRAY(&emptyArray);
					inner = entry.stringKeyOrNull() != NULL ? zend_hash_add_new(Z_ARRVAL_P(outer), entry.stringKey(), &emptyArray) : zend_hash_index_add_new(Z_ARRVAL_P(outer), entry.indexKey(), &emptyArray);
				}
				SEPARATE_ARRAY(inner);
				zval *holder = holderEntry.value().raw();
				Z_TRY_ADDREF_P(holder);
				if (holderEntry.stringKeyOrNull() != NULL) {
					zend_hash_update(Z_ARRVAL_P(inner), holderEntry.stringKey(), holder);
				} else {
					zend_hash_index_update(Z_ARRVAL_P(inner), holderEntry.indexKey(), holder);
				}
			}
		}
	}

	/* (twin 2213) */
	zv::Val processMatchForConditionalExpressionsAfterAssign(zval *scope, zend_string *variableName, zval *expr)
	{
		// the pairs were captured while the match (the assigned expression) was
		// processed just above - no arm re-walk
		AH_VAL(armScopesAndTypes, mhGetCapturedArmScopesAndTypes(prop(slots::matchHandler), expr));
		if (armScopesAndTypes.isNull() || zend_hash_num_elements(Z_ARRVAL_P(armScopesAndTypes.raw())) < 2) return ahEmptyArray();

		zv::Arr armScopes = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(armScopesAndTypes.raw())));
		for (auto entry : zv::TableRef(Z_ARRVAL_P(armScopesAndTypes.raw()))) {
			HashTable *pair = Z_ARRVAL_P(entry.value().deref().raw());
			zval *armScope = zend_hash_index_find(pair, 0);
			zval *armType = zend_hash_index_find(pair, 1);
			ZVAL_DEREF(armScope);
			ZVAL_DEREF(armType);
			AH_VAL(assigned, pt_mutating_scope_assign_variable(Z_OBJ_P(armScope), variableName, armType, armType, ahTrinary(PT_TRI_YES)));
			armScopes.push(std::move(assigned));
		}

		HashTable *armScopesTable = armScopes.table();
		uint32_t count = zend_hash_num_elements(armScopesTable);
		zv::Val mergedScope = zv::Val::copyOf(zv::Ref(zend_hash_index_find(armScopesTable, 0)));
		for (uint32_t i = 1; i < count; i++) {
			AH_SET(mergedScope, pt_mutating_scope_merge_with(Z_OBJ_P(zend_hash_index_find(armScopesTable, i)), mergedScope.raw(), true));
		}

		AH_VAL(existingConditionalExpressions, pt_mutating_scope_get_conditional_expressions(Z_OBJ_P(scope)));
		zv::Val newConditionalExpressions = ahEmptyArray();
		AH_VAL(mergedConditionalExpressions, pt_mutating_scope_get_conditional_expressions(Z_OBJ_P(mergedScope.raw())));
		for (auto entry : zv::TableRef(Z_ARRVAL_P(mergedConditionalExpressions.raw()))) {
			zend_string *exprString = entry.stringKeyOrNull();
			zval *existing = exprString != NULL ? zend_hash_find(Z_ARRVAL_P(existingConditionalExpressions.raw()), exprString) : zend_hash_index_find(Z_ARRVAL_P(existingConditionalExpressions.raw()), entry.indexKey());
			if (existing != NULL) ZVAL_DEREF(existing);
			for (auto holderEntry : zv::TableRef(Z_ARRVAL_P(entry.value().deref().raw()))) {
				zend_string *key = holderEntry.stringKeyOrNull();
				if (existing != NULL && Z_TYPE_P(existing) == IS_ARRAY) {
					zval *found = key != NULL ? zend_hash_find(Z_ARRVAL_P(existing), key) : zend_hash_index_find(Z_ARRVAL_P(existing), holderEntry.indexKey());
					if (found != NULL && Z_TYPE_P(found) != IS_NULL) continue;
				}
				zval *outer = newConditionalExpressions.raw();
				SEPARATE_ARRAY(outer);
				zval *inner = exprString != NULL ? zend_hash_find(Z_ARRVAL_P(outer), exprString) : zend_hash_index_find(Z_ARRVAL_P(outer), entry.indexKey());
				if (inner == NULL) {
					zval emptyArray;
					ZVAL_EMPTY_ARRAY(&emptyArray);
					inner = exprString != NULL ? zend_hash_add_new(Z_ARRVAL_P(outer), exprString, &emptyArray) : zend_hash_index_add_new(Z_ARRVAL_P(outer), entry.indexKey(), &emptyArray);
				}
				SEPARATE_ARRAY(inner);
				zval *holder = holderEntry.value().raw();
				Z_TRY_ADDREF_P(holder);
				if (key != NULL) {
					zend_hash_update(Z_ARRVAL_P(inner), key, holder);
				} else {
					zend_hash_index_update(Z_ARRVAL_P(inner), holderEntry.indexKey(), holder);
				}
			}
		}

		return newConditionalExpressions;
	}

	/* the ConditionalExpressionHolder / ExpressionTypeHolder reads of the
	 * derived-conditional loop: the native classes' slots (both final) */
	static zval *holderTypeHolder(zval *holder)
	{
		return zv::ObjRef(holder).propAt(PT_CEH_PROP_TYPEHOLDER).deref().raw();
	}

	static zval *holderConditions(zval *holder)
	{
		return zv::ObjRef(holder).propAt(PT_CEH_PROP_CONDS).deref().raw();
	}

	/* (twin 2267) */
	[[nodiscard]] static bool processDerivedConditionalExpressionsAfterAssign(zval *nsr, zval *scope, zend_string *variableName, zv::Val &conditionalExpressions, zval *assignedExpr, zval *assignedType, zval *rhsImpurePoints)
	{
		if (zend_hash_num_elements(Z_ARRVAL_P(rhsImpurePoints)) > 0) return true;
		AH_VALB(scopeConditionalExpressions, pt_mutating_scope_get_conditional_expressions(Z_OBJ_P(scope)));
		if (zend_hash_num_elements(Z_ARRVAL_P(scopeConditionalExpressions.raw())) == 0) return true;

		zv::Str targetExprString = zv::Str::adopt(zend_string_concat2(PT_LC("$"), ZSTR_VAL(variableName), ZSTR_LEN(variableName)));
		zend_long evaluations = 0;
		zv::Val seenReadExprStrings = ahEmptyArray();
		AH_VALB(readVariables, nodeFinderFindVariables(assignedExpr));
		for (auto readEntry : zv::TableRef(Z_ARRVAL_P(readVariables.raw()))) {
			zval *readVariable = readEntry.value().deref().raw();
			zend_string *readName = ahVariableName(readVariable);
			if (readName == NULL || zend_string_equals(readName, variableName)) continue;
			zv::Str readExprString = zv::Str::adopt(zend_string_concat2(PT_LC("$"), ZSTR_VAL(readName), ZSTR_LEN(readName)));
			if (zend_symtable_find(Z_ARRVAL_P(seenReadExprStrings.raw()), readExprString.get()) != NULL) continue;
			zval trueZv;
			ZVAL_TRUE(&trueZv);
			ahSetKey(seenReadExprStrings, readExprString.get(), &trueZv);

			zval *holders = zend_symtable_find(Z_ARRVAL_P(scopeConditionalExpressions.raw()), readExprString.get());
			if (holders == NULL) continue;
			ZVAL_DEREF(holders);
			if (Z_TYPE_P(holders) != IS_ARRAY) continue;
			for (auto holderEntry : zv::TableRef(Z_ARRVAL_P(holders))) {
				zval *holder = holderEntry.value().deref().raw();
				zval *consequent = holderTypeHolder(holder);
				if (pt_holder_certainty_value(Z_OBJ_P(consequent)) != PT_TRI_YES) continue;

				zval *conditionHolders = holderConditions(holder);
				zv::Val evalScope = zv::Val::copyOf(zv::Ref(scope));
				bool stale = false;
				for (auto conditionEntry : zv::TableRef(Z_ARRVAL_P(conditionHolders))) {
					zval *conditionHolder = conditionEntry.value().deref().raw();
					zend_string *conditionExprString = conditionEntry.stringKeyOrNull();
					if ((conditionExprString != NULL && zend_string_equals(conditionExprString, targetExprString.get())) || pt_holder_certainty_value(Z_OBJ_P(conditionHolder)) != PT_TRI_YES) {
						// a condition on the just-overwritten variable is stale
						stale = true;
						break;
					}
					zval *conditionType = OBJ_PROP_NUM(Z_OBJ_P(conditionHolder), PT_ETH_PROP_TYPE);
					AH_SETB(evalScope, pt_mutating_scope_assign_expression(Z_OBJ_P(evalScope.raw()), Z_OBJ_P(OBJ_PROP_NUM(Z_OBJ_P(conditionHolder), PT_ETH_PROP_EXPR)), conditionType, conditionType));
				}
				if (stale) continue;

				if (++evaluations > PT_AH_DERIVED_CONDITIONAL_EXPRESSIONS_LIMIT) return true;

				zval *consequentType = OBJ_PROP_NUM(Z_OBJ_P(consequent), PT_ETH_PROP_TYPE);
				AH_SETB(evalScope, pt_mutating_scope_assign_expression(Z_OBJ_P(evalScope.raw()), Z_OBJ_P(OBJ_PROP_NUM(Z_OBJ_P(consequent), PT_ETH_PROP_EXPR)), consequentType, consequentType));
				AH_VALB(derivedType, nsrReadScopeStateOrSyntheticType(nsr, assignedExpr, evalScope.raw()));
				bool equal;
				AH_OKB(ahEquals(derivedType.raw(), assignedType, equal));
				if (equal) continue;

				AH_VALB(variable, newVariable(variableName));
				AH_VALB(typeHolder, newExpressionTypeHolder(variable.raw(), derivedType.raw(), PT_TRI_YES));
				AH_VALB(derivedHolder, newConditionalExpressionHolder(conditionHolders, typeHolder.raw()));
				zv::Str derivedKey = zv::Str::adopt(conditionalHolderKey(derivedHolder.raw()));
				AH_OKB(!derivedKey.isNull());
				nestedSet(conditionalExpressions, targetExprString.get(), derivedKey.get(), derivedHolder.raw());
			}
		}

		return true;
	}

	/* (twin 2349) */
	[[nodiscard]] bool processInArrayForConditionalExpressionsAfterAssign(zval *nsr, zval *scope, zend_string *variableName, zv::Val &conditionalExpressions, zval *assignedExpr, zval *assignedType, zval *rhsImpurePoints)
	{
		if (!ahNameIs(AH_PROP(assignedExpr, name), PT_LC("in_array"))) return true;
		bool firstClassCallable;
		AH_OKB(pt_call_like_is_first_class_callable(Z_OBJ_P(assignedExpr), firstClassCallable));
		if (firstClassCallable) return true;
		{
			zend_long isTrue = ahCallTri(assignedType, PT_LC("istrue"), 0, NULL);
			AH_OKB(isTrue >= 0);
			if (isTrue != PT_TRI_MAYBE) return true;
		}

		HashTable *args = ahArgs(assignedExpr);
		if (args == NULL || zend_hash_num_elements(args) < 2) return true;
		zval *arg0 = zend_hash_index_find(args, 0);
		zval *arg1 = zend_hash_index_find(args, 1);
		if (arg0 == NULL || arg1 == NULL) return true;
		ZVAL_DEREF(arg0);
		ZVAL_DEREF(arg1);
		if (Z_TYPE_P(AH_PROP(arg0, name)) != IS_NULL || zend_is_true(AH_PROP(arg0, unpack)) || Z_TYPE_P(AH_PROP(arg1, name)) != IS_NULL || zend_is_true(AH_PROP(arg1, unpack))) return true;

		zval *needleExpr = AH_PROP(arg0, value);
		bool safe;
		AH_OKB(isExprSafeToProjectThroughVariable(needleExpr, variableName, rhsImpurePoints, assignedExpr, safe));
		if (!safe) return true;

		AH_VALB(haystackType, nsrReadScopeStateOrSyntheticType(nsr, AH_PROP(arg1, value), scope));
		{
			zend_long isConstantArray = ahTypeOpTri(haystackType.raw(), PT_OP_IS_CONSTANT_ARRAY, 0, NULL);
			AH_OKB(isConstantArray >= 0);
			if (isConstantArray != PT_TRI_YES) return true;
		}
		AH_VALB(constantArrays, ahTypeOp(haystackType.raw(), PT_OP_GET_CONSTANT_ARRAYS, 0, NULL));
		if (zend_hash_num_elements(Z_ARRVAL_P(constantArrays.raw())) != 1) return true;

		zv::Val guaranteedValueTypes = ahEmptyArray();
		zval *constantArray = zend_hash_index_find(Z_ARRVAL_P(constantArrays.raw()), 0);
		AH_OKB(constantArray != NULL);
		ZVAL_DEREF(constantArray);
		AH_VALB(valueTypes, ahTypeOp(constantArray, PT_OP_GET_VALUE_TYPES, 0, NULL));
		for (auto entry : zv::TableRef(Z_ARRVAL_P(valueTypes.raw()))) {
			zval *valueType = entry.value().deref().raw();
			zval index;
			if (entry.stringKeyOrNull() != NULL) {
				ZVAL_STR(&index, entry.stringKey());
			} else {
				ZVAL_LONG(&index, (zend_long) entry.indexKey());
			}
			{
				AH_VALB(optional, ahCall(constantArray, PT_LC("isoptionalkey"), 1, &index));
				if (zend_is_true(optional.raw())) continue;
			}
			{
				zend_long isConstantScalar = ahTypeOpTri(valueType, PT_OP_IS_CONSTANT_SCALAR_VALUE, 0, NULL);
				AH_OKB(isConstantScalar >= 0);
				if (isConstantScalar != PT_TRI_YES) continue;
			}
			AH_VALB(scalarValues, ahTypeOp(valueType, PT_OP_GET_CONSTANT_SCALAR_VALUES, 0, NULL));
			if (zend_hash_num_elements(Z_ARRVAL_P(scalarValues.raw())) != 1) continue;
			zval *first = zend_hash_index_find(Z_ARRVAL_P(scalarValues.raw()), 0);
			if (first != NULL) {
				ZVAL_DEREF(first);
				if (Z_TYPE_P(first) == IS_DOUBLE && zend_isnan(Z_DVAL_P(first))) {
					// NAN never compares equal, not even to itself
					continue;
				}
			}

			ahPushRef(guaranteedValueTypes, valueType);
		}

		if (zend_hash_num_elements(Z_ARRVAL_P(guaranteedValueTypes.raw())) == 0) return true;

		zv::Str needleString = zv::Str::adopt(printExpr(prop(slots::exprPrinter), needleExpr));
		AH_OKB(!needleString.isNull());
		HashTable *guaranteed = Z_ARRVAL_P(guaranteedValueTypes.raw());
		AH_VALB(unionType, pt_type_combinator_union(zend_hash_num_elements(guaranteed), guaranteed->arPacked));
		AH_VALB(needleHolder, newExpressionTypeHolder(needleExpr, unionType.raw(), PT_TRI_YES));
		zv::Val conditions = ahEmptyArray();
		ahSetKey(conditions, needleString.get(), needleHolder.raw());
		AH_VALB(variable, newVariable(variableName));
		AH_VALB(trueType, newConstantBooleanType(true));
		AH_VALB(typeHolder, newExpressionTypeHolder(variable.raw(), trueType.raw(), PT_TRI_YES));
		AH_VALB(holder, newConditionalExpressionHolder(conditions.raw(), typeHolder.raw()));
		zv::Str holderKey = zv::Str::adopt(conditionalHolderKey(holder.raw()));
		AH_OKB(!holderKey.isNull());
		zv::Str targetExprString = zv::Str::adopt(zend_string_concat2(PT_LC("$"), ZSTR_VAL(variableName), ZSTR_LEN(variableName)));
		nestedSet(conditionalExpressions, targetExprString.get(), holderKey.get(), holder.raw());

		return true;
	}

	/* (twin 2448) false = pending exception */
	[[nodiscard]] static bool isExprSafeToProjectThroughVariable(zval *expr, zend_string *variableName, zval *rhsImpurePoints, zval *assignedExpr, bool &out)
	{
		while (ahIs(expr, PT_CLASS_ISSET_EXPR)) {
			expr = AH_PROP(expr, expr);
		}

		// Scalar/const-fetch literals and PHPStan virtual nodes (e.g. NativeTypeExpr) are never
		// narrowing targets at a usage site — skip them so they don't collide with PHP's
		// numeric-string array-key autocast or leak internal virtual expressions into the
		// conditional-expression map.
		if (ahIs(expr, PT_CLASS_SCALAR) || ahIs(expr, PT_CLASS_CONST_FETCH) || ahIs(expr, PT_CLASS_VIRTUAL_NODE) || (ahIs(expr, PT_CLASS_UNARY_MINUS) && ahIs(AH_PROP(expr, expr), PT_CLASS_SCALAR))) {
			out = false;
			return true;
		}

		if (ahIs(expr, PT_CLASS_VARIABLE)) {
			zend_string *name = ahVariableName(expr);
			out = name != NULL && !zend_string_equals(name, variableName);
			return true;
		}

		if (ahIs(expr, PT_CLASS_PROPERTY_FETCH) || ahIs(expr, PT_CLASS_ARRAY_DIM_FETCH)) {
			out = true;
			return true;
		}

		if (ahIs(expr, PT_CLASS_FUNC_CALL) || ahIs(expr, PT_CLASS_METHOD_CALL) || ahIs(expr, PT_CLASS_NULLSAFE_METHOD_CALL) || ahIs(expr, PT_CLASS_STATIC_CALL)) {
			// A call's type can change between evaluations. We're willing to project the
			// narrowing through a stored boolean only when the sure-type expression is a
			// *sub*-expression of the assigned RHS — e.g. `$ok = $x->foo() !== null` builds
			// a sure type for the sub-call `$x->foo()`. In that case the RHS as a whole
			// carries the comparison result, and later `if ($ok)` usefully re-narrows the
			// remembered sub-call. When the sure-type expression IS the whole RHS (e.g.
			// `$device = $this->nullable(); if ($device === null) { … }` with the
			// falsey-scalar loop producing `$this->nullable() === null` narrowings), the
			// projection would survive across subsequent reassignments of the target
			// expression and wrongly re-narrow fresh calls — so skip it.
			out = Z_OBJ_P(expr) != Z_OBJ_P(assignedExpr);
			return true;
		}

		out = zend_hash_num_elements(Z_ARRVAL_P(rhsImpurePoints)) == 0;
		return true;
	}

	/* private (twin 2647): the key after $index - unpredictable (null) past
	 * PHP_INT_MAX, where PHP throws "Cannot add element to the array as the
	 * next element is already occupied" instead of wrapping the auto-index
	 * around to a float */
	static void advanceImplicitIndex(zend_long index, zend_long &implicitIndex, bool &implicitIndexIsNull)
	{
		if (index == ZEND_LONG_MAX) {
			implicitIndexIsNull = true;
			return;
		}
		implicitIndex = index + 1;
	}

	/* (twin 2524) */
	zv::Val processArrayByRefItems(zval *nsr, zval *scopeArg, zval *storage, zend_string *rootVarName, zval *arrayExpr, zval *parentExpr)
	{
		zend_long implicitIndex = 0;
		bool implicitIndexIsNull = false;
		return processArrayByRefItemsWithImplicitIndex(nsr, scopeArg, storage, rootVarName, arrayExpr, parentExpr, implicitIndex, implicitIndexIsNull);
	}

	/* private (twin 2536): the scope after the items; implicitIndex /
	 * implicitIndexIsNull carry the next implicit index in and out (null when
	 * it cannot be determined) */
	zv::Val processArrayByRefItemsWithImplicitIndex(zval *nsr, zval *scopeArg, zval *storage, zend_string *rootVarName, zval *arrayExpr, zval *parentExpr, zend_long &implicitIndex, bool &implicitIndexIsNull)
	{
		zv::Val scope = zv::Val::copyOf(zv::Ref(scopeArg));
		zval *items = AH_PROP(arrayExpr, items);
		if (Z_TYPE_P(items) != IS_ARRAY) return scope;
		for (auto entry : zv::TableRef(Z_ARRVAL_P(items))) {
			zval *arrayItem = entry.value().deref().raw();
			if (zend_is_true(AH_PROP(arrayItem, unpack))) {
				zval *unpackedValue = AH_PROP(arrayItem, value);
				// Unpacked items are flattened into the surrounding array, so they take up
				// as many implicit indices as the unpacked value has integer keys.
				if (ahIs(unpackedValue, PT_CLASS_ARRAY_EXPR) && isFlattenableUnpackedArray(unpackedValue)) {
					// one native frame per literal nesting level
					zv::Val flattened;
					pt_engine_with_stack([&]() { flattened = processArrayByRefItemsWithImplicitIndex(nsr, scope.raw(), storage, rootVarName, unpackedValue, parentExpr, implicitIndex, implicitIndexIsNull); });
					AH_SET(scope, std::move(flattened));
					continue;
				}

				if (!implicitIndexIsNull) {
					// the unpacked value was walked as part of the assigned array literal
					AH_VAL(unpackedResult, nsrReadStoredResult(nsr, unpackedValue, storage));
					AH_VAL(unpackedType, ahTypeOnScope(unpackedResult.raw(), scope.raw()));
					AH_VAL(unpackedIntegerKeysCount, auhGetImplicitIndexCount(prop(slots::arrayUnpackingHelper), unpackedType.raw()));
					if (Z_TYPE_P(unpackedIntegerKeysCount.raw()) != IS_LONG) {
						implicitIndexIsNull = true;
					} else {
						for (zend_long i = 0; i < Z_LVAL_P(unpackedIntegerKeysCount.raw()) && !implicitIndexIsNull; i++) {
							advanceImplicitIndex(implicitIndex, implicitIndex, implicitIndexIsNull);
						}
					}
				}

				continue;
			}
			zval *key = AH_PROP(arrayItem, key);
			zval *value = AH_PROP(arrayItem, value);
			zv::Val dimExpr;
			if (Z_TYPE_P(key) != IS_NULL) {
				// the key was walked as part of the assigned array literal
				AH_VAL(keyResult, nsrReadStoredResult(nsr, key, storage));
				AH_VAL(rawKeyType, ahTypeOnScope(keyResult.raw(), scope.raw()));
				AH_VAL(keyType, ahTypeOp(rawKeyType.raw(), PT_OP_TO_ARRAY_KEY, 0, NULL));

				if (!implicitIndexIsNull) {
					AH_VAL(keyValues, ahTypeOp(keyType.raw(), PT_OP_GET_CONSTANT_SCALAR_VALUES, 0, NULL));
					if (zend_hash_num_elements(Z_ARRVAL_P(keyValues.raw())) == 1) {
						zval *keyValue = zend_hash_index_find(Z_ARRVAL_P(keyValues.raw()), 0);
						if (keyValue != NULL) ZVAL_DEREF(keyValue);
						if (keyValue != NULL && Z_TYPE_P(keyValue) == IS_LONG && Z_LVAL_P(keyValue) >= implicitIndex) {
							advanceImplicitIndex(Z_LVAL_P(keyValue), implicitIndex, implicitIndexIsNull);
						}
					} else {
						zend_long isInteger = ahTypeOpTri(keyType.raw(), PT_OP_IS_INTEGER, 0, NULL);
						AH_OK(isInteger >= 0);
						if (isInteger != PT_TRI_NO) {
							// Key could be an integer, but we don't know which one,
							// so subsequent implicit indices are unpredictable
							implicitIndexIsNull = true;
						}
					}
				}

				dimExpr = zv::Val::copyOf(zv::Ref(key));
			} else if (!implicitIndexIsNull) {
				zval index;
				ZVAL_LONG(&index, implicitIndex);
				AH_SET(dimExpr, pt_type_new(PT_CLASS_SCALAR_INT, 1, &index));
				advanceImplicitIndex(implicitIndex, implicitIndex, implicitIndexIsNull);
			} else {
				AH_VAL(integerType, newIntegerType());
				AH_SET(dimExpr, newTypeExpr(integerType.raw()));
			}

			if (ahIs(value, PT_CLASS_ARRAY_EXPR)) {
				AH_VAL(dimFetchExpr, newArrayDimFetch(parentExpr, dimExpr.raw()));
				// one native frame per literal nesting level
				zv::Val nested;
				zend_long nestedImplicitIndex = 0;
				bool nestedImplicitIndexIsNull = false;
				pt_engine_with_stack([&]() { nested = processArrayByRefItemsWithImplicitIndex(nsr, scope.raw(), storage, rootVarName, value, dimFetchExpr.raw(), nestedImplicitIndex, nestedImplicitIndexIsNull); });
				AH_SET(scope, std::move(nested));
			}

			zend_string *refVarName = zend_is_true(AH_PROP(arrayItem, byRef)) ? ahStringVariableName(value) : NULL;
			if (refVarName == NULL) continue;

			// `$root = [&$ref]` aliases the two slots from now on
			AH_VAL(dimFetchExpr, newArrayDimFetch(parentExpr, dimExpr.raw()));
			// a plain variable read is scope state - no need to price a synthetic
			// Variable node on demand (mirrors VariableHandler's typeCallback)
			AH_VAL(nativeScope, pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scope.raw())));
			AH_VAL(refType, ahVariableTypeOrError(scope.raw(), refVarName));
			AH_VAL(refNativeType, ahVariableTypeOrError(nativeScope.raw(), refVarName));

			// When $rootVarName's array key changes, update $refVarName
			{
				AH_VAL(refVariable, newVariable(refVarName));
				AH_VAL(intertwined, newIntertwined(rootVarName, refVariable.raw(), dimFetchExpr.raw()));
				AH_SET(scope, pt_mutating_scope_assign_expression(Z_OBJ_P(scope.raw()), Z_OBJ_P(intertwined.raw()), refType.raw(), refNativeType.raw()));
			}

			// When $refVarName changes, update $rootVarName's array key
			{
				AH_VAL(refVariable, newVariable(refVarName));
				AH_VAL(intertwined, newIntertwined(refVarName, dimFetchExpr.raw(), refVariable.raw()));
				AH_SET(scope, pt_mutating_scope_assign_expression(Z_OBJ_P(scope.raw()), Z_OBJ_P(intertwined.raw()), refType.raw(), refNativeType.raw()));
			}
		}

		return scope;
	}

	/* private (twin 2629): unpacking renumbers integer keys, so a by-reference
	 * item inside the unpacked array literal only stays at the key it's
	 * written with if all of its items are keyless */
	static bool isFlattenableUnpackedArray(zval *arrayExpr)
	{
		zval *items = AH_PROP(arrayExpr, items);
		if (Z_TYPE_P(items) != IS_ARRAY) return true;
		for (auto entry : zv::TableRef(Z_ARRVAL_P(items))) {
			zval *arrayItem = entry.value().deref().raw();
			if (Z_TYPE_P(AH_PROP(arrayItem, key)) != IS_NULL) return false;
		}
		return true;
	}

	/* }}} */

	/* {{{ applyWrite() — the KIND_ARRAY_DIM_FETCH branch (twin 1363) */

	[[nodiscard]] bool applyWriteArrayDimFetch(zval *nsr, zval *target, zval *valueResult, zval *assignedValueResult, zval *storage, zval *nodeCallback, zval *context, zval *targetVar, zval *assignedExpr, zv::Val &scope, bool isAssignOp, bool &hasYield, zv::Val &throwPoints, zv::Val &impurePoints, bool &isAlwaysTerminating, zv::Val &resultVar)
	{
		if (UNEXPECTED(!ahIs(targetVar, PT_CLASS_ARRAY_DIM_FETCH))) {
			pt_throw_should_not_happen();
			return false;
		}
		zval *var = ahRequire(AH_TARGET(target, rootVar));
		AH_OKB(var != NULL);
		resultVar = zv::Val::copyOf(zv::Ref(var));
		zval *varResult = ahRequire(AH_TARGET(target, varResult));
		AH_OKB(varResult != NULL);
		zval *dimFetchStack = ahRequire(AH_TARGET(target, dimFetchStack));
		AH_OKB(dimFetchStack != NULL);
		zval *assignedPropertyExpr = ahRequire(AH_TARGET(target, assignedPropertyExpr));
		AH_OKB(assignedPropertyExpr != NULL);
		zval *offsetTypes = ahRequire(AH_TARGET(target, offsetTypes));
		AH_OKB(offsetTypes != NULL);
		zval *offsetNativeTypes = ahRequire(AH_TARGET(target, offsetNativeTypes));
		AH_OKB(offsetNativeTypes != NULL);
		// 3. eval assigned expr first, then read the assigned value on the pre-eval
		// scope - so the read consumes the now-stored result of $assignedExpr (and
		// of its operands) instead of pricing unprocessed nodes (mirrors the
		// Variable branch above). The ??= left side's optional array{} branch is
		// preserved by the coalesce typeCallback carrying the isset descriptor, not
		// by reading a stale resolvedTypes cache (bug-13623).
		zv::Val scopeBeforeAssignEval = zv::Val::copyOf(scope.ref());
		zval *result = valueResult;
		AH_OKB(pt_expression_result_has_yield_or(result, hasYield));
		{
			AH_VALB(points, ahResultThrowPoints(result));
			AH_OKB(ahMerge(throwPoints, points.raw()));
		}
		{
			AH_VALB(points, ahResultImpurePoints(result));
			AH_OKB(ahMerge(impurePoints, points.raw()));
		}
		AH_OKB(pt_expression_result_is_always_terminating_or(result, isAlwaysTerminating));
		AH_SETB(scope, ahResultScope(result));

		// read from the storage the walk just wrote into - the scope's storage
		// stack misses it on loop-convergence passes (the temp storage is never
		// pushed), which fell back to a full on-demand re-walk of the assigned
		// expression for both flavours
		zv::Val storedValueResult;
		if (assignedValueResult != NULL) {
			storedValueResult = zv::Val::copyOf(zv::Ref(assignedValueResult));
		} else {
			AH_SETB(storedValueResult, pt_expression_result_storage_find(storage, assignedExpr));
		}
		zval *stored = storedValueResult.isNull() ? NULL : storedValueResult.raw();
		AH_VALB(nativeScopeBeforeAssignEval, pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scopeBeforeAssignEval.raw())));
		AH_VALB(valueToWrite, readAssignedValueType(nsr, stored, assignedExpr, scopeBeforeAssignEval.raw()));
		AH_VALB(nativeValueToWrite, readAssignedValueType(nsr, stored, assignedExpr, nativeScopeBeforeAssignEval.raw()));
		// the value the write puts in, before the chain walk below rebuilds
		// $valueToWrite into the containers of the enclosing dimensions
		zv::Val writtenValueType = zv::Val::copyOf(valueToWrite.ref());

		zv::Val varType;
		zv::Val varNativeType;
		AH_OKB(resolveContainerTypesAfterAssignedExprEval(nsr, var, varResult, scope.raw(), scopeBeforeAssignEval.raw(), storage, varType, varNativeType));

		// 4. compose types
		zend_long isImplicitArrayCreation = this->isImplicitArrayCreation(dimFetchStack, scope.raw());
		AH_OKB(isImplicitArrayCreation >= 0);
		if (isImplicitArrayCreation == PT_TRI_YES) {
			AH_SETB(varType, newEmptyConstantArrayType());
			AH_SETB(varNativeType, newEmptyConstantArrayType());
		}
		zval *offsetValueType = varType.raw();
		zval *offsetNativeValueType = varNativeType.raw();

		zv::Val additionalExpressions;
		{
			zv::Val produced;
			AH_OKB(produceArrayDimFetchAssignValueToWrite(nsr, dimFetchStack, offsetTypes, offsetValueType, valueToWrite.raw(), scope.raw(), storage, produced, additionalExpressions));
			valueToWrite = std::move(produced);
		}

		zv::Val additionalNativeExpressions = ahNull();
		bool offsetEqual;
		AH_OKB(ahEquals(offsetValueType, offsetNativeValueType, offsetEqual));
		bool valueEqual = false;
		if (offsetEqual) {
			AH_OKB(ahEquals(valueToWrite.raw(), nativeValueToWrite.raw(), valueEqual));
		}
		if (!offsetEqual || !valueEqual) {
			zv::Val produced;
			AH_OKB(produceArrayDimFetchAssignValueToWrite(nsr, dimFetchStack, offsetNativeTypes, offsetNativeValueType, nativeValueToWrite.raw(), scope.raw(), storage, produced, additionalNativeExpressions));
			nativeValueToWrite = std::move(produced);
		} else {
			bool rewritten = false;
			for (auto entry : zv::TableRef(Z_ARRVAL_P(offsetTypes))) {
				zval *offsetType = zend_hash_index_find(Z_ARRVAL_P(entry.value().deref().raw()), 0);
				zval *nativePair = entry.stringKeyOrNull() != NULL ? zend_hash_find(Z_ARRVAL_P(offsetNativeTypes), entry.stringKey()) : zend_hash_index_find(Z_ARRVAL_P(offsetNativeTypes), entry.indexKey());
				zval *offsetNativeType = nativePair != NULL ? zend_hash_index_find(Z_ARRVAL_P(nativePair), 0) : NULL;

				if (Z_TYPE_P(offsetType) == IS_NULL) {
					if (UNEXPECTED(offsetNativeType == NULL || Z_TYPE_P(offsetNativeType) != IS_NULL)) {
						pt_throw_should_not_happen();
						return false;
					}

					continue;
				} else if (UNEXPECTED(offsetNativeType == NULL || Z_TYPE_P(offsetNativeType) == IS_NULL)) {
					pt_throw_should_not_happen();
					return false;
				}
				bool equal;
				AH_OKB(ahEquals(offsetType, offsetNativeType, equal));
				if (equal) continue;

				zv::Val produced;
				zv::Val discardedExpressions;
				AH_OKB(produceArrayDimFetchAssignValueToWrite(nsr, dimFetchStack, offsetNativeTypes, offsetNativeValueType, nativeValueToWrite.raw(), scope.raw(), storage, produced, discardedExpressions));
				nativeValueToWrite = std::move(produced);
				rewritten = true;
				break;
			}

			if (!rewritten) {
				nativeValueToWrite = zv::Val::copyOf(valueToWrite.ref());
			}
		}

		bool plainWrite;
		{
			zend_long isArray = ahTypeOpTri(varType.raw(), PT_OP_IS_ARRAY, 0, NULL);
			AH_OKB(isArray >= 0);
			plainWrite = isArray == PT_TRI_YES;
			if (!plainWrite) {
				AH_VALB(arrayAccess, newObjectType(PT_LC("ArrayAccess")));
				zend_long isArrayAccess = ahTypeOpTri(arrayAccess.raw(), PT_OP_IS_SUPER_TYPE_OF, 1, varType.raw());
				AH_OKB(isArrayAccess >= 0);
				plainWrite = isArrayAccess != PT_TRI_YES;
			}
		}
		if (plainWrite) {
			zend_string *varName = ahStringVariableName(var);
			if (varName != NULL) {
				AH_VALB(typeExpr, newTypeExpr(valueToWrite.raw()));
				AH_VALB(assignNode, newVariableAssignNode(var, typeExpr.raw()));
				AH_OKB(nsrCallNodeCallback(nsr, nodeCallback, assignNode.raw(), scopeBeforeAssignEval.raw(), storage));
				AH_SETB(scope, pt_mutating_scope_assign_variable(Z_OBJ_P(scope.raw()), varName, valueToWrite.raw(), nativeValueToWrite.raw(), ahTrinary(PT_TRI_YES)));
			} else {
				if (ahIs(var, PT_CLASS_PROPERTY_FETCH) || ahIs(var, PT_CLASS_STATIC_PROPERTY_FETCH)) {
					AH_OKB(emitPropertyAssignAndInitialize(nsr, nodeCallback, storage, var, assignedPropertyExpr, isAssignOp, scopeBeforeAssignEval.raw(), scope));
				}
				AH_SETB(scope, pt_mutating_scope_assign_expression(Z_OBJ_P(scope.raw()), Z_OBJ_P(var), valueToWrite.raw(), nativeValueToWrite.raw()));
			}
		} else {
			if (ahIs(var, PT_CLASS_VARIABLE)) {
				AH_VALB(assignNode, newVariableAssignNode(var, assignedPropertyExpr));
				AH_OKB(nsrCallNodeCallback(nsr, nodeCallback, assignNode.raw(), scopeBeforeAssignEval.raw(), storage));
			} else if (ahIs(var, PT_CLASS_PROPERTY_FETCH) || ahIs(var, PT_CLASS_STATIC_PROPERTY_FETCH)) {
				AH_OKB(emitPropertyAssignAndInitialize(nsr, nodeCallback, storage, var, assignedPropertyExpr, isAssignOp, scopeBeforeAssignEval.raw(), scope));
			}
		}

		for (auto entry : zv::TableRef(Z_ARRVAL_P(additionalExpressions.raw()))) {
			HashTable *pair = Z_ARRVAL_P(entry.value().raw());
			zval *expr = zend_hash_index_find(pair, 0);
			zval *type = zend_hash_index_find(pair, 1);
			zval *nativeType = type;
			if (Z_TYPE_P(additionalNativeExpressions.raw()) == IS_ARRAY) {
				zval *nativeEntry = zend_hash_index_find(Z_ARRVAL_P(additionalNativeExpressions.raw()), entry.indexKey());
				if (nativeEntry != NULL && Z_TYPE_P(nativeEntry) != IS_NULL) {
					nativeType = zend_hash_index_find(Z_ARRVAL_P(nativeEntry), 1);
				}
			}

			AH_SETB(scope, pt_mutating_scope_assign_expression(Z_OBJ_P(scope.raw()), Z_OBJ_P(expr), type, nativeType));
		}

		// the second-outermost chain link's result (for a single-dimension
		// target: the root), threaded from the walk
		zval *offsetSetTargetResult = ahRequire(AH_TARGET(target, offsetSetTargetResult));
		AH_OKB(offsetSetTargetResult != NULL);
		AH_VALB(setVarType, ahTypeOnScope(offsetSetTargetResult, scope.raw()));
		if (!ahIsCe(setVarType.raw(), pt_ce_error_type)) {
			zend_long isArray = ahTypeOpTri(setVarType.raw(), PT_OP_IS_ARRAY, 0, NULL);
			AH_OKB(isArray >= 0);
			if (isArray != PT_TRI_YES) {
				AH_VALB(arrayAccess, newObjectType(PT_LC("ArrayAccess")));
				zend_long isArrayAccess = ahTypeOpTri(arrayAccess.raw(), PT_OP_IS_SUPER_TYPE_OF, 1, setVarType.raw());
				AH_OKB(isArrayAccess >= 0);
				if (isArrayAccess != PT_TRI_NO) {
					HashTable *offsetTypesTable = Z_ARRVAL_P(offsetTypes);
					zval *lastPair = zend_hash_index_find(offsetTypesTable, zend_hash_num_elements(offsetTypesTable) - 1);
					zval *keyType = zend_hash_index_find(Z_ARRVAL_P(lastPair), 0);
					AH_VALB(constraints, collectOffsetSetUsage(nsr, scope.raw(), setVarType.raw(), Z_TYPE_P(keyType) == IS_NULL ? NULL : keyType, writtenValueType.raw()));
					AH_SETB(scope, pt_mutating_scope_add_template_argument_constraints(Z_OBJ_P(scope.raw()), constraints.raw()));
					AH_VALB(typeExpr, newTypeExpr(setVarType.raw()));
					AH_VALB(methodCall, newMethodCall(typeExpr.raw(), PT_LC("offsetSet")));
					AH_VALB(points, mtphGetThrowPointsForCallOnType(prop(slots::methodThrowPointHelper), scope.raw(), context, setVarType.raw(), methodCall.raw()));
					AH_OKB(ahMerge(throwPoints, points.raw()));
				}
			}
		}

		return true;
	}

	/* the chain root's PropertyAssignNode, then its initialization when a
	 * named PropertyFetch is written outside an op-assignment (twin 1453 /
	 * 1469) */
	[[nodiscard]] static bool emitPropertyAssignAndInitialize(zval *nsr, zval *nodeCallback, zval *storage, zval *var, zval *assignedPropertyExpr, bool isAssignOp, zval *scopeBeforeAssignEval, zv::Val &scope)
	{
		{
			AH_VALB(assignNode, newPropertyAssignNode(var, assignedPropertyExpr, isAssignOp));
			AH_OKB(nsrCallNodeCallback(nsr, nodeCallback, assignNode.raw(), scopeBeforeAssignEval, storage));
		}
		if (ahIs(var, PT_CLASS_PROPERTY_FETCH) && ahIs(AH_PROP(var, name), PT_CLASS_IDENTIFIER) && !isAssignOp) {
			// the chain root's receiver was walked by prepareTarget()
			AH_VALB(receiverResult, nsrReadStoredResult(nsr, AH_PROP(var, var), storage));
			AH_VALB(receiverType, ahTypeOnScope(receiverResult.raw(), scope.raw()));
			zval *propertyName = AH_PROP(AH_PROP(var, name), name);
			if (UNEXPECTED(Z_TYPE_P(propertyName) != IS_STRING)) {
				zend_type_error("PHPStan\\Analyser\\MutatingScope::assignInitializedProperty(): Argument #2 ($propertyName) must be of type string, %s given", zend_zval_value_name(propertyName));
				return false;
			}
			AH_SETB(scope, pt_mutating_scope_assign_initialized_property(Z_OBJ_P(scope.raw()), receiverType.raw(), Z_STR_P(propertyName)));
		}
		return true;
	}

	/* (twin 1824) $keyType NULL for null */
	zv::Val collectOffsetSetUsage(zval *nsr, zval *scope, zval *receiverType, zval *keyType, zval *valueType)
	{
		AH_VAL(constraints, tacCreateEmpty());
		AH_VAL(frame, nsrObservingTemplateArgumentFrame(nsr, scope));
		if (frame.isNull()) return constraints;
		{
			zv::Val methodName = zv::Val::string(PT_LC("offsetSet"));
			zend_long hasMethod = ahTypeOpTri(receiverType, PT_OP_HAS_METHOD, 1, methodName.raw());
			AH_OK(hasMethod >= 0);
			if (hasMethod != PT_TRI_YES) return constraints;
		}

		zv::Val methodName = zv::Val::string(PT_LC("offsetSet"));
		zv::Args getMethodArgs{methodName.raw(), scope};
		AH_VAL(method, ahCall(receiverType, PT_LC("getmethod"), 2, getMethodArgs));
		AH_VAL(variant, method.ref().isObject() ? pt_extended_method_reflection_call(method.raw(), PT_MR_GET_ONLY_VARIANT) : ahCall(method.raw(), PT_LC("getonlyvariant"), 0, NULL));
		AH_VAL(parameters, pt_parameters_acceptor_call(variant.raw(), PT_PA_GET_PARAMETERS));
		HashTable *parametersTable = Z_ARRVAL_P(parameters.raw());
		zval *parameter0 = zend_hash_index_find(parametersTable, 0);
		if (keyType != NULL && parameter0 != NULL && Z_TYPE_P(parameter0) != IS_NULL) {
			AH_VAL(parameterType, ahCall(parameter0, PT_LC("gettype"), 0, NULL));
			AH_VAL(collected, taoCollectArgument(prop(slots::templateArgumentObserver), parameterType.raw(), keyType));
			AH_SET(constraints, tacMerge(constraints.raw(), collected.raw()));
		}
		zval *parameter1 = zend_hash_index_find(parametersTable, 1);
		if (parameter1 == NULL || Z_TYPE_P(parameter1) == IS_NULL) return constraints;

		AH_VAL(parameterType, ahCall(parameter1, PT_LC("gettype"), 0, NULL));
		AH_VAL(collected, taoCollectArgument(prop(slots::templateArgumentObserver), parameterType.raw(), valueType));
		return tacMerge(constraints.raw(), collected.raw());
	}

	/* (twin 1894) false = pending exception */
	[[nodiscard]] static bool resolveContainerTypesAfterAssignedExprEval(zval *nsr, zval *var, zval *varResult, zval *postEvalScope, zval *preEvalScope, zval *storage, zv::Val &type, zv::Val &nativeType)
	{
		zend_string *varName = ahStringVariableName(var);
		if (varName != NULL) {
			// A superglobal keeps composing over the pre-eval view: a volatile
			// invalidation between the walk and this read (any maybe-impure call
			// in the assigned expression) would otherwise degrade the write to
			// the raw superglobal array (see bug-14999).
			if (!pt_is_superglobal_name(varName)) {
				bool matches;
				AH_OKB(pt_expression_result_ask_scope_variable_state_matches(varResult, postEvalScope, false, matches));
				if (!matches) {
					// the assigned expression reassigned the root variable itself
					AH_SETB(type, pt_mutating_scope_get_variable_type(Z_OBJ_P(postEvalScope), varName));
					AH_VALB(nativeScope, pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(postEvalScope)));
					AH_SETB(nativeType, pt_mutating_scope_get_variable_type(Z_OBJ_P(nativeScope.raw()), varName));
					return true;
				}
			}

			AH_SETB(type, pt_expression_result_get_type(varResult));
			AH_SETB(nativeType, pt_expression_result_get_native_type(varResult));
			return true;
		}

		{
						zend_long hasValue = pt_mutating_scope_has_expression_type(Z_OBJ_P(postEvalScope), var);
			AH_OKB(hasValue >= 0);
			if (hasValue == PT_TRI_YES) {
				AH_SETB(type, pt_expression_result_get_type_on_scope(varResult, postEvalScope, false));
				AH_SETB(nativeType, pt_expression_result_get_type_on_scope(varResult, postEvalScope, true));
				return true;
			}
		}

		if (preEvalScope != NULL) {
						zend_long hasValue = pt_mutating_scope_has_expression_type(Z_OBJ_P(preEvalScope), var);
			AH_OKB(hasValue >= 0);
			if (hasValue == PT_TRI_YES) {
				// a fetch the assigned expression invalidated (tracked before the
				// eval, untracked after) - re-price it at the post-eval position
				AH_VALB(duplicate, pt_expression_result_storage_duplicate(storage));
				AH_VALB(reprocessed, nsrProcessExprOnDemand(nsr, var, postEvalScope, duplicate.raw()));
				AH_SETB(type, pt_expression_result_get_type(reprocessed.raw()));
				AH_SETB(nativeType, pt_expression_result_get_native_type(reprocessed.raw()));
				return true;
			}
		}

		// untracked throughout - nothing the assigned expression did could have
		// changed what the walk read
		AH_SETB(type, pt_expression_result_get_type(varResult));
		AH_SETB(nativeType, pt_expression_result_get_native_type(varResult));
		return true;
	}

	/* (twin 2498) the PT_TRI_* value, -1 = pending exception */
	[[nodiscard]] zend_long isImplicitArrayCreation(zval *dimFetchStack, zval *scope)
	{
		HashTable *stack = Z_ARRVAL_P(dimFetchStack);
		if (zend_hash_num_elements(stack) == 0) return PT_TRI_NO;

		zval *first = zend_hash_index_find(stack, 0);
		if (first == NULL) return PT_TRI_NO;
		ZVAL_DEREF(first);
		zval *varNode = AH_PROP(first, var);
		if (!ahIs(varNode, PT_CLASS_VARIABLE)) return PT_TRI_NO;

		zend_string *name = ahVariableName(varNode);
		if (name == NULL) return PT_TRI_NO;

		zv::Val has = pt_mutating_scope_has_variable_type(Z_OBJ_P(scope), name);
		if (UNEXPECTED(has.isUndef())) return -1;
		zend_long hasValue = ahTri(has.raw());
		if (UNEXPECTED(hasValue < 0)) return -1;
		return hasValue == PT_TRI_YES ? PT_TRI_NO : (hasValue == PT_TRI_NO ? PT_TRI_YES : PT_TRI_MAYBE);
	}

	/* (twin 2590) the [$valueToWrite, $additionalExpressions] pair into the
	 * two out values; false = pending exception */
	[[nodiscard]] bool produceArrayDimFetchAssignValueToWrite(zval *nsr, zval *dimFetchStackZv, zval *offsetTypesZv, zval *offsetValueTypeArg, zval *valueToWriteArg, zval *scope, zval *storage, zv::Val &valueToWriteOut, zv::Val &additionalExpressionsOut)
	{
		HashTable *offsetTypes = Z_ARRVAL_P(offsetTypesZv);
		HashTable *dimFetchStack = Z_ARRVAL_P(dimFetchStackZv);
		zval *originalValueToWrite = valueToWriteArg;
		zv::Val valueToWrite = zv::Val::copyOf(zv::Ref(valueToWriteArg));
		zv::Val offsetValueType = zv::Val::copyOf(zv::Ref(offsetValueTypeArg));

		uint32_t offsetCount = zend_hash_num_elements(offsetTypes);
		zv::Arr offsetValueTypeStack = zv::Arr::create(offsetCount);
		offsetValueTypeStack.push(offsetValueType.ref());
		bool generalizeOnWrite;
		{
			zval *lastPair = zend_hash_index_find(offsetTypes, offsetCount - 1);
			generalizeOnWrite = Z_TYPE_P(zend_hash_index_find(Z_ARRVAL_P(lastPair), 0)) != IS_NULL;
		}
		zend_long dimDepth = 0;
		for (uint32_t index = 0; index + 1 < offsetCount; index++) {
			HashTable *pair = Z_ARRVAL_P(zend_hash_index_find(offsetTypes, index));
			zval *offsetType = zend_hash_index_find(pair, 0);
			zval *dimFetch = zend_hash_index_find(pair, 1);
			dimDepth++;
			if (Z_TYPE_P(offsetType) == IS_NULL) {
				AH_SETB(offsetValueType, newEmptyConstantArrayType());
				generalizeOnWrite = false;
			} else {
				bool oversized = false;
				if (dimDepth > PT_AH_ARRAY_DIM_FETCH_WRITE_DEPTH_LIMIT) {
					zend_long isOversized = ahCallTri(offsetValueType.raw(), PT_LC("isoversizedarray"), 0, NULL);
					AH_OKB(isOversized >= 0);
					oversized = isOversized == PT_TRI_YES;
				}
				if (oversized) {
					AH_SETB(offsetValueType, pt_type_new_mixed_type());
				} else {
					zend_long has = ahTypeOpTri(offsetValueType.raw(), PT_OP_HAS_OFFSET_VALUE_TYPE, 1, offsetType);
					AH_OKB(has >= 0);
					if (has == PT_TRI_YES) {
												zend_long trackedValue = pt_mutating_scope_has_expression_type(Z_OBJ_P(scope), dimFetch);
						AH_OKB(trackedValue >= 0);
						if (trackedValue == PT_TRI_YES) {
							AH_SETB(offsetValueType, pt_mutating_scope_get_state_type(Z_OBJ_P(scope), Z_OBJ_P(dimFetch)));
						} else {
							AH_SETB(offsetValueType, ahTypeOp(offsetValueType.raw(), PT_OP_GET_OFFSET_VALUE_TYPE, 1, offsetType));
						}
					} else if (has == PT_TRI_MAYBE) {
												zend_long trackedValue = pt_mutating_scope_has_expression_type(Z_OBJ_P(scope), dimFetch);
						AH_OKB(trackedValue >= 0);
						if (trackedValue == PT_TRI_YES) {
							generalizeOnWrite = false;
							AH_SETB(offsetValueType, pt_mutating_scope_get_state_type(Z_OBJ_P(scope), Z_OBJ_P(dimFetch)));
						} else {
							AH_VALB(inner, ahTypeOp(offsetValueType.raw(), PT_OP_GET_OFFSET_VALUE_TYPE, 1, offsetType));
							AH_VALB(emptyArray, newEmptyConstantArrayType());
							zv::Args unionArgs{inner.raw(), emptyArray.raw()};
							AH_SETB(offsetValueType, pt_type_combinator_union(2, unionArgs));
						}
					} else {
						generalizeOnWrite = false;
						AH_SETB(offsetValueType, newEmptyConstantArrayType());
					}
				}
			}

			offsetValueTypeStack.push(offsetValueType.ref());
		}

		zend_long lastDimKey = (zend_long) zend_hash_num_elements(dimFetchStack) - 1;
		zv::Val computedContainerValues = ahEmptyArray();
		HashTable *stack = offsetValueTypeStack.table();
		for (uint32_t reversedIndex = 0; reversedIndex < offsetCount; reversedIndex++) {
			zend_long i = (zend_long) reversedIndex;
			HashTable *pair = Z_ARRVAL_P(zend_hash_index_find(offsetTypes, offsetCount - 1 - reversedIndex));
			zval *offsetType = zend_hash_index_find(pair, 0);
			zval *writtenDimFetch = zend_hash_index_find(pair, 1);
			/** @var Type $offsetValueType — array_pop($offsetValueTypeStack) */
			offsetValueType = zv::Val::copyOf(zv::Ref(zend_hash_index_find(stack, offsetCount - 1 - reversedIndex)));
			if (!ahIsCe(offsetValueType.raw(), pt_ce_mixed_type)) {
				zend_long isArray = ahTypeOpTri(offsetValueType.raw(), PT_OP_IS_ARRAY, 0, NULL);
				AH_OKB(isArray >= 0);
				if (isArray != PT_TRI_YES) {
					bool integerOffset = false;
					if (Z_TYPE_P(offsetType) != IS_NULL) {
						zend_long isInteger = ahTypeOpTri(offsetType, PT_OP_IS_INTEGER, 0, NULL);
						AH_OKB(isInteger >= 0);
						integerOffset = isInteger == PT_TRI_YES;
					}
					AH_VALB(accessible, integerOffset ? pt_static_type_factory_int_offset_accessible() : pt_static_type_factory_general_offset_accessible());
					zv::Args intersectArgs{offsetValueType.raw(), accessible.raw()};
					AH_SETB(offsetValueType, pt_type_combinator_intersect(2, intersectArgs));
				}
			}

			// the link of the chain that is looked up in the scope: the written
			// dim fetch itself for a one-dimensional write, another link of the
			// same chain for a nested one
			zval *trackedDimFetch = zend_hash_index_find(dimFetchStack, (zend_ulong) i);
			if (trackedDimFetch != NULL && Z_TYPE_P(trackedDimFetch) == IS_NULL) trackedDimFetch = NULL;
			bool existing = false;
			if (Z_TYPE_P(offsetType) != IS_NULL && trackedDimFetch != NULL) {
								zend_long trackedValue = pt_mutating_scope_has_expression_type(Z_OBJ_P(scope), trackedDimFetch);
				AH_OKB(trackedValue >= 0);
				if (trackedValue == PT_TRI_YES) {
					zend_long has = ahTypeOpTri(offsetValueType.raw(), PT_OP_HAS_OFFSET_VALUE_TYPE, 1, offsetType);
					AH_OKB(has >= 0);
					if (has != PT_TRI_NO) {
						if (Z_TYPE_P(trackedDimFetch) == IS_OBJECT && Z_TYPE_P(writtenDimFetch) == IS_OBJECT && Z_OBJ_P(trackedDimFetch) == Z_OBJ_P(writtenDimFetch)) {
							existing = true;
						} else {
							AH_OKB(trackedLinkImpliesOffset(offsetValueType.raw(), offsetType, existing));
						}
					}
				}
			}
			if (existing) {
				zv::Val hasOffsetType = ahNull();
				if (ahIsCe(offsetType, pt_ce_constant_string_type) || ahIsCe(offsetType, pt_ce_constant_integer_type)) {
					AH_SETB(hasOffsetType, newHasOffsetValueType(offsetType, valueToWrite.raw()));
				}
				{
					zv::Args setArgs{offsetType, valueToWrite.raw()};
					AH_SETB(valueToWrite, ahCall(offsetValueType.raw(), PT_LC("setexistingoffsetvaluetype"), 2, setArgs));
				}

				zend_long isArray = ahTypeOpTri(valueToWrite.raw(), PT_OP_IS_ARRAY, 0, NULL);
				AH_OKB(isArray >= 0);
				if (isArray == PT_TRI_YES) {
					if (!hasOffsetType.isNull()) {
						zv::Args intersectArgs{valueToWrite.raw(), hasOffsetType.raw()};
						AH_SETB(valueToWrite, pt_type_combinator_intersect(2, intersectArgs));
					} else {
						AH_VALB(nonEmpty, newNonEmptyArrayType());
						zv::Args intersectArgs{valueToWrite.raw(), nonEmpty.raw()};
						AH_SETB(valueToWrite, pt_type_combinator_intersect(2, intersectArgs));
					}
				}
			} else {
				// when $unionValues=false the array item-type will be replaced with $valueToWrite
				// when $unionValues=true the existing array item-type will be union'ed with $valueToWrite -> type gets wider
				bool unionValues = false;
				if (i == 0) {
					unionValues = true;
				} else if (generalizeOnWrite && i == (zend_long) offsetCount - 1) {
					zend_long isConstantScalar = ahTypeOpTri(originalValueToWrite, PT_OP_IS_CONSTANT_SCALAR_VALUE, 0, NULL);
					AH_OKB(isConstantScalar >= 0);
					if (isConstantScalar == PT_TRI_YES) {
						unionValues = true;
					} else {
						AH_VALB(iterableValueType, ahTypeOp(offsetValueType.raw(), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL));
						zend_long isSuper = ahTypeOpTri(iterableValueType.raw(), PT_OP_IS_SUPER_TYPE_OF, 1, valueToWrite.raw());
						AH_OKB(isSuper >= 0);
						unionValues = isSuper != PT_TRI_YES;
					}
				}

				zv::Args setArgs{offsetType, valueToWrite.raw(), unionValues};
				AH_SETB(valueToWrite, ahCall(offsetValueType.raw(), PT_LC("setoffsetvaluetype"), 3, setArgs));
			}

			{
				zend_long isList = ahTypeOpTri(offsetValueType.raw(), PT_OP_IS_LIST, 0, NULL);
				AH_OKB(isList >= 0);
				if (isList == PT_TRI_YES) {
					bool keepList;
					AH_OKB(shouldKeepList(nsr, writtenDimFetch, scope, storage, offsetValueType.raw(), keepList));
					if (keepList) {
						AH_VALB(listType, newAccessoryArrayListType());
						zv::Args intersectArgs{valueToWrite.raw(), listType.raw()};
						AH_SETB(valueToWrite, pt_type_combinator_intersect(2, intersectArgs));
					}
				}
			}

			zend_long containerKey = lastDimKey - i - 1;
			if (containerKey < 0) continue;

			ahSetIndex(computedContainerValues, (zend_ulong) containerKey, valueToWrite.raw());
		}

		zv::Val additionalExpressions = ahEmptyArray();
		for (auto entry : zv::TableRef(dimFetchStack)) {
			zend_ulong key = entry.indexKey();
			zval *dimFetch = entry.value().deref().raw();
			if (Z_TYPE_P(AH_PROP(dimFetch, dim)) == IS_NULL) continue;

			zv::Val additionalValueType;
			zval *computed = zend_hash_index_find(Z_ARRVAL_P(computedContainerValues.raw()), key);
			if ((zend_long) key == lastDimKey) {
				additionalValueType = zv::Val::copyOf(zv::Ref(originalValueToWrite));
			} else if (computed != NULL && Z_TYPE_P(computed) != IS_NULL) {
				additionalValueType = zv::Val::copyOf(zv::Ref(computed));
			} else {
				// the dimension's walk-captured type, aligned with the stack by key
				zval *pairZv = zend_hash_index_find(offsetTypes, key);
				zval *offsetType = pairZv != NULL ? zend_hash_index_find(Z_ARRVAL_P(pairZv), 0) : NULL;
				if (UNEXPECTED(offsetType == NULL || Z_TYPE_P(offsetType) == IS_NULL)) {
					pt_throw_should_not_happen();
					return false;
				}
				AH_SETB(additionalValueType, ahTypeOp(valueToWrite.raw(), PT_OP_GET_OFFSET_VALUE_TYPE, 1, offsetType));
			}

			ahPush(additionalExpressions, ahPair(dimFetch, additionalValueType.raw()));
		}

		valueToWriteOut = std::move(valueToWrite);
		additionalExpressionsOut = std::move(additionalExpressions);
		return true;
	}

	/* (twin 2731) false = pending exception */
	[[nodiscard]] static bool shouldKeepList(zval *nsr, zval *arrayDimFetch, zval *scope, zval *storage, zval *offsetValueType, bool &out)
	{
		out = false;
		bool failed = false;
		auto sameVariable = [&failed](zval *a, zval *b) {
			if (failed) return false;
			bool same;
			if (UNEXPECTED(!isSameVariable(a, b, same))) {
				failed = true;
				return false;
			}
			return same;
		};
		zval *dim = AH_PROP(arrayDimFetch, dim);
		zval *arrayVar = AH_PROP(arrayDimFetch, var);
		if (ahIs(dim, PT_CLASS_BINARY_OP_PLUS)) {
			zval *left = AH_PROP(dim, left);
			zval *right = AH_PROP(dim, right);
			if ( // keep list for $list[$index + 1] assignments
				ahIs(right, PT_CLASS_VARIABLE)
				&& ahIs(left, PT_CLASS_SCALAR_INT)
				&& isIntOne(left)
			) {
				AH_VALB(fetch, newArrayDimFetch(arrayVar, right));
								zend_long hasValue = pt_mutating_scope_has_expression_type(Z_OBJ_P(scope), fetch.raw());
				AH_OKB(hasValue >= 0);
				if (hasValue == PT_TRI_YES) {
					out = true;
					return true;
				}
			}
			if ( // keep list for $list[1 + $index] assignments
				ahIs(left, PT_CLASS_VARIABLE)
				&& ahIs(right, PT_CLASS_SCALAR_INT)
				&& isIntOne(right)
			) {
				AH_VALB(fetch, newArrayDimFetch(arrayVar, left));
								zend_long hasValue = pt_mutating_scope_has_expression_type(Z_OBJ_P(scope), fetch.raw());
				AH_OKB(hasValue >= 0);
				if (hasValue == PT_TRI_YES) {
					out = true;
					return true;
				}
			}
		} else if ( // keep list for $list[count($list) - n] assignments
			ahIs(dim, PT_CLASS_BINARY_OP_MINUS)
			&& ahIs(AH_PROP(dim, right), PT_CLASS_SCALAR_INT)
			&& ahIs(AH_PROP(dim, left), PT_CLASS_FUNC_CALL)
			&& (ahNameIs(AH_PROP(AH_PROP(dim, left), name), PT_LC("count")) || ahNameIs(AH_PROP(AH_PROP(dim, left), name), PT_LC("sizeof")))
			&& ahArgCount(AH_PROP(dim, left)) == 1 // could support COUNT_RECURSIVE, COUNT_NORMAL
			&& sameVariable(arrayVar, ahArgValue(AH_PROP(dim, left), 0))
		) {
			// the dimension was walked as part of the assign target chain
			AH_VALB(range, pt_integer_range_from_interval(NullableLong::of(0), NullableLong::null(), 0));
			AH_VALB(dimResult, nsrReadStoredResult(nsr, dim, storage));
			AH_VALB(dimType, ahTypeOnScope(dimResult.raw(), scope));
			zend_long nonNegative = ahTypeOpTri(range.raw(), PT_OP_IS_SUPER_TYPE_OF, 1, dimType.raw());
			AH_OKB(nonNegative >= 0);
			if (nonNegative == PT_TRI_YES) {
				zend_long nonEmpty = ahTypeOpTri(offsetValueType, PT_OP_IS_ITERABLE_AT_LEAST_ONCE, 0, NULL);
				AH_OKB(nonEmpty >= 0);
				if (nonEmpty == PT_TRI_YES) {
					out = true;
					return true;
				}
			}
		} else if ( // keep list for $list[array_key_last($list)] and $list[array_key_first($list)] assignments
			ahIs(dim, PT_CLASS_FUNC_CALL)
			&& (ahNameIs(AH_PROP(dim, name), PT_LC("array_key_last")) || ahNameIs(AH_PROP(dim, name), PT_LC("array_key_first")))
			&& ahArgCount(dim) >= 1
			&& sameVariable(arrayVar, ahArgValue(dim, 0))
		) {
			out = true;
			return true;
		} else if ( // keep list for $list[array_search($needle, $list)] assignments
			ahIs(dim, PT_CLASS_FUNC_CALL)
			&& ahNameIs(AH_PROP(dim, name), PT_LC("array_search"))
			&& ahArgCount(dim) >= 2 // the haystack is the second argument
			&& sameVariable(arrayVar, ahArgValue(dim, 1))
		) {
			out = true;
			return true;
		}

		return !failed;
	}

	/* $int->value === 1 of a Scalar\Int_ */
	static bool isIntOne(zval *intNode)
	{
		zval *value = AH_PROP(intNode, value);
		return Z_TYPE_P(value) == IS_LONG && Z_LVAL_P(value) == 1;
	}

	/* private (twin 2819): a link of the chain other than the written one
	 * being tracked does not prove the written offset is there. It is the
	 * usual evidence when the offset comes from the written structure itself,
	 * like in `foreach ($rows as $k => $v) { $matrix[$i][$k] = ...; }`, so it
	 * only counts as long as the offset stays within the keys the container
	 * can have. false = pending exception */
	[[nodiscard]] static bool trackedLinkImpliesOffset(zval *offsetValueType, zval *offsetType, bool &out)
	{
		zend_long isArray = ahTypeOpTri(offsetValueType, PT_OP_IS_ARRAY, 0, NULL);
		AH_OKB(isArray >= 0);
		if (isArray != PT_TRI_YES) {
			out = true;
			return true;
		}

		AH_VALB(keyType, ahTypeOp(offsetValueType, PT_OP_GET_ITERABLE_KEY_TYPE, 0, NULL));
		zend_long isSuper = ahTypeOpTri(keyType.raw(), PT_OP_IS_SUPER_TYPE_OF, 1, offsetType);
		AH_OKB(isSuper >= 0);
		out = isSuper == PT_TRI_YES;
		return true;
	}

	/* $a->name === $b->name of two Identifier / Name nodes (the
	 * toString() / toLowerString() comparisons) */
	static bool nodeNamesEqual(zval *a, zval *b, bool caseInsensitive)
	{
		zval *aName = AH_PROP(a, name);
		zval *bName = AH_PROP(b, name);
		if (Z_TYPE_P(aName) != IS_STRING || Z_TYPE_P(bName) != IS_STRING) return false;
		if (caseInsensitive) return zend_binary_strcasecmp(Z_STRVAL_P(aName), Z_STRLEN_P(aName), Z_STRVAL_P(bName), Z_STRLEN_P(bName)) == 0;
		return zend_string_equals(Z_STR_P(aName), Z_STR_P(bName));
	}

	/* private (twin 2838): whether both expressions denote the same
	 * container - the write target's `$container[...]` and the `$container`
	 * handed to count()/array_key_last() and friends. Only side-effect-free
	 * forms are compared, so that reading the container twice is guaranteed
	 * to yield the same array. false = pending exception */
	[[nodiscard]] static bool isSameVariable(zval *a, zval *b, bool &out)
	{
		out = false;
		if (ahIs(a, PT_CLASS_VARIABLE) && ahIs(b, PT_CLASS_VARIABLE)) {
			zend_string *aName = ahVariableName(a);
			zend_string *bName = ahVariableName(b);
			out = aName != NULL && bName != NULL && zend_string_equals(aName, bName);
			return true;
		}

		if (ahIs(a, PT_CLASS_PROPERTY_FETCH) && ahIs(b, PT_CLASS_PROPERTY_FETCH)) {
			zval *aName = AH_PROP(a, name);
			zval *bName = AH_PROP(b, name);
			if (!ahIs(aName, PT_CLASS_IDENTIFIER) || !ahIs(bName, PT_CLASS_IDENTIFIER) || !nodeNamesEqual(aName, bName, false)) return true;
			return isSameVariable(AH_PROP(a, var), AH_PROP(b, var), out);
		}

		if (ahIs(a, PT_CLASS_STATIC_PROPERTY_FETCH) && ahIs(b, PT_CLASS_STATIC_PROPERTY_FETCH)) {
			zval *aClass = AH_PROP(a, class);
			zval *bClass = AH_PROP(b, class);
			if (!ahIs(aClass, PT_CLASS_NAME) || !ahIs(bClass, PT_CLASS_NAME) || !nodeNamesEqual(aClass, bClass, true)) return true;
			zval *aName = AH_PROP(a, name);
			zval *bName = AH_PROP(b, name);
			out = ahIs(aName, PT_CLASS_VAR_LIKE_IDENTIFIER) && ahIs(bName, PT_CLASS_VAR_LIKE_IDENTIFIER) && nodeNamesEqual(aName, bName, false);
			return true;
		}

		if (ahIs(a, PT_CLASS_ARRAY_DIM_FETCH) && ahIs(b, PT_CLASS_ARRAY_DIM_FETCH)) {
			zval *aDim = AH_PROP(a, dim);
			zval *bDim = AH_PROP(b, dim);
			if (Z_TYPE_P(aDim) == IS_NULL || Z_TYPE_P(bDim) == IS_NULL) return true;
			bool sameOffset;
			AH_OKB(isSameOffset(aDim, bDim, sameOffset));
			if (!sameOffset) return true;
			return isSameVariable(AH_PROP(a, var), AH_PROP(b, var), out);
		}

		return true;
	}

	/* private (twin 2866); false = pending exception */
	[[nodiscard]] static bool isSameOffset(zval *a, zval *b, bool &out)
	{
		zv::Val aKeyType;
		AH_OKB(getLiteralArrayKeyType(a, aKeyType));
		if (!aKeyType.isNull()) {
			zv::Val bKeyType;
			AH_OKB(getLiteralArrayKeyType(b, bKeyType));
			if (bKeyType.isNull()) {
				out = false;
				return true;
			}
			AH_VALB(equals, ahTypeOp(aKeyType.raw(), PT_OP_EQUALS, 1, bKeyType.raw()));
			out = zend_is_true(equals.raw());
			return true;
		}

		return isSameVariable(a, b, out);
	}

	/* private (twin 2883): the array key a literal offset ends up as, so that
	 * offsets addressing the same element compare as equal - `$a[1]`,
	 * `$a['1']` and `$a[1.5]` all read the same one; null for any other
	 * expression. false = pending exception */
	[[nodiscard]] static bool getLiteralArrayKeyType(zval *expr, zv::Val &out)
	{
		out = zv::Val::null();
		if (!ahIs(expr, PT_CLASS_SCALAR_INT) && !ahIs(expr, PT_CLASS_SCALAR_STRING) && !ahIs(expr, PT_CLASS_SCALAR_FLOAT)) return true;
		AH_VALB(type, pt_constant_type_helper_get_type_from_value(AH_PROP(expr, value)));
		AH_SETB(out, ahTypeOp(type.raw(), PT_OP_TO_ARRAY_KEY, 0, NULL));
		return true;
	}

	/* (twin 2796) */
	zv::Val getOriginalPropertyType(zval *nsr, zval *propertyFetch, zval *scope)
	{
		// the fetch is a write target inside an offset chain - nothing of it is
		// processed yet, so the holder type is read maybe-stored (a plain variable
		// receiver like $this answers from scope state without a walk)
		zv::Val propertyHolderType;
		if (ahIs(propertyFetch, PT_CLASS_PROPERTY_FETCH)) {
			AH_SET(propertyHolderType, nsrReadTypeOfMaybeStored(nsr, AH_PROP(propertyFetch, var), scope));
		} else if (ahIs(AH_PROP(propertyFetch, class), PT_CLASS_NAME)) {
			AH_SET(propertyHolderType, pt_mutating_scope_resolve_type_by_name(Z_OBJ_P(scope), Z_OBJ_P(AH_PROP(propertyFetch, class))));
		} else {
			AH_SET(propertyHolderType, nsrReadTypeOfMaybeStored(nsr, AH_PROP(propertyFetch, class), scope));
		}
		AH_VAL(propertyReflection, prfFindPropertyReflectionFromNodeWithHolderType(prop(slots::propertyReflectionFinder), propertyFetch, propertyHolderType.raw(), scope));
		zv::Val originalPropertyType;
		if (!propertyReflection.isNull()) {
			AH_SET(originalPropertyType, ahCall(propertyReflection.raw(), PT_LC("getreadabletype"), 0, NULL));
		} else {
			AH_SET(originalPropertyType, pt_type_new_error_type());
		}
		if (ahIsCe(originalPropertyType.raw(), pt_ce_union_type)) {
			AH_VAL(currentPropertyType, nsrReadTypeOfMaybeStored(nsr, propertyFetch, scope));
			zv::Val filter = pt_native_closure(&filterNotDisjointCallbackBody, currentPropertyType.raw());
			AH_SET(originalPropertyType, ahCall(originalPropertyType.raw(), PT_LC("filtertypes"), 1, filter.raw()));
		}

		return originalPropertyType;
	}

	/* }}} */

	/* {{{ applyWrite() — the property branches (twin 1510, 1610) */

	/* the writable-type send, the node callback and the type change of a
	 * resolved property write (the part both property branches share) */
	[[nodiscard]] bool assignResolvedProperty(zval *nsr, zval *nodeCallback, zval *storage, zval *var, zval *assignedExpr, zval *assignedValueResult, bool isAssignOp, zval *scopeBeforeAssignEval, zval *propertyReflection, zval *assignedExprType, zv::Val &scope, bool sendsWhenReflected)
	{
		if (sendsWhenReflected) {
			AH_VALB(templateArgumentFrame, nsrObservingTemplateArgumentFrame(nsr, scope.raw()));
			if (!templateArgumentFrame.isNull()) {
				AH_VALB(writableType, ahCall(propertyReflection, PT_LC("getwritabletype"), 0, NULL));
				AH_VALB(constraints, taoCollectSend(prop(slots::templateArgumentObserver), writableType.raw(), assignedExprType));
				AH_SETB(scope, pt_mutating_scope_add_template_argument_constraints(Z_OBJ_P(scope.raw()), constraints.raw()));
			}
		}
		{
			AH_VALB(assignNode, newPropertyAssignNode(var, assignedExpr, isAssignOp));
			AH_OKB(nsrCallNodeCallback(nsr, nodeCallback, assignNode.raw(), scopeBeforeAssignEval, storage));
		}
		if (propertyReflection == NULL) return true;
		{
			AH_VALB(canChange, ahCall(propertyReflection, PT_LC("canchangetypeafterassignment"), 0, NULL));
			if (!zend_is_true(canChange.raw())) return true;
		}
		AH_VALB(hasNativeType, ahCall(propertyReflection, PT_LC("hasnativetype"), 0, NULL));
		if (zend_is_true(hasNativeType.raw())) {
			AH_VALB(propertyNativeType, ahCall(propertyReflection, PT_LC("getnativetype"), 0, NULL));
			zend_long compatible = ahTypeOpTri(propertyNativeType.raw(), PT_OP_IS_SUPER_TYPE_OF, 1, assignedExprType);
			AH_OKB(compatible >= 0);
			bool assignedTypeIsCompatible = compatible == PT_TRI_YES;
			if (!assignedTypeIsCompatible) {
				AH_VALB(flattened, pt_type_utils_flatten_types(propertyNativeType.raw()));
				for (auto entry : zv::TableRef(Z_ARRVAL_P(flattened.raw()))) {
					zend_long isSuper = ahTypeOpTri(entry.value().deref().raw(), PT_OP_IS_SUPER_TYPE_OF, 1, assignedExprType);
					AH_OKB(isSuper >= 0);
					if (isSuper == PT_TRI_YES) {
						assignedTypeIsCompatible = true;
						break;
					}
				}
			}

			if (assignedTypeIsCompatible) {
				AH_VALB(nativeScope, pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scope.raw())));
				AH_VALB(nativeType, readAssignedValueType(nsr, assignedValueResult, assignedExpr, nativeScope.raw()));
				AH_SETB(scope, pt_mutating_scope_assign_expression(Z_OBJ_P(scope.raw()), Z_OBJ_P(var), assignedExprType, nativeType.raw()));
			} else {
				bool strictTypes;
				AH_OKB(pt_mutating_scope_is_declare_strict_types(Z_OBJ_P(scope.raw()), strictTypes));
				AH_VALB(coerced, ahCall(assignedExprType, PT_LC("tocoercedargumenttype"), 1, zv::Args{strictTypes}));
				zv::Args intersectArgs{coerced.raw(), propertyNativeType.raw()};
				AH_VALB(type, pt_type_combinator_intersect(2, intersectArgs));
				AH_VALB(nativeScope, pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scope.raw())));
				AH_VALB(nativeAssignedType, readAssignedValueType(nsr, assignedValueResult, assignedExpr, nativeScope.raw()));
				AH_OKB(pt_mutating_scope_is_declare_strict_types(Z_OBJ_P(scope.raw()), strictTypes));
				AH_VALB(nativeCoerced, ahCall(nativeAssignedType.raw(), PT_LC("tocoercedargumenttype"), 1, zv::Args{strictTypes}));
				zv::Args nativeIntersectArgs{nativeCoerced.raw(), propertyNativeType.raw()};
				AH_VALB(nativeType, pt_type_combinator_intersect(2, nativeIntersectArgs));
				AH_SETB(scope, pt_mutating_scope_assign_expression(Z_OBJ_P(scope.raw()), Z_OBJ_P(var), type.raw(), nativeType.raw()));
			}
		} else {
			AH_VALB(nativeScope, pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scope.raw())));
			AH_VALB(nativeType, readAssignedValueType(nsr, assignedValueResult, assignedExpr, nativeScope.raw()));
			AH_SETB(scope, pt_mutating_scope_assign_expression(Z_OBJ_P(scope.raw()), Z_OBJ_P(var), assignedExprType, nativeType.raw()));
		}

		return true;
	}

	/* the fallback of both property branches: the node callback and the
	 * assigned value as the fetch's type */
	[[nodiscard]] static bool assignUnresolvedProperty(zval *nsr, zval *nodeCallback, zval *storage, zval *var, zval *assignedExpr, zval *assignedValueResult, bool isAssignOp, zval *scopeBeforeAssignEval, zv::Val &scope)
	{
		AH_VALB(assignedExprType, readAssignedValueType(nsr, assignedValueResult, assignedExpr, scope.raw()));
		{
			AH_VALB(assignNode, newPropertyAssignNode(var, assignedExpr, isAssignOp));
			AH_OKB(nsrCallNodeCallback(nsr, nodeCallback, assignNode.raw(), scopeBeforeAssignEval, storage));
		}
		AH_VALB(nativeScope, pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scope.raw())));
		AH_VALB(nativeType, readAssignedValueType(nsr, assignedValueResult, assignedExpr, nativeScope.raw()));
		AH_SETB(scope, pt_mutating_scope_assign_expression(Z_OBJ_P(scope.raw()), Z_OBJ_P(var), assignedExprType.raw(), nativeType.raw()));
		return true;
	}

	[[nodiscard]] bool applyWritePropertyFetch(zval *nsr, zval *target, zval *valueResult, zval *assignedValueResult, zval *storage, zval *nodeCallback, zval *context, zval *var, zval *assignedExpr, zv::Val &scope, bool enterExpressionAssign, bool isAssignOp, bool &hasYield, zv::Val &throwPoints, zv::Val &impurePoints, bool &isAlwaysTerminating)
	{
		if (UNEXPECTED(!ahIs(var, PT_CLASS_PROPERTY_FETCH))) {
			pt_throw_should_not_happen();
			return false;
		}
		zval *objectResult = ahRequire(AH_TARGET(target, objectResult));
		AH_OKB(objectResult != NULL);
		zval *propertyName = AH_TARGET(target, propertyName);
		zv::Val scopeBeforeAssignEval = zv::Val::copyOf(scope.ref());
		zval *result = valueResult;
		AH_OKB(pt_expression_result_has_yield_or(result, hasYield));
		{
			AH_VALB(points, ahResultThrowPoints(result));
			AH_OKB(ahMerge(throwPoints, points.raw()));
		}
		{
			AH_VALB(points, ahResultImpurePoints(result));
			AH_OKB(ahMerge(impurePoints, points.raw()));
		}
		AH_OKB(pt_expression_result_is_always_terminating_or(result, isAlwaysTerminating));
		AH_SETB(scope, ahResultScope(result));

		if (ahIs(AH_PROP(var, name), PT_CLASS_EXPR)) {
			bool supportsPropertyHooks;
			AH_OKB(phpVersionSupportsPropertyHooks(prop(slots::phpVersion), supportsPropertyHooks));
			if (supportsPropertyHooks) {
				AH_VALB(throwPoint, itpCreateImplicit(scope.raw(), var));
				ahPush(throwPoints, std::move(throwPoint));
			}
		}

		AH_VALB(propertyHolderType, pt_expression_result_get_type(objectResult));
		bool hasProperty = false;
		if (Z_TYPE_P(propertyName) == IS_STRING) {
			zend_long has = ahTypeOpTri(propertyHolderType.raw(), PT_OP_HAS_INSTANCE_PROPERTY, 1, propertyName);
			AH_OKB(has >= 0);
			hasProperty = has == PT_TRI_YES;
		}
		if (hasProperty) {
			zv::Args getPropertyArgs{propertyName, scope.raw()};
			AH_VALB(propertyReflection, ahCall(propertyHolderType.raw(), PT_LC("getinstanceproperty"), 2, getPropertyArgs));
			AH_VALB(assignedExprType, readAssignedValueType(nsr, assignedValueResult, assignedExpr, scope.raw()));
			AH_OKB(assignResolvedProperty(nsr, nodeCallback, storage, var, assignedExpr, assignedValueResult, isAssignOp, scopeBeforeAssignEval.raw(), propertyReflection.raw(), assignedExprType.raw(), scope, true));
			AH_VALB(declaringClass, ahCall(propertyReflection.raw(), PT_LC("getdeclaringclass"), 0, NULL));
			AH_VALB(hasNativeProperty, ahCall(declaringClass.raw(), PT_LC("hasnativeproperty"), 1, propertyName));
			if (zend_is_true(hasNativeProperty.raw())) {
				AH_VALB(nativeProperty, ahCall(declaringClass.raw(), PT_LC("getnativeproperty"), 1, propertyName));
				AH_VALB(propertyNativeType, ahCall(nativeProperty.raw(), PT_LC("getnativetype"), 0, NULL));

				zend_long compatible = ahTypeOpTri(propertyNativeType.raw(), PT_OP_IS_SUPER_TYPE_OF, 1, assignedExprType.raw());
				AH_OKB(compatible >= 0);
				bool assignedTypeIsCompatible = compatible == PT_TRI_YES;
				if (!assignedTypeIsCompatible && !ahIsCe(assignedExprType.raw(), pt_ce_mixed_type)) {
					AH_VALB(coerced, ahCall(assignedExprType.raw(), PT_LC("tocoercedargumenttype"), 1, zv::Args{true}));
					AH_VALB(flattened, pt_type_utils_flatten_types(coerced.raw()));
					for (auto entry : zv::TableRef(Z_ARRVAL_P(flattened.raw()))) {
						zv::Args acceptsArgs{entry.value().deref().raw(), true};
						zend_long accepts = ahTypeOpTri(propertyNativeType.raw(), PT_OP_ACCEPTS, 2, acceptsArgs);
						AH_OKB(accepts >= 0);
						if (accepts == PT_TRI_YES) {
							assignedTypeIsCompatible = true;
							continue;
						}
						assignedTypeIsCompatible = false;
						break;
					}
				}

				if (!assignedTypeIsCompatible) {
					AH_VALB(typeError, newObjectType(PT_LC("TypeError")));
					AH_VALB(throwPoint, itpCreateExplicit(scope.raw(), typeError.raw(), assignedExpr, false));
					ahPush(throwPoints, std::move(throwPoint));
				}
				bool supportsPropertyHooks;
				AH_OKB(phpVersionSupportsPropertyHooks(prop(slots::phpVersion), supportsPropertyHooks));
				if (supportsPropertyHooks) {
					AH_VALB(points, phtprGetThrowPointsFromSetHook(prop(slots::propertyHookThrowPointsResolver), scope.raw(), var, nativeProperty.raw()));
					AH_OKB(ahMerge(throwPoints, points.raw()));
				}
				if (enterExpressionAssign) {
					AH_SETB(scope, pt_mutating_scope_assign_initialized_property(Z_OBJ_P(scope.raw()), propertyHolderType.raw(), Z_STR_P(propertyName)));
				}
			}
		} else {
			// fallback
			AH_OKB(assignUnresolvedProperty(nsr, nodeCallback, storage, var, assignedExpr, assignedValueResult, isAssignOp, scopeBeforeAssignEval.raw(), scope));
			// simulate dynamic property assign by __set to get throw points
			zv::Val methodName = zv::Val::string(PT_LC("__set"));
			zend_long hasSet = ahTypeOpTri(propertyHolderType.raw(), PT_OP_HAS_METHOD, 1, methodName.raw());
			AH_OKB(hasSet >= 0);
			if (hasSet != PT_TRI_NO) {
				AH_VALB(methodCall, newMethodCall(AH_PROP(var, var), PT_LC("__set")));
				AH_VALB(points, mtphGetThrowPointsForCallOnType(prop(slots::methodThrowPointHelper), scope.raw(), context, propertyHolderType.raw(), methodCall.raw()));
				AH_OKB(ahMerge(throwPoints, points.raw()));
			}
		}

		return true;
	}

	[[nodiscard]] bool applyWriteStaticPropertyFetch(zval *nsr, zval *target, zval *valueResult, zval *assignedValueResult, zval *storage, zval *nodeCallback, zval *var, zval *assignedExpr, zv::Val &scope, bool isAssignOp, bool &hasYield, zv::Val &throwPoints, zv::Val &impurePoints, bool &isAlwaysTerminating)
	{
		if (UNEXPECTED(!ahIs(var, PT_CLASS_STATIC_PROPERTY_FETCH))) {
			pt_throw_should_not_happen();
			return false;
		}
		zval *propertyHolderType = ahRequire(AH_TARGET(target, propertyHolderType));
		AH_OKB(propertyHolderType != NULL);
		zval *propertyName = AH_TARGET(target, propertyName);
		zv::Val scopeBeforeAssignEval = zv::Val::copyOf(scope.ref());
		zval *result = valueResult;
		AH_OKB(pt_expression_result_has_yield_or(result, hasYield));
		{
			AH_VALB(points, ahResultThrowPoints(result));
			AH_OKB(ahMerge(throwPoints, points.raw()));
		}
		{
			AH_VALB(points, ahResultImpurePoints(result));
			AH_OKB(ahMerge(impurePoints, points.raw()));
		}
		AH_OKB(pt_expression_result_is_always_terminating_or(result, isAlwaysTerminating));
		AH_SETB(scope, ahResultScope(result));

		if (Z_TYPE_P(propertyName) == IS_STRING) {
			AH_VALB(propertyReflection, pt_mutating_scope_get_static_property_reflection(Z_OBJ_P(scope.raw()), propertyHolderType, Z_STR_P(propertyName)));
			AH_VALB(assignedExprType, readAssignedValueType(nsr, assignedValueResult, assignedExpr, scope.raw()));
			zval *reflection = propertyReflection.isNull() ? NULL : propertyReflection.raw();
			AH_OKB(assignResolvedProperty(nsr, nodeCallback, storage, var, assignedExpr, assignedValueResult, isAssignOp, scopeBeforeAssignEval.raw(), reflection, assignedExprType.raw(), scope, reflection != NULL));
		} else {
			// fallback
			AH_OKB(assignUnresolvedProperty(nsr, nodeCallback, storage, var, assignedExpr, assignedValueResult, isAssignOp, scopeBeforeAssignEval.raw(), scope));
		}

		return true;
	}

	/* }}} */

	/* {{{ applyWrite() — the KIND_LIST branch (twin 1667) */

	[[nodiscard]] bool applyWriteList(zval *nsr, zval *valueResult, zval *assignedValueResult, zval *stmt, zval *storage, zval *nodeCallback, zval *context, zval *var, zval *assignedExpr, zv::Val &scope, bool enterExpressionAssign, bool &hasYield, zv::Val &throwPoints, zv::Val &impurePoints, bool &isAlwaysTerminating)
	{
		if (UNEXPECTED(!ahIs(var, PT_CLASS_LIST_EXPR))) {
			pt_throw_should_not_happen();
			return false;
		}
		zval *result = valueResult;
		AH_OKB(pt_expression_result_has_yield(result, hasYield));
		{
			AH_VALB(points, ahResultThrowPoints(result));
			AH_OKB(ahMerge(throwPoints, points.raw()));
		}
		{
			AH_VALB(points, ahResultImpurePoints(result));
			AH_OKB(ahMerge(impurePoints, points.raw()));
		}
		AH_OKB(pt_expression_result_is_always_terminating(result, isAlwaysTerminating));
		AH_SETB(scope, ahResultScope(result));
		zval *items = AH_PROP(var, items);
		if (Z_TYPE_P(items) != IS_ARRAY) return true;
		zv::Val itemsHolder = zv::Val::copyOf(zv::Ref(items));
		for (auto entry : zv::TableRef(Z_ARRVAL_P(itemsHolder.raw()))) {
			zval *arrayItem = entry.value().deref().raw();
			if (Z_TYPE_P(arrayItem) == IS_NULL) continue;

			zval *itemValue = AH_PROP(arrayItem, value);
			zval *itemKey = AH_PROP(arrayItem, key);
			zv::Val itemScope = zv::Val::copyOf(scope.ref());
			if (enterExpressionAssign) {
				AH_SETB(itemScope, pt_mutating_scope_enter_expression_assign(Z_OBJ_P(itemScope.raw()), Z_OBJ_P(itemValue), true));
			}
			AH_SETB(itemScope, nsrLookForSetAllowedUndefinedExpressions(nsr, itemScope.raw(), itemValue));
			zv::Val keyResult = ahNull();
			if (Z_TYPE_P(itemKey) != IS_NULL) {
				AH_VALB(keyContext, pt_expression_context_enter_deep(context));
				AH_SETB(keyResult, nsrProcessExprNode(nsr, stmt, itemKey, itemScope.raw(), storage, nodeCallback, keyContext.raw()));
				AH_OKB(pt_expression_result_has_yield_or(keyResult.raw(), hasYield));
				{
					AH_VALB(points, ahResultThrowPoints(keyResult.raw()));
					AH_OKB(ahMerge(throwPoints, points.raw()));
				}
				{
					AH_VALB(points, ahResultImpurePoints(keyResult.raw()));
					AH_OKB(ahMerge(impurePoints, points.raw()));
				}
				AH_OKB(pt_expression_result_is_always_terminating_or(keyResult.raw(), isAlwaysTerminating));
				AH_SETB(scope, ahResultScope(keyResult.raw()));
			}
			// the item fires after its key so a rule reading the key's type
			// consumes the key's result instead of pricing the node ahead of
			// its walk (the literal-array handler orders the two the same way)
			AH_OKB(nsrCallNodeCallback(nsr, nodeCallback, arrayItem, itemScope.raw(), storage));

			zv::Val dimType;
			zv::Val nativeDimType;
			if (!keyResult.isNull()) {
				AH_SETB(dimType, ahTypeOnScope(keyResult.raw(), scope.raw()));
				AH_SETB(nativeDimType, pt_expression_result_get_type_on_scope(keyResult.raw(), scope.raw(), true));
			} else if (entry.stringKeyOrNull() != NULL) {
				zend_type_error("PHPStan\\Type\\Constant\\ConstantIntegerType::__construct(): Argument #1 ($value) must be of type int, string given");
				return false;
			} else {
				AH_SETB(dimType, newConstantIntegerType((zend_long) entry.indexKey()));
				nativeDimType = zv::Val::copyOf(dimType.ref());
			}
			AH_VALB(valueType, readAssignedValueType(nsr, assignedValueResult, assignedExpr, scope.raw()));
			AH_VALB(offsetValueType, ahTypeOp(valueType.raw(), PT_OP_GET_OFFSET_VALUE_TYPE, 1, dimType.raw()));
			AH_VALB(nativeItemScope, pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scope.raw())));
			AH_VALB(nativeValueType, readAssignedValueType(nsr, assignedValueResult, assignedExpr, nativeItemScope.raw()));
			AH_VALB(nativeOffsetValueType, ahTypeOp(nativeValueType.raw(), PT_OP_GET_OFFSET_VALUE_TYPE, 1, nativeDimType.raw()));
			AH_VALB(getOffsetValueTypeExpr, newNativeTypeExpr(offsetValueType.raw(), nativeOffsetValueType.raw()));
			// store the fabricated result so narrowing walks over the item value
			// compose from it instead of falling back to on-demand pricing
			AH_VALB(itemValueResult, vehCreateTypeExprResult(prop(slots::virtualExprResultHelper), scope.raw(), getOffsetValueTypeExpr.raw()));
			AH_OKB(nsrStoreExpressionResult(nsr, storage, getOffsetValueTypeExpr.raw(), itemValueResult.raw()));
			AH_VALB(mode, enterExpressionAssign ? walkModeAssign() : walkModeVirtualAssign());
			AH_VALB(itemTarget, prepareTarget(nsr, scope.raw(), storage, stmt, itemValue, getOffsetValueTypeExpr.raw(), nodeCallback, context, mode.raw()));
			zval *itemTargetScope = AH_TARGET(itemTarget.raw(), scope);
			zv::Val typeCallback = pt_native_closure(&mixedTypeCallbackBody);
			AH_VALB(specifyTypesCallback, pt_specified_types_empty_specify_callback());
			pt_expression_result_args args(itemTargetScope, itemTargetScope, getOffsetValueTypeExpr.raw(), false, false, NULL, NULL, typeCallback.raw(), specifyTypesCallback.raw());
			AH_VALB(itemWriteValueResult, pt_expression_result_create(factory(), args));
			// a nested list() target recurses natively, one level per nesting
			zv::Val itemResult;
			pt_engine_with_stack([&]() { itemResult = applyWrite(nsr, itemTarget.raw(), itemWriteValueResult.raw(), itemValueResult.raw(), stmt, storage, nodeCallback, context); });
			AH_OKB(!itemResult.isUndef());
			AH_SETB(scope, ahResultScope(itemResult.raw()));
			AH_OKB(pt_expression_result_has_yield_or(itemResult.raw(), hasYield));
			{
				AH_VALB(points, ahResultThrowPoints(itemResult.raw()));
				AH_OKB(ahMerge(throwPoints, points.raw()));
			}
			{
				AH_VALB(points, ahResultImpurePoints(itemResult.raw()));
				AH_OKB(ahMerge(impurePoints, points.raw()));
			}
			AH_OKB(pt_expression_result_is_always_terminating_or(itemResult.raw(), isAlwaysTerminating));
		}

		return true;
	}

	/* }}} */

	/* {{{ applyWrite() — the KIND_EXISTING_ARRAY_DIM_FETCH branch (twin 1748) */

	[[nodiscard]] bool applyWriteExistingArrayDimFetch(zval *nsr, zval *target, zval *assignedValueResult, zval *storage, zval *nodeCallback, zval *assignedExpr, zv::Val &scope, bool isAssignOp, zv::Val &resultVar)
	{
		zval *var = ahRequire(AH_TARGET(target, rootVar));
		AH_OKB(var != NULL);
		resultVar = zv::Val::copyOf(zv::Ref(var));
		zval *varResult = ahRequire(AH_TARGET(target, varResult));
		AH_OKB(varResult != NULL);
		zval *assignedPropertyExpr = ahRequire(AH_TARGET(target, assignedPropertyExpr));
		AH_OKB(assignedPropertyExpr != NULL);
		zval *offsetTypes = ahRequire(AH_TARGET(target, existingOffsetTypes));
		AH_OKB(offsetTypes != NULL);
		zval *offsetNativeTypes = ahRequire(AH_TARGET(target, existingOffsetNativeTypes));
		AH_OKB(offsetNativeTypes != NULL);
		AH_VALB(valueToWrite, readAssignedValueType(nsr, assignedValueResult, assignedExpr, scope.raw()));
		AH_VALB(nativeScope, pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scope.raw())));
		AH_VALB(nativeValueToWrite, readAssignedValueType(nsr, assignedValueResult, assignedExpr, nativeScope.raw()));
		zv::Val varType;
		zv::Val varNativeType;
		AH_OKB(resolveContainerTypesAfterAssignedExprEval(nsr, var, varResult, scope.raw(), NULL, storage, varType, varNativeType));

		AH_OKB(rebuildExistingOffsetChain(Z_ARRVAL_P(offsetTypes), varType.raw(), valueToWrite));
		AH_OKB(rebuildExistingOffsetChain(Z_ARRVAL_P(offsetNativeTypes), varNativeType.raw(), nativeValueToWrite));

		zend_string *varName = ahStringVariableName(var);
		if (varName != NULL) {
			AH_VALB(assignNode, newVariableAssignNode(var, assignedPropertyExpr));
			AH_OKB(nsrCallNodeCallback(nsr, nodeCallback, assignNode.raw(), scope.raw(), storage));
			AH_SETB(scope, pt_mutating_scope_assign_variable(Z_OBJ_P(scope.raw()), varName, valueToWrite.raw(), nativeValueToWrite.raw(), ahTrinary(PT_TRI_YES)));
		} else {
			if (ahIs(var, PT_CLASS_PROPERTY_FETCH) || ahIs(var, PT_CLASS_STATIC_PROPERTY_FETCH)) {
				AH_VALB(assignNode, newPropertyAssignNode(var, assignedPropertyExpr, isAssignOp));
				AH_OKB(nsrCallNodeCallback(nsr, nodeCallback, assignNode.raw(), scope.raw(), storage));
			}
			AH_SETB(scope, pt_mutating_scope_assign_expression(Z_OBJ_P(scope.raw()), Z_OBJ_P(var), valueToWrite.raw(), nativeValueToWrite.raw()));
		}

		return true;
	}

	/* the offset value type stack over all but the last offset, then
	 * setExistingOffsetValueType() back out from the outermost (twin
	 * 1758-1780, one flavour) */
	[[nodiscard]] static bool rebuildExistingOffsetChain(HashTable *offsetTypes, zval *containerType, zv::Val &valueToWrite)
	{
		uint32_t count = zend_hash_num_elements(offsetTypes);
		zv::Arr stack = zv::Arr::create(count);
		zv::Val offsetValueType = zv::Val::copyOf(zv::Ref(containerType));
		stack.push(offsetValueType.ref());
		for (uint32_t i = 0; i + 1 < count; i++) {
			zval *offsetType = zend_hash_index_find(Z_ARRVAL_P(zend_hash_index_find(offsetTypes, i)), 0);
			AH_SETB(offsetValueType, ahTypeOp(offsetValueType.raw(), PT_OP_GET_OFFSET_VALUE_TYPE, 1, offsetType));
			stack.push(offsetValueType.ref());
		}
		for (uint32_t i = count; i > 0; i--) {
			zval *offsetType = zend_hash_index_find(Z_ARRVAL_P(zend_hash_index_find(offsetTypes, i - 1)), 0);
			zval *container = zend_hash_index_find(stack.table(), i - 1);
			zv::Args setArgs{offsetType, valueToWrite.raw()};
			AH_SETB(valueToWrite, ahCall(container, PT_LC("setexistingoffsetvaluetype"), 2, setArgs));
		}
		return true;
	}

	/* }}} */

	/* {{{ the narrowing callbacks of an Assign (twin 313-551, 1844) */

	/* (twin 313) */
	static zv::Val captureAssignedCallArgResults(zval *assignedExpr, zval *storage)
	{
		zval *call = NULL;
		if (ahIs(assignedExpr, PT_CLASS_FUNC_CALL)) {
			call = assignedExpr;
		} else if (ahIs(assignedExpr, PT_CLASS_BINARY_OP_MINUS) && ahIs(AH_PROP(assignedExpr, left), PT_CLASS_FUNC_CALL)) {
			call = AH_PROP(assignedExpr, left);
		}
		if (call == NULL) return ahEmptyArray();
		bool firstClassCallable;
		AH_OK(pt_call_like_is_first_class_callable(Z_OBJ_P(call), firstClassCallable));
		if (firstClassCallable) return ahEmptyArray();

		zv::Val argResults = ahEmptyArray();
		HashTable *args = ahArgs(call);
		if (args == NULL) return argResults;
		for (auto entry : zv::TableRef(args)) {
			zval *arg = entry.value().deref().raw();
			zval *value = AH_PROP(arg, value);
			AH_VAL(argResult, pt_expression_result_storage_find(storage, value));
			if (argResult.isNull()) continue;

			ahSetIndex(argResults, ahObjectId(value), argResult.raw());
		}

		return argResults;
	}

	/* (twin 367) */
	zv::Val createSpecifyTypesCallback(zval *expr, zval *assignedExprResult, zval *beforeScope, zval *storage)
	{
		// the value expression's call arguments were walked as its children -
		// capture their results now so the lazy narrowing below reads them
		// instead of the storage
		AH_VAL(argResults, captureAssignedCallArgResults(AH_PROP(expr, expr), storage));

		return pt_native_closure(&specifyTypesCallbackBody, self, expr, assignedExprResult, beforeScope, argResults.raw());
	}

	/* $argType($e) of the specifyTypesCallback: the captured argument result's
	 * type on $s */
	static zv::Val argType(zval *argResults, zval *s, zval *e)
	{
		zval *result = Z_TYPE_P(e) == IS_OBJECT ? zend_hash_index_find(Z_ARRVAL_P(argResults), ahObjectId(e)) : NULL;
		if (result != NULL && Z_TYPE_P(result) != IS_NULL) return ahTypeOnScope(result, s);

		// every argument of the walked call has a captured result
		pt_throw_should_not_happen();
		return zv::Val();
	}

	/* $specifiedTypes = $specifiedTypes->unionWith($this->defaultNarrowingHelper->createSubjectTypes($s, $subject, null, $type, TypeSpecifierContext::createTrue())) */
	static zv::Val unionWithTrueSubjectTypes(zend_object *handler, zval *specifiedTypes, zval *s, zval *subject, zval *type)
	{
		zend_object *trueContext = pt_type_specifier_context_create_true();
		if (UNEXPECTED(trueContext == NULL)) return zv::Val();
		zval trueContextZv;
		ZVAL_OBJ(&trueContextZv, trueContext);
		zval null;
		ZVAL_NULL(&null);
		AH_VAL(subjectTypes, dnhCreateSubjectTypes(OBJ_PROP_NUM(handler, slots::defaultNarrowingHelper), s, subject, &null, type, &trueContextZv));
		return pt_specified_types_union_with(Z_OBJ_P(specifiedTypes), subjectTypes.raw());
	}

	/* function (TypeSpecifierContext $context, bool $nativeTypesPromoted) use
	 * ($expr, $assignedExprResult, $beforeScope, $argResults): SpecifiedTypes —
	 * captures: $this, $expr, $assignedExprResult, $beforeScope, $argResults */
	static void specifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 2)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\ExprHandler\\AssignHandler::{closure}(), %u passed and exactly 2 expected", argc);
			return;
		}
		// the nested assignment in the value re-enters this body through its
		// result (a chain `$a = $b = ... = 1` recurses once per link) —
		// natively, where the twin grew only the VM stack
		zv::Val specifiedTypes;
		pt_engine_with_stack([&]() { specifiedTypes = specifyTypes(captures, &argv[0], zend_is_true(&argv[1])); });
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}

	static zv::Val specifyTypes(zval *captures, zval *context, bool nativeTypesPromoted)
	{
		zend_object *handler = Z_OBJ(captures[0]);
		zval *expr = &captures[1];
		zval *assignedExprResult = &captures[2];
		zval *beforeScope = &captures[3];
		zval *argResults = &captures[4];

		AH_VAL(s, ahFlavourScope(beforeScope, nativeTypesPromoted));
		zval *exprVar = AH_PROP(expr, var);
		zval *exprExpr = AH_PROP(expr, expr);
		bool contextNull;
		AH_OK(pt_type_specifier_context_null(Z_OBJ_P(context), contextNull));
		zv::Val specifiedTypes;
		if (contextNull) {
			AH_VAL(assignedScope, pt_mutating_scope_exit_first_level_statements(Z_OBJ_P(s.raw())));
			AH_VAL(assignedSpecifiedTypes, pt_expression_result_get_specified_types_for_scope(assignedExprResult, assignedScope.raw(), context));
			AH_SET(specifiedTypes, pt_specified_types_set_root_expr(Z_OBJ_P(assignedSpecifiedTypes.raw()), expr));
			zv::Str varString = zv::Str::adopt(printExpr(OBJ_PROP_NUM(handler, slots::exprPrinter), exprVar));
			AH_OK(!varString.isNull());
			AH_SET(specifiedTypes, pt_specified_types_remove_expr(Z_OBJ_P(specifiedTypes.raw()), varString.get()));
		} else {
			AH_VAL(defaultTypes, dnhSpecifyDefaultTypes(OBJ_PROP_NUM(handler, slots::defaultNarrowingHelper), exprVar, context));
			AH_SET(specifiedTypes, pt_specified_types_set_root_expr(Z_OBJ_P(defaultTypes.raw()), expr));
		}

		bool isFuncCall = ahIs(exprExpr, PT_CLASS_FUNC_CALL);
		zval *funcName = isFuncCall ? AH_PROP(exprExpr, name) : NULL;
		bool firstClassCallable = false;
		if (isFuncCall && ahIs(funcName, PT_CLASS_NAME)) {
			AH_OK(pt_call_like_is_first_class_callable(Z_OBJ_P(exprExpr), firstClassCallable));
		}

		// infer $arr[$key] after $key = array_key_first/last($arr)
		if (
			isFuncCall
			&& ahIs(funcName, PT_CLASS_NAME)
			&& !firstClassCallable
			&& (ahNameIs(funcName, PT_LC("array_key_first")) || ahNameIs(funcName, PT_LC("array_key_last")))
			&& ahArgCount(exprExpr) >= 1
		) {
			zval *arrayArg = ahArgValue(exprExpr, 0);
			AH_VAL(arrayType, argType(argResults, s.raw(), arrayArg));

			zend_long isArray = ahTypeOpTri(arrayType.raw(), PT_OP_IS_ARRAY, 0, NULL);
			AH_OK(isArray >= 0);
			if (isArray == PT_TRI_YES) {
				bool contextTrue;
				AH_OK(pt_type_specifier_context_true(Z_OBJ_P(context), contextTrue));
				bool isNonEmpty;
				if (contextTrue) {
					AH_VAL(nonEmptyArray, newNonEmptyArrayType());
					AH_SET(specifiedTypes, unionWithTrueSubjectTypes(handler, specifiedTypes.raw(), s.raw(), arrayArg, nonEmptyArray.raw()));
					isNonEmpty = true;
				} else {
					zend_long nonEmpty = ahTypeOpTri(arrayType.raw(), PT_OP_IS_ITERABLE_AT_LEAST_ONCE, 0, NULL);
					AH_OK(nonEmpty >= 0);
					isNonEmpty = nonEmpty == PT_TRI_YES;
				}

				if (isNonEmpty) {
					AH_VAL(dimFetch, newArrayDimFetch(arrayArg, exprVar));
					AH_VAL(iterableValueType, ahTypeOp(arrayType.raw(), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL));
					AH_SET(specifiedTypes, unionWithTrueSubjectTypes(handler, specifiedTypes.raw(), s.raw(), dimFetch.raw(), iterableValueType.raw()));
				} else if (ahStringVariableName(exprVar) != NULL) {
					AH_VAL(keyType, ahTypeOnScope(assignedExprResult, s.raw()));
					AH_VAL(nonNullKeyType, pt_type_combinator_remove_null(keyType.raw()));
					if (!ahIsCe(nonNullKeyType.raw(), pt_ce_never_type)) {
						AH_VAL(iterableValueType, ahTypeOp(arrayType.raw(), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL));
						AH_VAL(holderTypes, createArrayDimFetchConditionalExpressionHolder(handler, exprVar, arrayArg, nonNullKeyType.raw(), iterableValueType.raw()));
						AH_SET(specifiedTypes, pt_specified_types_union_with(Z_OBJ_P(specifiedTypes.raw()), holderTypes.raw()));
					}
				}
			}
		}

		// infer $arr[$key] after $key = array_search($needle, $arr) or $key = array_find_key($arr, $callback)
		if (
			isFuncCall
			&& ahIs(funcName, PT_CLASS_NAME)
			&& !firstClassCallable
			&& ahArgCount(exprExpr) >= 2
		) {
			zval *arrayArg = NULL;
			zv::Val sentinelType;
			bool isStrictArraySearch = false;

			if (ahNameIs(funcName, PT_LC("array_search"))) {
				arrayArg = ahArgValue(exprExpr, 1);
				AH_SET(sentinelType, newConstantBooleanType(false));
				if (ahArgCount(exprExpr) >= 3) {
					AH_VAL(strictType, argType(argResults, s.raw(), ahArgValue(exprExpr, 2)));
					zend_long isTrue = ahCallTri(strictType.raw(), PT_LC("istrue"), 0, NULL);
					AH_OK(isTrue >= 0);
					isStrictArraySearch = isTrue == PT_TRI_YES;
				}
			} else if (ahNameIs(funcName, PT_LC("array_find_key"))) {
				arrayArg = ahArgValue(exprExpr, 0);
				AH_SET(sentinelType, newNullType());
			}

			if (arrayArg != NULL) {
				AH_VAL(arrayType, argType(argResults, s.raw(), arrayArg));

				zend_long isArray = ahTypeOpTri(arrayType.raw(), PT_OP_IS_ARRAY, 0, NULL);
				AH_OK(isArray >= 0);
				if (isArray == PT_TRI_YES) {
					bool contextTrue;
					AH_OK(pt_type_specifier_context_true(Z_OBJ_P(context), contextTrue));
					if (contextTrue) {
						AH_VAL(nonEmptyArray, newNonEmptyArrayType());
						AH_SET(specifiedTypes, unionWithTrueSubjectTypes(handler, specifiedTypes.raw(), s.raw(), arrayArg, nonEmptyArray.raw()));

						AH_VAL(dimFetch, newArrayDimFetch(arrayArg, exprVar));

						zv::Val dimFetchType;
						if (isStrictArraySearch) {
							AH_VAL(needleType, argType(argResults, s.raw(), ahArgValue(exprExpr, 0)));
							AH_VAL(iterableValueType, ahTypeOp(arrayType.raw(), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL));
							zv::Args intersectArgs{needleType.raw(), iterableValueType.raw()};
							AH_SET(dimFetchType, pt_type_combinator_intersect(2, intersectArgs));
						} else {
							AH_SET(dimFetchType, ahTypeOp(arrayType.raw(), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL));
						}

						AH_SET(specifiedTypes, unionWithTrueSubjectTypes(handler, specifiedTypes.raw(), s.raw(), dimFetch.raw(), dimFetchType.raw()));
					} else if (ahStringVariableName(exprVar) != NULL) {
						AH_VAL(keyType, ahTypeOnScope(assignedExprResult, s.raw()));
						AH_VAL(narrowedKeyType, pt_type_combinator_remove(keyType.raw(), sentinelType.raw()));
						if (!ahIsCe(narrowedKeyType.raw(), pt_ce_never_type)) {
							zv::Val dimFetchType;
							if (isStrictArraySearch) {
								AH_VAL(needleType, argType(argResults, s.raw(), ahArgValue(exprExpr, 0)));
								AH_VAL(iterableValueType, ahTypeOp(arrayType.raw(), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL));
								zv::Args intersectArgs{needleType.raw(), iterableValueType.raw()};
								AH_SET(dimFetchType, pt_type_combinator_intersect(2, intersectArgs));
							} else {
								AH_SET(dimFetchType, ahTypeOp(arrayType.raw(), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL));
							}
							AH_VAL(holderTypes, createArrayDimFetchConditionalExpressionHolder(handler, exprVar, arrayArg, narrowedKeyType.raw(), dimFetchType.raw()));
							AH_SET(specifiedTypes, pt_specified_types_union_with(Z_OBJ_P(specifiedTypes.raw()), holderTypes.raw()));
						}
					}
				}
			}
		}

		if (contextNull) {
			// infer $arr[$key] after $key = array_rand($arr)
			if (
				isFuncCall
				&& ahIs(funcName, PT_CLASS_NAME)
				&& !firstClassCallable
				&& ahNameIs(funcName, PT_LC("array_rand"))
				&& ahArgCount(exprExpr) >= 1
			) {
				zval *numArg = NULL;
				zval *arrayArg = ahArgValue(exprExpr, 0);
				if (ahArgCount(exprExpr) > 1) {
					numArg = ahArgValue(exprExpr, 1);
				}
				AH_VAL(one, newConstantIntegerType(1));
				AH_VAL(arrayType, argType(argResults, s.raw(), arrayArg));

				zend_long isArray = ahTypeOpTri(arrayType.raw(), PT_OP_IS_ARRAY, 0, NULL);
				AH_OK(isArray >= 0);
				bool applies = isArray == PT_TRI_YES;
				if (applies) {
					zend_long nonEmpty = ahTypeOpTri(arrayType.raw(), PT_OP_IS_ITERABLE_AT_LEAST_ONCE, 0, NULL);
					AH_OK(nonEmpty >= 0);
					applies = nonEmpty == PT_TRI_YES;
				}
				if (applies && numArg != NULL) {
					AH_VAL(numType, argType(argResults, s.raw(), numArg));
					zend_long isOne = ahTypeOpTri(one.raw(), PT_OP_IS_SUPER_TYPE_OF, 1, numType.raw());
					AH_OK(isOne >= 0);
					applies = isOne == PT_TRI_YES;
				}
				if (applies) {
					AH_VAL(dimFetch, newArrayDimFetch(arrayArg, exprVar));
					AH_VAL(iterableValueType, ahTypeOp(arrayType.raw(), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL));
					return unionWithTrueSubjectTypes(handler, specifiedTypes.raw(), s.raw(), dimFetch.raw(), iterableValueType.raw());
				}
			}

			// infer $list[$count] after $count = count($list) - 1
			if (ahIs(exprExpr, PT_CLASS_BINARY_OP_MINUS)) {
				zval *left = AH_PROP(exprExpr, left);
				zval *right = AH_PROP(exprExpr, right);
				bool leftFirstClassCallable = false;
				bool leftIsCall = ahIs(left, PT_CLASS_FUNC_CALL) && ahIs(AH_PROP(left, name), PT_CLASS_NAME);
				if (leftIsCall) {
					AH_OK(pt_call_like_is_first_class_callable(Z_OBJ_P(left), leftFirstClassCallable));
				}
				if (
					leftIsCall
					&& !leftFirstClassCallable
					&& ahIs(right, PT_CLASS_SCALAR_INT)
					&& isIntOne(right)
					&& (ahNameIs(AH_PROP(left, name), PT_LC("count")) || ahNameIs(AH_PROP(left, name), PT_LC("sizeof")))
					&& ahArgCount(left) >= 1
				) {
					zval *arrayArg = ahArgValue(left, 0);
					AH_VAL(arrayType, argType(argResults, s.raw(), arrayArg));
					zend_long isList = ahTypeOpTri(arrayType.raw(), PT_OP_IS_LIST, 0, NULL);
					AH_OK(isList >= 0);
					if (isList == PT_TRI_YES) {
						zend_long nonEmpty = ahTypeOpTri(arrayType.raw(), PT_OP_IS_ITERABLE_AT_LEAST_ONCE, 0, NULL);
						AH_OK(nonEmpty >= 0);
						if (nonEmpty == PT_TRI_YES) {
							AH_VAL(dimFetch, newArrayDimFetch(arrayArg, exprVar));
							AH_VAL(iterableValueType, ahTypeOp(arrayType.raw(), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL));
							return unionWithTrueSubjectTypes(handler, specifiedTypes.raw(), s.raw(), dimFetch.raw(), iterableValueType.raw());
						}
					}
				}
			}
		}

		return specifiedTypes;
	}

	/* (twin 1844) */
	static zv::Val createArrayDimFetchConditionalExpressionHolder(zend_object *handler, zval *keyVar, zval *arrayArg, zval *narrowedKeyType, zval *dimFetchType)
	{
		AH_VAL(dimFetch, newArrayDimFetch(arrayArg, keyVar));
		zval *exprPrinter = OBJ_PROP_NUM(handler, slots::exprPrinter);
		zv::Str dimFetchString = zv::Str::adopt(printExpr(exprPrinter, dimFetch.raw()));
		AH_OK(!dimFetchString.isNull());
		zv::Str keyExprString = zv::Str::adopt(printExpr(exprPrinter, keyVar));
		AH_OK(!keyExprString.isNull());

		AH_VAL(keyHolder, newExpressionTypeHolder(keyVar, narrowedKeyType, PT_TRI_YES));
		zv::Val conditions = ahEmptyArray();
		ahSetKey(conditions, keyExprString.get(), keyHolder.raw());
		AH_VAL(typeHolder, newExpressionTypeHolder(dimFetch.raw(), dimFetchType, PT_TRI_YES));
		AH_VAL(holder, newConditionalExpressionHolder(conditions.raw(), typeHolder.raw()));

		AH_VAL(specifiedTypes, pt_specified_types_new());
		zv::Str holderKey = zv::Str::adopt(conditionalHolderKey(holder.raw()));
		AH_OK(!holderKey.isNull());
		zv::Val holders = ahEmptyArray();
		{
			zv::Val inner = ahEmptyArray();
			ahSetKey(inner, holderKey.get(), holder.raw());
			ahSetKey(holders, dimFetchString.get(), inner.raw());
		}
		return pt_specified_types_set_new_conditional_expression_holders(Z_OBJ_P(specifiedTypes.raw()), holders.raw());
	}

	/* function (Type $type, TypeSpecifierContext $context, bool
	 * $nativeTypesPromoted) use ($expr, $assignedExprResult, $beforeScope):
	 * SpecifiedTypes — captures: $this, $expr, $assignedExprResult,
	 * $beforeScope (twin 346) */
	static void createTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 3)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\ExprHandler\\AssignHandler::{closure}(), %u passed and exactly 3 expected", argc);
			return;
		}
		zv::Val types;
		pt_engine_with_stack([&]() { types = createTypes(captures, &argv[0], &argv[1], zend_is_true(&argv[2])); });
		if (UNEXPECTED(types.isUndef())) return;
		types.intoReturnValue(return_value);
	}

	static zv::Val createTypes(zval *captures, zval *type, zval *context, bool nativeTypesPromoted)
	{
		zval *helper = OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::defaultNarrowingHelper);
		zval *expr = &captures[1];
		zval *assignedExprResult = &captures[2];
		AH_VAL(s, ahFlavourScope(&captures[3], nativeTypesPromoted));
		zval null;
		ZVAL_NULL(&null);
		AH_VAL(types, dnhCreateSubjectTypes(helper, s.raw(), AH_PROP(expr, var), &null, type, context));
		AH_VAL(exprTypes, dnhCreateSubjectTypes(helper, s.raw(), AH_PROP(expr, expr), assignedExprResult, type, context));
		return pt_specified_types_union_with(Z_OBJ_P(types.raw()), exprTypes.raw());
	}

	/* fn (TypeSpecifierContext $context, bool $nativeTypesPromoted): SpecifiedTypes =>
	 * $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context) —
	 * captures: $this, $expr */
	static void defaultSpecifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 2)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\ExprHandler\\AssignHandler::{closure}(), %u passed and exactly 2 expected", argc);
			return;
		}
		zv::Val specifiedTypes = dnhSpecifyDefaultTypes(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::defaultNarrowingHelper), &captures[1], &argv[0]);
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}

	/* }}} */

	/* {{{ the value callbacks */

	/* static fn (bool $nativeTypesPromoted): Type => $nativeTypesPromoted ?
	 * $assignedExprResult->getNativeType() : $assignedExprResult->getType() —
	 * captures: $assignedExprResult */
	static void resultTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 1)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\ExprHandler\\AssignHandler::{closure}(), %u passed and exactly 1 expected", argc);
			return;
		}
		zv::Val type;
		pt_engine_with_stack([&]() { type = zend_is_true(&argv[0]) ? pt_expression_result_get_native_type(&captures[0]) : pt_expression_result_get_type(&captures[0]); });
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
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

	/* static fn (): Type => new NeverType() */
	static void neverTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) captures;
		(void) argc;
		(void) argv;
		zv::Val type = newNeverType();
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* static function (bool $nativeTypesPromoted) use ($previousLinkResult,
	 * $dimNodeResult, $scope): Type — captures: $previousLinkResult,
	 * $dimNodeResult, $scope (twin 818) */
	static void dimLinkTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 1)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\ExprHandler\\AssignHandler::{closure}(), %u passed and exactly 1 expected", argc);
			return;
		}
		zv::Val type;
		pt_engine_with_stack([&]() { type = dimLinkType(captures, zend_is_true(&argv[0])); });
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	static zv::Val dimLinkType(zval *captures, bool nativeTypesPromoted)
	{
		AH_VAL(s, ahFlavourScope(&captures[2], nativeTypesPromoted));
		AH_VAL(containerType, ahTypeOnScope(&captures[0], s.raw()));
		AH_VAL(dimType, ahTypeOnScope(&captures[1], s.raw()));
		return ahTypeOp(containerType.raw(), PT_OP_GET_OFFSET_VALUE_TYPE, 1, dimType.raw());
	}

	/* static fn (): Type => $type->equals($falseyType) ? new
	 * ConstantBooleanType(true) : new BooleanType() — captures: $type,
	 * $falseyType (twin 1308) */
	static void identicalFalseyTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) argc;
		(void) argv;
		bool equal;
		if (UNEXPECTED(!ahEquals(&captures[0], &captures[1], equal))) return;
		zv::Val type = equal ? newConstantBooleanType(true) : newBooleanType();
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* static fn (Type $innerType) => !$innerType->isSuperTypeOf($currentPropertyType)->no()
	 * — captures: $currentPropertyType (twin 2812) */
	static void filterNotDisjointCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 1)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\ExprHandler\\AssignHandler::{closure}(), %u passed and exactly 1 expected", argc);
			return;
		}
		zend_long isSuper = ahTypeOpTri(&argv[0], PT_OP_IS_SUPER_TYPE_OF, 1, &captures[0]);
		if (UNEXPECTED(isSuper < 0)) return;
		RETURN_BOOL(isSuper != PT_TRI_NO);
	}

	/* }}} */

	/* (twin 2818) */
	static bool hasArrayReference(zval *array)
	{
		zval *items = AH_PROP(array, items);
		if (Z_TYPE_P(items) != IS_ARRAY) return false;
		for (auto entry : zv::TableRef(Z_ARRVAL_P(items))) {
			zval *item = entry.value().deref().raw();
			if (zend_is_true(AH_PROP(item, byRef))) return true;
			zval *value = AH_PROP(item, value);
			if (ahIs(value, PT_CLASS_ARRAY_EXPR)) {
				bool nested = false;
				pt_engine_with_stack([&]() { nested = hasArrayReference(value); });
				if (nested) return true;
			}
		}
		return false;
	}

	/* (twin 2828) */
	static zv::Val redundant(zval *rhs, zval *targetArg, zval *storage)
	{
		zval *target = targetArg;
		zv::Arr dimensions = zv::Arr::create(0);
		while (ahIs(target, PT_CLASS_ARRAY_DIM_FETCH)) {
			zval *dim = AH_PROP(target, dim);
			if (Z_TYPE_P(dim) == IS_NULL) return ahNull();
			AH_VAL(dimResult, pt_expression_result_storage_find(storage, dim));
			if (dimResult.isNull()) return ahNull();
			{
				AH_VAL(dimType, pt_expression_result_get_type(dimResult.raw()));
				AH_VAL(arrayKey, ahTypeOp(dimType.raw(), PT_OP_TO_ARRAY_KEY, 0, NULL));
				AH_VAL(values, ahTypeOp(arrayKey.raw(), PT_OP_GET_CONSTANT_SCALAR_VALUES, 0, NULL));
				if (zend_hash_num_elements(Z_ARRVAL_P(values.raw())) != 1) return ahNull();
			}
			dimensions.push(std::move(dimResult));
			target = AH_PROP(target, var);
		}
		zend_string *name = ahStringVariableName(target);
		if (name == NULL) return ahNull();
		AH_VAL(scope, ahResultScope(rhs));
		AH_VAL(nativeScope, pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scope.raw())));
		{
			AH_VAL(has, pt_mutating_scope_has_variable_type(Z_OBJ_P(scope.raw()), name));
			zend_long hasValue = ahTri(has.raw());
			AH_OK(hasValue >= 0);
			if (hasValue != PT_TRI_YES) return ahNull();
			AH_VAL(nativeHas, pt_mutating_scope_has_variable_type(Z_OBJ_P(nativeScope.raw()), name));
			zend_long nativeHasValue = ahTri(nativeHas.raw());
			AH_OK(nativeHasValue >= 0);
			if (nativeHasValue != PT_TRI_YES) return ahNull();
		}
		AH_VAL(type, pt_mutating_scope_get_variable_type(Z_OBJ_P(scope.raw()), name));
		AH_VAL(nativeType, pt_mutating_scope_get_variable_type(Z_OBJ_P(nativeScope.raw()), name));
		HashTable *dimensionsTable = dimensions.table();
		for (uint32_t i = zend_hash_num_elements(dimensionsTable); i > 0; i--) {
			zval *dimension = zend_hash_index_find(dimensionsTable, i - 1);
			AH_VAL(offsetType, pt_expression_result_get_type(dimension));
			AH_VAL(offset, ahTypeOp(offsetType.raw(), PT_OP_TO_ARRAY_KEY, 0, NULL));
			AH_VAL(nativeOffsetType, pt_expression_result_get_native_type(dimension));
			AH_VAL(nativeOffset, ahTypeOp(nativeOffsetType.raw(), PT_OP_TO_ARRAY_KEY, 0, NULL));
			zend_long check = ahTypeOpTri(type.raw(), PT_OP_IS_ARRAY, 0, NULL);
			AH_OK(check >= 0);
			if (check != PT_TRI_YES) return ahNull();
			check = ahTypeOpTri(nativeType.raw(), PT_OP_IS_ARRAY, 0, NULL);
			AH_OK(check >= 0);
			if (check != PT_TRI_YES) return ahNull();
			check = ahTypeOpTri(type.raw(), PT_OP_HAS_OFFSET_VALUE_TYPE, 1, offset.raw());
			AH_OK(check >= 0);
			if (check != PT_TRI_YES) return ahNull();
			check = ahTypeOpTri(nativeType.raw(), PT_OP_HAS_OFFSET_VALUE_TYPE, 1, nativeOffset.raw());
			AH_OK(check >= 0);
			if (check != PT_TRI_YES) return ahNull();
			AH_SET(type, ahTypeOp(type.raw(), PT_OP_GET_OFFSET_VALUE_TYPE, 1, offset.raw()));
			AH_SET(nativeType, ahTypeOp(nativeType.raw(), PT_OP_GET_OFFSET_VALUE_TYPE, 1, nativeOffset.raw()));
		}
		AH_VAL(values, ahCall(type.raw(), PT_LC("getfinitetypes"), 0, NULL));
		if (zend_hash_num_elements(Z_ARRVAL_P(values.raw())) != 1) return ahNull();
		{
			AH_VAL(rhsType, pt_expression_result_get_type(rhs));
			bool equal;
			AH_OK(ahEquals(zend_hash_index_find(Z_ARRVAL_P(values.raw()), 0), rhsType.raw(), equal));
			if (!equal) return ahNull();
		}
		AH_VAL(nativeValues, ahCall(nativeType.raw(), PT_LC("getfinitetypes"), 0, NULL));
		if (zend_hash_num_elements(Z_ARRVAL_P(nativeValues.raw())) != 1) return ahNull();
		AH_VAL(rhsNativeType, pt_expression_result_get_native_type(rhs));
		bool equal;
		AH_OK(ahEquals(zend_hash_index_find(Z_ARRVAL_P(nativeValues.raw()), 0), rhsNativeType.raw(), equal));
		if (!equal) return ahNull();
		return pt_expression_result_get_type(rhs);
	}
};

} // namespace phpstanturbo

using phpstanturbo::AssignHandler;

/* {{{ direct entries for native callers (support.h) */

zv::Val pt_assign_handler_prepare_target(zval *handler, zval *nodeScopeResolver, zval *scope, zval *storage, zval *stmt, zval *var, zval *assignedExpr, zval *nodeCallback, zval *context, zval *mode)
{
	if (EXPECTED(Z_OBJCE_P(handler) == pt_ce_assign_handler)) return AssignHandler(Z_OBJ_P(handler)).prepareTarget(nodeScopeResolver, scope, storage, stmt, var, assignedExpr, nodeCallback, context, mode);
	zv::Args argv{nodeScopeResolver, scope, storage, stmt, var, assignedExpr, nodeCallback, context, mode};
	return pt_type_call(Z_OBJ_P(handler), PT_LC("preparetarget"), 9, argv);
}

zv::Val pt_assign_handler_apply_write(zval *handler, zval *nodeScopeResolver, zval *target, zval *valueResult, zval *assignedValueResult, zval *stmt, zval *storage, zval *nodeCallback, zval *context)
{
	if (EXPECTED(Z_OBJCE_P(handler) == pt_ce_assign_handler)) return AssignHandler(Z_OBJ_P(handler)).applyWrite(nodeScopeResolver, target, valueResult, assignedValueResult, stmt, storage, nodeCallback, context);
	zval null = {};
	ZVAL_NULL(&null);
	zv::Args argv{nodeScopeResolver, target, valueResult, assignedValueResult != NULL ? assignedValueResult : &null, stmt, storage, nodeCallback, context};
	return pt_type_call(Z_OBJ_P(handler), PT_LC("applywrite"), 8, argv);
}

zv::Val pt_assign_handler_process_virtual_assign(zval *handler, zval *nodeScopeResolver, zval *scope, zval *storage, zval *stmt, zval *var, zval *assignedExpr, zval *nodeCallback, zval *assignedExprResult)
{
	if (EXPECTED(Z_OBJCE_P(handler) == pt_ce_assign_handler)) return AssignHandler(Z_OBJ_P(handler)).processVirtualAssign(nodeScopeResolver, scope, storage, stmt, var, assignedExpr, nodeCallback, assignedExprResult);
	zval null = {};
	ZVAL_NULL(&null);
	zv::Args argv{nodeScopeResolver, scope, storage, stmt, var, assignedExpr, nodeCallback, assignedExprResult != NULL ? assignedExprResult : &null};
	return pt_type_call(Z_OBJ_P(handler), PT_LC("processvirtualassign"), 8, argv);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_assign_handler()
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\AssignHandler");
	ptdecl::AssignHandler::declareClass(cls);
	ptdecl::AssignHandler::declareProperties(cls);
	cls.privateClassConstantLong("TERNARY_ARM_EXCLUDED_VALUES_LIMIT", PT_AH_TERNARY_ARM_EXCLUDED_VALUES_LIMIT);
	cls.privateClassConstantLong("DERIVED_CONDITIONAL_EXPRESSIONS_LIMIT", PT_AH_DERIVED_CONDITIONAL_EXPRESSIONS_LIMIT);
	cls.privateClassConstantLong("ARRAY_DIM_FETCH_WRITE_DEPTH_LIMIT", PT_AH_ARRAY_DIM_FETCH_WRITE_DEPTH_LIMIT);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *argv;
		uint32_t argc;
		ZEND_PARSE_PARAMETERS_START(20, 20)
			Z_PARAM_VARIADIC('+', argv, argc)
		ZEND_PARSE_PARAMETERS_END();
		for (uint32_t i = 0; i < argc; i++) {
			if (UNEXPECTED(Z_TYPE(argv[i]) != IS_OBJECT)) {
				zend_argument_type_error(i + 1, "must be of type object, %s given", zend_zval_value_name(&argv[i]));
				RETURN_THROWS();
			}
		}
		AssignHandler(Z_OBJ_P(ZEND_THIS)).construct(argv);
	});

	cls.method<&AssignHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(AssignHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.method(sigs::processVirtualAssign, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *scope, *storage, *stmt, *var, *assignedExpr, *nodeCallback, *assignedExprResult = NULL;
		ZEND_PARSE_PARAMETERS_START(7, 8)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(var)
			Z_PARAM_OBJECT(assignedExpr)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OPTIONAL
			Z_PARAM_OBJECT_OR_NULL(assignedExprResult)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(AssignHandler(Z_OBJ_P(ZEND_THIS)).processVirtualAssign(nodeScopeResolver, scope, storage, stmt, var, assignedExpr, nodeCallback, assignedExprResult));
	});

	cls.method(sigs::prepareTarget, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *scope, *storage, *stmt, *var, *assignedExpr, *nodeCallback, *context, *mode;
		ZEND_PARSE_PARAMETERS_START(9, 9)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(var)
			Z_PARAM_OBJECT(assignedExpr)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(context)
			Z_PARAM_OBJECT(mode)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(AssignHandler(Z_OBJ_P(ZEND_THIS)).prepareTarget(nodeScopeResolver, scope, storage, stmt, var, assignedExpr, nodeCallback, context, mode));
	});

	cls.method(sigs::applyWrite, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *target, *valueResult, *assignedValueResult, *stmt, *storage, *nodeCallback, *context;
		ZEND_PARSE_PARAMETERS_START(8, 8)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(target)
			Z_PARAM_OBJECT(valueResult)
			Z_PARAM_OBJECT_OR_NULL(assignedValueResult)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(AssignHandler(Z_OBJ_P(ZEND_THIS)).applyWrite(nodeScopeResolver, target, valueResult, assignedValueResult, stmt, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_assign_handler);
	pt_expr_handler_entry_register(&pt_ce_assign_handler, &AssignHandler::processExprEntry);
}

/* }}} */
