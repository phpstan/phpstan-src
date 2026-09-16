/*
 * PHPStanTurbo\NonNullabilityHelper — native implementation of
 * PHPStan\Analyser\ExprHandler\Helper\NonNullabilityHelper.
 *
 * A final DI service implementing PerFileAnalysisResettable: the
 * constructor keeps the twin's arginfo. The three ensure stacks live in the
 * twin's private array slots (generated declarations). applyPendingEnsure()
 * runs for every expression NodeScopeResolver processes, so its direct entry
 * answers the empty-stack case from the slot without a call.
 *
 * The closure ensureNonNullability() hands to the private
 * lookForExpressionCallback() never escapes: it is a C++ callback over the
 * by-reference accumulators it captures. EnsuredNonNullabilityResult and
 * EnsuredNonNullabilityResultExpression stay PHP for now and are reached
 * through the cached method sites in the block below.
 */

#include "support.h"
#include "generated/NonNullabilityHelper.h"

namespace slots = ptdecl::NonNullabilityHelper::slot;
namespace sigs = ptdecl::NonNullabilityHelper::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "AnalyserValues.h"

#include <initializer_list>

zend_class_entry *pt_ce_non_nullability_helper = nullptr;

namespace {

/* {{{ the PHP collaborators (one site each; switch to their direct entries
 * once they are ported) */

pt_method_site pt_nnh_get_specified_expressions_site;
pt_method_site pt_nnh_result_get_scope_site;
pt_method_site pt_nnh_get_expression_site;
pt_method_site pt_nnh_get_original_type_site;
pt_method_site pt_nnh_get_original_native_type_site;
pt_method_site pt_nnh_get_certainty_site;
pt_property_site pt_nnh_name_site;
pt_property_site pt_nnh_var_site;
pt_property_site pt_nnh_dim_site;
pt_property_site pt_nnh_class_site;
pt_property_site pt_nnh_items_site;
pt_property_site pt_nnh_item_value_site;

zv::Val callOn(pt_method_site &site, zval *object, const char *lcname, size_t len, const char *displayName)
{
	if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", displayName, zend_zval_value_name(object));
		return zv::Val();
	}
	return pt_call_method_cached(site, Z_OBJ_P(object), lcname, len, 0, NULL);
}

/* EnsuredNonNullabilityResult::getSpecifiedExpressions() / getScope() */
zv::Val resultGetSpecifiedExpressions(zval *result) { return callOn(pt_nnh_get_specified_expressions_site, result, PT_LC("getspecifiedexpressions"), "getSpecifiedExpressions"); }
zv::Val resultGetScope(zval *result) { return callOn(pt_nnh_result_get_scope_site, result, PT_LC("getscope"), "getScope"); }

/* EnsuredNonNullabilityResultExpression's getters */
zv::Val expressionGetExpression(zval *expression) { return callOn(pt_nnh_get_expression_site, expression, PT_LC("getexpression"), "getExpression"); }
zv::Val expressionGetOriginalType(zval *expression) { return callOn(pt_nnh_get_original_type_site, expression, PT_LC("getoriginaltype"), "getOriginalType"); }
zv::Val expressionGetOriginalNativeType(zval *expression) { return callOn(pt_nnh_get_original_native_type_site, expression, PT_LC("getoriginalnativetype"), "getOriginalNativeType"); }
zv::Val expressionGetCertainty(zval *expression) { return callOn(pt_nnh_get_certainty_site, expression, PT_LC("getcertainty"), "getCertainty"); }

/* new EnsuredNonNullabilityResult($scope, $specifiedExpressions) */
zv::Val newResult(zval *scope, zval *specifiedExpressions)
{
	zv::Args argv{scope, specifiedExpressions};
	return pt_type_new(PT_CLASS_ENSURED_NON_NULLABILITY_RESULT, 2, argv);
}

zv::Val newEmptyResult(zval *scope)
{
	zval empty;
	ZVAL_EMPTY_ARRAY(&empty);
	return newResult(scope, &empty);
}

/* new EnsuredNonNullabilityResultExpression($expression, $originalType,
 * $originalNativeType, $certainty) */
zv::Val newResultExpression(zval *expression, zval *originalType, zval *originalNativeType, zval *certainty)
{
	zv::Args argv{expression, originalType, originalNativeType, certainty};
	return pt_type_new(PT_CLASS_ENSURED_NON_NULLABILITY_RESULT_EXPRESSION, 4, argv);
}

/* }}} */

/* {{{ node shapes, types and arrays */

[[nodiscard]] inline bool isInstance(zval *value, int classIdx, bool &out)
{
	ZVAL_DEREF(value);
	if (Z_TYPE_P(value) != IS_OBJECT) {
		out = false;
		return true;
	}
	zend_class_entry *ce = pt_class(classIdx);
	if (UNEXPECTED(ce == NULL)) return false;
	out = instanceof_function(Z_OBJCE_P(value), ce);
	return true;
}

/* the value of $node->$name (dereferenced); NULL = pending exception */
zval *nodeProperty(pt_property_site &site, zval *node, const char *name, size_t len)
{
	ZVAL_DEREF(node);
	zval *slot = pt_property_cached(site, Z_OBJ_P(node), name, len);
	if (UNEXPECTED(slot == NULL)) {
		zend_throw_error(NULL, "phpstan_turbo: %s has no declared property $%s", ZSTR_VAL(Z_OBJCE_P(node)->name), name);
		return NULL;
	}
	ZVAL_DEINDIRECT(slot);
	ZVAL_DEREF(slot);
	if (UNEXPECTED(Z_TYPE_P(slot) == IS_UNDEF)) {
		zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(Z_OBJCE_P(node)->name), name);
		return NULL;
	}
	return slot;
}

/* $type->isNull() as a PT_TRI_* value; -1 = pending exception */
zend_long typeIsNull(zval *type)
{
	if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function isNull() on %s", zend_zval_value_name(type));
		return -1;
	}
	return pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_NULL, 0, NULL);
}

