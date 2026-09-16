/*
 * PHPStanTurbo\SpecifiedTypes — native implementation of
 * PHPStan\Analyser\SpecifiedTypes.
 *
 * When the extension is active, PHPStan\Analyser\SpecifiedTypes is this
 * class, declared under that name at activation (final, like the twin). The
 * state lives in the twin's property slots (generated declarations); the
 * with*()/set*() copies are engine clones of the object with the changed
 * slots written, exactly the `$self = clone $this` of the twin. The merges
 * (intersectWith(), unionWith() and the private term algebra behind them)
 * run natively over the twin's arrays, calling the native TypeCombinator
 * and the Type ops directly; the deferred augments and conditional-holder
 * recipes it carries stay opaque values, as in the twin.
 *
 * emptySpecifyCallback() hands out one process-wide Closure over a
 * PHPStanTurbo\NativeCallback holder (TypeTraits.cpp) whose body is
 * `new self()`, cached in the twin's own static $emptySpecifyCallback slot:
 * identity per process is the twin's, and a native caller invoking it
 * (ExpressionResult::getSpecifiedTypes() through pt_type_call_callable())
 * enters the body without an engine frame.
 *
 * Native callers (MutatingScope::applySpecifiedTypes(), the handlers) use
 * the pt_specified_types_* direct entries declared in support.h.
 */

#include "support.h"
#include "generated/SpecifiedTypes.h"

namespace slots = ptdecl::SpecifiedTypes::slot;
namespace sigs = ptdecl::SpecifiedTypes::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"

zend_class_entry *pt_ce_specified_types = NULL;

namespace {

/* SpecifiedTypes::ALTERNATIVE_TERMS_LIMIT */
constexpr uint32_t ALTERNATIVE_TERMS_LIMIT = 32;

/* the twin's `private static ?Closure $emptySpecifyCallback` slot (borrowed;
 * resolved once per activated class, as TemplateTypeVariance.cpp resolves
 * its registry) */
zend_class_entry *pt_st_callback_ce = nullptr;
zval *pt_st_callback_slot = nullptr;

zval *emptySpecifyCallbackSlot()
{
	zend_class_entry *ce = pt_ce_specified_types;
	if (UNEXPECTED(pt_st_callback_ce != ce)) {
		ZEND_ASSERT(ce != NULL);
		if (CE_STATIC_MEMBERS(ce) == NULL) {
			zend_class_init_statics(ce);
		}
		zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&ce->properties_info, PT_LC("emptySpecifyCallback"));
		ZEND_ASSERT(info != NULL && (info->flags & ZEND_ACC_STATIC) != 0);
		pt_st_callback_slot = CE_STATIC_MEMBERS(ce) + info->offset;
		pt_st_callback_ce = ce;
	}
	return pt_st_callback_slot;
}

/* a borrowed zval as a nullable operand: NULL for PHP null (and a missing
 * element), the dereferenced value otherwise */
zval *nullable(zval *value)
{
	if (value == NULL) return NULL;
	ZVAL_DEREF(value);
	return Z_TYPE_P(value) == IS_NULL ? NULL : value;
}

/* $array[$index] of a borrowed value that may not be an array; NULL when
 * absent */
zval *elementAt(zval *array, zend_ulong index)
{
	if (array == NULL) return NULL;
	ZVAL_DEREF(array);
	if (Z_TYPE_P(array) != IS_ARRAY) return NULL;
	return zend_hash_index_find(Z_ARRVAL_P(array), index);
}

/* $table[$key] of an array key taken from another array (a string key of a
 * PHP array is never numeric, so no symtable coercion applies); NULL when
 * absent */
zval *entryAt(zval *table, zend_string *skey, zend_ulong h)
{
	if (Z_TYPE_P(table) != IS_ARRAY) return NULL;
	return skey != NULL ? zend_hash_find(Z_ARRVAL_P(table), skey) : zend_hash_index_find(Z_ARRVAL_P(table), h);
}

/* isset($table[$key]) */
bool issetAt(zval *table, zend_string *skey, zend_ulong h)
{
	return nullable(entryAt(table, skey, h)) != NULL;
}

/* $table[$key][1] ?? null */
zval *secondOfEntryAt(zval *table, zend_string *skey, zend_ulong h)
{
	return nullable(elementAt(entryAt(table, skey, h), 1));
}

/* $table[$key] = $value (owned) */
void setAt(zv::Arr &table, zend_string *skey, zend_ulong h, zv::Val value)
{
	table.separate();
	zval v = value.take();
	if (skey != NULL) {
		zend_hash_update(table.table(), skey, &v);
	} else {
		zend_hash_index_update(table.table(), h, &v);
	}
}

/* an owned copy of a borrowed element, PHP null for a missing one */
zv::Val copyOrNull(zval *value)
{
	if (value == NULL) return zv::Val::null();
	return zv::Val::copyOf(zv::Ref(value).deref());
}

/* [$first, $second] with null for NULL (both borrowed) */
zv::Val pairOf(zval *first, zval *second)
{
	zv::Arr pair = zv::Arr::create(2);
	zval item;
	if (first != NULL) {
		ZVAL_COPY(&item, first);
	} else {
		ZVAL_NULL(&item);
	}
	zend_hash_next_index_insert_new(pair.table(), &item);
	if (second != NULL) {
		ZVAL_COPY(&item, second);
	} else {
		ZVAL_NULL(&item);
	}
	zend_hash_next_index_insert_new(pair.table(), &item);
	return zv::Val(std::move(pair));
}

/* array_merge($a, $b) of two borrowed arrays (a non-array counts as empty):
 * string keys overwrite, integer keys renumber */
zv::Val arrayMerge(zval *a, zval *b)
{
	uint32_t hint = (Z_TYPE_P(a) == IS_ARRAY ? zend_hash_num_elements(Z_ARRVAL_P(a)) : 0) + (Z_TYPE_P(b) == IS_ARRAY ? zend_hash_num_elements(Z_ARRVAL_P(b)) : 0);
	if (hint == 0) return zv::Val(zv::Arr::empty());
	zv::Arr merged = zv::Arr::create(hint);
	for (zval *source : { a, b }) {
		if (Z_TYPE_P(source) != IS_ARRAY) continue;
		for (zv::ArrayEntry entry : zv::ArrRef(source)) {
			zval *value = entry.value().raw();
			/* array_merge() unwraps a reference nobody else holds */
			if (Z_ISREF_P(value) && Z_REFCOUNT_P(value) == 1) {
				value = Z_REFVAL_P(value);
			}
			if (entry.hasStringKey()) {
				merged.separate();
				Z_TRY_ADDREF_P(value);
				zend_hash_update(merged.table(), entry.stringKey(), value);
			} else {
				merged.push(zv::Ref(value));
			}
		}
	}
	return zv::Val(std::move(merged));
}

