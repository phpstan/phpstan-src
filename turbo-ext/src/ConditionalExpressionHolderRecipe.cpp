/*
 * PHPStanTurbo\ConditionalExpressionHolderRecipe — native implementation of
 * PHPStan\Analyser\ConditionalExpressionHolderRecipe.
 *
 * A final value class: the raw condition/holder entries live in the twin's
 * property slots (generated declarations), evaluate() runs the
 * state-dependent math against the applying scope — the stored results'
 * getTypeOnScope() (ExpressionResult.cpp), the scope's getStateType()
 * (MutatingScope.cpp), the native TypeCombinator and Type ops — and builds
 * the shadowing ExpressionTypeHolder / ConditionalExpressionHolder objects
 * directly.
 *
 * MutatingScope::applySpecifiedTypes() reaches evaluate() through
 * pt_conditional_expression_holder_recipe_evaluate(); the boolean narrowing
 * helper builds recipes through pt_conditional_expression_holder_recipe_new().
 */

#include "support.h"
#include "generated/ConditionalExpressionHolderRecipe.h"

namespace slots = ptdecl::ConditionalExpressionHolderRecipe::slot;
namespace sigs = ptdecl::ConditionalExpressionHolderRecipe::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"

zend_class_entry *pt_ce_conditional_expression_holder_recipe = NULL;