/* $a->equals($b); false = pending exception */
[[nodiscard]] bool typeEquals(zval *a, zval *b, bool &out)
{
	if (UNEXPECTED(Z_TYPE_P(a) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function equals() on %s", zend_zval_value_name(a));
		return false;
	}
	zv::Val equals = pt_type_op(Z_OBJ_P(a), PT_OP_EQUALS, 1, b);
	if (UNEXPECTED(equals.isUndef())) return false;
	out = Z_TYPE_P(equals.raw()) == IS_TRUE;
	return true;
}

/* TrinaryLogic::createYes() */
inline zval *trinaryYes()
{
	return pt_trinary_singleton(PT_TRI_YES);
}

/* $this->printExpr($expr) through the ExprPrinter slot */
zv::Val printExpr(zend_object *helper, zval *expr)
{
	zval *exprPrinter = OBJ_PROP_NUM(helper, slots::exprPrinter);
	if (UNEXPECTED(Z_TYPE_P(exprPrinter) != IS_OBJECT)) {
		zend_throw_error(NULL, "Typed property PHPStan\\Analyser\\ExprHandler\\Helper\\NonNullabilityHelper::$exprPrinter must not be accessed before initialization");
		return zv::Val();
	}
	zend_string *printed = pt_expr_printer_print(exprPrinter, Z_OBJ_P(expr));
	if (UNEXPECTED(printed == NULL)) return zv::Val();
	return zv::Val::adoptString(printed);
}

/* the element $array[$index] of a list slot, created as [] when missing,
 * separated for a write */
zval *writableIndex(zval *array, zend_ulong index)
{
	ZVAL_DEREF(array);
	if (UNEXPECTED(Z_TYPE_P(array) != IS_ARRAY)) {
		zval_ptr_dtor(array);
		ZVAL_EMPTY_ARRAY(array);
	}
	SEPARATE_ARRAY(array);
	zval *element = zend_hash_index_find(Z_ARRVAL_P(array), index);
	if (element == NULL) {
		zval empty;
		ZVAL_EMPTY_ARRAY(&empty);
		element = zend_hash_index_add_new(Z_ARRVAL_P(array), index, &empty);
	}
	ZVAL_DEREF(element);
	if (UNEXPECTED(Z_TYPE_P(element) != IS_ARRAY)) {
		zval_ptr_dtor(element);
		ZVAL_EMPTY_ARRAY(element);
	}
	SEPARATE_ARRAY(element);
	return element;
}

/* the element $array['<key>'] of an array, separated for a write, created
 * as [] when missing */
zval *writableKey(zval *array, const char *key, size_t len)
{
	zval *element = zend_hash_str_find(Z_ARRVAL_P(array), key, len);
	if (element == NULL) {
		zval empty;
		ZVAL_EMPTY_ARRAY(&empty);
		element = zend_hash_str_add_new(Z_ARRVAL_P(array), key, len, &empty);
	}
	ZVAL_DEREF(element);
	if (UNEXPECTED(Z_TYPE_P(element) != IS_ARRAY)) {
		zval_ptr_dtor(element);
		ZVAL_EMPTY_ARRAY(element);
	}
	SEPARATE_ARRAY(element);
	return element;
}

/* isset($table[$key]) with array-key semantics */
inline bool issetKey(HashTable *table, zend_string *key)
{
	zval *found = zend_symtable_find(table, key);
	if (found == NULL) return false;
	ZVAL_DEREF(found);
	return Z_TYPE_P(found) != IS_NULL;
}

/* the element $array[$index] for a read, NULL when missing or not an array */
zval *readIndex(zval *array, zend_ulong index)
{
	ZVAL_DEREF(array);
	if (Z_TYPE_P(array) != IS_ARRAY) return NULL;
	zval *element = zend_hash_index_find(Z_ARRVAL_P(array), index);
	if (element == NULL) return NULL;
	ZVAL_DEREF(element);
	return Z_TYPE_P(element) == IS_ARRAY ? element : NULL;
}

/* array_pop($slot) of a list property; the popped value (null when empty) */
zv::Val arrayPop(zval *slot)
{
	ZVAL_DEREF(slot);
	if (Z_TYPE_P(slot) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL_P(slot)) == 0) return zv::Val::null();
	SEPARATE_ARRAY(slot);
	HashTable *ht = Z_ARRVAL_P(slot);
	uint32_t idx = ht->nNumUsed;
	while (idx > 0) {
		idx--;
		zval *value;
		zend_ulong h;
		zend_string *key;
		if (HT_IS_PACKED(ht)) {
			value = &ht->arPacked[idx];
			h = idx;
			key = NULL;
		} else {
			Bucket *p = ht->arData + idx;
			value = &p->val;
			h = p->h;
			key = p->key;
		}
		if (Z_TYPE_P(value) == IS_UNDEF) continue;
		zv::Val popped = zv::Val::copyOf(zv::Ref(value).deref());
		if (key == NULL) {
			if ((zend_long) h == ht->nNextFreeElement - 1) {
				ht->nNextFreeElement--;
			}
			zend_hash_index_del(ht, h);
		} else {
			zend_hash_del(ht, key);
		}
		return popped;
	}
	return zv::Val::null();
}

/* }}} */

/* the by-reference captures of ensureNonNullability()'s closure */
struct EnsureCallbackState
{
	zv::Arr *specifiedExpressions;
	zval *pending;
	zval *originalScope;
};

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Helper\NonNullabilityHelper; UNDEF /
 * false = pending exception. */
class NonNullabilityHelper
{
public:
	explicit NonNullabilityHelper(zend_object *self) : self(self) {}

	void construct(zval *exprPrinter)
	{
		zv::ObjRef(self).propAtWrite(slots::exprPrinter, zv::Val::copyOf(zv::Ref(exprPrinter)));
	}

	/* Mirrors resetFileAnalysisState(). */
	void resetFileAnalysisState()
	{
		zv::ObjRef object(self);
		object.propAtWrite(slots::activeEnsures, zv::Val(zv::Arr::empty()));
		object.propAtWrite(slots::pendingEnsures, zv::Val(zv::Arr::empty()));
		object.propAtWrite(slots::lateEnsures, zv::Val(zv::Arr::empty()));
	}