/* TypeCombinator::union(...$types) / intersect(...$types) over a PHP list */
zv::Val combineAll(bool isUnion, HashTable *types)
{
	if (EXPECTED(HT_IS_PACKED(types) && HT_IS_WITHOUT_HOLES(types))) {
		uint32_t count = zend_hash_num_elements(types);
		return isUnion ? pt_type_combinator_union(count, types->arPacked) : pt_type_combinator_intersect(count, types->arPacked);
	}
	return isUnion ? pt_type_combinator_call_spread(PT_LC("union"), types) : pt_type_combinator_call_spread(PT_LC("intersect"), types);
}

/* TypeCombinator::union($a, $b) / intersect($a, $b) */
zv::Val combine(bool isUnion, zval *a, zval *b)
{
	zv::Args argv{a, b};
	return isUnion ? pt_type_combinator_union(2, argv) : pt_type_combinator_intersect(2, argv);
}

/* $type instanceof NeverType */
bool isNeverType(zval *type)
{
	return Z_TYPE_P(type) == IS_OBJECT && instanceof_function(Z_OBJCE_P(type), pt_ce_never_type);
}

} // namespace

namespace phpstanturbo {

/*
 * Mirrors PHPStan\Analyser\SpecifiedTypes. The handle wraps one object;
 * methods returning zv::Val use UNDEF for a pending exception.
 */
class SpecifiedTypes
{
public:
	explicit SpecifiedTypes(zend_object *self) : self(self) {}

	/* Mirrors __construct(); NULL arguments stand for the [] defaults */
	static void construct(zend_object *object, zval *sureTypes, zval *sureNotTypes)
	{
		writeArray(object, slots::sureTypes, sureTypes);
		writeArray(object, slots::sureNotTypes, sureNotTypes);
	}

