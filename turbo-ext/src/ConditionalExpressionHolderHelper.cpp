/*
 * PHPStanTurbo\ConditionalExpressionHolderHelper — native implementation of
 * PHPStan\Analyser\ExprHandler\Helper\ConditionalExpressionHolderHelper.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's exact
 * arginfo, the state lives in the twin's property slot (generated
 * declarations). Both builders read the SpecifiedTypes tables through the
 * native entries (SpecifiedTypes.cpp), ask the scopes through MutatingScope.cpp
 * and ExpressionResultStorage.cpp, and construct the native recipe / augment
 * directly (ConditionalExpressionHolderRecipe.cpp,
 * DisjunctionBranchUnionAugment.cpp); the branch-scope thunks are the callers'
 * callables and NodeScopeResolver stays PHP (called by name from a local
 * helper).
 *
 * BooleanNarrowingHelper.cpp calls the builders through the
 * pt_conditional_expression_holder_helper_* direct entries.
 */

#include "support.h"
#include "generated/ConditionalExpressionHolderHelper.h"

namespace slots = ptdecl::ConditionalExpressionHolderHelper::slot;
namespace sigs = ptdecl::ConditionalExpressionHolderHelper::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "ParserVisitors.h"

zend_class_entry *pt_ce_conditional_expression_holder_helper = NULL;