	/* Mirrors getActiveEnsuredOriginalType(). */
	zv::Val getActiveEnsuredOriginalType(zval *expr, bool native)
	{
		zval *activeEnsures = OBJ_PROP_NUM(self, slots::activeEnsures);
		ZVAL_DEREF(activeEnsures);
		if (Z_TYPE_P(activeEnsures) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL_P(activeEnsures)) == 0) return zv::Val::null();

		zv::Val key = printExpr(self, expr);
		if (UNEXPECTED(key.isUndef())) return zv::Val();
		for (zend_long i = (zend_long) zend_hash_num_elements(Z_ARRVAL_P(activeEnsures)) - 1; i >= 0; i--) {
			zval *frame = readIndex(OBJ_PROP_NUM(self, slots::activeEnsures), (zend_ulong) i);
			if (frame == NULL) continue;
			zval *pair = zend_symtable_find(Z_ARRVAL_P(frame), Z_STR_P(key.raw()));
			if (pair == NULL) continue;
			ZVAL_DEREF(pair);
			if (Z_TYPE_P(pair) == IS_NULL) continue;
			zval *original = Z_TYPE_P(pair) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(pair), native ? 1 : 0) : NULL;
			if (original == NULL) {
				zend_error(E_WARNING, "Undefined array key %d", native ? 1 : 0);
				if (UNEXPECTED(EG(exception))) return zv::Val();
				return zv::Val::null();
			}
			return zv::Val::copyOf(zv::Ref(original).deref());
		}
		return zv::Val::null();
	}

	/* Mirrors ensureShallowNonNullability(). */
	zv::Val ensureShallowNonNullability(zval *scope, zval *originalScope, zval *exprToSpecify)
	{
		zv::Val result = doEnsureShallowNonNullability(scope, originalScope, exprToSpecify);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (UNEXPECTED(!pushActiveEnsure(result.raw(), NULL))) return zv::Val();
		return result;
	}

	/* Mirrors applyPendingEnsure(). */
	zv::Val applyPendingEnsure(zval *expr, zval *result)
	{
		zval *pendingEnsures = OBJ_PROP_NUM(self, slots::pendingEnsures);
		ZVAL_DEREF(pendingEnsures);
		if (EXPECTED(Z_TYPE_P(pendingEnsures) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL_P(pendingEnsures)) == 0)) return zv::Val::copyOf(zv::Ref(result));

		zv::Val key;
		zend_string *className = Z_OBJCE_P(expr)->name;
		for (zend_long i = (zend_long) zend_hash_num_elements(Z_ARRVAL_P(pendingEnsures)) - 1; i >= 0; i--) {
			zval *frame = readIndex(OBJ_PROP_NUM(self, slots::pendingEnsures), (zend_ulong) i);
			zval *classes = frame != NULL ? zend_hash_str_find(Z_ARRVAL_P(frame), PT_LC("classes")) : NULL;
			if (classes != NULL) {
				ZVAL_DEREF(classes);
			}
			// the node class screens the common case before the node is printed
			if (classes == NULL || Z_TYPE_P(classes) != IS_ARRAY || !issetKey(Z_ARRVAL_P(classes), className)) continue;
			if (key.isUndef()) {
				key = printExpr(self, expr);
				if (UNEXPECTED(key.isUndef())) return zv::Val();
			}
			zval *keys = zend_hash_str_find(Z_ARRVAL_P(frame), PT_LC("keys"));
			if (keys != NULL) {
				ZVAL_DEREF(keys);
			}
			if (keys == NULL || Z_TYPE_P(keys) != IS_ARRAY || !issetKey(Z_ARRVAL_P(keys), Z_STR_P(key.raw()))) continue;

			{
				zval *writableFrame = writableIndex(OBJ_PROP_NUM(self, slots::pendingEnsures), (zend_ulong) i);
				zval *writableKeys = writableKey(writableFrame, PT_LC("keys"));
				zend_symtable_del(Z_ARRVAL_P(writableKeys), Z_STR_P(key.raw()));
			}
			zv::Val scopeHold;
			zval *scopeRead = pt_expression_result_scope(result, scopeHold);
			if (UNEXPECTED(scopeRead == NULL)) return zv::Val();
			zv::Val scope = zv::Val::copyOf(zv::Ref(scopeRead));
			zend_long hasValue = pt_mutating_scope_has_expression_type(Z_OBJ_P(scope.raw()), expr);
			if (UNEXPECTED(hasValue < 0)) return zv::Val();
			// an earlier link printing the same way installed the device
			if (hasValue == PT_TRI_YES) return zv::Val::copyOf(zv::Ref(result));

			zv::Val type = pt_expression_result_get_type(result);
			if (UNEXPECTED(type.isUndef())) return zv::Val();
			zend_long isNull = typeIsNull(type.raw());
			if (UNEXPECTED(isNull < 0)) return zv::Val();
			if (isNull == PT_TRI_YES) return zv::Val::copyOf(zv::Ref(result));
			zv::Val typeWithoutNull = pt_type_combinator_remove_null(type.raw());
			if (UNEXPECTED(typeWithoutNull.isUndef())) return zv::Val();
			bool equals;
			if (UNEXPECTED(!typeEquals(type.raw(), typeWithoutNull.raw(), equals))) return zv::Val();
			if (equals) return zv::Val::copyOf(zv::Ref(result));

			zv::Val nativeType = pt_expression_result_get_native_type(result);
			if (UNEXPECTED(nativeType.isUndef())) return zv::Val();
			zv::Val nativeTypeWithoutNull = pt_type_combinator_remove_null(nativeType.raw());
			if (UNEXPECTED(nativeTypeWithoutNull.isUndef())) return zv::Val();
			{
				zv::Arr pair = zv::Arr::create(2);
				pair.push(type.ref());
				pair.push(nativeType.ref());
				zval *activeFrame = writableIndex(OBJ_PROP_NUM(self, slots::activeEnsures), (zend_ulong) i);
				zval pairZv = pair.take();
				zend_symtable_update(Z_ARRVAL_P(activeFrame), Z_STR_P(key.raw()), &pairZv);
			}
			{
				zv::Val late = newResultExpression(expr, type.raw(), nativeType.raw(), trinaryYes());
				if (UNEXPECTED(late.isUndef())) return zv::Val();
				zval *lateFrame = writableIndex(OBJ_PROP_NUM(self, slots::lateEnsures), (zend_ulong) i);
				zval lateZv = late.take();
				zend_hash_next_index_insert(Z_ARRVAL_P(lateFrame), &lateZv);
			}

			zv::Val beforeScopeHold;
			zval *beforeScope = pt_expression_result_before_scope(result, beforeScopeHold);
			if (UNEXPECTED(beforeScope == NULL)) return zv::Val();
			zv::Val devicedBeforeScope = pt_mutating_scope_specify_expression_type(Z_OBJ_P(beforeScope), Z_OBJ_P(expr), typeWithoutNull.raw(), nativeTypeWithoutNull.raw(), trinaryYes());
			if (UNEXPECTED(devicedBeforeScope.isUndef())) return zv::Val();
			zv::Val devicedScope = pt_mutating_scope_specify_expression_type(Z_OBJ_P(scope.raw()), Z_OBJ_P(expr), typeWithoutNull.raw(), nativeTypeWithoutNull.raw(), trinaryYes());
			if (UNEXPECTED(devicedScope.isUndef())) return zv::Val();
			return pt_expression_result_on_non_nullability_deviced_scopes(result, devicedBeforeScope.raw(), devicedScope.raw());
		}

		return zv::Val::copyOf(zv::Ref(result));
	}

	/* Mirrors ensureNonNullability(). */
	zv::Val ensureNonNullability(zval *scope, zval *expr)
	{
		zv::Arr specifiedExpressions = zv::Arr::empty();
		zv::Arr pending = emptyPending();
		EnsureCallbackState state = { &specifiedExpressions, pending.raw(), scope };
		zv::Val ensuredScope = lookForExpressionCallback(scope, expr, state, false);
		if (UNEXPECTED(ensuredScope.isUndef())) return zv::Val();

		zv::Val result = newResult(ensuredScope.raw(), specifiedExpressions.raw());
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (UNEXPECTED(!pushActiveEnsure(result.raw(), pending.raw()))) return zv::Val();
		return result;
	}

	/* Mirrors revertNonNullability(). */
	zv::Val revertNonNullability(zval *scopeArg, zval *specifiedExpressions)
	{
		(void) arrayPop(OBJ_PROP_NUM(self, slots::activeEnsures));
		(void) arrayPop(OBJ_PROP_NUM(self, slots::pendingEnsures));
		zv::Val lateEnsures = arrayPop(OBJ_PROP_NUM(self, slots::lateEnsures));
		if (lateEnsures.isNull()) {
			lateEnsures = zv::Val(zv::Arr::empty());
		}
		if (UNEXPECTED(!lateEnsures.ref().isArray())) {
			zend_type_error("array_merge(): Argument #2 must be of type array, %s given", zend_zval_value_name(lateEnsures.raw()));
			return zv::Val();
		}

		zv::Val scope = zv::Val::copyOf(zv::Ref(scopeArg));
		for (zval *list : { specifiedExpressions, lateEnsures.raw() }) {
			zv::Val held = zv::Val::copyOf(zv::Ref(list));
			for (auto entry : zv::ArrRef(held.raw())) {
				zval *specifiedExpressionResult = entry.value().deref().raw();
				zv::Val certainty = expressionGetCertainty(specifiedExpressionResult);
				if (UNEXPECTED(certainty.isUndef())) return zv::Val();
				zend_long certaintyValue = pt_type_trinary_value(certainty.raw());
				if (UNEXPECTED(certaintyValue < 0)) return zv::Val();
				zv::Val expression = expressionGetExpression(specifiedExpressionResult);
				if (UNEXPECTED(expression.isUndef())) return zv::Val();
				if (certaintyValue == PT_TRI_NO) {
					scope = pt_mutating_scope_invalidate_expression(Z_OBJ_P(scope.raw()), expression.raw());
					if (UNEXPECTED(scope.isUndef())) return zv::Val();
					continue;
				}
				zv::Val originalType = expressionGetOriginalType(specifiedExpressionResult);
				if (UNEXPECTED(originalType.isUndef())) return zv::Val();
				zv::Val originalNativeType = expressionGetOriginalNativeType(specifiedExpressionResult);
				if (UNEXPECTED(originalNativeType.isUndef())) return zv::Val();
				zv::Val specifiedCertainty = expressionGetCertainty(specifiedExpressionResult);
				if (UNEXPECTED(specifiedCertainty.isUndef())) return zv::Val();
				scope = pt_mutating_scope_specify_expression_type(Z_OBJ_P(scope.raw()), Z_OBJ_P(expression.raw()), originalType.raw(), originalNativeType.raw(), specifiedCertainty.raw());
				if (UNEXPECTED(scope.isUndef())) return zv::Val();
			}
		}
		return scope;
	}