	/* new self($sureTypes, $sureNotTypes); UNDEF = pending exception */
	static zv::Val create(zval *sureTypes, zval *sureNotTypes)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_specified_types) != SUCCESS)) return zv::Val();
		construct(Z_OBJ(object), sureTypes, sureNotTypes);
		return zv::Val::adopt(object);
	}

	/* Mirrors emptySpecifyCallback(). */
	static zv::Val emptySpecifyCallback()
	{
		if (UNEXPECTED(pt_ce_specified_types == NULL)) {
			zend_throw_error(NULL, "phpstan_turbo: SpecifiedTypes used before the shadowing classes were activated");
			return zv::Val();
		}
		zval *slot = emptySpecifyCallbackSlot();
		ZVAL_DEREF(slot);
		if (EXPECTED(Z_TYPE_P(slot) == IS_OBJECT)) return zv::Val::copyOf(zv::Ref(slot));

		zv::Val callback = pt_carr_native_closure(emptySpecifyCallbackBody, NULL, NULL);
		if (UNEXPECTED(callback.isUndef())) return zv::Val();
		zv::Ref(slot).assign(zv::Val::copyOf(callback.ref()));
		return callback;
	}

	/* the body of emptySpecifyCallback(): static fn (): self => new self() */
	static void emptySpecifyCallbackBody(zval *state0, zval *state1, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) state0;
		(void) state1;
		(void) argc;
		(void) argv;
		zv::Val created = create(NULL, NULL);
		if (UNEXPECTED(created.isUndef())) return;
		created.intoReturnValue(return_value);
	}

	/* Mirrors setAlwaysOverwriteTypes(). */
	zv::Val setAlwaysOverwriteTypes() const
	{
		zend_object *copy = cloneSelf();
		if (UNEXPECTED(copy == NULL)) return zv::Val();
		ZVAL_TRUE(OBJ_PROP_NUM(copy, slots::overwrite));
		return adoptObject(copy);
	}

	/* Mirrors setEquality(). */
	zv::Val setEquality() const
	{
		zend_object *copy = cloneSelf();
		if (UNEXPECTED(copy == NULL)) return zv::Val();
		ZVAL_TRUE(OBJ_PROP_NUM(copy, slots::equality));
		return adoptObject(copy);
	}

	/* Mirrors setRootExpr(); $rootExpr NULL for null. */
	zv::Val setRootExpr(zval *rootExpr) const
	{
		zend_object *copy = cloneSelf();
		if (UNEXPECTED(copy == NULL)) return zv::Val();
		writeNullable(copy, slots::rootExpr, rootExpr);
		return adoptObject(copy);
	}

	/* Mirrors setNewConditionalExpressionHolders(). */
	zv::Val setNewConditionalExpressionHolders(zval *newConditionalExpressionHolders) const
	{
		return withArray(slots::newConditionalExpressionHolders, newConditionalExpressionHolders);
	}

	/* Mirrors setConditionalExpressionHolderRecipes(). */
	zv::Val setConditionalExpressionHolderRecipes(zval *recipes) const
	{
		return withArray(slots::conditionalExpressionHolderRecipes, recipes);
	}

	zv::Val getConditionalExpressionHolderRecipes() const { return readArray(slots::conditionalExpressionHolderRecipes, "conditionalExpressionHolderRecipes"); }

	/* Mirrors withDeferredAugment(). */
	zv::Val withDeferredAugment(zval *augment) const
	{
		zval *current = OBJ_PROP_NUM(self, slots::deferredAugments);
		if (UNEXPECTED(Z_TYPE_P(current) != IS_ARRAY)) return uninitialized("deferredAugments");
		/* [...$this->deferredAugments, $augment] */
		zv::Arr augments = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(current)) + 1);
		for (zv::ArrayEntry entry : zv::ArrRef(current)) {
			if (entry.hasStringKey()) {
				augments.set(entry.stringKey(), zv::Val::copyOf(entry.value().deref()));
			} else {
				augments.push(entry.value().deref());
			}
		}
		augments.push(zv::Ref(augment));

		zend_object *copy = cloneSelf();
		if (UNEXPECTED(copy == NULL)) return zv::Val();
		zv::ObjRef(copy).propAtWrite(slots::deferredAugments, std::move(augments));
		return adoptObject(copy);
	}

	zv::Val getDeferredAugments() const { return readArray(slots::deferredAugments, "deferredAugments"); }
	zv::Val getSureTypes() const { return readArray(slots::sureTypes, "sureTypes"); }
	zv::Val getSureNotTypes() const { return readArray(slots::sureNotTypes, "sureNotTypes"); }
	zv::Val getAlternativeTypes() const { return readArray(slots::alternativeTypes, "alternativeTypes"); }

	/* Mirrors withoutConditionalExpressionHolders(). */
	zv::Val withoutConditionalExpressionHolders() const
	{
		zend_object *copy = cloneSelf();
		if (UNEXPECTED(copy == NULL)) return zv::Val();
		zv::ObjRef object(copy);
		object.propAtWrite(slots::newConditionalExpressionHolders, zv::Arr::empty());
		object.propAtWrite(slots::conditionalExpressionHolderRecipes, zv::Arr::empty());
		return adoptObject(copy);
	}

	/* false = pending exception */
	[[nodiscard]] bool shouldOverwrite(bool &out) const
	{
		zval *slot = OBJ_PROP_NUM(self, slots::overwrite);
		if (UNEXPECTED(Z_TYPE_P(slot) != IS_TRUE && Z_TYPE_P(slot) != IS_FALSE)) {
			throwUninitialized("overwrite");
			return false;
		}
		out = Z_TYPE_P(slot) == IS_TRUE;
		return true;
	}

	/* false = pending exception */
	[[nodiscard]] bool isEquality(bool &out) const
	{
		zval *slot = OBJ_PROP_NUM(self, slots::equality);
		if (UNEXPECTED(Z_TYPE_P(slot) != IS_TRUE && Z_TYPE_P(slot) != IS_FALSE)) {
			throwUninitialized("equality");
			return false;
		}
		out = Z_TYPE_P(slot) == IS_TRUE;
		return true;
	}

	zv::Val getNewConditionalExpressionHolders() const { return readArray(slots::newConditionalExpressionHolders, "newConditionalExpressionHolders"); }

	zv::Val getRootExpr() const
	{
		zval *slot = OBJ_PROP_NUM(self, slots::rootExpr);
		if (UNEXPECTED(Z_TYPE_P(slot) == IS_UNDEF)) return uninitialized("rootExpr");
		return zv::Val::copyOf(zv::Ref(slot));
	}

	/* Mirrors removeExpr(). */
	zv::Val removeExpr(zend_string *exprString) const
	{
		zend_object *copy = cloneSelf();
		if (UNEXPECTED(copy == NULL)) return zv::Val();
		for (uint32_t index : { slots::sureTypes, slots::sureNotTypes, slots::alternativeTypes }) {
			zval *slot = OBJ_PROP_NUM(copy, index);
			/* unset($self->x[$exprString]) — only a present key changes the table */
			if (Z_TYPE_P(slot) != IS_ARRAY || zend_symtable_find(Z_ARRVAL_P(slot), exprString) == NULL) continue;
			SEPARATE_ARRAY(slot);
			zend_symtable_del(Z_ARRVAL_P(slot), exprString);
		}
		return adoptObject(copy);
	}

	/* Mirrors intersectWith(); $other is a native instance. */
	zv::Val intersectWith(zend_object *other) const
	{
		SpecifiedTypes that(other);
		zval *sureTypes = slot(slots::sureTypes);
		zval *sureNotTypes = slot(slots::sureNotTypes);
		zval *alternativeTypes = slot(slots::alternativeTypes);
		zval *otherSureTypes = that.slot(slots::sureTypes);
		zval *otherSureNotTypes = that.slot(slots::sureNotTypes);
		zval *otherAlternativeTypes = that.slot(slots::alternativeTypes);
		if (UNEXPECTED(Z_TYPE_P(sureTypes) != IS_ARRAY)) return uninitialized("sureTypes");
		if (UNEXPECTED(Z_TYPE_P(sureNotTypes) != IS_ARRAY)) return uninitialized("sureNotTypes");
		if (UNEXPECTED(Z_TYPE_P(otherSureTypes) != IS_ARRAY)) return that.uninitialized("sureTypes");
		if (UNEXPECTED(Z_TYPE_P(otherSureNotTypes) != IS_ARRAY)) return that.uninitialized("sureNotTypes");

		zv::Arr sureTypeUnion = zv::Arr::empty();
		zv::Arr sureNotTypeUnion = zv::Arr::empty();
		zv::Arr alternativeUnion = zv::Arr::empty();
		zval *rootExpr = mergeRootExpr(nullable(slot(slots::rootExpr)), nullable(that.slot(slots::rootExpr)));

		/* $keys[$exprString] = $entry[0] over the six maps, in order */
		zv::Arr keys = zv::Arr::empty();
		for (zval *map : { sureTypes, sureNotTypes, alternativeTypes, otherSureTypes, otherSureNotTypes, otherAlternativeTypes }) {
			if (Z_TYPE_P(map) != IS_ARRAY) continue;
			for (zv::ArrayEntry entry : zv::ArrRef(map)) {
				zval *exprNode = elementAt(entry.value().raw(), 0);
				setAt(keys, entry.stringKeyOrNull(), entry.indexKey(), exprNode != NULL ? zv::Val::copyOf(zv::Ref(exprNode).deref()) : zv::Val::null());
			}
		}

		for (zv::ArrayEntry keyEntry : zv::ArrRef(keys.raw())) {
			zend_string *skey = keyEntry.stringKeyOrNull();
			zend_ulong h = keyEntry.indexKey();
			zval *exprNode = keyEntry.value().raw();

			zv::Val thisTerms, otherTerms;
			if (UNEXPECTED(!collectTerms(skey, h, thisTerms))) return zv::Val();
			if (UNEXPECTED(!that.collectTerms(skey, h, otherTerms))) return zv::Val();
			if (thisTerms.isNull() || otherTerms.isNull()) {
				// unconstrained on one side - unconstrained in the merge
				continue;
			}

			zv::Val terms = arrayMerge(thisTerms.raw(), otherTerms.raw());
			zv::Arr sures = zv::Arr::empty();
			zv::Arr subtracts = zv::Arr::empty();
			bool pureSure = true;
			bool pureSureNot = true;
			for (zv::ArrayEntry term : zv::ArrRef(terms.raw())) {
				zval *sure = nullable(elementAt(term.value().raw(), 0));
				zval *subtract = nullable(elementAt(term.value().raw(), 1));
				if (sure == NULL) {
					pureSure = false;
				} else {
					sures.push(zv::Ref(sure));
				}
				if (subtract == NULL) {
					pureSureNot = false;
				} else {
					subtracts.push(zv::Ref(subtract));
				}
				if (sure == NULL || subtract == NULL) continue;

				pureSure = false;
				pureSureNot = false;
			}

			if (pureSure) {
				zv::Val united = combineAll(true, sures.table());
				if (UNEXPECTED(united.isUndef())) return zv::Val();
				setAt(sureTypeUnion, skey, h, pairOf(exprNode, united.raw()));
			} else if (pureSureNot) {
				zv::Val merged = combineAll(false, subtracts.table());
				if (UNEXPECTED(merged.isUndef())) return zv::Val();
				if (isNeverType(merged.raw())) {
					// removing never removes nothing - a vacuous constraint
					continue;
				}
				setAt(sureNotTypeUnion, skey, h, pairOf(exprNode, merged.raw()));
			} else {
				setAt(alternativeUnion, skey, h, pairOf(exprNode, terms.raw()));
			}
		}

		zv::Val result = create(sureTypeUnion.raw(), sureNotTypeUnion.raw());
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		zv::ObjRef object(result.raw());
		object.propAtWrite(slots::alternativeTypes, std::move(alternativeUnion));
		if (boolSlot(slots::overwrite) && that.boolSlot(slots::overwrite)) {
			ZVAL_TRUE(OBJ_PROP_NUM(object.raw(), slots::overwrite));
		}
		if (boolSlot(slots::equality) || that.boolSlot(slots::equality)) {
			ZVAL_TRUE(OBJ_PROP_NUM(object.raw(), slots::equality));
		}
		writeNullable(object.raw(), slots::rootExpr, rootExpr);

		return result;
	}

	/* Mirrors unionWith(); $other is a native instance. */
	zv::Val unionWith(zend_object *other) const
	{
		SpecifiedTypes that(other);
		zval *sureTypes = slot(slots::sureTypes);
		zval *sureNotTypes = slot(slots::sureNotTypes);
		zval *otherSureTypes = that.slot(slots::sureTypes);
		zval *otherSureNotTypes = that.slot(slots::sureNotTypes);
		if (UNEXPECTED(Z_TYPE_P(sureTypes) != IS_ARRAY)) return uninitialized("sureTypes");
		if (UNEXPECTED(Z_TYPE_P(otherSureTypes) != IS_ARRAY)) return that.uninitialized("sureTypes");
		if (UNEXPECTED(Z_TYPE_P(sureNotTypes) != IS_ARRAY)) return uninitialized("sureNotTypes");
		if (UNEXPECTED(Z_TYPE_P(otherSureNotTypes) != IS_ARRAY)) return that.uninitialized("sureNotTypes");

		zv::Arr sureTypeUnion = arrayUnion(sureTypes, otherSureTypes);
		zv::Arr sureNotTypeUnion = arrayUnion(sureNotTypes, otherSureNotTypes);
		zval *rootExpr = mergeRootExpr(nullable(slot(slots::rootExpr)), nullable(that.slot(slots::rootExpr)));

		if (UNEXPECTED(!mergeSameKeyTypes(sureTypeUnion, sureTypes, otherSureTypes, false))) return zv::Val();
		if (UNEXPECTED(!mergeSameKeyTypes(sureNotTypeUnion, sureNotTypes, otherSureNotTypes, true))) return zv::Val();

		zv::Val result = create(sureTypeUnion.raw(), sureNotTypeUnion.raw());
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		zend_object *resultObject = Z_OBJ_P(result.raw());

		zval *alternativeTypes = slot(slots::alternativeTypes);
		zval *otherAlternativeTypes = that.slot(slots::alternativeTypes);
		if (UNEXPECTED(Z_TYPE_P(alternativeTypes) != IS_ARRAY)) return uninitialized("alternativeTypes");
		if (UNEXPECTED(Z_TYPE_P(otherAlternativeTypes) != IS_ARRAY)) return that.uninitialized("alternativeTypes");
		zv::Arr alternativeUnion = zv::Arr::copyOfTable(Z_ARRVAL_P(alternativeTypes));
		for (zv::ArrayEntry entry : zv::ArrRef(otherAlternativeTypes)) {
			zend_string *skey = entry.stringKeyOrNull();
			zend_ulong h = entry.indexKey();
			zval *exprNode = nullable(elementAt(entry.value().raw(), 0));
			zval *otherTermsZv = elementAt(entry.value().raw(), 1);
			if (!issetAt(alternativeUnion.raw(), skey, h)) {
				setAt(alternativeUnion, skey, h, pairOf(exprNode, nullable(otherTermsZv)));
				continue;
			}

			/* held while the conjunction runs: the write below separates the
			 * table the borrowed entry lives in */
			zval *existing = entryAt(alternativeUnion.raw(), skey, h);
			zv::Val existingExpr = copyOrNull(elementAt(existing, 0));
			zv::Val existingTerms = copyOrNull(elementAt(existing, 1));
			zv::Val otherTerms = copyOrNull(otherTermsZv);
			zv::Val conjoined = conjoinTerms(existingTerms.raw(), otherTerms.raw());
			if (UNEXPECTED(conjoined.isUndef())) return zv::Val();
			setAt(alternativeUnion, skey, h, pairOf(nullable(existingExpr.raw()), conjoined.raw()));
		}

		zv::ObjRef object(resultObject);
		object.propAtWrite(slots::alternativeTypes, std::move(alternativeUnion));
		if (boolSlot(slots::overwrite) || that.boolSlot(slots::overwrite)) {
			ZVAL_TRUE(OBJ_PROP_NUM(resultObject, slots::overwrite));
		}
		if (boolSlot(slots::equality) || that.boolSlot(slots::equality)) {
			ZVAL_TRUE(OBJ_PROP_NUM(resultObject, slots::equality));
		}

		zval *holders = slot(slots::newConditionalExpressionHolders);
		zval *otherHolders = that.slot(slots::newConditionalExpressionHolders);
		if (UNEXPECTED(Z_TYPE_P(holders) != IS_ARRAY)) return uninitialized("newConditionalExpressionHolders");
		if (UNEXPECTED(Z_TYPE_P(otherHolders) != IS_ARRAY)) return that.uninitialized("newConditionalExpressionHolders");
		zv::Arr conditionalExpressionHolders = zv::Arr::copyOfTable(Z_ARRVAL_P(holders));
		for (zv::ArrayEntry entry : zv::ArrRef(otherHolders)) {
			zend_string *skey = entry.stringKeyOrNull();
			zend_ulong h = entry.indexKey();
			zval *existing = entryAt(conditionalExpressionHolders.raw(), skey, h);
			if (existing == NULL) {
				setAt(conditionalExpressionHolders, skey, h, zv::Val::copyOf(entry.value().deref()));
				continue;
			}
			zval *existingValue = existing;
			ZVAL_DEREF(existingValue);
			zval *otherValue = entry.value().raw();
			ZVAL_DEREF(otherValue);
			if (UNEXPECTED(Z_TYPE_P(existingValue) != IS_ARRAY || Z_TYPE_P(otherValue) != IS_ARRAY)) {
				zend_type_error("array_merge(): Argument #%d must be of type array, %s given", Z_TYPE_P(existingValue) != IS_ARRAY ? 1 : 2, zend_zval_value_name(Z_TYPE_P(existingValue) != IS_ARRAY ? existingValue : otherValue));
				return zv::Val();
			}
			setAt(conditionalExpressionHolders, skey, h, arrayMerge(existingValue, otherValue));
		}
		object.propAtWrite(slots::newConditionalExpressionHolders, std::move(conditionalExpressionHolders));

		zval *recipes = slot(slots::conditionalExpressionHolderRecipes);
		zval *otherRecipes = that.slot(slots::conditionalExpressionHolderRecipes);
		if (UNEXPECTED(Z_TYPE_P(recipes) != IS_ARRAY)) return uninitialized("conditionalExpressionHolderRecipes");
		if (UNEXPECTED(Z_TYPE_P(otherRecipes) != IS_ARRAY)) return that.uninitialized("conditionalExpressionHolderRecipes");
		object.propAtWrite(slots::conditionalExpressionHolderRecipes, arrayMerge(recipes, otherRecipes));

		zval *augments = slot(slots::deferredAugments);
		zval *otherAugments = that.slot(slots::deferredAugments);
		if (UNEXPECTED(Z_TYPE_P(augments) != IS_ARRAY)) return uninitialized("deferredAugments");
		if (UNEXPECTED(Z_TYPE_P(otherAugments) != IS_ARRAY)) return that.uninitialized("deferredAugments");
		object.propAtWrite(slots::deferredAugments, arrayMerge(augments, otherAugments));

		writeNullable(resultObject, slots::rootExpr, rootExpr);

		return result;
	}