namespace {

using phpstanturbo::visitors::NodeProp;

NodeProp pt_cehh_variable_name = PT_NODE_PROP(PT_CLASS_VARIABLE, "name");

/* {{{ the analyser classes still PHP: one local helper per call */

/* $nodeScopeResolver->requireScopeStateType($expr, $scope) */
zv::Val requireScopeStateType(zval *nodeScopeResolver, zval *expr, zval *scope)
{
	return pt_node_scope_resolver_require_scope_state_type(nodeScopeResolver, expr, scope);
}

/* }}} */

/* $entry[0] / $entry[1] of a SpecifiedTypes entry ([$exprNode, $type]);
 * NULL when absent */
zval *entryAt(zval *entry, zend_ulong index)
{
	if (Z_TYPE_P(entry) != IS_ARRAY) return NULL;
	zval *value = zend_hash_index_find(Z_ARRVAL_P(entry), index);
	if (value == NULL) return NULL;
	ZVAL_DEREF(value);
	return value;
}

/* isset($table[$key]) for a key taken from another array */
bool issetKey(zval *table, zend_string *skey, zend_ulong h)
{
	if (Z_TYPE_P(table) != IS_ARRAY) return false;
	zval *found = pt_ht_find(Z_ARRVAL_P(table), skey, h);
	if (found == NULL) return false;
	ZVAL_DEREF(found);
	return Z_TYPE_P(found) != IS_NULL;
}

/* (string) $key of an array key: the string key itself, the decimal form of
 * an integer key */
zv::Val keyString(zend_string *skey, zend_ulong h)
{
	if (skey != NULL) return zv::Val::string(skey);
	return zv::Val::adoptString(zend_long_to_str((zend_long) h));
}

/* the table of a SpecifiedTypes getter that must be an array; UNDEF =
 * pending exception */
zv::Val requireArray(zv::Val table, const char *getter)
{
	if (UNEXPECTED(table.isUndef())) return zv::Val();
	if (UNEXPECTED(Z_TYPE_P(table.raw()) != IS_ARRAY)) {
		zend_type_error("phpstan_turbo: SpecifiedTypes::%s() must return array, %s returned", getter, zend_zval_value_name(table.raw()));
		return zv::Val();
	}
	return table;
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Helper\ConditionalExpressionHolderHelper. */
class ConditionalExpressionHolderHelper
{
public:
	explicit ConditionalExpressionHolderHelper(zend_object *self) : self(self) {}

	/* Mirrors __construct(): the promoted property */
	static void construct(zend_object *object, zval *defaultNarrowingHelper)
	{
		zv::ObjRef(object).propAtWrite(slots::defaultNarrowingHelper, zv::Val::copyOf(zv::Ref(defaultNarrowingHelper)));
		Z_PROP_FLAG_P(OBJ_PROP_NUM(object, slots::defaultNarrowingHelper)) = 0; /* no longer IS_PROP_UNINIT */
	}

	/* Mirrors buildBranchUnionAugment(); the augment or PHP null, UNDEF =
	 * pending exception */
	zv::Val buildBranchUnionAugment(zval *nodeScopeResolver, zval *leftTypes, zval *rightTypes, zval *leftFilteredScope, zval *rightFilteredScope, zval *types) const
	{
		zv::Arr candidateExprs = zv::Arr::empty();
		struct Source
		{
			zval *specifiedTypes;
			bool sure;
		};
		// sureNot entries constrain their branch too - the old normalize()
		// converted them to sure entries before candidates were collected, so a
		// sureNot-only narrowing (e.g. the truthy of a bool variable) must also
		// contribute its subject. The branch-scope reads below price the subject
		// on each filtered scope, where an impossible branch (a holder-fixpoint
		// contradiction) collapses to never and drops out of the union.
		const Source sources[4] = { { leftTypes, true }, { rightTypes, true }, { leftTypes, false }, { rightTypes, false } };
		for (const Source &source : sources) {
			zend_object *object = Z_OBJ_P(source.specifiedTypes);
			zv::Val table = source.sure
				? requireArray(pt_specified_types_get_sure_types(object), "getSureTypes")
				: requireArray(pt_specified_types_get_sure_not_types(object), "getSureNotTypes");
			if (UNEXPECTED(table.isUndef())) return zv::Val();
			for (zv::ArrayEntry entry : zv::ArrRef(table.raw())) {
				zval *exprNode = entryAt(entry.value().deref().raw(), 0);
				candidateExprs.separate();
				zval value;
				if (exprNode != NULL) {
					ZVAL_COPY(&value, exprNode);
				} else {
					ZVAL_NULL(&value);
				}
				pt_ht_update(candidateExprs.table(), entry.stringKeyOrNull(), entry.indexKey(), &value);
			}
		}

		zv::Val existingSureTypes = requireArray(pt_specified_types_get_sure_types(Z_OBJ_P(types)), "getSureTypes");
		if (UNEXPECTED(existingSureTypes.isUndef())) return zv::Val();
		zv::Val existingAlternativeTypes = requireArray(pt_specified_types_get_alternative_types(Z_OBJ_P(types)), "getAlternativeTypes");
		if (UNEXPECTED(existingAlternativeTypes.isUndef())) return zv::Val();

		zv::Arr candidates = zv::Arr::empty();
		zv::Val leftScope;
		zv::Val rightScope;
		for (zv::ArrayEntry entry : zv::ArrRef(candidateExprs.raw())) {
			zend_string *skey = entry.stringKeyOrNull();
			zend_ulong h = entry.indexKey();
			// an alternative-form entry already encodes the either-branch
			// union for this expression, deferred to the application point
			if (issetKey(existingSureTypes.raw(), skey, h) || issetKey(existingAlternativeTypes.raw(), skey, h)) continue;

			if (leftScope.isNull()) {
				leftScope = pt_type_call_callable(leftFilteredScope, 0, NULL);
				if (UNEXPECTED(leftScope.isUndef())) return zv::Val();
			}
			if (rightScope.isNull()) {
				rightScope = pt_type_call_callable(rightFilteredScope, 0, NULL);
				if (UNEXPECTED(rightScope.isUndef())) return zv::Val();
			}
			zval *targetExpr = entry.value().deref().raw();
			int inLeft = hasExpressionTypeYes(leftScope.raw(), targetExpr);
			if (UNEXPECTED(inLeft < 0)) return zv::Val();
			if (!inLeft) continue;
			int inRight = hasExpressionTypeYes(rightScope.raw(), targetExpr);
			if (UNEXPECTED(inRight < 0)) return zv::Val();
			if (!inRight) continue;

			// the guards above pin the target as tracked on both filtered
			// scopes - scope state answers without a walk
			zv::Val leftType = requireScopeStateType(nodeScopeResolver, targetExpr, leftScope.raw());
			if (UNEXPECTED(leftType.isUndef())) return zv::Val();
			zv::Val rightType = requireScopeStateType(nodeScopeResolver, targetExpr, rightScope.raw());
			if (UNEXPECTED(rightType.isUndef())) return zv::Val();
			zv::Arr candidate = zv::Arr::create(3);
			candidate.push(zv::Ref(targetExpr));
			candidate.push(std::move(leftType));
			candidate.push(std::move(rightType));
			candidates.push(zv::Val(std::move(candidate)));
		}

		if (zend_hash_num_elements(candidates.table()) == 0) return zv::Val::null();

		zval *defaultNarrowingHelper = slot(slots::defaultNarrowingHelper);
		if (UNEXPECTED(Z_TYPE_P(defaultNarrowingHelper) != IS_OBJECT)) return uninitialized("defaultNarrowingHelper");
		return pt_disjunction_branch_union_augment_new(nodeScopeResolver, defaultNarrowingHelper, candidates.raw());
	}

	/* Mirrors buildConditionalHolderRecipe(); $nonVariableTargetScope /
	 * $holderSideExpr NULL for null. The recipe or PHP null, UNDEF = pending
	 * exception */
	zv::Val buildConditionalHolderRecipe(zval *composeScope, zval *conditionSpecifiedTypes, zval *holderSpecifiedTypes, bool holdersFromSureTypes, bool holderSideIsNegated, zval *nonVariableTargetScope, zval *holderSideExpr) const
	{
		// an alternative-form entry (a cross-kind either-branch merge) has no
		// single condition type; dropping it from the condition set would let
		// the holder fire too eagerly - build no holders from such a condition
		zv::Val alternativeTypes = pt_specified_types_get_alternative_types(Z_OBJ_P(conditionSpecifiedTypes));
		if (UNEXPECTED(alternativeTypes.isUndef())) return zv::Val();
		if (Z_TYPE_P(alternativeTypes.raw()) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL_P(alternativeTypes.raw())) > 0) return zv::Val::null();

		// A holder side that is itself a compound boolean cannot always be split
		// into independent per-expression holders (see the twin).
		bool unsplittable;
		if (UNEXPECTED(!isUnsplittableCompoundHolderSide(holderSideExpr, holderSideIsNegated, unsplittable))) return zv::Val();
		if (unsplittable) return zv::Val::null();

		zv::Arr conditionEntries = zv::Arr::empty();
		for (bool sure : { true, false }) {
			zv::Val table = sure
				? requireArray(pt_specified_types_get_sure_types(Z_OBJ_P(conditionSpecifiedTypes)), "getSureTypes")
				: requireArray(pt_specified_types_get_sure_not_types(Z_OBJ_P(conditionSpecifiedTypes)), "getSureNotTypes");
			if (UNEXPECTED(table.isUndef())) return zv::Val();
			for (zv::ArrayEntry entry : zv::ArrRef(table.raw())) {
				zval *tuple = entry.value().deref().raw();
				zval *expr = entryAt(tuple, 0);
				zval *type = entryAt(tuple, 1);
				bool trackable;
				if (UNEXPECTED(!isTrackableExpression(expr, trackable))) return zv::Val();
				if (!trackable) continue;

				zv::Val conditionResult = findConditionResult(composeScope, expr);
				if (UNEXPECTED(conditionResult.isUndef())) return zv::Val();
				zv::Arr conditionEntry = zv::Arr::create(5);
				conditionEntry.push(keyString(entry.stringKeyOrNull(), entry.indexKey()));
				conditionEntry.push(zv::Ref(expr));
				conditionEntry.push(zv::Val::boolean(sure));
				conditionEntry.push(type != NULL ? zv::Val::copyOf(zv::Ref(type)) : zv::Val::null());
				conditionEntry.push(std::move(conditionResult));
				conditionEntries.push(zv::Val(std::move(conditionEntry)));
			}
		}

		if (zend_hash_num_elements(conditionEntries.table()) == 0) return zv::Val::null();

		zv::Arr holderEntries = zv::Arr::empty();
		zv::Val holderTypes = holdersFromSureTypes
			? requireArray(pt_specified_types_get_sure_types(Z_OBJ_P(holderSpecifiedTypes)), "getSureTypes")
			: requireArray(pt_specified_types_get_sure_not_types(Z_OBJ_P(holderSpecifiedTypes)), "getSureNotTypes");
		if (UNEXPECTED(holderTypes.isUndef())) return zv::Val();
		zend_class_entry *variableCe = pt_class(PT_CLASS_VARIABLE);
		if (UNEXPECTED(variableCe == NULL)) return zv::Val();
		for (zv::ArrayEntry entry : zv::ArrRef(holderTypes.raw())) {
			zval *tuple = entry.value().deref().raw();
			zval *expr = entryAt(tuple, 0);
			zval *type = entryAt(tuple, 1);
			bool trackable;
			if (UNEXPECTED(!isTrackableExpression(expr, trackable))) return zv::Val();
			if (!trackable) continue;

			zv::Val pinnedTargetType;
			if (!instanceof_function(Z_OBJCE_P(expr), variableCe) && nonVariableTargetScope != NULL) {
				pinnedTargetType = pt_mutating_scope_get_state_type(Z_OBJ_P(nonVariableTargetScope), Z_OBJ_P(expr));
				if (UNEXPECTED(pinnedTargetType.isUndef())) return zv::Val();
			} else {
				pinnedTargetType = zv::Val::null();
			}
			zv::Arr holderEntry = zv::Arr::create(4);
			holderEntry.push(keyString(entry.stringKeyOrNull(), entry.indexKey()));
			holderEntry.push(zv::Ref(expr));
			holderEntry.push(type != NULL ? zv::Val::copyOf(zv::Ref(type)) : zv::Val::null());
			holderEntry.push(std::move(pinnedTargetType));
			holderEntries.push(zv::Val(std::move(holderEntry)));
		}

		if (zend_hash_num_elements(holderEntries.table()) == 0) return zv::Val::null();

		return pt_conditional_expression_holder_recipe_new(conditionEntries.raw(), holderEntries.raw(), holdersFromSureTypes);
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

	/* $scope->hasExpressionType($expr)->yes() on a thunk-produced scope; -1
	 * = pending exception, else 0 / 1 */
	static int hasExpressionTypeYes(zval *scope, zval *expr)
	{
		if (UNEXPECTED(Z_TYPE_P(scope) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function hasExpressionType() on %s", zend_zval_value_name(scope));
			return -1;
		}
		if (UNEXPECTED(Z_TYPE_P(expr) != IS_OBJECT)) {
			zend_type_error("PHPStan\\Analyser\\MutatingScope::hasExpressionType(): Argument #1 ($node) must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(expr));
			return -1;
		}
		zend_long verdict = pt_mutating_scope_has_expression_type(Z_OBJ_P(scope), expr);
		if (UNEXPECTED(verdict < 0)) return -1;
		return verdict == PT_TRI_YES ? 1 : 0;
	}

	/* Mirrors findConditionResult(); the result or PHP null, UNDEF = pending
	 * exception */
	static zv::Val findConditionResult(zval *composeScope, zval *expr)
	{
		zv::Val storage = pt_mutating_scope_get_current_expression_result_storage(Z_OBJ_P(composeScope));
		if (UNEXPECTED(storage.isUndef())) return zv::Val();
		if (Z_TYPE_P(storage.raw()) == IS_NULL) return zv::Val::null();
		return pt_expression_result_storage_find(storage.raw(), expr);
	}

	/* Mirrors isUnsplittableCompoundHolderSide(); false = pending exception */
	[[nodiscard]] static bool isUnsplittableCompoundHolderSide(zval *holderSideExpr, bool holderSideIsNegated, bool &out)
	{
		out = false;
		if (holderSideExpr == NULL) return true;

		int first = holderSideIsNegated ? PT_CLASS_BOOLEAN_AND_EXPR : PT_CLASS_BOOLEAN_OR_EXPR;
		int second = holderSideIsNegated ? PT_CLASS_LOGICAL_AND_EXPR : PT_CLASS_LOGICAL_OR_EXPR;
		for (int classIdx : { first, second }) {
			zend_class_entry *ce = pt_class_loaded(classIdx);
			if (ce == NULL) {
				if (UNEXPECTED(EG(exception))) return false;
				continue;
			}
			if (instanceof_function(Z_OBJCE_P(holderSideExpr), ce)) {
				out = true;
				return true;
			}
		}
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
			zend_type_error("PHPStan\\Analyser\\ExprHandler\\Helper\\ConditionalExpressionHolderHelper::isTrackableExpression(): Argument #1 ($expr) must be of type PhpParser\\Node\\Expr, %s given", expr != NULL ? zend_zval_value_name(expr) : "null");
			return false;
		}
		zend_object *object = Z_OBJ_P(expr);
		zend_class_entry *variableCe = pt_class(PT_CLASS_VARIABLE);
		if (UNEXPECTED(variableCe == NULL)) return false;
		if (instanceof_function(object->ce, variableCe)) {
			zval *name = pt_cehh_variable_name.of(object);
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

using phpstanturbo::ConditionalExpressionHolderHelper;

/* {{{ direct entries (support.h) */

zv::Val pt_conditional_expression_holder_helper_build_branch_union_augment(zend_object *helper, zval *nodeScopeResolver, zval *leftTypes, zval *rightTypes, zval *leftFilteredScope, zval *rightFilteredScope, zval *types)
{
	if (EXPECTED(helper->ce == pt_ce_conditional_expression_holder_helper && Z_TYPE_P(nodeScopeResolver) == IS_OBJECT && Z_TYPE_P(leftTypes) == IS_OBJECT && Z_TYPE_P(rightTypes) == IS_OBJECT && Z_TYPE_P(types) == IS_OBJECT)) {
		return ConditionalExpressionHolderHelper(helper).buildBranchUnionAugment(nodeScopeResolver, leftTypes, rightTypes, leftFilteredScope, rightFilteredScope, types);
	}
	zv::Args args{nodeScopeResolver, leftTypes, rightTypes, leftFilteredScope, rightFilteredScope, types};
	return pt_type_call(helper, PT_LC("buildbranchunionaugment"), 6, args);
}

zv::Val pt_conditional_expression_holder_helper_build_conditional_holder_recipe(zend_object *helper, zval *composeScope, zval *conditionSpecifiedTypes, zval *holderSpecifiedTypes, bool holdersFromSureTypes, bool holderSideIsNegated, zval *nonVariableTargetScope, zval *holderSideExpr)
{
	if (EXPECTED(helper->ce == pt_ce_conditional_expression_holder_helper && Z_TYPE_P(composeScope) == IS_OBJECT && Z_TYPE_P(conditionSpecifiedTypes) == IS_OBJECT && Z_TYPE_P(holderSpecifiedTypes) == IS_OBJECT)) {
		return ConditionalExpressionHolderHelper(helper).buildConditionalHolderRecipe(composeScope, conditionSpecifiedTypes, holderSpecifiedTypes, holdersFromSureTypes, holderSideIsNegated, nonVariableTargetScope, holderSideExpr);
	}
	zv::Args args{composeScope, conditionSpecifiedTypes, holderSpecifiedTypes, holdersFromSureTypes, holderSideIsNegated, zv::null, zv::null};
	zval *argv = args;
	if (nonVariableTargetScope != NULL) {
		ZVAL_COPY_VALUE(&argv[5], nonVariableTargetScope);
	}
	if (holderSideExpr != NULL) {
		ZVAL_COPY_VALUE(&argv[6], holderSideExpr);
	}
	return pt_type_call(helper, PT_LC("buildconditionalholderrecipe"), 7, argv);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_conditional_expression_holder_helper)
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Helper\\ConditionalExpressionHolderHelper");
	ptdecl::ConditionalExpressionHolderHelper::declareClass(cls);
	ptdecl::ConditionalExpressionHolderHelper::declareProperties(cls);

	/* the DI service's constructor: the generated arginfo names the twin's
	 * parameter class exactly (README rule 6) */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *defaultNarrowingHelper;
		if (!zp::parse<zp::Obj>(execute_data, defaultNarrowingHelper)) RETURN_THROWS();
		ConditionalExpressionHolderHelper::construct(Z_OBJ_P(ZEND_THIS), defaultNarrowingHelper);
	});

	cls.method(sigs::buildBranchUnionAugment, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *leftTypes, *rightTypes, *leftFilteredScope, *rightFilteredScope, *types;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Zval, zp::Zval, zp::Obj>(execute_data, nodeScopeResolver, leftTypes, rightTypes, leftFilteredScope, rightFilteredScope, types)) RETURN_THROWS();
		PT_RETURN_VAL(ConditionalExpressionHolderHelper(Z_OBJ_P(ZEND_THIS)).buildBranchUnionAugment(nodeScopeResolver, leftTypes, rightTypes, leftFilteredScope, rightFilteredScope, types));
	});

	cls.method(sigs::buildConditionalHolderRecipe, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *composeScope, *conditionSpecifiedTypes, *holderSpecifiedTypes, *nonVariableTargetScope, *holderSideExpr = NULL;
		bool holdersFromSureTypes, holderSideIsNegated;
		ZEND_PARSE_PARAMETERS_START(6, 7)
			Z_PARAM_OBJECT(composeScope)
			Z_PARAM_OBJECT(conditionSpecifiedTypes)
			Z_PARAM_OBJECT(holderSpecifiedTypes)
			Z_PARAM_BOOL(holdersFromSureTypes)
			Z_PARAM_BOOL(holderSideIsNegated)
			Z_PARAM_OBJECT_OR_NULL(nonVariableTargetScope)
			Z_PARAM_OPTIONAL
			Z_PARAM_OBJECT_OR_NULL(holderSideExpr)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(ConditionalExpressionHolderHelper(Z_OBJ_P(ZEND_THIS)).buildConditionalHolderRecipe(composeScope, conditionSpecifiedTypes, holderSpecifiedTypes, holdersFromSureTypes, holderSideIsNegated, nonVariableTargetScope, holderSideExpr));
	});

	cls.shadow(&pt_ce_conditional_expression_holder_helper);
}

/* }}} */
