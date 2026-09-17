/*
 * PHPStanTurbo\AssignOpHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\AssignOpHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry; the target walk and the write go to the native AssignHandler
 * through pt_assign_handler_prepare_target() / _apply_write(). The twin's
 * closures (typeCallback with its per-call $getType, specifyTypesCallback,
 * the ??= createTypesCallback) are native closures capturing exactly what the
 * PHP closures capture.
 *
 * The collaborators that stay PHP for now (NodeScopeResolver,
 * InitializerExprTypeResolver, DefaultNarrowingHelper, PreparedAssignTarget,
 * AssignTargetWalkMode, InternalThrowPoint) are called through the helpers in
 * the block below, one per called method.
 */

#include "support.h"
#include "generated/AssignOpHandler.h"

namespace slots = ptdecl::AssignOpHandler::slot;
namespace sigs = ptdecl::AssignOpHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"

#include <utility>

zend_class_entry *pt_ce_assign_op_handler = nullptr;

/* VariableWrite::KIND_READ_MODIFY_WRITE */
#define PT_AOH_KIND_READ_MODIFY_WRITE 2

#define AOH_VAL(name, expr) \
	zv::Val name = (expr); \
	if (UNEXPECTED(name.isUndef())) return zv::Val()

#define AOH_SET(name, expr) \
	do { \
		zv::Val aoh_tmp_ = (expr); \
		if (UNEXPECTED(aoh_tmp_.isUndef())) return zv::Val(); \
		(name) = std::move(aoh_tmp_); \
	} while (0)

#define AOH_OK(expr) \
	do { \
		if (UNEXPECTED(!(expr))) return zv::Val(); \
	} while (0)

namespace {

/* {{{ node and value access */

inline bool aohIs(zval *value, int classIdx)
{
	if (Z_TYPE_P(value) != IS_OBJECT) return false;
	/* pt_class() caches the entry (autoloading it once) where
	 * pt_class_loaded() would repeat the name lookup while the class is not
	 * declared yet; loading an AST or virtual node class has no observable
	 * effect */
	zend_class_entry *ce = pt_class(classIdx);
	return ce != NULL && instanceof_function(Z_OBJCE_P(value), ce);
}

zval pt_aoh_undef_zval;

zval *aohPropResolve(pt_property_site &site, zval *object, const char *name, size_t len)
{
	if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) {
		ZVAL_UNDEF(&pt_aoh_undef_zval);
		return &pt_aoh_undef_zval;
	}
	zval *slot = pt_property_cached(site, Z_OBJ_P(object), name, len);
	if (UNEXPECTED(slot == NULL)) {
		ZVAL_UNDEF(&pt_aoh_undef_zval);
		return &pt_aoh_undef_zval;
	}
	ZVAL_DEREF(slot);
	return slot;
}

/* $object->name through a property site of its own (one per use) */
#define AOH_PROP(object, name) ([](zval *aoh_object_) -> zval * { static pt_property_site aoh_site_; return aohPropResolve(aoh_site_, aoh_object_, PT_LC(#name)); }(object))

/* the PT_TRI_* value of a TrinaryLogic / IsSuperTypeOfResult; -1 = pending exception */
zend_long aohTri(zval *value)
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

/* $nativeTypesPromoted ? $scope->doNotTreatPhpDocTypesAsCertain() : $scope */
zv::Val aohFlavourScope(zval *scope, bool nativeTypesPromoted)
{
	if (nativeTypesPromoted) return pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scope));
	return zv::Val::copyOf(zv::Ref(scope));
}

/* $result->getTypeOnScope($scope, $scope->nativeTypesPromoted) */
zv::Val aohTypeOnScope(zval *result, zval *scope)
{
	bool promoted;
	AOH_OK(pt_mutating_scope_native_types_promoted(Z_OBJ_P(scope), promoted));
	return pt_expression_result_get_type_on_scope(result, scope, promoted);
}

/* $into = array_merge($into, $more) */
[[nodiscard]] bool aohMerge(zv::Val &into, zval *more)
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
inline void aohPush(zv::Val &list, zv::Val value)
{
	zval *raw = list.raw();
	SEPARATE_ARRAY(raw);
	zval v = value.take();
	zend_hash_next_index_insert(Z_ARRVAL_P(raw), &v);
}

/* $result->getScope() / ->getThrowPoints() / ->getImpurePoints() as owned
 * values (the borrowed AnalyserValues.h readers, copied) */
inline zv::Val aohResultRead(zval *value, zv::Val &hold)
{
	if (UNEXPECTED(value == NULL)) return zv::Val();
	if (!hold.isUndef()) return std::move(hold);
	return zv::Val::copyOf(zv::Ref(value));
}

inline zv::Val aohResultScope(zval *result)
{
	zv::Val hold;
	zval *value = pt_expression_result_scope(result, hold);
	return aohResultRead(value, hold);
}