private:
	zend_object *self;

	zval *slot(uint32_t index) const { return OBJ_PROP_NUM(self, index); }
	bool boolSlot(uint32_t index) const { return Z_TYPE_P(slot(index)) == IS_TRUE; }

	/* the engine's Error for reading a never-written typed property */
	void throwUninitialized(const char *property) const
	{
		zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(self->ce->name), property);
	}

	/* the same, as the UNDEF result of a zv::Val-returning member */
	zv::Val uninitialized(const char *property) const
	{
		throwUninitialized(property);
		return zv::Val();
	}

	zv::Val readArray(uint32_t index, const char *property) const
	{
		zval *value = slot(index);
		if (UNEXPECTED(Z_TYPE_P(value) != IS_ARRAY)) return uninitialized(property);
		return zv::Val::copyOf(zv::Ref(value));
	}

	static void writeArray(zend_object *object, uint32_t index, zval *array)
	{
		zv::ObjRef(object).propAtWrite(index, array != NULL ? zv::Val::copyOf(zv::Ref(array)) : zv::Val(zv::Arr::empty()));
	}

	static void writeNullable(zend_object *object, uint32_t index, zval *value)
	{
		zv::ObjRef(object).propAtWrite(index, value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val::null());
	}

	static zv::Val adoptObject(zend_object *object)
	{
		zval value;
		ZVAL_OBJ(&value, object);
		return zv::Val::adopt(value);
	}

	/* clone $this; NULL = pending exception */
	zend_object *cloneSelf() const
	{
		zend_object *copy = self->handlers->clone_obj(self);
		if (UNEXPECTED(copy == NULL || EG(exception))) {
			if (copy != NULL) {
				OBJ_RELEASE(copy);
			}
			return NULL;
		}
		return copy;
	}

	/* $self = clone $this; $self->x = $array */
	zv::Val withArray(uint32_t index, zval *array) const
	{
		zend_object *copy = cloneSelf();
		if (UNEXPECTED(copy == NULL)) return zv::Val();
		writeArray(copy, index, array);
		return adoptObject(copy);
	}

	/* Mirrors mergeRootExpr(): the borrowed operand the merge keeps, NULL for null */
	static zval *mergeRootExpr(zval *rootExprA, zval *rootExprB)
	{
		if (rootExprA == NULL || rootExprB == NULL) return rootExprA != NULL ? rootExprA : rootExprB;
		if (Z_OBJ_P(rootExprA) == Z_OBJ_P(rootExprB)) return rootExprA;
		return NULL;
	}

	/* $a + $b */
	static zv::Arr arrayUnion(zval *a, zval *b)
	{
		zv::Arr result = zv::Arr::copyOfTable(Z_ARRVAL_P(a));
		if (zend_hash_num_elements(Z_ARRVAL_P(b)) == 0) return result;
		result.separate();
		zend_hash_merge(result.table(), Z_ARRVAL_P(b), zval_add_ref, 0);
		return result;
	}

	/* the same-key folds of unionWith(): foreach ($this->x as $k => [$exprNode,
	 * $type]) — $union[$k] = [$exprNode, TypeCombinator::intersect() (sure) /
	 * union() (sure-not) of $type and $other->x[$k][1]] where the other side
	 * has the key; false = pending exception */
	static bool mergeSameKeyTypes(zv::Arr &unionTable, zval *mine, zval *theirs, bool isUnion)
	{
		for (zv::ArrayEntry entry : zv::ArrRef(mine)) {
			zend_string *skey = entry.stringKeyOrNull();
			zend_ulong h = entry.indexKey();
			if (!issetAt(theirs, skey, h)) continue;

			zval *exprNode = nullable(elementAt(entry.value().raw(), 0));
			zval *type = nullable(elementAt(entry.value().raw(), 1));
			zval *otherType = secondOfEntryAt(theirs, skey, h);
			if (UNEXPECTED(type == NULL || otherType == NULL)) {
				zend_type_error("phpstan_turbo: a SpecifiedTypes entry has no type");
				return false;
			}
			zv::Val merged = combine(isUnion, type, otherType);
			if (UNEXPECTED(merged.isUndef())) return false;
			setAt(unionTable, skey, h, pairOf(exprNode, merged.raw()));
		}
		return true;
	}

	/* Mirrors collectTerms(): the terms (an owned array), PHP null when
	 * unconstrained; false = pending exception */
	[[nodiscard]] bool collectTerms(zend_string *skey, zend_ulong h, zv::Val &out) const
	{
		zval *alternativeTypes = slot(slots::alternativeTypes);
		zval *sureTypes = slot(slots::sureTypes);
		zval *sureNotTypes = slot(slots::sureNotTypes);
		if (issetAt(alternativeTypes, skey, h)) {
			zval *terms = nullable(elementAt(entryAt(alternativeTypes, skey, h), 1));
			// sure/sureNot on the same key as an alternative entry: fold them
			// into every term (they hold in addition to the alternatives)
			if (issetAt(sureTypes, skey, h) || issetAt(sureNotTypes, skey, h)) {
				zval *extraSure = secondOfEntryAt(sureTypes, skey, h);
				zval *extraSubtract = secondOfEntryAt(sureNotTypes, skey, h);
				zv::Arr folded = zv::Arr::empty();
				if (terms != NULL && Z_TYPE_P(terms) == IS_ARRAY) {
					for (zv::ArrayEntry term : zv::ArrRef(terms)) {
						zval *sure = nullable(elementAt(term.value().raw(), 0));
						zval *subtract = nullable(elementAt(term.value().raw(), 1));
						zv::Val sureHolder, subtractHolder;
						if (extraSure != NULL) {
							if (sure == NULL) {
								sure = extraSure;
							} else {
								sureHolder = combine(false, sure, extraSure);
								if (UNEXPECTED(sureHolder.isUndef())) return false;
								sure = sureHolder.raw();
							}
						}
						if (extraSubtract != NULL) {
							if (subtract == NULL) {
								subtract = extraSubtract;
							} else {
								subtractHolder = combine(true, subtract, extraSubtract);
								if (UNEXPECTED(subtractHolder.isUndef())) return false;
								subtract = subtractHolder.raw();
							}
						}
						folded.push(pairOf(sure, subtract));
					}
				}

				out = zv::Val(std::move(folded));
				return true;
			}

			out = terms != NULL ? zv::Val::copyOf(zv::Ref(terms)) : zv::Val::null();
			return true;
		}

		zval *sure = secondOfEntryAt(sureTypes, skey, h);
		zval *subtract = secondOfEntryAt(sureNotTypes, skey, h);
		if (sure == NULL && subtract == NULL) {
			out = zv::Val::null();
			return true;
		}

		zv::Arr terms = zv::Arr::create(1);
		terms.push(pairOf(sure, subtract));
		out = zv::Val(std::move(terms));
		return true;
	}

	/* Mirrors conjoinTerms(); UNDEF = pending exception */
	static zv::Val conjoinTerms(zval *terms, zval *otherTerms)
	{
		zv::Arr conjoined = zv::Arr::empty();
		if (Z_TYPE_P(terms) == IS_ARRAY && Z_TYPE_P(otherTerms) == IS_ARRAY) {
			for (zv::ArrayEntry term : zv::ArrRef(terms)) {
				zval *sure = nullable(elementAt(term.value().raw(), 0));
				zval *subtract = nullable(elementAt(term.value().raw(), 1));
				for (zv::ArrayEntry otherTerm : zv::ArrRef(otherTerms)) {
					zval *otherSure = nullable(elementAt(otherTerm.value().raw(), 0));
					zval *otherSubtract = nullable(elementAt(otherTerm.value().raw(), 1));

					zv::Val mergedSureHolder, mergedSubtractHolder;
					zval *mergedSure;
					if (sure == NULL) {
						mergedSure = otherSure;
					} else if (otherSure == NULL) {
						mergedSure = sure;
					} else {
						mergedSureHolder = combine(false, sure, otherSure);
						if (UNEXPECTED(mergedSureHolder.isUndef())) return zv::Val();
						mergedSure = mergedSureHolder.raw();
					}

					zval *mergedSubtract;
					if (subtract == NULL) {
						mergedSubtract = otherSubtract;
					} else if (otherSubtract == NULL) {
						mergedSubtract = subtract;
					} else {
						mergedSubtractHolder = combine(true, subtract, otherSubtract);
						if (UNEXPECTED(mergedSubtractHolder.isUndef())) return zv::Val();
						mergedSubtract = mergedSubtractHolder.raw();
					}

					if (mergedSure != NULL) {
						zv::Val removed;
						if (mergedSubtract != NULL) {
							// a fixed base with a subtraction is just the narrower base -
							// folding it keeps the term list free of redundant pairs
							removed = pt_type_combinator_remove(mergedSure, mergedSubtract);
							if (UNEXPECTED(removed.isUndef())) return zv::Val();
							mergedSure = removed.raw();
							mergedSubtract = NULL;
						}
						if (isNeverType(mergedSure)) continue;
						conjoined.push(pairOf(mergedSure, mergedSubtract));
						continue;
					}

					conjoined.push(pairOf(mergedSure, mergedSubtract));
				}
			}
		}

		if (zend_hash_num_elements(conjoined.table()) == 0) {
			// every pair was impossible - so is the conjunction
			zv::Val never = pt_type_new_never_type();
			if (UNEXPECTED(never.isUndef())) return zv::Val();
			zv::Arr impossible = zv::Arr::create(1);
			impossible.push(pairOf(never.raw(), NULL));
			return zv::Val(std::move(impossible));
		}

		zv::Val deduped = dedupeTerms(conjoined.raw());
		if (UNEXPECTED(deduped.isUndef())) return zv::Val();
		if (zend_hash_num_elements(Z_ARRVAL_P(deduped.raw())) > ALTERNATIVE_TERMS_LIMIT) {
			zv::Val widened = widenTerms(deduped.raw());
			if (UNEXPECTED(widened.isUndef())) return zv::Val();
			zv::Arr single = zv::Arr::create(1);
			single.push(std::move(widened));
			return zv::Val(std::move(single));
		}

		return deduped;
	}

	/* Mirrors dedupeTerms(); UNDEF = pending exception */
	static zv::Val dedupeTerms(zval *terms)
	{
		zv::Arr deduped = zv::Arr::empty();
		for (zv::ArrayEntry term : zv::ArrRef(terms)) {
			zval *sure = nullable(elementAt(term.value().raw(), 0));
			zval *subtract = nullable(elementAt(term.value().raw(), 1));
			bool seen = false;
			for (zv::ArrayEntry seenTerm : zv::ArrRef(deduped.raw())) {
				zval *seenSure = nullable(elementAt(seenTerm.value().raw(), 0));
				zval *seenSubtract = nullable(elementAt(seenTerm.value().raw(), 1));
				if ((sure == NULL) != (seenSure == NULL)) continue;
				if ((subtract == NULL) != (seenSubtract == NULL)) continue;
				if (sure != NULL && seenSure != NULL) {
					bool equal = pt_call_type_equals(sure, seenSure);
					if (UNEXPECTED(EG(exception))) return zv::Val();
					if (!equal) continue;
				}
				if (subtract != NULL && seenSubtract != NULL) {
					bool equal = pt_call_type_equals(subtract, seenSubtract);
					if (UNEXPECTED(EG(exception))) return zv::Val();
					if (!equal) continue;
				}

				seen = true;
				break;
			}
			if (seen) continue;

			deduped.push(pairOf(sure, subtract));
		}

		return zv::Val(std::move(deduped));
	}

	/* Mirrors widenTerms(); UNDEF = pending exception */
	static zv::Val widenTerms(zval *terms)
	{
		zv::Arr sures = zv::Arr::empty();
		zv::Arr subtracts = zv::Arr::empty();
		bool suresNull = false;
		bool subtractsNull = false;
		for (zv::ArrayEntry term : zv::ArrRef(terms)) {
			zval *sure = nullable(elementAt(term.value().raw(), 0));
			zval *subtract = nullable(elementAt(term.value().raw(), 1));
			if (sure == NULL) {
				// null reads as the subject's type at the application point,
				// which every term is narrowed to anyway
				suresNull = true;
			} else if (!suresNull) {
				sures.push(zv::Ref(sure));
			}

			if (subtract == NULL) {
				subtractsNull = true;
			} else if (!subtractsNull) {
				subtracts.push(zv::Ref(subtract));
			}
		}

		zv::Val sure = suresNull ? zv::Val::null() : combineAll(true, sures.table());
		if (UNEXPECTED(sure.isUndef())) return zv::Val();
		zv::Val subtract = subtractsNull ? zv::Val::null() : combineAll(false, subtracts.table());
		if (UNEXPECTED(subtract.isUndef())) return zv::Val();

		return pairOf(nullable(sure.raw()), nullable(subtract.raw()));
	}
};

} // namespace phpstanturbo