private:
	zend_object *self;

	/* ['keys' => [], 'classes' => []] */
	static zv::Arr emptyPending()
	{
		zv::Arr pending = zv::Arr::create(2);
		pending.set("keys", zv::Val(zv::Arr::empty()));
		pending.set("classes", zv::Val(zv::Arr::empty()));
		return pending;
	}

	/* the private pushActiveEnsure(); pending NULL = the default */
	[[nodiscard]] bool pushActiveEnsure(zval *result, zval *pending)
	{
		zv::Arr originals = zv::Arr::empty();
		zv::Val specifiedExpressions = resultGetSpecifiedExpressions(result);
		if (UNEXPECTED(specifiedExpressions.isUndef())) return false;
		if (UNEXPECTED(!specifiedExpressions.ref().isArray())) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(specifiedExpressions.raw()));
			if (UNEXPECTED(EG(exception))) return false;
		} else {
			for (auto entry : zv::ArrRef(specifiedExpressions.raw())) {
				zval *specifiedExpression = entry.value().deref().raw();
				zv::Val expression = expressionGetExpression(specifiedExpression);
				if (UNEXPECTED(expression.isUndef())) return false;
				zv::Val key = printExpr(self, expression.raw());
				if (UNEXPECTED(key.isUndef())) return false;
				zv::Val originalType = expressionGetOriginalType(specifiedExpression);
				if (UNEXPECTED(originalType.isUndef())) return false;
				zv::Val originalNativeType = expressionGetOriginalNativeType(specifiedExpression);
				if (UNEXPECTED(originalNativeType.isUndef())) return false;
				zv::Arr pair = zv::Arr::create(2);
				pair.push(std::move(originalType));
				pair.push(std::move(originalNativeType));
				originals.set(Z_STR_P(key.raw()), std::move(pair));
			}
		}
		zv::ArrRef(OBJ_PROP_NUM(self, slots::activeEnsures)).push(originals.ref());
		if (pending != NULL) {
			zv::ArrRef(OBJ_PROP_NUM(self, slots::pendingEnsures)).push(zv::Ref(pending));
		} else {
			zv::Arr defaultPending = emptyPending();
			zv::ArrRef(OBJ_PROP_NUM(self, slots::pendingEnsures)).push(defaultPending.ref());
		}
		zv::Arr empty = zv::Arr::empty();
		zv::ArrRef(OBJ_PROP_NUM(self, slots::lateEnsures)).push(empty.ref());
		return true;
	}

	/* the private isPricedFromState() */
	[[nodiscard]] bool isPricedFromState(zval *expr, zval *scope, bool &out)
	{
		bool ok = true;
		pt_engine_with_stack([&]() { ok = isPricedFromStateBody(expr, scope, out); });
		return ok;
	}

	[[nodiscard]] bool isPricedFromStateBody(zval *expr, zval *scope, bool &out)
	{
		out = false;
		bool is;
		if (UNEXPECTED(!isInstance(expr, PT_CLASS_VARIABLE, is))) return false;
		if (is) {
			zval *name = nodeProperty(pt_nnh_name_site, expr, PT_LC("name"));
			if (UNEXPECTED(name == NULL)) return false;
			out = Z_TYPE_P(name) == IS_STRING;
			return true;
		}
		zend_long hasValue = pt_mutating_scope_has_expression_type(Z_OBJ_P(scope), expr);
		if (UNEXPECTED(hasValue < 0)) return false;
		if (hasValue == PT_TRI_YES) {
			out = true;
			return true;
		}
		if (UNEXPECTED(!isInstance(expr, PT_CLASS_ARRAY_DIM_FETCH, is))) return false;
		if (is) {
			zval *dim = nodeProperty(pt_nnh_dim_site, expr, PT_LC("dim"));
			if (UNEXPECTED(dim == NULL)) return false;
			if (Z_TYPE_P(dim) == IS_NULL) return true;
			zval *var = nodeProperty(pt_nnh_var_site, expr, PT_LC("var"));
			if (UNEXPECTED(var == NULL)) return false;
			if (UNEXPECTED(!isPricedFromState(var, scope, out))) return false;
			if (!out) return true;
			zval *dimAgain = nodeProperty(pt_nnh_dim_site, expr, PT_LC("dim"));
			if (UNEXPECTED(dimAgain == NULL)) return false;
			return isPricedFromState(dimAgain, scope, out);
		}
		if (UNEXPECTED(!isInstance(expr, PT_CLASS_PROPERTY_FETCH, is))) return false;
		if (!is && UNEXPECTED(!isInstance(expr, PT_CLASS_NULLSAFE_PROPERTY_FETCH, is))) return false;
		if (is) {
			zval *name = nodeProperty(pt_nnh_name_site, expr, PT_LC("name"));
			if (UNEXPECTED(name == NULL)) return false;
			bool isIdentifier;
			if (UNEXPECTED(!isInstance(name, PT_CLASS_IDENTIFIER, isIdentifier))) return false;
			if (!isIdentifier) return true;
			zval *var = nodeProperty(pt_nnh_var_site, expr, PT_LC("var"));
			if (UNEXPECTED(var == NULL)) return false;
			return isPricedFromState(var, scope, out);
		}
		if (UNEXPECTED(!isInstance(expr, PT_CLASS_STATIC_PROPERTY_FETCH, is))) return false;
		if (is) {
			zval *name = nodeProperty(pt_nnh_name_site, expr, PT_LC("name"));
			if (UNEXPECTED(name == NULL)) return false;
			bool isVarLikeIdentifier;
			if (UNEXPECTED(!isInstance(name, PT_CLASS_VAR_LIKE_IDENTIFIER, isVarLikeIdentifier))) return false;
			if (!isVarLikeIdentifier) return true;
			zval *classNode = nodeProperty(pt_nnh_class_site, expr, PT_LC("class"));
			if (UNEXPECTED(classNode == NULL)) return false;
			bool isName;
			if (UNEXPECTED(!isInstance(classNode, PT_CLASS_NAME, isName))) return false;
			if (isName) {
				out = true;
				return true;
			}
			return isPricedFromState(classNode, scope, out);
		}
		for (int classIdx : { PT_CLASS_SCALAR_STRING, PT_CLASS_SCALAR_INT, PT_CLASS_SCALAR_FLOAT, PT_CLASS_CONST_FETCH }) {
			if (UNEXPECTED(!isInstance(expr, classIdx, out))) return false;
			if (out) return true;
		}
		if (UNEXPECTED(!isInstance(expr, PT_CLASS_CLASS_CONST_FETCH, is))) return false;
		if (!is) return true;
		zval *classNode = nodeProperty(pt_nnh_class_site, expr, PT_LC("class"));
		if (UNEXPECTED(classNode == NULL)) return false;
		bool isName;
		if (UNEXPECTED(!isInstance(classNode, PT_CLASS_NAME, isName))) return false;
		if (!isName) return true;
		zval *name = nodeProperty(pt_nnh_name_site, expr, PT_LC("name"));
		if (UNEXPECTED(name == NULL)) return false;
		return isInstance(name, PT_CLASS_IDENTIFIER, out);
	}

	/* the private doEnsureShallowNonNullability() */
	zv::Val doEnsureShallowNonNullability(zval *scope, zval *originalScope, zval *exprToSpecify)
	{
		zend_object *exprObject = Z_OBJ_P(exprToSpecify);
		// the expression has not been processed into the storage yet (this runs
		// before processExprNode) - derive its current type from the scope's
		// tracked state instead of pricing the node on demand.
		zv::Val exprType = pt_mutating_scope_get_state_type(Z_OBJ_P(scope), exprObject);
		if (UNEXPECTED(exprType.isUndef())) return zv::Val();
		zend_long isNull = typeIsNull(exprType.raw());
		if (UNEXPECTED(isNull < 0)) return zv::Val();
		if (isNull == PT_TRI_YES) return newEmptyResult(scope);

		zend_long hasExpressionTypeValue = pt_mutating_scope_has_expression_type(Z_OBJ_P(originalScope), exprToSpecify);
		if (UNEXPECTED(hasExpressionTypeValue < 0)) return zv::Val();
		/* the TrinaryLogic singleton the answer is */
		zval *hasExpressionType = pt_trinary_singleton(hasExpressionTypeValue);

		zv::Val exprTypeWithoutNull = pt_type_combinator_remove_null(exprType.raw());
		if (UNEXPECTED(exprTypeWithoutNull.isUndef())) return zv::Val();
		bool equals;
		if (UNEXPECTED(!typeEquals(exprType.raw(), exprTypeWithoutNull.raw(), equals))) return zv::Val();
		if (equals) {
			zv::Val originalExprType = pt_mutating_scope_get_state_type(Z_OBJ_P(originalScope), exprObject);
			if (UNEXPECTED(originalExprType.isUndef())) return zv::Val();
			bool originalEquals;
			if (UNEXPECTED(!typeEquals(originalExprType.raw(), exprTypeWithoutNull.raw(), originalEquals))) return zv::Val();
			if (!originalEquals) {
				zv::Val nativeOriginalScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(originalScope));
				if (UNEXPECTED(nativeOriginalScope.isUndef())) return zv::Val();
				zv::Val originalNativeType = pt_mutating_scope_get_state_type(Z_OBJ_P(nativeOriginalScope.raw()), exprObject);
				if (UNEXPECTED(originalNativeType.isUndef())) return zv::Val();
				zv::Val specifiedExpression = newResultExpression(exprToSpecify, originalExprType.raw(), originalNativeType.raw(), hasExpressionType);
				if (UNEXPECTED(specifiedExpression.isUndef())) return zv::Val();
				zv::Arr specifiedExpressions = zv::Arr::create(1);
				specifiedExpressions.push(std::move(specifiedExpression));
				return newResult(scope, specifiedExpressions.raw());
			}
			return newEmptyResult(scope);
		}

		zv::Arr specifiedExpressions = zv::Arr::empty();

		// When narrowing an ArrayDimFetch, specifyExpressionType also recursively
		// narrows the parent array's offset type via intersection with HasOffsetValueType.
		// To properly revert this, we must also save and restore the parent expression's type.
		bool isArrayDimFetch;
		if (UNEXPECTED(!isInstance(exprToSpecify, PT_CLASS_ARRAY_DIM_FETCH, isArrayDimFetch))) return zv::Val();
		if (isArrayDimFetch) {
			zval *dim = nodeProperty(pt_nnh_dim_site, exprToSpecify, PT_LC("dim"));
			if (UNEXPECTED(dim == NULL)) return zv::Val();
			if (Z_TYPE_P(dim) != IS_NULL) {
				zval *parentExpr = nodeProperty(pt_nnh_var_site, exprToSpecify, PT_LC("var"));
				if (UNEXPECTED(parentExpr == NULL)) return zv::Val();
				zv::Val parentHeld = zv::Val::copyOf(zv::Ref(parentExpr));
				zv::Val parentType = pt_mutating_scope_get_state_type(Z_OBJ_P(scope), Z_OBJ_P(parentHeld.raw()));
				if (UNEXPECTED(parentType.isUndef())) return zv::Val();
				zv::Val nativeScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scope));
				if (UNEXPECTED(nativeScope.isUndef())) return zv::Val();
				zv::Val parentNativeType = pt_mutating_scope_get_state_type(Z_OBJ_P(nativeScope.raw()), Z_OBJ_P(parentHeld.raw()));
				if (UNEXPECTED(parentNativeType.isUndef())) return zv::Val();
				zend_long parentHas = pt_mutating_scope_has_expression_type(Z_OBJ_P(originalScope), parentHeld.raw());
				if (UNEXPECTED(parentHas < 0)) return zv::Val();
				zv::Val parentExpression = newResultExpression(parentHeld.raw(), parentType.raw(), parentNativeType.raw(), pt_trinary_singleton(parentHas));
				if (UNEXPECTED(parentExpression.isUndef())) return zv::Val();
				specifiedExpressions.push(std::move(parentExpression));
			}
		}

		// Keep the "might not be defined" certainty of variables so that rules
		// reporting possibly undefined variables still see it. For any other
		// expression a Maybe certainty would make the narrowed type invisible to
		// Scope::getType(), throwing the narrowing away.
		zval *certainty = trinaryYes();
		if (hasExpressionTypeValue == PT_TRI_MAYBE) {
			bool isVariable;
			if (UNEXPECTED(!isInstance(exprToSpecify, PT_CLASS_VARIABLE, isVariable))) return zv::Val();
			if (isVariable) {
				certainty = hasExpressionType;
			}
		}

		zv::Val nativeScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scope));
		if (UNEXPECTED(nativeScope.isUndef())) return zv::Val();
		zv::Val nativeType = pt_mutating_scope_get_state_type(Z_OBJ_P(nativeScope.raw()), exprObject);
		if (UNEXPECTED(nativeType.isUndef())) return zv::Val();
		zv::Val specifiedExpression = newResultExpression(exprToSpecify, exprType.raw(), nativeType.raw(), certainty);
		if (UNEXPECTED(specifiedExpression.isUndef())) return zv::Val();
		specifiedExpressions.push(std::move(specifiedExpression));
		zv::Val nativeTypeWithoutNull = pt_type_combinator_remove_null(nativeType.raw());
		if (UNEXPECTED(nativeTypeWithoutNull.isUndef())) return zv::Val();
		zv::Val specifiedScope = pt_mutating_scope_specify_expression_type(Z_OBJ_P(scope), exprObject, exprTypeWithoutNull.raw(), nativeTypeWithoutNull.raw(), certainty);
		if (UNEXPECTED(specifiedScope.isUndef())) return zv::Val();

		return newResult(specifiedScope.raw(), specifiedExpressions.raw());
	}

	/* the closure of ensureNonNullability(): function ($scope, $expr) use
	 * (&$specifiedExpressions, &$pending, $originalScope) */
	zv::Val ensureCallback(zval *scope, zval *expr, EnsureCallbackState &state)
	{
		// a link the scope cannot price from its state has no type before
		// its walk: device it when the walk completes instead of pricing
		// the node ahead of its turn
		bool priced;
		if (UNEXPECTED(!instanceof_function(Z_OBJCE_P(scope), pt_ce_mutating_scope))) {
			zend_type_error("PHPStan\\Analyser\\ExprHandler\\Helper\\NonNullabilityHelper::isPricedFromState(): Argument #2 ($scope) must be of type PHPStan\\Analyser\\MutatingScope, %s given", zend_zval_value_name(scope));
			return zv::Val();
		}
		if (UNEXPECTED(!isPricedFromState(expr, scope, priced))) return zv::Val();
		if (!priced) {
			zv::Val key = printExpr(self, expr);
			if (UNEXPECTED(key.isUndef())) return zv::Val();
			SEPARATE_ARRAY(state.pending);
			zval flag;
			ZVAL_TRUE(&flag);
			zval *keys = writableKey(state.pending, PT_LC("keys"));
			zend_symtable_update(Z_ARRVAL_P(keys), Z_STR_P(key.raw()), &flag);
			zval *classes = writableKey(state.pending, PT_LC("classes"));
			zend_hash_update(Z_ARRVAL_P(classes), Z_OBJCE_P(expr)->name, &flag);
			return zv::Val::copyOf(zv::Ref(scope));
		}

		zv::Val result = doEnsureShallowNonNullability(scope, state.originalScope, expr);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		zv::Val specifiedExpressions = resultGetSpecifiedExpressions(result.raw());
		if (UNEXPECTED(specifiedExpressions.isUndef())) return zv::Val();
		if (specifiedExpressions.ref().isArray()) {
			for (auto entry : zv::ArrRef(specifiedExpressions.raw())) {
				state.specifiedExpressions->push(entry.value().deref());
			}
		}
		return resultGetScope(result.raw());
	}

	/* the private lookForExpressionCallback() with ensureNonNullability()'s
	 * closure */
	zv::Val lookForExpressionCallback(zval *scopeArg, zval *expr, EnsureCallbackState &state, bool includeExpr)
	{
		zv::Val result;
		pt_engine_with_stack([&]() { result = lookForExpressionCallbackBody(scopeArg, expr, state, includeExpr); });
		return result;
	}

	zv::Val lookForExpressionCallbackBody(zval *scopeArg, zval *expr, EnsureCallbackState &state, bool includeExpr)
	{
		zv::Val scope = zv::Val::copyOf(zv::Ref(scopeArg));
		// $includeExpr is false only for the outermost operand: ensuring its chain
		// links non-null lets it be walked without spurious "possibly null" noise,
		// but the operand's own value must keep its real (nullable) type
		bool isArrayDimFetch;
		if (UNEXPECTED(!isInstance(expr, PT_CLASS_ARRAY_DIM_FETCH, isArrayDimFetch))) return zv::Val();
		if (includeExpr) {
			bool apply = true;
			if (isArrayDimFetch) {
				zval *dim = nodeProperty(pt_nnh_dim_site, expr, PT_LC("dim"));
				if (UNEXPECTED(dim == NULL)) return zv::Val();
				apply = Z_TYPE_P(dim) != IS_NULL;
			}
			for (int classIdx : { PT_CLASS_NEW, PT_CLASS_ARRAY_EXPR, PT_CLASS_CLOSURE_EXPR, PT_CLASS_ARROW_FUNCTION, PT_CLASS_SCALAR }) {
				if (!apply) break;
				bool is;
				if (UNEXPECTED(!isInstance(expr, classIdx, is))) return zv::Val();
				apply = !is;
			}
			if (apply) {
				scope = ensureCallback(scope.raw(), expr, state);
				if (UNEXPECTED(scope.isUndef())) return zv::Val();
				if (UNEXPECTED(!scope.ref().isObject() || !instanceof_function(Z_OBJCE_P(scope.raw()), pt_ce_mutating_scope))) {
					zend_type_error("PHPStan\\Analyser\\ExprHandler\\Helper\\NonNullabilityHelper::lookForExpressionCallback(): Argument #1 ($scope) must be of type PHPStan\\Analyser\\MutatingScope, %s given", zend_zval_value_name(scope.raw()));
					return zv::Val();
				}
			}
		}

		zval *next = NULL;
		bool is;
		if (isArrayDimFetch) {
			next = nodeProperty(pt_nnh_var_site, expr, PT_LC("var"));
			if (UNEXPECTED(next == NULL)) return zv::Val();
		} else {
			if (UNEXPECTED(!isInstance(expr, PT_CLASS_PROPERTY_FETCH, is))) return zv::Val();
			if (!is && UNEXPECTED(!isInstance(expr, PT_CLASS_NULLSAFE_PROPERTY_FETCH, is))) return zv::Val();
			if (is) {
				next = nodeProperty(pt_nnh_var_site, expr, PT_LC("var"));
				if (UNEXPECTED(next == NULL)) return zv::Val();
			} else {
				if (UNEXPECTED(!isInstance(expr, PT_CLASS_STATIC_PROPERTY_FETCH, is))) return zv::Val();
				if (is) {
					zval *classNode = nodeProperty(pt_nnh_class_site, expr, PT_LC("class"));
					if (UNEXPECTED(classNode == NULL)) return zv::Val();
					bool isExpr;
					if (UNEXPECTED(!isInstance(classNode, PT_CLASS_EXPR, isExpr))) return zv::Val();
					if (isExpr) {
						next = classNode;
					}
				} else {
					if (UNEXPECTED(!isInstance(expr, PT_CLASS_LIST_EXPR, is))) return zv::Val();
					if (is) {
						zval *items = nodeProperty(pt_nnh_items_site, expr, PT_LC("items"));
						if (UNEXPECTED(items == NULL)) return zv::Val();
						if (UNEXPECTED(Z_TYPE_P(items) != IS_ARRAY)) {
							zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(items));
							if (UNEXPECTED(EG(exception))) return zv::Val();
							return scope;
						}
						zv::Val itemsHeld = zv::Val::copyOf(zv::Ref(items));
						for (auto entry : zv::ArrRef(itemsHeld.raw())) {
							zval *item = entry.value().deref().raw();
							if (Z_TYPE_P(item) == IS_NULL) continue;
							zval *value = nodeProperty(pt_nnh_item_value_site, item, PT_LC("value"));
							if (UNEXPECTED(value == NULL)) return zv::Val();
							zv::Val valueHeld = zv::Val::copyOf(zv::Ref(value));
							scope = lookForExpressionCallback(scope.raw(), valueHeld.raw(), state, true);
							if (UNEXPECTED(scope.isUndef())) return zv::Val();
						}
						return scope;
					}
				}
			}
		}

		if (next != NULL) {
			zv::Val nextHeld = zv::Val::copyOf(zv::Ref(next));
			return lookForExpressionCallback(scope.raw(), nextHeld.raw(), state, true);
		}
		return scope;
	}
};

} // namespace phpstanturbo

