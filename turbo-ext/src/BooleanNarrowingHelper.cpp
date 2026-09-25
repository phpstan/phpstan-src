/*
 * PHPStanTurbo\BooleanNarrowingHelper — native implementation of
 * PHPStan\Analyser\ExprHandler\Helper\BooleanNarrowingHelper.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's exact
 * arginfo, the state lives in the twin's property slots (generated
 * declarations). The conjunction / disjunction compositions run natively
 * over the SpecifiedTypes and TypeSpecifierContext entries, build the holder
 * recipes and augments through ConditionalExpressionHolderHelper.cpp and
 * DisjunctionHolderProjectionAugment.cpp directly, and invoke the callers'
 * per-operand callables through pt_type_call_callable() (a native callback
 * holder is entered without a frame). The operand verdict Types' toBoolean()
 * / isFalse() / isTrue() are not hot ops and go through the engine.
 *
 * Native callers use pt_boolean_narrowing_helper_specify_conjunction() /
 * _specify_disjunction().
 */

#include "support.h"
#include "generated/BooleanNarrowingHelper.h"

namespace slots = ptdecl::BooleanNarrowingHelper::slot;
namespace sigs = ptdecl::BooleanNarrowingHelper::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "ParserVisitors.h"

zend_class_entry *pt_ce_boolean_narrowing_helper = NULL;

namespace {

using phpstanturbo::visitors::NodeProp;

NodeProp pt_bnh_variable_name = PT_NODE_PROP(PT_CLASS_VARIABLE, "name");

/* $callback($scope, $context)->setRootExpr($rootExpr); UNDEF = pending
 * exception */
zv::Val typesOf(zval *callback, zval *scope, zend_object *context, zval *rootExpr)
{
	zv::Args args{scope, context};
	zv::Val types = pt_type_call_callable(callback, 2, args);
	if (UNEXPECTED(types.isUndef())) return zv::Val();
	if (rootExpr == NULL) return types;
	if (UNEXPECTED(Z_TYPE_P(types.raw()) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function setRootExpr() on %s", zend_zval_value_name(types.raw()));
		return zv::Val();
	}
	return pt_specified_types_set_root_expr(Z_OBJ_P(types.raw()), rootExpr);
}

/* a SpecifiedTypes value that must be an object; its zend_object, NULL with
 * the Error of the member call `method` pending */
zend_object *specifiedTypesObject(zval *value, const char *method)
{
	if (EXPECTED(Z_TYPE_P(value) == IS_OBJECT)) return Z_OBJ_P(value);
	zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(value));
	return NULL;
}

/* $types->getSureTypes() === [] && $types->getSureNotTypes() === []; -1 =
 * pending exception, else 0 / 1 */
int isEmptyNarrowing(zval *types)
{
	zend_object *object = specifiedTypesObject(types, "getSureTypes");
	if (UNEXPECTED(object == NULL)) return -1;
	zv::Val sure = pt_specified_types_get_sure_types(object);
	if (UNEXPECTED(sure.isUndef())) return -1;
	if (Z_TYPE_P(sure.raw()) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL_P(sure.raw())) > 0) return 0;
	zv::Val sureNot = pt_specified_types_get_sure_not_types(object);
	if (UNEXPECTED(sureNot.isUndef())) return -1;
	return Z_TYPE_P(sureNot.raw()) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(sureNot.raw())) == 0 ? 1 : 0;
}

/* a context query; -1 = pending exception, else 0 / 1 */
int contextIs(bool (*query)(zend_object *, bool &), zval *context)
{
	bool out;
	if (UNEXPECTED(!query(Z_OBJ_P(context), out))) return -1;
	return out ? 1 : 0;
}

/* $typeCallback($nativeTypesPromoted)->toBoolean()-><verdict>()->yes(); -1 =
 * pending exception, else 0 / 1 */