using phpstanturbo::SpecifiedTypes;

/* {{{ direct entries (support.h) */

namespace {

/* $object->method(...$args) for a receiver that is not the native class
 * (the PHP twin declared next to it in the differential tests) */
zv::Val foreignCall(zend_object *object, const char *lcname, size_t len, uint32_t argc = 0, zval *argv = NULL)
{
	return pt_type_call(object, lcname, len, argc, argv);
}

bool isNative(zend_object *object)
{
	return EXPECTED(object->ce == pt_ce_specified_types);
}

} // namespace

zv::Val pt_specified_types_new(zval *sureTypes, zval *sureNotTypes)
{
	if (UNEXPECTED(pt_ce_specified_types == NULL)) {
		zend_throw_error(NULL, "phpstan_turbo: SpecifiedTypes used before the shadowing classes were activated");
		return zv::Val();
	}
	return SpecifiedTypes::create(sureTypes, sureNotTypes);
}

zv::Val pt_specified_types_empty_specify_callback()
{
	return SpecifiedTypes::emptySpecifyCallback();
}

zv::Val pt_specified_types_set_always_overwrite_types(zend_object *specifiedTypes)
{
	if (isNative(specifiedTypes)) return SpecifiedTypes(specifiedTypes).setAlwaysOverwriteTypes();
	return foreignCall(specifiedTypes, PT_LC("setalwaysoverwritetypes"));
}