using phpstanturbo::NonNullabilityHelper;

/* {{{ direct entries for native callers (support.h) */

/* the assignment handlers' (AssignHandler.cpp) */
zv::Val pt_non_nullability_helper_ensure_non_nullability(zval *helper, zval *scope, zval *expr)
{
	if (EXPECTED(Z_OBJCE_P(helper) == pt_ce_non_nullability_helper)) return NonNullabilityHelper(Z_OBJ_P(helper)).ensureNonNullability(scope, expr);
	zv::Args argv{scope, expr};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("ensurenonnullability"), 2, argv);
}

zv::Val pt_non_nullability_helper_apply_pending_ensure(zval *helper, zval *expr, zval *result)
{
	if (EXPECTED(Z_OBJCE_P(helper) == pt_ce_non_nullability_helper)) {
		zval *pendingEnsures = OBJ_PROP_NUM(Z_OBJ_P(helper), slots::pendingEnsures);
		if (EXPECTED(Z_TYPE_P(pendingEnsures) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(pendingEnsures)) == 0)) return zv::Val::copyOf(zv::Ref(result));
		return NonNullabilityHelper(Z_OBJ_P(helper)).applyPendingEnsure(expr, result);
	}
	zv::Args argv{expr, result};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("applypendingensure"), 2, argv);
}