inline zv::Val aohResultThrowPoints(zval *result)
{
	zv::Val hold;
	zval *value = pt_expression_result_throw_points(result, hold);
	return aohResultRead(value, hold);
}

inline zv::Val aohResultImpurePoints(zval *result)
{
	zv::Val hold;
	zval *value = pt_expression_result_impure_points(result, hold);
	return aohResultRead(value, hold);
}

/* $write->getId() of a VariableWrite */
zv::Val aohVariableWriteId(zval *write)
{
	bool error = false;
	const pt_variable_write_slots *writeSlots = pt_variable_write_slots_of(Z_OBJ_P(write), error);
	if (writeSlots != NULL) return zv::Val::copyOf(zv::ObjRef(write).propAtOffset(writeSlots->id));
	if (UNEXPECTED(error)) return zv::Val();
	return pt_type_call(Z_OBJ_P(write), PT_LC("getid"), 0, NULL);
}

/* }}} */

/* {{{ the collaborators — the native ones through their direct entries, the
 * PHP ones through one cached method site each (switch them to direct entries
 * once they are ported) */

/* $nodeScopeResolver->processExprNode($stmt, $expr, $scope, $storage, $nodeCallback, $context) */
zv::Val nsrProcessExprNode(zval *nsr, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
{
	return pt_node_scope_resolver_process_expr_node(nsr, stmt, expr, scope, storage, nodeCallback, context);
}

/* $nodeScopeResolver->storeExpressionResult($storage, $expr, $result); false = pending exception */
[[nodiscard]] bool nsrStoreExpressionResult(zval *nsr, zval *storage, zval *expr, zval *result)
{
	return pt_node_scope_resolver_store_expression_result(nsr, storage, expr, result);
}

/* $nodeScopeResolver->callNodeCallbackWithExpression($nodeCallback, $node, $scope, $storage, $context); false = pending exception */
[[nodiscard]] bool nsrCallNodeCallbackWithExpression(zval *nsr, zval *nodeCallback, zval *node, zval *scope, zval *storage, zval *context)
{
	return pt_node_scope_resolver_call_node_callback_with_expression(nsr, nodeCallback, node, scope, storage, context);
}

/* $nodeScopeResolver->processSyntheticOnDemand($expr, $scope) */
zv::Val nsrProcessSyntheticOnDemand(zval *nsr, zval *expr, zval *scope)
{
	return pt_node_scope_resolver_process_synthetic_on_demand(nsr, expr, scope);
}

/* $coalesceCompositionHelper->getRightSideScopeSpecifiedTypes($s, $leftExpr, $leftResult, $chainResults, $rootExpr) */
zv::Val cchGetRightSideScopeSpecifiedTypes(zval *helper, zval *scope, zval *leftExpr, zval *leftResult, zval *chainResults, zval *rootExpr)
{
	return pt_coalesce_composition_helper_get_right_side_scope_specified_types(helper, scope, leftExpr, leftResult, chainResults, rootExpr);
}

/* $coalesceCompositionHelper->composeType($nodeScopeResolver, $leftExpr, $leftResult, $rightResult, $evaluationScope, $chainResults, $rootExpr, $nativeTypesPromoted) */
zv::Val cchComposeType(zval *helper, zval *nsr, zval *leftExpr, zval *leftResult, zval *rightResult, zval *evaluationScope, zval *chainResults, zval *rootExpr, bool nativeTypesPromoted)
{
	return pt_coalesce_composition_helper_compose_type(helper, nsr, leftExpr, leftResult, rightResult, evaluationScope, chainResults, rootExpr, nativeTypesPromoted);
}

/* $implicitToStringCallHelper->processImplicitToStringCall($expr, $scope, $exprResult) */
zv::Val itschProcessImplicitToStringCall(zval *helper, zval *expr, zval *scope, zval *exprResult)
{
	return pt_implicit_to_string_call_helper_process_implicit_to_string_call(helper, expr, scope, exprResult);
}

/* $defaultNarrowingHelper->specifyDefaultTypes($expr, $context) */
zv::Val dnhSpecifyDefaultTypes(zval *helper, zval *expr, zval *context)
{
	return pt_default_narrowing_helper_specify_default_types(helper, expr, context);
}

/* $defaultNarrowingHelper->createSubjectTypes($s, $subject, $subjectResult, $type, $context) */
zv::Val dnhCreateSubjectTypes(zval *helper, zval *scope, zval *subject, zval *subjectResult, zval *type, zval *context)
{
	return pt_default_narrowing_helper_create_subject_types(helper, scope, subject, subjectResult, type, context);
}

/* AssignTargetWalkMode::readModifyWrite() / coalesceReadModifyWrite() */
zv::Val walkModeReadModifyWrite()
{
	return pt_assign_target_walk_mode_new(false, true, false);
}

zv::Val walkModeCoalesceReadModifyWrite()
{
	return pt_assign_target_walk_mode_new(true, true, true);
}

/* InternalThrowPoint::createExplicit($scope, $type, $node, $canContainAnyThrowable) */
zv::Val itpCreateExplicit(zval *scope, zval *type, zval *node, bool canContainAnyThrowable)
{
	return pt_internal_throw_point_create_explicit(scope, type, node, canContainAnyThrowable, false);
}

/* new CoalesceExpressionNode($originalExpr, $subjectResult, $rightResult, $operatorDescription) */
zv::Val newCoalesceExpressionNode(zval *originalExpr, zval *subjectResult, zval *rightResult, const char *description, size_t len)
{
	zv::Val descriptionValue = zv::Val::string(description, len);
	zv::Args argv{originalExpr, subjectResult, rightResult, descriptionValue.raw()};
	return pt_type_new(PT_CLASS_COALESCE_EXPRESSION_NODE, 4, argv);
}

/* a PreparedAssignTarget read (AnalyserValues.h) as an owned value: the
 * slot copied, or the getter's result (the throwing getters' exception) */
inline zv::Val targetRead(zval *(*reader)(zval *, zv::Val &), zval *target)
{
	zv::Val hold;
	zval *value = reader(target, hold);
	if (UNEXPECTED(value == NULL)) return zv::Val();
	if (!hold.isUndef()) return hold;
	return zv::Val::copyOf(zv::Ref(value));
}

/* }}} */

/* the InitializerExprTypeResolver method of each arithmetic AssignOp class,
 * in the twin's order (Coalesce is composed separately) */
struct OperatorMethod
{
	int classIdx;
	pt_ietr_binary_operator op;
};

const OperatorMethod pt_aoh_operator_methods[] = {
	{ PT_CLASS_ASSIGN_OP_CONCAT, PT_IETR_OP_CONCAT },
	{ PT_CLASS_ASSIGN_OP_BITWISE_AND, PT_IETR_OP_BITWISE_AND },
	{ PT_CLASS_ASSIGN_OP_BITWISE_OR, PT_IETR_OP_BITWISE_OR },
	{ PT_CLASS_ASSIGN_OP_BITWISE_XOR, PT_IETR_OP_BITWISE_XOR },
	{ PT_CLASS_ASSIGN_OP_DIV, PT_IETR_OP_DIV },
	{ PT_CLASS_ASSIGN_OP_MOD, PT_IETR_OP_MOD },
	{ PT_CLASS_ASSIGN_OP_PLUS, PT_IETR_OP_PLUS },
	{ PT_CLASS_ASSIGN_OP_MINUS, PT_IETR_OP_MINUS },
	{ PT_CLASS_ASSIGN_OP_MUL, PT_IETR_OP_MUL },
	{ PT_CLASS_ASSIGN_OP_POW, PT_IETR_OP_POW },
	{ PT_CLASS_ASSIGN_OP_SHIFT_LEFT, PT_IETR_OP_SHIFT_LEFT },
	{ PT_CLASS_ASSIGN_OP_SHIFT_RIGHT, PT_IETR_OP_SHIFT_RIGHT },
};

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\AssignOpHandler; UNDEF = pending
 * exception. */
class AssignOpHandler
{
public:
	explicit AssignOpHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *argv)
	{
		zv::ObjRef object(self);
		for (uint32_t i = 0; i < 6; i++) {
			object.propAtWrite(i, zv::Val::copyOf(zv::Ref(&argv[i])));
		}
	}

	/* false = pending exception */
	[[nodiscard]] bool supports(zval *expr, bool &out) const
	{
		zend_class_entry *assignOpCe = pt_class(PT_CLASS_ASSIGN_OP_EXPR);
		if (UNEXPECTED(assignOpCe == NULL)) return false;
		out = instanceof_function(Z_OBJCE_P(expr), assignOpCe);
		return true;
	}

	/* the state processExpr() carries across the walk of the value */
	struct ValueWalk
	{
		zv::Val target;
		zv::Val targetReadResult;
		zv::Val condResult;
		zv::Val chainResults;
		zv::Val valueBeforeScope;
		zv::Val valueScope;
		zv::Val valueContext;
		zv::Val valueFlowWrite;
	};

	/* split in three like AssignHandler::processExpr(): only the walk state
	 * stays on the frame a nested assignment in the value re-enters through */
	zv::Val processExpr(zval *nsr, zval *stmt, zval *expr, zval *scopeArg, zval *storage, zval *nodeCallback, zval *context)
	{
		ValueWalk walk;
		if (UNEXPECTED(!prepareValueWalk(nsr, stmt, expr, scopeArg, storage, nodeCallback, context, walk))) return zv::Val();
		AOH_VAL(valueResult, nsrProcessExprNode(nsr, stmt, AOH_PROP(expr, expr), walk.valueScope.raw(), storage, nodeCallback, walk.valueContext.raw()));
		return finishProcessExpr(nsr, stmt, expr, scopeArg, storage, nodeCallback, context, walk, valueResult.raw());
	}

	/* processExpr() up to the walk of the value; false = pending exception */
	[[nodiscard]] zend_never_inline bool prepareValueWalk(zval *nsr, zval *stmt, zval *expr, zval *scopeArg, zval *storage, zval *nodeCallback, zval *context, ValueWalk &walk)
	{
		zval *exprVar = AOH_PROP(expr, var);
		zval *exprExpr = AOH_PROP(expr, expr);
		bool isCoalesce = aohIs(expr, PT_CLASS_COALESCE_ASSIGN_OP_EXPR);

		{
			zv::Val mode = isCoalesce ? walkModeCoalesceReadModifyWrite() : walkModeReadModifyWrite();
			if (UNEXPECTED(mode.isUndef())) return false;
			walk.target = pt_assign_handler_prepare_target(prop(slots::assignHandler), nsr, scopeArg, storage, stmt, exprVar, expr, nodeCallback, context, mode.raw());
			if (UNEXPECTED(walk.target.isUndef())) return false;
		}
		walk.targetReadResult = targetRead(&pt_prepared_assign_target_target_read_result, walk.target.raw());
		if (UNEXPECTED(walk.targetReadResult.isUndef())) return false;
		walk.condResult = isCoalesce ? zv::Val::copyOf(walk.targetReadResult.ref()) : zv::Val::null();
		walk.chainResults = targetRead(&pt_prepared_assign_target_target_chain_results, walk.target.raw());
		if (UNEXPECTED(walk.chainResults.isUndef())) return false;

		walk.valueBeforeScope = targetRead(&pt_prepared_assign_target_scope, walk.target.raw());
		if (UNEXPECTED(walk.valueBeforeScope.isUndef())) return false;
		walk.valueScope = zv::Val::copyOf(walk.valueBeforeScope.ref());
		walk.valueContext = zv::Val::copyOf(zv::Ref(context));
		if (isCoalesce) {
			// the value expr only evaluates when the left side is null or
			// unset - the falsey isset() narrowing, composed from the left read
			zv::Val rightSideTypes = cchGetRightSideScopeSpecifiedTypes(prop(slots::coalesceCompositionHelper), walk.valueScope.raw(), exprVar, walk.condResult.raw(), walk.chainResults.raw(), expr);
			if (UNEXPECTED(rightSideTypes.isUndef())) return false;
			walk.valueScope = pt_mutating_scope_apply_specified_types(Z_OBJ_P(walk.valueScope.raw()), rightSideTypes.raw());
			if (UNEXPECTED(walk.valueScope.isUndef())) return false;

			if (aohIs(exprVar, PT_CLASS_VARIABLE)) {
				zval *name = AOH_PROP(exprVar, name);
				if (Z_TYPE_P(name) == IS_STRING) {
					walk.valueContext = pt_expression_context_enter_right_side_assign(walk.valueContext.raw(), Z_STR_P(name), exprExpr);
					if (UNEXPECTED(walk.valueContext.isUndef())) return false;
				}
			}
		}

		walk.valueFlowWrite = pt_variable_flow_builder_write_site(exprVar, PT_AOH_KIND_READ_MODIFY_WRITE, walk.valueScope.raw(), storage);
		if (UNEXPECTED(walk.valueFlowWrite.isUndef())) return false;
		walk.valueContext = pt_expression_context_enter_deep(walk.valueContext.raw());
		if (UNEXPECTED(walk.valueContext.isUndef())) return false;
		if (!walk.valueFlowWrite.isNull()) {
			walk.valueContext = pt_expression_context_enter_value_flow(walk.valueContext.raw(), walk.valueFlowWrite.raw(), false);
			if (UNEXPECTED(walk.valueContext.isUndef())) return false;
		}

		return true;
	}

	/* processExpr() after the walk of the value */
	zend_never_inline zv::Val finishProcessExpr(zval *nsr, zval *stmt, zval *expr, zval *scopeArg, zval *storage, zval *nodeCallback, zval *context, ValueWalk &walk, zval *valueResultArg)
	{
		zval *beforeScope = scopeArg;
		zval *exprVar = AOH_PROP(expr, var);
		zval *exprExpr = AOH_PROP(expr, expr);
		bool isCoalesce = aohIs(expr, PT_CLASS_COALESCE_ASSIGN_OP_EXPR);
		zv::Val &target = walk.target;
		zv::Val &targetReadResult = walk.targetReadResult;
		zv::Val &condResult = walk.condResult;
		zv::Val &chainResults = walk.chainResults;
		zv::Val &valueBeforeScope = walk.valueBeforeScope;
		zv::Val &valueFlowWrite = walk.valueFlowWrite;
		zv::Val rightResult = zv::Val::null();
		zv::Val valueResult = zv::Val::copyOf(zv::Ref(valueResultArg));
		zv::Val rhsResult = zv::Val::copyOf(valueResult.ref());
		if (isCoalesce) {
			rightResult = zv::Val::copyOf(valueResult.ref());
			AOH_VAL(rightScope, aohResultScope(rightResult.raw()));
			AOH_VAL(mergedScope, pt_mutating_scope_merge_with(Z_OBJ_P(rightScope.raw()), valueBeforeScope.raw(), false));
			bool hasYield;
			AOH_OK(pt_expression_result_has_yield(rightResult.raw(), hasYield));
			bool isAlwaysTerminating;
			AOH_OK(pt_expression_result_is_always_terminating(rightResult.raw(), isAlwaysTerminating));
			if (isAlwaysTerminating) {
				AOH_VAL(condType, pt_expression_result_get_type(condResult.raw()));
				if (UNEXPECTED(Z_TYPE_P(condType.raw()) != IS_OBJECT)) {
					zend_throw_error(NULL, "Call to a member function isNull() on %s", zend_zval_value_name(condType.raw()));
					return zv::Val();
				}
				AOH_VAL(isNull, pt_type_op(Z_OBJ_P(condType.raw()), PT_OP_IS_NULL, 0, NULL));
				zend_long isNullValue = aohTri(isNull.raw());
				AOH_OK(isNullValue >= 0);
				isAlwaysTerminating = isNullValue == PT_TRI_YES;
			}
			AOH_VAL(throwPoints, aohResultThrowPoints(rightResult.raw()));
			AOH_VAL(impurePoints, aohResultImpurePoints(rightResult.raw()));
			zv::Val typeCallback = pt_native_closure(&mixedTypeCallbackBody);
			AOH_VAL(specifyTypesCallback, pt_specified_types_empty_specify_callback());
			pt_expression_result_args args(mergedScope.raw(), valueBeforeScope.raw(), exprExpr, hasYield, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
			AOH_SET(valueResult, pt_expression_result_create(factory(), args));
		}

		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, self, expr, nsr, beforeScope, condResult.raw(), chainResults.raw(), rightResult.raw(), targetReadResult.raw(), rhsResult.raw());
		zv::Val specifyTypesCallback = pt_native_closure(&specifyTypesCallbackBody, self, expr, condResult.raw(), beforeScope);
		zv::Val createTypesCallback = zv::Val::null();
		if (isCoalesce) {
			// a type constraint on `$x ??= y` constrains the assigned variable -
			// what TypeSpecifier::create() recovered by its AssignOp\Coalesce arm
			createTypesCallback = pt_native_closure(&createTypesCallbackBody, self, expr, condResult.raw(), beforeScope);
		}

		// the result standing for the whole `$lvalue OP= value` expression - the
		// value applyWrite() writes to the target
		pt_expression_result_args valueArgs(beforeScope, beforeScope, expr, false, false, NULL, NULL, typeCallback.raw(), specifyTypesCallback.raw());
		valueArgs.withCreateTypesCallback(createTypesCallback.raw());
		AOH_VAL(assignOpValueResult, pt_expression_result_create(factory(), valueArgs));

		// applyWrite() emits nodes (PropertyAssignNode) whose rules ask about
		// this whole `$lvalue OP= value` expression - store its result first so
		// those asks answer from the storage; processExprNode() overwrites it
		// with the final result after this handler returns
		AOH_OK(nsrStoreExpressionResult(nsr, storage, expr, assignOpValueResult.raw()));
		AOH_VAL(assignResult, pt_assign_handler_apply_write(prop(slots::assignHandler), nsr, target.raw(), valueResult.raw(), assignOpValueResult.raw(), stmt, storage, nodeCallback, context));
		AOH_VAL(scope, aohResultScope(assignResult.raw()));
		AOH_VAL(throwPoints, aohResultThrowPoints(assignResult.raw()));
		AOH_VAL(impurePoints, aohResultImpurePoints(assignResult.raw()));
		if (aohIs(expr, PT_CLASS_ASSIGN_OP_DIV) || aohIs(expr, PT_CLASS_ASSIGN_OP_MOD)) {
			AOH_VAL(rhsType, pt_expression_result_get_type_on_scope(rhsResult.raw(), scope.raw(), false));
			AOH_VAL(number, pt_type_call(Z_OBJ_P(rhsType.raw()), PT_LC("tonumber"), 0, NULL));
			zval zeroType;
			AOH_OK(pt_constant_integer_type_new(&zeroType, 0));
			zv::Val zero = zv::Val::adopt(zeroType);
			AOH_VAL(isSuper, pt_type_op(Z_OBJ_P(number.raw()), PT_OP_IS_SUPER_TYPE_OF, 1, zero.raw()));
			zend_long isSuperValue = aohTri(isSuper.raw());
			AOH_OK(isSuperValue >= 0);
			if (isSuperValue != PT_TRI_NO) {
				zv::Val className = zv::Val::string(PT_LC("DivisionByZeroError"));
				AOH_VAL(errorType, pt_type_new_object_type(className.raw()));
				AOH_VAL(throwPoint, itpCreateExplicit(scope.raw(), errorType.raw(), expr, false));
				aohPush(throwPoints, std::move(throwPoint));
			}
		}
		if (aohIs(expr, PT_CLASS_ASSIGN_OP_CONCAT)) {
			AOH_VAL(toStringResult, itschProcessImplicitToStringCall(prop(slots::implicitToStringCallHelper), exprExpr, scope.raw(), rhsResult.raw()));
			{
				AOH_VAL(points, aohResultThrowPoints(toStringResult.raw()));
				AOH_OK(aohMerge(throwPoints, points.raw()));
			}
			{
				AOH_VAL(points, aohResultImpurePoints(toStringResult.raw()));
				AOH_OK(aohMerge(impurePoints, points.raw()));
			}
		}

		if (isCoalesce) {
			if (UNEXPECTED(condResult.isNull() || rightResult.isNull())) {
				pt_throw_should_not_happen();
				return zv::Val();
			}

			AOH_VAL(coalesceNode, newCoalesceExpressionNode(expr, condResult.raw(), rightResult.raw(), PT_LC("on left side of ?\?=")));
			AOH_OK(nsrCallNodeCallbackWithExpression(nsr, nodeCallback, coalesceNode.raw(), beforeScope, storage, context));
		}

		zv::Val writeFlow;
		{
			AOH_VAL(rhsFlow, pt_expression_result_variable_flow(rhsResult.raw()));
			zv::Val inputsFlow = zv::Val::null();
			if (!valueFlowWrite.isNull()) {
				bool consumed;
				AOH_OK(pt_expression_context_is_value_consumed(context, consumed));
				if (consumed) {
					AOH_VAL(writeId, aohVariableWriteId(valueFlowWrite.raw()));
					AOH_VAL(valueFlowTarget, pt_expression_context_get_value_flow_target(context));
					zv::Val targetId = zv::Val::null();
					if (!valueFlowTarget.isNull()) {
						AOH_SET(targetId, aohVariableWriteId(valueFlowTarget.raw()));
					}
					AOH_SET(inputsFlow, pt_variable_flow_inputs(zval_get_long(writeId.raw()), targetId.raw()));
				}
			}
			zval null = {};
			ZVAL_NULL(&null);
			AOH_VAL(targetWriteFlow, pt_variable_flow_builder_target_write(exprVar, PT_AOH_KIND_READ_MODIFY_WRITE, scope.raw(), storage, &null));
			zv::Args flows{rhsFlow.raw(), inputsFlow.raw(), targetWriteFlow.raw()};
			AOH_SET(writeFlow, pt_variable_flow_sequence(3, flows));
		}
		zv::Val variableFlow;
		{
			zv::Val readTargetId = zv::Val::null();
			if (!isCoalesce && !valueFlowWrite.isNull()) {
				AOH_SET(readTargetId, aohVariableWriteId(valueFlowWrite.raw()));
			}
			AOH_VAL(targetReadFlow, pt_variable_flow_builder_target_read(exprVar, storage, true, readTargetId.raw()));
			zv::Val secondFlow;
			if (isCoalesce) {
				zval null = {};
				ZVAL_NULL(&null);
				zv::Args branches{writeFlow.raw(), &null};
				AOH_SET(secondFlow, pt_variable_flow_choice(2, branches));
			} else {
				secondFlow = std::move(writeFlow);
			}
			zv::Args flows{targetReadFlow.raw(), secondFlow.raw()};
			AOH_SET(variableFlow, pt_variable_flow_sequence(2, flows));
		}

		bool hasYield;
		bool isAlwaysTerminating;
		AOH_OK(pt_expression_result_has_yield(assignResult.raw(), hasYield));
		AOH_OK(pt_expression_result_is_always_terminating(assignResult.raw(), isAlwaysTerminating));
		pt_expression_result_args args(scope.raw(), beforeScope, expr, hasYield, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw()).withCreateTypesCallback(createTypesCallback.raw());
		return pt_expression_result_create(factory(), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return AssignOpHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	zval *prop(uint32_t slot) const { return OBJ_PROP_NUM(self, slot); }
	zval *factory() const { return prop(slots::expressionResultFactory); }

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

	/* function (bool $nativeTypesPromoted) use ($expr, $nodeScopeResolver,
	 * $beforeScope, $condResult, $chainResults, $rightResult,
	 * $targetReadResult, $rhsResult): Type — captures: $this, $expr,
	 * $nodeScopeResolver, $beforeScope, $condResult, $chainResults,
	 * $rightResult, $targetReadResult, $rhsResult */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 1)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\ExprHandler\\AssignOpHandler::{closure}(), %u passed and exactly 1 expected", argc);
			return;
		}
		// a nested op-assignment in an operand re-enters this body through
		// its result — natively, where the twin grew only the VM stack
		zv::Val type;
		pt_engine_with_stack([&]() { type = resolveType(captures, zend_is_true(&argv[0])); });
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	static zv::Val resolveType(zval *captures, bool nativeTypesPromoted)
	{
		zend_object *handler = Z_OBJ(captures[0]);
		zval *expr = &captures[1];
		zval *nsr = &captures[2];
		zval *beforeScope = &captures[3];
		zval *condResult = &captures[4];
		zval *chainResults = &captures[5];
		zval *rightResult = &captures[6];
		zval *targetReadResult = &captures[7];
		zval *rhsResult = &captures[8];

		// the operands' results are in hand: the target read from
		// prepareTarget(), the value expr from the phase between
		// prepareTarget() and applyWrite() - no storage round-trip
		// $getType's captures ($expr, $nodeScopeResolver, $beforeScope,
		// $targetReadResult, $rhsResult, $nativeTypesPromoted), borrowed:
		// InitializerExprTypeResolver calls it synchronously
		zval getTypeCaptures[6];
		ZVAL_COPY_VALUE(&getTypeCaptures[0], expr);
		ZVAL_COPY_VALUE(&getTypeCaptures[1], nsr);
		ZVAL_COPY_VALUE(&getTypeCaptures[2], beforeScope);
		ZVAL_COPY_VALUE(&getTypeCaptures[3], targetReadResult);
		ZVAL_COPY_VALUE(&getTypeCaptures[4], rhsResult);
		ZVAL_BOOL(&getTypeCaptures[5], nativeTypesPromoted);
		pt_ietr_get_type getType{&getTypeCallback, getTypeCaptures, &getTypeCallable};

		if (aohIs(expr, PT_CLASS_COALESCE_ASSIGN_OP_EXPR)) {
			return cchComposeType(OBJ_PROP_NUM(handler, slots::coalesceCompositionHelper), nsr, AOH_PROP(expr, var), condResult, rightResult, beforeScope, chainResults, expr, nativeTypesPromoted);
		}

		for (size_t i = 0; i < sizeof(pt_aoh_operator_methods) / sizeof(pt_aoh_operator_methods[0]); i++) {
			const OperatorMethod &method = pt_aoh_operator_methods[i];
			if (!aohIs(expr, method.classIdx)) continue;
			zval *resolver = OBJ_PROP_NUM(handler, slots::initializerExprTypeResolver);
			return pt_initializer_expr_type_resolver_get_binary_op_type(resolver, method.op, AOH_PROP(expr, var), AOH_PROP(expr, expr), getType);
		}

		zend_class_entry *shouldNotHappen = pt_class(PT_CLASS_SHOULD_NOT_HAPPEN);
		if (shouldNotHappen != NULL) {
			zend_throw_exception_ex(shouldNotHappen, 0, "Unhandled %s", ZSTR_VAL(Z_OBJCE_P(expr)->name));
		}
		return zv::Val();
	}

	/* static function (Expr $e) use ($expr, $nodeScopeResolver, $beforeScope,
	 * $targetReadResult, $rhsResult, $nativeTypesPromoted): Type — data: the
	 * captures, in that order */
	static zv::Val getTypeCallback(void *data, zval *e)
	{
		zv::Val type;
		pt_engine_with_stack([&]() { type = operandType(static_cast<zval *>(data), e); });
		return type;
	}

	/* the $getType as a PHP callable that outlives the call: a native
	 * closure over copies of the captures */
	static zv::Val getTypeCallable(void *data)
	{
		return pt_native_closure_new(&getTypeCallbackBody, 6, static_cast<zval *>(data));
	}

	/* the same closure called from PHP — captures: $expr, $nodeScopeResolver,
	 * $beforeScope, $targetReadResult, $rhsResult, $nativeTypesPromoted */
	static void getTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 1)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\ExprHandler\\AssignOpHandler::{closure}(), %u passed and exactly 1 expected", argc);
			return;
		}
		zval *e = &argv[0];
		ZVAL_DEREF(e);
		zv::Val type = getTypeCallback(captures, e);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	static zv::Val operandType(zval *captures, zval *e)
	{
		zval *expr = &captures[0];
		zval *nsr = &captures[1];
		AOH_VAL(s, aohFlavourScope(&captures[2], Z_TYPE(captures[5]) == IS_TRUE));
		if (Z_TYPE_P(e) == IS_OBJECT) {
			zval *var = AOH_PROP(expr, var);
			if (Z_TYPE_P(var) == IS_OBJECT && Z_OBJ_P(var) == Z_OBJ_P(e)) return aohTypeOnScope(&captures[3], s.raw());
			zval *value = AOH_PROP(expr, expr);
			if (Z_TYPE_P(value) == IS_OBJECT && Z_OBJ_P(value) == Z_OBJ_P(e)) return aohTypeOnScope(&captures[4], s.raw());
		}

		// InitializerExprTypeResolver also asks about synthetic composed
		// nodes (e.g. Mod($left, $right) for modulo bounds) - price those
		AOH_VAL(result, nsrProcessSyntheticOnDemand(nsr, e, s.raw()));
		return aohTypeOnScope(result.raw(), s.raw());
	}

	/* function (TypeSpecifierContext $context, bool $nativeTypesPromoted) use
	 * ($expr, $condResult, $beforeScope): SpecifiedTypes — captures: $this,
	 * $expr, $condResult, $beforeScope */
	static void specifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 2)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\ExprHandler\\AssignOpHandler::{closure}(), %u passed and exactly 2 expected", argc);
			return;
		}
		zv::Val types;
		pt_engine_with_stack([&]() { types = specifyTypes(captures, &argv[0], zend_is_true(&argv[1])); });
		if (UNEXPECTED(types.isUndef())) return;
		types.intoReturnValue(return_value);
	}

	static zv::Val specifyTypes(zval *captures, zval *context, bool nativeTypesPromoted)
	{
		zval *helper = OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::defaultNarrowingHelper);
		zval *expr = &captures[1];
		AOH_VAL(types, dnhSpecifyDefaultTypes(helper, expr, context));
		if (!aohIs(expr, PT_CLASS_COALESCE_ASSIGN_OP_EXPR)) return types;
		bool contextNull;
		AOH_OK(pt_type_specifier_context_null(Z_OBJ_P(context), contextNull));
		if (contextNull) return types;

		// a truthiness constraint on `$x ??= y` also constrains the assigned
		// target - the specify-side mirror of the createTypesCallback below
		// (the raw term on the assign node itself cannot be unpacked at the
		// application point)
		zv::Val removedType;
		bool truthy;
		AOH_OK(pt_type_specifier_context_truthy(Z_OBJ_P(context), truthy));
		if (!truthy) {
			AOH_SET(removedType, pt_static_type_factory_truthy());
		} else {
			bool falsey;
			AOH_OK(pt_type_specifier_context_falsey(Z_OBJ_P(context), falsey));
			if (!falsey) {
				AOH_SET(removedType, pt_static_type_factory_falsey());
			} else {
				return types;
			}
		}
		AOH_VAL(cs, aohFlavourScope(&captures[3], nativeTypesPromoted));

		zend_object *falseContext = pt_type_specifier_context_create_false();
		AOH_OK(falseContext != NULL);
		zval falseContextZv;
		ZVAL_OBJ(&falseContextZv, falseContext);
		AOH_VAL(subjectTypes, dnhCreateSubjectTypes(helper, cs.raw(), AOH_PROP(expr, var), &captures[2], removedType.raw(), &falseContextZv));
		AOH_VAL(rootedTypes, pt_specified_types_set_root_expr(Z_OBJ_P(subjectTypes.raw()), expr));
		return pt_specified_types_union_with(Z_OBJ_P(types.raw()), rootedTypes.raw());
	}

	/* function (Type $constraintType, TypeSpecifierContext $cctx, bool
	 * $nativeTypesPromoted) use ($expr, $condResult, $beforeScope):
	 * SpecifiedTypes — captures: $this, $expr, $condResult, $beforeScope */
	static void createTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 3)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\ExprHandler\\AssignOpHandler::{closure}(), %u passed and exactly 3 expected", argc);
			return;
		}
		zv::Val types;
		pt_engine_with_stack([&]() {
			zv::Val cs = aohFlavourScope(&captures[3], zend_is_true(&argv[2]));
			if (UNEXPECTED(cs.isUndef())) return;
			types = dnhCreateSubjectTypes(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::defaultNarrowingHelper), cs.raw(), AOH_PROP(&captures[1], var), &captures[2], &argv[0], &argv[1]);
		});
		if (UNEXPECTED(types.isUndef())) return;
		types.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::AssignOpHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_assign_op_handler()
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\AssignOpHandler");
	ptdecl::AssignOpHandler::declareClass(cls);
	ptdecl::AssignOpHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *argv;
		uint32_t argc;
		ZEND_PARSE_PARAMETERS_START(6, 6)
			Z_PARAM_VARIADIC('+', argv, argc)
		ZEND_PARSE_PARAMETERS_END();
		for (uint32_t i = 0; i < argc; i++) {
			if (UNEXPECTED(Z_TYPE(argv[i]) != IS_OBJECT)) {
				zend_argument_type_error(i + 1, "must be of type object, %s given", zend_zval_value_name(&argv[i]));
				RETURN_THROWS();
			}
		}
		AssignOpHandler(Z_OBJ_P(ZEND_THIS)).construct(argv);
	});

	cls.method<&AssignOpHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(AssignOpHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_assign_op_handler);
	pt_expr_handler_entry_register(&pt_ce_assign_op_handler, &AssignOpHandler::processExprEntry);
}

/* }}} */