namespace {

/* $array[$index] of an entry tuple (dereferenced), NULL when absent */
zval *tupleAt(zval *tuple, zend_ulong index)
{
	zval *value = zend_hash_index_find(Z_ARRVAL_P(tuple), index);
	if (value == NULL) return NULL;
	ZVAL_DEREF(value);
	return value;
}

/* $table[$key] for a string or int key (the array-key semantics of a PHP
 * dim read); NULL when absent */
zval *findKey(HashTable *table, zval *key)
{
	if (Z_TYPE_P(key) == IS_STRING) return zend_symtable_find(table, Z_STR_P(key));
	if (Z_TYPE_P(key) == IS_LONG) return zend_hash_index_find(table, (zend_ulong) Z_LVAL_P(key));
	return NULL;
}

/* $table[$key] = $value for a string or int key */
void setKey(HashTable *table, zval *key, zval *value)
{
	if (Z_TYPE_P(key) == IS_STRING) {
		zend_symtable_update(table, Z_STR_P(key), value);
	} else {
		zend_hash_index_update(table, (zend_ulong) Z_LVAL_P(key), value);
	}
}

void deleteKey(HashTable *table, zval *key)
{
	if (Z_TYPE_P(key) == IS_STRING) {
		zend_symtable_del(table, Z_STR_P(key));
	} else {
		zend_hash_index_del(table, (zend_ulong) Z_LVAL_P(key));
	}
}

/* $type instanceof NeverType */
bool isNeverType(zval *type)
{
	return Z_TYPE_P(type) == IS_OBJECT && instanceof_function(Z_OBJCE_P(type), pt_ce_never_type);
}

/* ExpressionTypeHolder::createYes($expr, $type) */
zv::Val createYesHolder(zval *expr, zval *type)
{
	zval holder;
	pt_holder_create(&holder, expr, type, PT_TRI_YES);
	return zv::Val::adopt(holder);
}

/* a malformed entry tuple: the twin's list() assignment reads an undefined
 * offset — never produced by ConditionalExpressionHolderHelper */
zv::Val malformedEntry(const char *list)
{
	zend_throw_error(NULL, "phpstan_turbo: a ConditionalExpressionHolderRecipe %s entry is malformed", list);
	return zv::Val();
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ConditionalExpressionHolderRecipe. */
class ConditionalExpressionHolderRecipe
{
public:
	explicit ConditionalExpressionHolderRecipe(zend_object *self) : self(self) {}

	/* Mirrors __construct(): the promoted properties */
	static void construct(zend_object *object, zval *conditionEntries, zval *holderEntries, bool holdersFromSureTypes)
	{
		writeSlot(object, slots::conditionEntries, zv::Val::copyOf(zv::Ref(conditionEntries)));
		writeSlot(object, slots::holderEntries, zv::Val::copyOf(zv::Ref(holderEntries)));
		writeSlot(object, slots::holdersFromSureTypes, zv::Val::boolean(holdersFromSureTypes));
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val create(zval *conditionEntries, zval *holderEntries, bool holdersFromSureTypes)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_conditional_expression_holder_recipe) != SUCCESS)) return zv::Val();
		construct(Z_OBJ(object), conditionEntries, holderEntries, holdersFromSureTypes);
		return zv::Val::adopt(object);
	}

	/* Mirrors evaluate(); UNDEF = pending exception */
	zv::Val evaluate(zval *scope) const
	{
		zval *conditionEntries = slot(slots::conditionEntries);
		if (UNEXPECTED(Z_TYPE_P(conditionEntries) != IS_ARRAY)) return uninitialized("conditionEntries");

		zv::Arr conditionExpressionTypes = zv::Arr::empty();
		zv::ScratchTable droppedNoOpConditions(8);
		// the unnarrowed type of each condition expression, for the
		// dropped-self-condition complement below
		zv::Arr conditionOriginalTypes = zv::Arr::empty();
		for (zv::ArrayEntry entry : zv::ArrRef(conditionEntries)) {
			zval *tuple = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(tuple) != IS_ARRAY)) return malformedEntry("condition");
			zval *exprString = tupleAt(tuple, 0);
			zval *expr = tupleAt(tuple, 1);
			zval *fromSureTypes = tupleAt(tuple, 2);
			zval *type = tupleAt(tuple, 3);
			zval *conditionResult = tupleAt(tuple, 4);
			if (UNEXPECTED(exprString == NULL || (Z_TYPE_P(exprString) != IS_STRING && Z_TYPE_P(exprString) != IS_LONG) || expr == NULL || Z_TYPE_P(expr) != IS_OBJECT || fromSureTypes == NULL || type == NULL || Z_TYPE_P(type) != IS_OBJECT || conditionResult == NULL)) return malformedEntry("condition");

			// through the expression's own result, which answers from the applying
			// scope's state where that scope owns the expression - a narrowing
			// subject no walk produced (an extension is free to specify a type for
			// an expression the source never evaluated on its own) has no result
			// and is read from the scope state directly
			zv::Val scopeType;
			if (Z_TYPE_P(conditionResult) != IS_NULL) {
				if (UNEXPECTED(Z_TYPE_P(conditionResult) != IS_OBJECT)) {
					zend_throw_error(NULL, "Call to a member function getTypeOnScope() on %s", zend_zval_value_name(conditionResult));
					return zv::Val();
				}
				bool nativeTypesPromoted;
				if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(scope), nativeTypesPromoted))) return zv::Val();
				scopeType = pt_expression_result_get_type_on_scope(conditionResult, scope, nativeTypesPromoted);
			} else {
				scopeType = pt_mutating_scope_get_state_type(Z_OBJ_P(scope), Z_OBJ_P(expr));
			}
			if (UNEXPECTED(scopeType.isUndef())) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(scopeType.raw()) != IS_OBJECT)) return malformedEntry("condition");

			zv::Val conditionType;
			if (zend_is_true(fromSureTypes)) {
				conditionType = pt_type_combinator_remove(scopeType.raw(), type);
			} else {
				zv::Args args{scopeType.raw(), type};
				conditionType = pt_type_combinator_intersect(2, args);
			}
			if (UNEXPECTED(conditionType.isUndef())) return zv::Val();
			bool equal = pt_call_type_equals(scopeType.raw(), conditionType.raw());
			if (UNEXPECTED(EG(exception))) return zv::Val();
			if (equal) {
				zval flag;
				ZVAL_TRUE(&flag);
				setKey(droppedNoOpConditions.table(), exprString, &flag);
				continue;
			}

			conditionExpressionTypes.separate();
			zval holder = createYesHolder(expr, conditionType.raw()).take();
			setKey(conditionExpressionTypes.table(), exprString, &holder);
			conditionOriginalTypes.separate();
			zval original = scopeType.take();
			setKey(conditionOriginalTypes.table(), exprString, &original);
		}

		if (zend_hash_num_elements(conditionExpressionTypes.table()) == 0) return zv::Val(zv::Arr::empty());

		zval *holderEntries = slot(slots::holderEntries);
		if (UNEXPECTED(Z_TYPE_P(holderEntries) != IS_ARRAY)) return uninitialized("holderEntries");
		zv::Arr holders = zv::Arr::empty();
		for (zv::ArrayEntry entry : zv::ArrRef(holderEntries)) {
			zval *tuple = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(tuple) != IS_ARRAY)) return malformedEntry("holder");
			zval *exprString = tupleAt(tuple, 0);
			zval *expr = tupleAt(tuple, 1);
			zval *type = tupleAt(tuple, 2);
			zval *pinnedTargetType = tupleAt(tuple, 3);
			if (UNEXPECTED(exprString == NULL || (Z_TYPE_P(exprString) != IS_STRING && Z_TYPE_P(exprString) != IS_LONG) || expr == NULL || Z_TYPE_P(expr) != IS_OBJECT || type == NULL || Z_TYPE_P(type) != IS_OBJECT || pinnedTargetType == NULL)) return malformedEntry("holder");

			// The target's only link to the antecedent was a no-op relation (e.g.
			// `$a === $b`) that got dropped, so the antecedent no longer constrains
			// it. Projecting a consequent onto it would fire unsoundly. Skip it.
			if (findKey(droppedNoOpConditions.table(), exprString) != NULL) continue;

			zv::Arr conditions = zv::Arr::copyOfTable(conditionExpressionTypes.table());
			zv::Val droppedSelfCondition;
			zval *self_ = findKey(conditions.table(), exprString);
			if (self_ != NULL) {
				ZVAL_DEREF(self_);
			}
			if (self_ != NULL && Z_TYPE_P(self_) != IS_NULL) {
				droppedSelfCondition = zv::Val::copyOf(zv::Ref(self_));
				conditions.separate();
				deleteKey(conditions.table(), exprString);
			}

			if (zend_hash_num_elements(conditions.table()) == 0) continue;

			zv::Val targetType;
			if (Z_TYPE_P(pinnedTargetType) != IS_NULL) {
				targetType = zv::Val::copyOf(zv::Ref(pinnedTargetType));
			} else {
				targetType = pt_mutating_scope_get_state_type(Z_OBJ_P(scope), Z_OBJ_P(expr));
				if (UNEXPECTED(targetType.isUndef())) return zv::Val();
			}
			if (UNEXPECTED(Z_TYPE_P(targetType.raw()) != IS_OBJECT)) return malformedEntry("holder");

			zval *holdersFromSureTypes = slot(slots::holdersFromSureTypes);
			if (UNEXPECTED(Z_TYPE_P(holdersFromSureTypes) != IS_TRUE && Z_TYPE_P(holdersFromSureTypes) != IS_FALSE)) return uninitialized("holdersFromSureTypes");
			zv::Val holderType;
			if (Z_TYPE_P(holdersFromSureTypes) == IS_TRUE) {
				zv::Args args{targetType.raw(), type};
				holderType = pt_type_combinator_intersect(2, args);
			} else {
				holderType = pt_type_combinator_remove(targetType.raw(), type);
			}
			if (UNEXPECTED(holderType.isUndef())) return zv::Val();

			// The dropped self-condition narrowed the target; without it the
			// holder must allow the values it excluded, or it over-narrows when
			// only the remaining conditions hold. So union back the complement.
			if (!droppedSelfCondition.isUndef()) {
				zval *originalType = findKey(conditionOriginalTypes.table(), exprString);
				if (UNEXPECTED(originalType == NULL)) return malformedEntry("holder");
				zval *droppedType = OBJ_PROP_NUM(Z_OBJ_P(droppedSelfCondition.raw()), PT_ETH_PROP_TYPE);
				zv::Val complement = pt_type_combinator_remove(originalType, droppedType);
				if (UNEXPECTED(complement.isUndef())) return zv::Val();
				if (!isNeverType(complement.raw())) {
					zv::Args args{holderType.raw(), complement.raw()};
					zv::Val united = pt_type_combinator_union(2, args);
					if (UNEXPECTED(united.isUndef())) return zv::Val();
					holderType = std::move(united);
				}
			}

			// These boolean-decomposition holders only refine an expression's
			// type in a future scope; they must never collapse it to never and
			// thereby mark the whole scope unreachable. A never result is an
			// artifact (e.g. removing a non-nullable property's full type after
			// swapping isset() narrowing), not a real contradiction.
			if (isNeverType(holderType.raw()) && !isNeverType(targetType.raw())) continue;

			zv::Val typeHolder = createYesHolder(expr, holderType.raw());
			zval holderZv;
			object_init_ex(&holderZv, pt_ce_cond_expr_holder);
			zv::Val holder = zv::Val::adopt(holderZv);
			zv::ObjRef holderObject(holder.raw());
			holderObject.propAtWrite(PT_CEH_PROP_CONDS, zv::Val(std::move(conditions)));
			holderObject.propAtWrite(PT_CEH_PROP_TYPEHOLDER, std::move(typeHolder));

			zv::Str key = zv::Str::adopt(pt_ceh_key_build(Z_ARRVAL_P(OBJ_PROP_NUM(holderObject.raw(), PT_CEH_PROP_CONDS)), OBJ_PROP_NUM(holderObject.raw(), PT_CEH_PROP_TYPEHOLDER)));
			if (UNEXPECTED(key.isNull())) return zv::Val();

			holders.separate();
			zval *bucket = findKey(holders.table(), exprString);
			if (bucket == NULL || Z_TYPE_P(bucket) == IS_NULL) {
				zval fresh;
				ZVAL_EMPTY_ARRAY(&fresh);
				setKey(holders.table(), exprString, &fresh);
				bucket = findKey(holders.table(), exprString);
			}
			ZVAL_DEREF(bucket);
			if (UNEXPECTED(Z_TYPE_P(bucket) != IS_ARRAY)) return malformedEntry("holder");
			SEPARATE_ARRAY(bucket);
			zval stored = holder.take();
			zend_symtable_update(Z_ARRVAL_P(bucket), key.get(), &stored);
		}

		return zv::Val(std::move(holders));
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
};

} // namespace phpstanturbo