bool pt_non_nullability_helper_reset_file_analysis_state(zval *resettable)
{
	if (Z_OBJCE_P(resettable) == pt_ce_non_nullability_helper) {
		NonNullabilityHelper(Z_OBJ_P(resettable)).resetFileAnalysisState();
		return true;
	}
	return !pt_type_call(Z_OBJ_P(resettable), PT_LC("resetfileanalysisstate"), 0, NULL).isUndef();
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_non_nullability_helper()
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Helper\\NonNullabilityHelper");
	ptdecl::NonNullabilityHelper::declareClass(cls);
	ptdecl::NonNullabilityHelper::declareProperties(cls);

	/* the real parameter class name: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *exprPrinter;
		if (!zp::parse<zp::Obj>(execute_data, exprPrinter)) RETURN_THROWS();
		NonNullabilityHelper(Z_OBJ_P(ZEND_THIS)).construct(exprPrinter);
	});

	cls.method(sigs::resetFileAnalysisState, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		NonNullabilityHelper(Z_OBJ_P(ZEND_THIS)).resetFileAnalysisState();
	});

	cls.method(sigs::getActiveEnsuredOriginalType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		bool native;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_BOOL(native)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(NonNullabilityHelper(Z_OBJ_P(ZEND_THIS)).getActiveEnsuredOriginalType(expr, native));
	});

	cls.method(sigs::ensureShallowNonNullability, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *originalScope, *exprToSpecify;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(originalScope)
			Z_PARAM_OBJECT(exprToSpecify)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(NonNullabilityHelper(Z_OBJ_P(ZEND_THIS)).ensureShallowNonNullability(scope, originalScope, exprToSpecify));
	});

	cls.method(sigs::applyPendingEnsure, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *result;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(result)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(NonNullabilityHelper(Z_OBJ_P(ZEND_THIS)).applyPendingEnsure(expr, result));
	});

	cls.method(sigs::ensureNonNullability, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(expr)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(NonNullabilityHelper(Z_OBJ_P(ZEND_THIS)).ensureNonNullability(scope, expr));
	});

	cls.method(sigs::revertNonNullability, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *specifiedExpressions;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_ARRAY(specifiedExpressions)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(NonNullabilityHelper(Z_OBJ_P(ZEND_THIS)).revertNonNullability(scope, specifiedExpressions));
	});

	cls.shadow(&pt_ce_non_nullability_helper);
}

/* }}} */