zv::Val pt_specified_types_set_root_expr(zend_object *specifiedTypes, zval *rootExpr)
{
	if (isNative(specifiedTypes)) return SpecifiedTypes(specifiedTypes).setRootExpr(rootExpr != NULL && Z_TYPE_P(rootExpr) != IS_NULL ? rootExpr : NULL);
	zval nullZv;
	ZVAL_NULL(&nullZv);
	return foreignCall(specifiedTypes, PT_LC("setrootexpr"), 1, rootExpr != NULL ? rootExpr : &nullZv);
}

zv::Val pt_specified_types_set_new_conditional_expression_holders(zend_object *specifiedTypes, zval *holders)
{
	if (isNative(specifiedTypes)) return SpecifiedTypes(specifiedTypes).setNewConditionalExpressionHolders(holders);
	return foreignCall(specifiedTypes, PT_LC("setnewconditionalexpressionholders"), 1, holders);
}

zv::Val pt_specified_types_set_conditional_expression_holder_recipes(zend_object *specifiedTypes, zval *recipes)
{
	if (isNative(specifiedTypes)) return SpecifiedTypes(specifiedTypes).setConditionalExpressionHolderRecipes(recipes);
	return foreignCall(specifiedTypes, PT_LC("setconditionalexpressionholderrecipes"), 1, recipes);
}