using phpstanturbo::ConditionalExpressionHolderRecipe;

/* {{{ direct entries (support.h) */

zv::Val pt_conditional_expression_holder_recipe_new(zval *conditionEntries, zval *holderEntries, bool holdersFromSureTypes)
{
	if (UNEXPECTED(pt_ce_conditional_expression_holder_recipe == NULL)) {
		zend_throw_error(NULL, "phpstan_turbo: ConditionalExpressionHolderRecipe used before the shadowing classes were activated");
		return zv::Val();
	}
	return ConditionalExpressionHolderRecipe::create(conditionEntries, holderEntries, holdersFromSureTypes);
}

zv::Val pt_conditional_expression_holder_recipe_evaluate(zend_object *recipe, zval *scope)
{
	if (EXPECTED(recipe->ce == pt_ce_conditional_expression_holder_recipe && Z_TYPE_P(scope) == IS_OBJECT)) return ConditionalExpressionHolderRecipe(recipe).evaluate(scope);
	return pt_type_call(recipe, PT_LC("evaluate"), 1, scope);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_conditional_expression_holder_recipe()
{
	reg::Class cls("PHPStan\\Analyser\\ConditionalExpressionHolderRecipe");
	ptdecl::ConditionalExpressionHolderRecipe::declareClass(cls);
	ptdecl::ConditionalExpressionHolderRecipe::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *conditionEntries, *holderEntries;
		bool holdersFromSureTypes;
		if (!zp::parse<zp::Arr, zp::Arr, zp::Bool>(execute_data, conditionEntries, holderEntries, holdersFromSureTypes)) RETURN_THROWS();
		ConditionalExpressionHolderRecipe::construct(Z_OBJ_P(ZEND_THIS), conditionEntries, holderEntries, holdersFromSureTypes);
	});

	cls.method(sigs::evaluate, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope;
		if (!zp::parse<zp::Obj>(execute_data, scope)) RETURN_THROWS();
		PT_RETURN_VAL(ConditionalExpressionHolderRecipe(Z_OBJ_P(ZEND_THIS)).evaluate(scope));
	});

	cls.shadow(&pt_ce_conditional_expression_holder_recipe);
}

/* }}} */