int verdictOf(zval *typeCallback, zval *scope, const char *verdictLcname, size_t verdictLen, const char *verdictName)
{
	bool nativeTypesPromoted;
	if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(scope), nativeTypesPromoted))) return -1;
	zval promoted;
	ZVAL_BOOL(&promoted, nativeTypesPromoted);
	zv::Val type = pt_type_call_callable(typeCallback, 1, &promoted);
	if (UNEXPECTED(type.isUndef())) return -1;
	if (UNEXPECTED(Z_TYPE_P(type.raw()) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function toBoolean() on %s", zend_zval_value_name(type.raw()));
		return -1;
	}
	zv::Val boolean = pt_type_call(Z_OBJ_P(type.raw()), PT_LC("toboolean"), 0, NULL);
	if (UNEXPECTED(boolean.isUndef())) return -1;
	if (UNEXPECTED(Z_TYPE_P(boolean.raw()) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", verdictName, zend_zval_value_name(boolean.raw()));
		return -1;
	}
	zend_long verdict = pt_type_call_trinary(Z_OBJ_P(boolean.raw()), verdictLcname, verdictLen, 0, NULL);
	if (UNEXPECTED(verdict < 0)) return -1;
	return verdict == PT_TRI_YES ? 1 : 0;
}

/* array_values(array_filter($recipes)) over the four built recipes */
zv::Val filteredRecipes(zv::Val *recipes, size_t count)
{
	zv::Arr list = zv::Arr::empty();
	for (size_t i = 0; i < count; i++) {
		if (!zend_is_true(recipes[i].raw())) continue;
		list.push(std::move(recipes[i]));
	}
	return zv::Val(std::move(list));
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Helper\BooleanNarrowingHelper. */
class BooleanNarrowingHelper
{
public:
	explicit BooleanNarrowingHelper(zend_object *self) : self(self) {}

	/* Mirrors __construct(): the promoted properties */
	static void construct(zend_object *object, zval *conditionalExpressionHolderHelper, zval *defaultNarrowingHelper)
	{
		writeSlot(object, slots::conditionalExpressionHolderHelper, zv::Val::copyOf(zv::Ref(conditionalExpressionHolderHelper)));
		writeSlot(object, slots::defaultNarrowingHelper, zv::Val::copyOf(zv::Ref(defaultNarrowingHelper)));
	}

	/* Mirrors specifyConjunction(); UNDEF = pending exception */
	zv::Val specifyConjunction(zval *nodeScopeResolver, zval *s, zval *context, zval *rootExpr, zval *leftExpr, zval *leftTypesCallback, zval *leftTruthyScope, zval *leftFalseyScope, zval *rightExpr, zval *rightTypesCallback, zval *rightFalseyScope) const
	{
		zv::Val leftTypes = typesOf(leftTypesCallback, s, Z_OBJ_P(context), rootExpr);
		if (UNEXPECTED(leftTypes.isUndef())) return zv::Val();
		// the right operand lives after the left is known true - its narrowing
		// bases read from the left-truthy view, never the raw ask scope
		zv::Val rightScope = pt_type_call_callable(leftTruthyScope, 0, NULL);
		if (UNEXPECTED(rightScope.isUndef())) return zv::Val();
		zv::Val rightTypes = typesOf(rightTypesCallback, rightScope.raw(), Z_OBJ_P(context), rootExpr);
		if (UNEXPECTED(rightTypes.isUndef())) return zv::Val();

		zend_object *leftTypesObject = specifiedTypesObject(leftTypes.raw(), "unionWith");
		if (UNEXPECTED(leftTypesObject == NULL)) return zv::Val();
		int isTrue = contextIs(pt_type_specifier_context_true, context);
		if (UNEXPECTED(isTrue < 0)) return zv::Val();
		zv::Val types;
		if (isTrue) {
			types = pt_specified_types_union_with(leftTypesObject, rightTypes.raw());
			if (UNEXPECTED(types.isUndef())) return zv::Val();
		} else {
			types = pt_specified_types_intersect_with(leftTypesObject, rightTypes.raw());
			if (UNEXPECTED(types.isUndef())) return zv::Val();
			zv::Val branchUnionAugment = buildBranchUnionAugment(nodeScopeResolver, leftTypes.raw(), rightTypes.raw(), leftFalseyScope, rightFalseyScope, types.raw());
			if (UNEXPECTED(branchUnionAugment.isUndef())) return zv::Val();
			if (Z_TYPE_P(branchUnionAugment.raw()) != IS_NULL) {
				zend_object *typesObject = specifiedTypesObject(types.raw(), "withDeferredAugment");
				if (UNEXPECTED(typesObject == NULL)) return zv::Val();
				zv::Val augmented = pt_specified_types_with_deferred_augment(typesObject, branchUnionAugment.raw());
				if (UNEXPECTED(augmented.isUndef())) return zv::Val();
				types = std::move(augmented);
			}
		}

		int isFalse = contextIs(pt_type_specifier_context_false, context);
		if (UNEXPECTED(isFalse < 0)) return zv::Val();
		if (isFalse) {
			// Consequent (holder) narrowings projected by each holder: these must be
			// the genuine falsey narrowing of the arm. When that is empty, the arm
			// has no sound falsey narrowing and must not contribute a consequent.
			zv::Val leftHolderTypes = zv::Val::copyOf(leftTypes.ref());
			zv::Val rightHolderTypes = zv::Val::copyOf(rightTypes.ref());
			// In a mixed truthy-and-false context, re-derive empty holders from the falsey narrowing.
			int isTruthy = contextIs(pt_type_specifier_context_truthy, context);
			if (UNEXPECTED(isTruthy < 0)) return zv::Val();
			if (isTruthy) {
				int leftEmpty = isEmptyNarrowing(leftHolderTypes.raw());
				if (UNEXPECTED(leftEmpty < 0)) return zv::Val();
				if (leftEmpty) {
					zend_object *falsey = pt_type_specifier_context_create_falsey();
					if (UNEXPECTED(falsey == NULL)) return zv::Val();
					leftHolderTypes = typesOf(leftTypesCallback, s, falsey, rootExpr);
					if (UNEXPECTED(leftHolderTypes.isUndef())) return zv::Val();
				}
				int rightEmpty = isEmptyNarrowing(rightHolderTypes.raw());
				if (UNEXPECTED(rightEmpty < 0)) return zv::Val();
				if (rightEmpty) {
					zend_object *falsey = pt_type_specifier_context_create_falsey();
					if (UNEXPECTED(falsey == NULL)) return zv::Val();
					rightHolderTypes = typesOf(rightTypesCallback, rightScope.raw(), falsey, rootExpr);
					if (UNEXPECTED(rightHolderTypes.isUndef())) return zv::Val();
				}
			}
			// Condition (antecedent) narrowings: when an arm has no falsey narrowing
			// (e.g. isset() on an array dim fetch), derive the condition from the truthy
			// narrowing by swapping sure/sureNot types (see the twin).
			zv::Val leftCondTypes = zv::Val::copyOf(leftHolderTypes.ref());
			zv::Val rightCondTypes = zv::Val::copyOf(rightHolderTypes.ref());
			if (UNEXPECTED(!swappedTruthyCondition(leftCondTypes, leftExpr, leftTypesCallback, s))) return zv::Val();
			if (UNEXPECTED(!swappedTruthyCondition(rightCondTypes, rightExpr, rightTypesCallback, rightScope.raw()))) return zv::Val();

			zend_object *typesObject = specifiedTypesObject(types.raw(), "withoutConditionalExpressionHolders");
			if (UNEXPECTED(typesObject == NULL)) return zv::Val();
			zv::Val result = pt_specified_types_without_conditional_expression_holders(typesObject);
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			zv::Val recipes[4];
			recipes[0] = buildConditionalHolderRecipe(s, leftCondTypes.raw(), rightHolderTypes.raw(), false, true, rightScope.raw(), rightExpr);
			if (UNEXPECTED(recipes[0].isUndef())) return zv::Val();
			recipes[1] = buildConditionalHolderRecipe(s, rightCondTypes.raw(), leftHolderTypes.raw(), false, true, NULL, leftExpr);
			if (UNEXPECTED(recipes[1].isUndef())) return zv::Val();
			recipes[2] = buildConditionalHolderRecipe(s, leftCondTypes.raw(), rightHolderTypes.raw(), true, true, rightScope.raw(), rightExpr);
			if (UNEXPECTED(recipes[2].isUndef())) return zv::Val();
			recipes[3] = buildConditionalHolderRecipe(s, rightCondTypes.raw(), leftHolderTypes.raw(), true, true, NULL, leftExpr);
			if (UNEXPECTED(recipes[3].isUndef())) return zv::Val();
			return withRecipes(std::move(result), recipes, rootExpr);
		}

		return types;
	}

	/* Mirrors specifyDisjunction(); UNDEF = pending exception */
	zv::Val specifyDisjunction(zval *nodeScopeResolver, zval *s, zval *context, zval *rootExpr, zval *leftExpr, zval *leftTypesCallback, zval *leftTypeCallback, zval *leftTruthyScope, zval *leftFalseyScope, zval *rightExpr, zval *rightTypesCallback, zval *rightTypeCallback, zval *rightTruthyScope) const
	{
		zv::Val leftTypes = typesOf(leftTypesCallback, s, Z_OBJ_P(context), rootExpr);
		if (UNEXPECTED(leftTypes.isUndef())) return zv::Val();
		zv::Val rightScope = pt_type_call_callable(leftFalseyScope, 0, NULL);
		if (UNEXPECTED(rightScope.isUndef())) return zv::Val();
		zv::Val rightTypes = typesOf(rightTypesCallback, rightScope.raw(), Z_OBJ_P(context), rootExpr);
		if (UNEXPECTED(rightTypes.isUndef())) return zv::Val();

		int isTrue = contextIs(pt_type_specifier_context_true, context);
		if (UNEXPECTED(isTrue < 0)) return zv::Val();
		zv::Val types;
		if (isTrue) {
			int leftFalse = verdictOf(leftTypeCallback, s, PT_LC("isfalse"), "isFalse");
			if (UNEXPECTED(leftFalse < 0)) return zv::Val();
			int useLeft = 0;
			if (!leftFalse) {
				int leftTrue = verdictOf(leftTypeCallback, s, PT_LC("istrue"), "isTrue");
				if (UNEXPECTED(leftTrue < 0)) return zv::Val();
				if (leftTrue) {
					useLeft = 1;
				} else {
					int rightFalse = verdictOf(rightTypeCallback, s, PT_LC("isfalse"), "isFalse");
					if (UNEXPECTED(rightFalse < 0)) return zv::Val();
					useLeft = rightFalse;
				}
			}
			if (leftFalse) {
				types = zv::Val::copyOf(rightTypes.ref());
			} else if (useLeft) {
				types = zv::Val::copyOf(leftTypes.ref());
			} else {
				zend_object *leftTypesObject = specifiedTypesObject(leftTypes.raw(), "intersectWith");
				if (UNEXPECTED(leftTypesObject == NULL)) return zv::Val();
				types = pt_specified_types_intersect_with(leftTypesObject, rightTypes.raw());
				if (UNEXPECTED(types.isUndef())) return zv::Val();
				zend_object *typesObject = specifiedTypesObject(types.raw(), "getAlternativeTypes");
				if (UNEXPECTED(typesObject == NULL)) return zv::Val();
				zv::Val alternativeTypes = pt_specified_types_get_alternative_types(typesObject);
				if (UNEXPECTED(alternativeTypes.isUndef())) return zv::Val();
				if (UNEXPECTED(Z_TYPE_P(alternativeTypes.raw()) != IS_ARRAY)) {
					zend_type_error("array_keys(): Argument #1 ($array) must be of type array, %s given", zend_zval_value_name(alternativeTypes.raw()));
					return zv::Val();
				}
				zv::Arr alternativeKeys = zv::Arr::empty();
				for (zv::ArrayEntry entry : zv::ArrRef(alternativeTypes.raw())) {
					alternativeKeys.separate();
					zval flag;
					ZVAL_TRUE(&flag);
					pt_ht_update(alternativeKeys.table(), entry.stringKeyOrNull(), entry.indexKey(), &flag);
				}
				zval *defaultNarrowingHelper = slot(slots::defaultNarrowingHelper);
				if (UNEXPECTED(Z_TYPE_P(defaultNarrowingHelper) != IS_OBJECT)) return uninitialized("defaultNarrowingHelper");
				zv::Val projection = pt_disjunction_holder_projection_augment_new(nodeScopeResolver, defaultNarrowingHelper, leftTruthyScope, rightScope.raw(), rightTruthyScope, alternativeKeys.raw());
				if (UNEXPECTED(projection.isUndef())) return zv::Val();
				zv::Val augmented = pt_specified_types_with_deferred_augment(typesObject, projection.raw());
				if (UNEXPECTED(augmented.isUndef())) return zv::Val();
				types = std::move(augmented);
				zv::Val branchUnionAugment = buildBranchUnionAugment(nodeScopeResolver, leftTypes.raw(), rightTypes.raw(), leftTruthyScope, rightTruthyScope, types.raw());
				if (UNEXPECTED(branchUnionAugment.isUndef())) return zv::Val();
				if (Z_TYPE_P(branchUnionAugment.raw()) != IS_NULL) {
					typesObject = specifiedTypesObject(types.raw(), "withDeferredAugment");
					if (UNEXPECTED(typesObject == NULL)) return zv::Val();
					zv::Val withBranchUnion = pt_specified_types_with_deferred_augment(typesObject, branchUnionAugment.raw());
					if (UNEXPECTED(withBranchUnion.isUndef())) return zv::Val();
					types = std::move(withBranchUnion);
				}
			}
		} else {
			zend_object *leftTypesObject = specifiedTypesObject(leftTypes.raw(), "unionWith");
			if (UNEXPECTED(leftTypesObject == NULL)) return zv::Val();
			types = pt_specified_types_union_with(leftTypesObject, rightTypes.raw());
			if (UNEXPECTED(types.isUndef())) return zv::Val();
		}

		isTrue = contextIs(pt_type_specifier_context_true, context);
		if (UNEXPECTED(isTrue < 0)) return zv::Val();
		if (isTrue) {
			zend_object *typesObject = specifiedTypesObject(types.raw(), "withoutConditionalExpressionHolders");
			if (UNEXPECTED(typesObject == NULL)) return zv::Val();
			zv::Val result = pt_specified_types_without_conditional_expression_holders(typesObject);
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			zv::Val recipes[4];
			recipes[0] = buildConditionalHolderRecipe(s, leftTypes.raw(), rightTypes.raw(), false, false, rightScope.raw(), rightExpr);
			if (UNEXPECTED(recipes[0].isUndef())) return zv::Val();
			recipes[1] = buildConditionalHolderRecipe(s, rightTypes.raw(), leftTypes.raw(), false, false, NULL, leftExpr);
			if (UNEXPECTED(recipes[1].isUndef())) return zv::Val();
			recipes[2] = buildConditionalHolderRecipe(s, leftTypes.raw(), rightTypes.raw(), true, false, rightScope.raw(), rightExpr);
			if (UNEXPECTED(recipes[2].isUndef())) return zv::Val();
			recipes[3] = buildConditionalHolderRecipe(s, rightTypes.raw(), leftTypes.raw(), true, false, NULL, leftExpr);
			if (UNEXPECTED(recipes[3].isUndef())) return zv::Val();
			return withRecipes(std::move(result), recipes, rootExpr);
		}

		return types;
	}

private:
	zend_object *self;

	zval *slot(uint32_t index) const { return OBJ_PROP_NUM(self, index); }

	/* the engine's Error for reading a never-written typed property */
	zv::Val uninitialized(const char *property) const
	{
		zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(self->ce->name), property);
		return zv::Val();
	}

	static void writeSlot(zend_object *object, uint32_t index, zv::Val value)
	{
		zv::ObjRef(object).propAtWrite(index, std::move(value));
		Z_PROP_FLAG_P(OBJ_PROP_NUM(object, index)) = 0; /* no longer IS_PROP_UNINIT */
	}

	zval *conditionalExpressionHolderHelper() const
	{
		zval *helper = slot(slots::conditionalExpressionHolderHelper);
		if (UNEXPECTED(Z_TYPE_P(helper) != IS_OBJECT)) {
			zend_throw_error(NULL, "Typed property %s::$conditionalExpressionHolderHelper must not be accessed before initialization", ZSTR_VAL(self->ce->name));
			return NULL;
		}
		return helper;
	}

	/* $this->conditionalExpressionHolderHelper->buildBranchUnionAugment(...) */
	zv::Val buildBranchUnionAugment(zval *nodeScopeResolver, zval *leftTypes, zval *rightTypes, zval *leftScope, zval *rightScope, zval *types) const
	{
		zval *helper = conditionalExpressionHolderHelper();
		if (UNEXPECTED(helper == NULL)) return zv::Val();
		return pt_conditional_expression_holder_helper_build_branch_union_augment(Z_OBJ_P(helper), nodeScopeResolver, leftTypes, rightTypes, leftScope, rightScope, types);
	}

	/* $this->conditionalExpressionHolderHelper->buildConditionalHolderRecipe(...) */
	zv::Val buildConditionalHolderRecipe(zval *composeScope, zval *conditionTypes, zval *holderTypes, bool holdersFromSureTypes, bool holderSideIsNegated, zval *nonVariableTargetScope, zval *holderSideExpr) const
	{
		zval *helper = conditionalExpressionHolderHelper();
		if (UNEXPECTED(helper == NULL)) return zv::Val();
		return pt_conditional_expression_holder_helper_build_conditional_holder_recipe(Z_OBJ_P(helper), composeScope, conditionTypes, holderTypes, holdersFromSureTypes, holderSideIsNegated, nonVariableTargetScope, holderSideExpr);
	}

	/* $result->setConditionalExpressionHolderRecipes(array_values(array_filter($recipes)))->setRootExpr($rootExpr) */
	static zv::Val withRecipes(zv::Val result, zv::Val *recipes, zval *rootExpr)
	{
		zend_object *resultObject = specifiedTypesObject(result.raw(), "setConditionalExpressionHolderRecipes");
		if (UNEXPECTED(resultObject == NULL)) return zv::Val();
		zv::Val list = filteredRecipes(recipes, 4);
		zv::Val withRecipesSet = pt_specified_types_set_conditional_expression_holder_recipes(resultObject, list.raw());
		if (UNEXPECTED(withRecipesSet.isUndef())) return zv::Val();
		zend_object *withRecipesObject = specifiedTypesObject(withRecipesSet.raw(), "setRootExpr");
		if (UNEXPECTED(withRecipesObject == NULL)) return zv::Val();
		return pt_specified_types_set_root_expr(withRecipesObject, rootExpr);
	}

	/* the antecedent swap: when $condTypes is an empty narrowing and the
	 * side's truthiness is implied by its truthy narrowing (an Isset_), the
	 * truthy narrowing with sure/sureNot swapped replaces it — provided every
	 * expression in it is trackable; false = pending exception */
	[[nodiscard]] static bool swappedTruthyCondition(zv::Val &condTypes, zval *sideExpr, zval *typesCallback, zval *scope)
	{
		int empty = isEmptyNarrowing(condTypes.raw());
		if (UNEXPECTED(empty < 0)) return false;
		if (!empty) return true;
		/* truthinessImpliedByTruthyNarrowing(): $side instanceof Expr\Isset_ */
		zend_class_entry *issetCe = pt_class_loaded(PT_CLASS_PARSER_ISSET_EXPR);
		if (issetCe == NULL) return !EG(exception);
		if (!instanceof_function(Z_OBJCE_P(sideExpr), issetCe)) return true;

		zend_object *truthy = pt_type_specifier_context_create_truthy();
		if (UNEXPECTED(truthy == NULL)) return false;
		zv::Val truthyTypes = typesOf(typesCallback, scope, truthy, NULL);
		if (UNEXPECTED(truthyTypes.isUndef())) return false;
		bool trackable;
		if (UNEXPECTED(!allExpressionsTrackable(truthyTypes.raw(), trackable))) return false;
		if (!trackable) return true;

		zend_object *truthyObject = Z_OBJ_P(truthyTypes.raw());
		zv::Val sureNot = pt_specified_types_get_sure_not_types(truthyObject);
		if (UNEXPECTED(sureNot.isUndef())) return false;
		zv::Val sure = pt_specified_types_get_sure_types(truthyObject);
		if (UNEXPECTED(sure.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(sureNot.raw()) != IS_ARRAY || Z_TYPE_P(sure.raw()) != IS_ARRAY)) {
			zend_type_error("phpstan_turbo: SpecifiedTypes tables must be arrays");
			return false;
		}
		zv::Val swapped = pt_specified_types_new(sureNot.raw(), sure.raw());
		if (UNEXPECTED(swapped.isUndef())) return false;
		condTypes = std::move(swapped);
		return true;
	}

	/* Mirrors allExpressionsTrackable(); false = pending exception */
	[[nodiscard]] static bool allExpressionsTrackable(zval *types, bool &out)
	{
		out = false;
		zend_object *object = specifiedTypesObject(types, "getAlternativeTypes");
		if (UNEXPECTED(object == NULL)) return false;
		// an alternative-form entry has no single condition type to track
		zv::Val alternativeTypes = pt_specified_types_get_alternative_types(object);
		if (UNEXPECTED(alternativeTypes.isUndef())) return false;
		if (Z_TYPE_P(alternativeTypes.raw()) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL_P(alternativeTypes.raw())) > 0) return true;

		for (bool sure : { true, false }) {
			zv::Val table = sure ? pt_specified_types_get_sure_types(object) : pt_specified_types_get_sure_not_types(object);
			if (UNEXPECTED(table.isUndef())) return false;
			if (UNEXPECTED(Z_TYPE_P(table.raw()) != IS_ARRAY)) {
				zend_type_error("foreach() argument must be of type array|object, %s given", zend_zval_value_name(table.raw()));
				return false;
			}
			for (zv::ArrayEntry entry : zv::ArrRef(table.raw())) {
				zval *tuple = entry.value().deref().raw();
				zval *expr = Z_TYPE_P(tuple) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(tuple), 0) : NULL;
				if (expr != NULL) {
					ZVAL_DEREF(expr);
				}
				bool trackable;
				if (UNEXPECTED(!isTrackableExpression(expr, trackable))) return false;
				if (!trackable) return true;
			}
		}

		zv::Val sure = pt_specified_types_get_sure_types(object);
		if (UNEXPECTED(sure.isUndef())) return false;
		if (Z_TYPE_P(sure.raw()) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL_P(sure.raw())) > 0) {
			out = true;
			return true;
		}
		zv::Val sureNot = pt_specified_types_get_sure_not_types(object);
		if (UNEXPECTED(sureNot.isUndef())) return false;
		out = Z_TYPE_P(sureNot.raw()) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL_P(sureNot.raw())) > 0;
		return true;
	}

	/* Mirrors isTrackableExpression(Expr $expr); false = pending exception
	 * (the TypeError of a non-Expr argument) */
	[[nodiscard]] static bool isTrackableExpression(zval *expr, bool &out)
	{
		out = false;
		zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
		if (UNEXPECTED(exprCe == NULL)) return false;
		if (UNEXPECTED(expr == NULL || Z_TYPE_P(expr) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(expr), exprCe))) {
			zend_type_error("PHPStan\\Analyser\\ExprHandler\\Helper\\BooleanNarrowingHelper::isTrackableExpression(): Argument #1 ($expr) must be of type PhpParser\\Node\\Expr, %s given", expr != NULL ? zend_zval_value_name(expr) : "null");
			return false;
		}
		zend_object *object = Z_OBJ_P(expr);
		zend_class_entry *variableCe = pt_class(PT_CLASS_VARIABLE);
		if (UNEXPECTED(variableCe == NULL)) return false;
		if (instanceof_function(object->ce, variableCe)) {
			zval *name = pt_bnh_variable_name.of(object);
			out = name != NULL && Z_TYPE_P(name) == IS_STRING;
			return true;
		}
		for (int classIdx : { PT_CLASS_PROPERTY_FETCH, PT_CLASS_ARRAY_DIM_FETCH, PT_CLASS_STATIC_PROPERTY_FETCH }) {
			zend_class_entry *ce = pt_class(classIdx);
			if (UNEXPECTED(ce == NULL)) return false;
			if (instanceof_function(object->ce, ce)) {
				out = true;
				return true;
			}
		}
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::BooleanNarrowingHelper;