zv::Val pt_specified_types_get_conditional_expression_holder_recipes(zend_object *specifiedTypes)
{
	if (isNative(specifiedTypes)) return SpecifiedTypes(specifiedTypes).getConditionalExpressionHolderRecipes();
	return foreignCall(specifiedTypes, PT_LC("getconditionalexpressionholderrecipes"));
}

zv::Val pt_specified_types_with_deferred_augment(zend_object *specifiedTypes, zval *augment)
{
	if (isNative(specifiedTypes)) return SpecifiedTypes(specifiedTypes).withDeferredAugment(augment);
	return foreignCall(specifiedTypes, PT_LC("withdeferredaugment"), 1, augment);
}

zv::Val pt_specified_types_get_deferred_augments(zend_object *specifiedTypes)
{
	if (isNative(specifiedTypes)) return SpecifiedTypes(specifiedTypes).getDeferredAugments();
	return foreignCall(specifiedTypes, PT_LC("getdeferredaugments"));
}

zv::Val pt_specified_types_get_sure_types(zend_object *specifiedTypes)
{
	if (isNative(specifiedTypes)) return SpecifiedTypes(specifiedTypes).getSureTypes();
	return foreignCall(specifiedTypes, PT_LC("getsuretypes"));
}

zv::Val pt_specified_types_get_sure_not_types(zend_object *specifiedTypes)
{
	if (isNative(specifiedTypes)) return SpecifiedTypes(specifiedTypes).getSureNotTypes();
	return foreignCall(specifiedTypes, PT_LC("getsurenottypes"));
}

zv::Val pt_specified_types_get_alternative_types(zend_object *specifiedTypes)
{
	if (isNative(specifiedTypes)) return SpecifiedTypes(specifiedTypes).getAlternativeTypes();
	return foreignCall(specifiedTypes, PT_LC("getalternativetypes"));
}

zv::Val pt_specified_types_without_conditional_expression_holders(zend_object *specifiedTypes)
{
	if (isNative(specifiedTypes)) return SpecifiedTypes(specifiedTypes).withoutConditionalExpressionHolders();
	return foreignCall(specifiedTypes, PT_LC("withoutconditionalexpressionholders"));
}

bool pt_specified_types_should_overwrite(zend_object *specifiedTypes, bool &out)
{
	if (isNative(specifiedTypes)) return SpecifiedTypes(specifiedTypes).shouldOverwrite(out);
	zv::Val result = foreignCall(specifiedTypes, PT_LC("shouldoverwrite"));
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

zv::Val pt_specified_types_get_new_conditional_expression_holders(zend_object *specifiedTypes)
{
	if (isNative(specifiedTypes)) return SpecifiedTypes(specifiedTypes).getNewConditionalExpressionHolders();
	return foreignCall(specifiedTypes, PT_LC("getnewconditionalexpressionholders"));
}

zv::Val pt_specified_types_get_root_expr(zend_object *specifiedTypes)
{
	if (isNative(specifiedTypes)) return SpecifiedTypes(specifiedTypes).getRootExpr();
	return foreignCall(specifiedTypes, PT_LC("getrootexpr"));
}

zv::Val pt_specified_types_remove_expr(zend_object *specifiedTypes, zend_string *exprString)
{
	if (isNative(specifiedTypes)) return SpecifiedTypes(specifiedTypes).removeExpr(exprString);
	zval exprStringZv;
	ZVAL_STR(&exprStringZv, exprString);
	return foreignCall(specifiedTypes, PT_LC("removeexpr"), 1, &exprStringZv);
}

zv::Val pt_specified_types_intersect_with(zend_object *specifiedTypes, zval *other)
{
	if (isNative(specifiedTypes) && Z_TYPE_P(other) == IS_OBJECT && isNative(Z_OBJ_P(other))) return SpecifiedTypes(specifiedTypes).intersectWith(Z_OBJ_P(other));
	return foreignCall(specifiedTypes, PT_LC("intersectwith"), 1, other);
}

zv::Val pt_specified_types_union_with(zend_object *specifiedTypes, zval *other)
{
	if (isNative(specifiedTypes) && Z_TYPE_P(other) == IS_OBJECT && isNative(Z_OBJ_P(other))) return SpecifiedTypes(specifiedTypes).unionWith(Z_OBJ_P(other));
	return foreignCall(specifiedTypes, PT_LC("unionwith"), 1, other);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_ST_THIS SpecifiedTypes(Z_OBJ_P(ZEND_THIS))

void pt_register_specified_types()
{
	reg::Class cls("PHPStan\\Analyser\\SpecifiedTypes");
	ptdecl::SpecifiedTypes::declareClass(cls);
	cls.privateClassConstantLong("ALTERNATIVE_TERMS_LIMIT", ALTERNATIVE_TERMS_LIMIT);
	ptdecl::SpecifiedTypes::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *sureTypes = NULL, *sureNotTypes = NULL;
		if (!zp::parse<zp::Opt<zp::Arr>, zp::Opt<zp::Arr>>(execute_data, sureTypes, sureNotTypes)) RETURN_THROWS();
		SpecifiedTypes::construct(Z_OBJ_P(ZEND_THIS), sureTypes, sureNotTypes);
	});

	cls.method(sigs::emptySpecifyCallback, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(SpecifiedTypes::emptySpecifyCallback());
	});

	cls.method<&SpecifiedTypes::setAlwaysOverwriteTypes>(sigs::setAlwaysOverwriteTypes);
	cls.method<&SpecifiedTypes::setEquality>(sigs::setEquality);
	cls.method<&SpecifiedTypes::isEquality>(sigs::isEquality);

	cls.method(sigs::setRootExpr, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *rootExpr;
		if (!zp::parse<zp::ObjOrNull>(execute_data, rootExpr)) RETURN_THROWS();
		PT_RETURN_VAL(PT_ST_THIS.setRootExpr(rootExpr));
	});

	cls.method<&SpecifiedTypes::setNewConditionalExpressionHolders, zp::Arr>(sigs::setNewConditionalExpressionHolders);
	cls.method<&SpecifiedTypes::setConditionalExpressionHolderRecipes, zp::Arr>(sigs::setConditionalExpressionHolderRecipes);
	cls.method<&SpecifiedTypes::getConditionalExpressionHolderRecipes>(sigs::getConditionalExpressionHolderRecipes);

	cls.method(sigs::withDeferredAugment, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *augment;
		if (!zp::parse<zp::Obj>(execute_data, augment)) RETURN_THROWS();
		PT_RETURN_VAL(PT_ST_THIS.withDeferredAugment(augment));
	});

	cls.method<&SpecifiedTypes::getDeferredAugments>(sigs::getDeferredAugments);
	cls.method<&SpecifiedTypes::getSureTypes>(sigs::getSureTypes);
	cls.method<&SpecifiedTypes::getSureNotTypes>(sigs::getSureNotTypes);
	cls.method<&SpecifiedTypes::getAlternativeTypes>(sigs::getAlternativeTypes);
	cls.method<&SpecifiedTypes::withoutConditionalExpressionHolders>(sigs::withoutConditionalExpressionHolders);
	cls.method<&SpecifiedTypes::shouldOverwrite>(sigs::shouldOverwrite);
	cls.method<&SpecifiedTypes::getNewConditionalExpressionHolders>(sigs::getNewConditionalExpressionHolders);
	cls.method<&SpecifiedTypes::getRootExpr>(sigs::getRootExpr);
	cls.method<&SpecifiedTypes::removeExpr, zp::Str>(sigs::removeExpr);

	cls.method(sigs::intersectWith, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(other, pt_ce_specified_types)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_ST_THIS.intersectWith(Z_OBJ_P(other)));
	});

	cls.method(sigs::unionWith, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(other, pt_ce_specified_types)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_ST_THIS.unionWith(Z_OBJ_P(other)));
	});

	cls.shadow(&pt_ce_specified_types);
}

/* }}} */

/* {{{ direct entries for the native readers of an ExpressionResult's and a scope's narrowing */

/* $specifiedTypes->isEquality() */
bool pt_specified_types_is_equality(zval *specifiedTypes, bool &out)
{
	if (EXPECTED(Z_TYPE_P(specifiedTypes) == IS_OBJECT && Z_OBJCE_P(specifiedTypes) == pt_ce_specified_types)) return SpecifiedTypes(Z_OBJ_P(specifiedTypes)).isEquality(out);
	if (UNEXPECTED(Z_TYPE_P(specifiedTypes) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function isEquality() on %s", zend_zval_value_name(specifiedTypes));
		return false;
	}
	zv::Val result = pt_type_call(Z_OBJ_P(specifiedTypes), PT_LC("isequality"), 0, NULL);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

/* }}} */