/* {{{ direct entries (support.h) */

zv::Val pt_boolean_narrowing_helper_specify_conjunction(zend_object *helper, zval *nodeScopeResolver, zval *s, zval *context, zval *rootExpr, zval *leftExpr, zval *leftTypesCallback, zval *leftTruthyScope, zval *leftFalseyScope, zval *rightExpr, zval *rightTypesCallback, zval *rightFalseyScope)
{
	if (EXPECTED(helper->ce == pt_ce_boolean_narrowing_helper && Z_TYPE_P(s) == IS_OBJECT && Z_TYPE_P(context) == IS_OBJECT && Z_TYPE_P(rootExpr) == IS_OBJECT && Z_TYPE_P(leftExpr) == IS_OBJECT && Z_TYPE_P(rightExpr) == IS_OBJECT)) {
		return BooleanNarrowingHelper(helper).specifyConjunction(nodeScopeResolver, s, context, rootExpr, leftExpr, leftTypesCallback, leftTruthyScope, leftFalseyScope, rightExpr, rightTypesCallback, rightFalseyScope);
	}
	zv::Args args{nodeScopeResolver, s, context, rootExpr, leftExpr, leftTypesCallback, leftTruthyScope, leftFalseyScope, rightExpr, rightTypesCallback, rightFalseyScope};
	return pt_type_call(helper, PT_LC("specifyconjunction"), 11, args);
}

zv::Val pt_boolean_narrowing_helper_specify_disjunction(zend_object *helper, zval *nodeScopeResolver, zval *s, zval *context, zval *rootExpr, zval *leftExpr, zval *leftTypesCallback, zval *leftTypeCallback, zval *leftTruthyScope, zval *leftFalseyScope, zval *rightExpr, zval *rightTypesCallback, zval *rightTypeCallback, zval *rightTruthyScope)
{
	if (EXPECTED(helper->ce == pt_ce_boolean_narrowing_helper && Z_TYPE_P(s) == IS_OBJECT && Z_TYPE_P(context) == IS_OBJECT && Z_TYPE_P(rootExpr) == IS_OBJECT && Z_TYPE_P(leftExpr) == IS_OBJECT && Z_TYPE_P(rightExpr) == IS_OBJECT)) {
		return BooleanNarrowingHelper(helper).specifyDisjunction(nodeScopeResolver, s, context, rootExpr, leftExpr, leftTypesCallback, leftTypeCallback, leftTruthyScope, leftFalseyScope, rightExpr, rightTypesCallback, rightTypeCallback, rightTruthyScope);
	}
	zv::Args args{nodeScopeResolver, s, context, rootExpr, leftExpr, leftTypesCallback, leftTypeCallback, leftTruthyScope, leftFalseyScope, rightExpr, rightTypesCallback, rightTypeCallback, rightTruthyScope};
	return pt_type_call(helper, PT_LC("specifydisjunction"), 13, args);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_boolean_narrowing_helper)
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Helper\\BooleanNarrowingHelper");
	ptdecl::BooleanNarrowingHelper::declareClass(cls);
	ptdecl::BooleanNarrowingHelper::declareProperties(cls);

	/* the DI service's constructor: the generated arginfo names the twin's
	 * parameter classes exactly (README rule 6) */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *conditionalExpressionHolderHelper, *defaultNarrowingHelper;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, conditionalExpressionHolderHelper, defaultNarrowingHelper)) RETURN_THROWS();
		BooleanNarrowingHelper::construct(Z_OBJ_P(ZEND_THIS), conditionalExpressionHolderHelper, defaultNarrowingHelper);
	});

	cls.method(sigs::specifyConjunction, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *s, *context, *rootExpr, *leftExpr, *leftTypesCallback, *leftTruthyScope, *leftFalseyScope, *rightExpr, *rightTypesCallback, *rightFalseyScope;
		/* raw zpp: eleven parameters, beyond zp::parse's arity */
		ZEND_PARSE_PARAMETERS_START(11, 11)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(s)
			Z_PARAM_OBJECT(context)
			Z_PARAM_OBJECT(rootExpr)
			Z_PARAM_OBJECT(leftExpr)
			Z_PARAM_ZVAL(leftTypesCallback)
			Z_PARAM_ZVAL(leftTruthyScope)
			Z_PARAM_ZVAL(leftFalseyScope)
			Z_PARAM_OBJECT(rightExpr)
			Z_PARAM_ZVAL(rightTypesCallback)
			Z_PARAM_ZVAL(rightFalseyScope)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(BooleanNarrowingHelper(Z_OBJ_P(ZEND_THIS)).specifyConjunction(nodeScopeResolver, s, context, rootExpr, leftExpr, leftTypesCallback, leftTruthyScope, leftFalseyScope, rightExpr, rightTypesCallback, rightFalseyScope));
	});

	cls.method(sigs::specifyDisjunction, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *s, *context, *rootExpr, *leftExpr, *leftTypesCallback, *leftTypeCallback, *leftTruthyScope, *leftFalseyScope, *rightExpr, *rightTypesCallback, *rightTypeCallback, *rightTruthyScope;
		/* raw zpp: thirteen parameters, beyond zp::parse's arity */
		ZEND_PARSE_PARAMETERS_START(13, 13)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(s)
			Z_PARAM_OBJECT(context)
			Z_PARAM_OBJECT(rootExpr)
			Z_PARAM_OBJECT(leftExpr)
			Z_PARAM_ZVAL(leftTypesCallback)
			Z_PARAM_ZVAL(leftTypeCallback)
			Z_PARAM_ZVAL(leftTruthyScope)
			Z_PARAM_ZVAL(leftFalseyScope)
			Z_PARAM_OBJECT(rightExpr)
			Z_PARAM_ZVAL(rightTypesCallback)
			Z_PARAM_ZVAL(rightTypeCallback)
			Z_PARAM_ZVAL(rightTruthyScope)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(BooleanNarrowingHelper(Z_OBJ_P(ZEND_THIS)).specifyDisjunction(nodeScopeResolver, s, context, rootExpr, leftExpr, leftTypesCallback, leftTypeCallback, leftTruthyScope, leftFalseyScope, rightExpr, rightTypesCallback, rightTypeCallback, rightTruthyScope));
	});

	cls.shadow(&pt_ce_boolean_narrowing_helper);
}

/* }}} */
