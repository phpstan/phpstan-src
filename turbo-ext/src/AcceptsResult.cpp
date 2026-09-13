/*
 * PHPStanTurbo\AcceptsResult — native implementation of
 * PHPStan\Type\AcceptsResult.
 *
 * Declared as PHPStan\Type\AcceptsResult itself at activation (final, like
 * the twin); every instance — the three singletons included — is of that
 * class. State lives in the declared public readonly property slots
 * ($result, $reasons), which PHP code reads directly; the std object
 * handlers do GC and freeing.
 *
 * This file also hosts the pt_result_* helpers shared with
 * IsSuperTypeOfResult.cpp (both result classes keep ->result in slot 0 and
 * ->reasons in slot 1): the readonly-slot initialization, the ->result
 * trinary folds, the reasons-array merging with array_merge()/array_unique()/
 * array_values() semantics, and the AcceptsResult factory that
 * IsSuperTypeOfResult::toAcceptsResult() needs. Their declarations live at
 * the top of IsSuperTypeOfResult.cpp until they move to support.h.
 */

#include "support.h"
#include "generated/AcceptsResult.h"
#include "zv.h"

#include <cstring>

zend_class_entry *pt_ce_accepts_result = nullptr;

/* {{{ helpers shared by the two result classes */

#define PT_RESULT_PROP_RESULT 0
#define PT_RESULT_PROP_REASONS 1

/* Declares a `public readonly` typed property with no default (UNDEF —
 * IS_PROP_UNINIT until the constructor initializes it). reg.h has no
 * readonly/typed property support, hence the raw zend form. Module startup
 * only. */
/* First initialization of a declared readonly slot: the owned value goes in
 * and IS_PROP_UNINIT is cleared, as the engine's write path does when a
 * constructor initializes a readonly property. */
void pt_readonly_slot_init(zend_object *object, uint32_t slot, zval *owned)
{
	zval *p = OBJ_PROP_NUM(object, slot);
	ZVAL_COPY_VALUE(p, owned);
	Z_PROP_FLAG_P(p) = 0;
}

/* The constructor's readonly guard: a second __construct() call on an
 * initialized object fails the way the twin's promoted readonly property
 * assignment does. false = pending exception. */
[[nodiscard]] bool pt_readonly_construct_guard(zend_object *object, const char *propertyName)
{
	if (UNEXPECTED(Z_TYPE_P(OBJ_PROP_NUM(object, PT_RESULT_PROP_RESULT)) != IS_UNDEF)) {
		zend_throw_error(NULL, "Cannot modify readonly property %s::$%s", ZSTR_VAL(object->ce->name), propertyName);
		return false;
	}
	return true;
}

/* ->result's trinary value of a result object; -1 with an Error pending
 * for an object that skipped its constructor
 * (ReflectionClass::newInstanceWithoutConstructor()) — the engine's own
 * uninitialized-typed-property error, as the twin's property read raises. */
[[nodiscard]] zend_long pt_result_value(zend_object *object)
{
	zval *slot = OBJ_PROP_NUM(object, PT_RESULT_PROP_RESULT);
	if (UNEXPECTED(Z_TYPE_P(slot) != IS_OBJECT)) {
		zend_throw_error(NULL, "Typed property %s::$result must not be accessed before initialization", ZSTR_VAL(object->ce->name));
		return -1;
	}
	return pt_trinary_value(Z_OBJ_P(slot));
}

/* An array-typed slot (->reasons, ->lazyReasons); NULL with an Error
 * pending when uninitialized, see pt_result_value(). */
[[nodiscard]] zval *pt_result_array_slot(zend_object *object, uint32_t slot, const char *propertyName)
{
	zval *p = OBJ_PROP_NUM(object, slot);
	if (UNEXPECTED(Z_TYPE_P(p) != IS_ARRAY)) {
		zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(object->ce->name), propertyName);
		return NULL;
	}
	return p;
}

/*
 * TrinaryLogic folds over the operands' ->result — the same PT_TRI_*
 * expressions TrinaryLogic.cpp's and_()/or_()/extremeIdentity()/maxMin()/
 * negate() evaluate (file-local there), so the result classes never derive
 * trinary values themselves. Operands are result objects (an argument
 * vector or the packed slots of a collected array); -1 = pending exception.
 */

/* TrinaryLogic::and(...$others): the YES-identity fold */
zend_long pt_result_and(zend_long self, zval *operands, uint32_t count)
{
	zend_long acc = self;
	for (uint32_t i = 0; i < count; i++) {
		zend_long v = pt_result_value(zv::Ref(&operands[i]).deref().asObject());
		if (UNEXPECTED(v < 0)) return -1;
		acc &= v;
	}
	return acc;
}

/* TrinaryLogic::or(...$others): the NO-identity fold */
zend_long pt_result_or(zend_long self, zval *operands, uint32_t count)
{
	zend_long acc = self;
	for (uint32_t i = 0; i < count; i++) {
		zend_long v = pt_result_value(zv::Ref(&operands[i]).deref().asObject());
		if (UNEXPECTED(v < 0)) return -1;
		acc |= v;
	}
	return acc;
}

/* TrinaryLogic::extremeIdentity(): all identical → that value, else maybe;
 * count >= 1 */
zend_long pt_result_extreme_identity(zval *operands, uint32_t count)
{
	zend_long min, max;
	min = max = pt_result_value(zv::Ref(&operands[0]).deref().asObject());
	if (UNEXPECTED(min < 0)) return -1;
	for (uint32_t i = 1; i < count; i++) {
		zend_long v = pt_result_value(zv::Ref(&operands[i]).deref().asObject());
		if (UNEXPECTED(v < 0)) return -1;
		if (v < min) {
			min = v;
		}
		if (v > max) {
			max = v;
		}
	}
	return min == max ? min : PT_TRI_MAYBE;
}

/* TrinaryLogic::maxMin(): yes if any is yes, else the and-fold */
zend_long pt_result_max_min(zval *operands, uint32_t count)
{
	zend_long max = PT_TRI_NO;
	zend_long min = PT_TRI_YES;
	for (uint32_t i = 0; i < count; i++) {
		zend_long v = pt_result_value(zv::Ref(&operands[i]).deref().asObject());
		if (UNEXPECTED(v < 0)) return -1;
		max |= v;
		min &= v;
	}
	return max == PT_TRI_YES ? PT_TRI_YES : min;
}

/* TrinaryLogic::negate() */
zend_long pt_trinary_negate_value(zend_long value)
{
	return 3 >> value;
}

/* TrinaryLogic::describe() */
const char *pt_trinary_describe_value(zend_long value)
{
	if (value == PT_TRI_YES) return "Yes";
	if (value == PT_TRI_MAYBE) return "Maybe";
	return "No";
}

/* Instantiates the given class and initializes ->result (borrowed
 * trinary), ->reasons (owned, consumed) and, when given, ->lazyReasons
 * (owned, consumed). false = pending exception; the owned arrays are
 * released. */
[[nodiscard]] bool pt_result_object_create(zval *out, zend_class_entry *ce, zval *trinary, zval *reasons, zval *lazyReasons)
{
	if (UNEXPECTED(object_init_ex(out, ce) != SUCCESS)) {
		zval_ptr_dtor(reasons);
		if (lazyReasons != NULL) {
			zval_ptr_dtor(lazyReasons);
		}
		return false;
	}
	zend_object *object = Z_OBJ_P(out);
	zval trinaryCopy;
	ZVAL_COPY(&trinaryCopy, trinary);
	pt_readonly_slot_init(object, PT_RESULT_PROP_RESULT, &trinaryCopy);
	pt_readonly_slot_init(object, PT_RESULT_PROP_REASONS, reasons);
	if (lazyReasons != NULL) {
		pt_readonly_slot_init(object, PT_RESULT_PROP_REASONS + 1, lazyReasons);
	}
	return true;
}

/* keys 0..n-1 in order — the arrays array_values() returns unchanged */
static zend_always_inline bool pt_is_plain_list(const HashTable *ht)
{
	return HT_IS_PACKED(ht) && HT_IS_WITHOUT_HOLES(ht) && ht->nNextFreeElement == (zend_long) zend_hash_num_elements(ht);
}

/* array_unique()'s (SORT_STRING) membership test: strings by value,
 * anything else by its string cast (zval_get_tmp_string(), which may throw —
 * the caller checks EG(exception)). true = first occurrence. */
static zend_always_inline bool pt_seen_add(HashTable *seen, zval *value)
{
	if (EXPECTED(Z_TYPE_P(value) == IS_STRING)) return zend_hash_add_empty_element(seen, Z_STR_P(value)) != NULL;
	zend_string *tmp;
	zend_string *str = zval_get_tmp_string(value, &tmp);
	bool first = zend_hash_add_empty_element(seen, str) != NULL;
	zend_tmp_string_release(tmp);
	return first;
}

/* $out[] = $value, with the reference unwrapping array_merge() and
 * array_values() apply to singly-referenced entries */
static zend_always_inline void pt_push_copy(HashTable *out, zval *value)
{
	if (UNEXPECTED(Z_ISREF_P(value) && Z_REFCOUNT_P(value) == 1)) {
		value = Z_REFVAL_P(value);
	}
	Z_TRY_ADDREF_P(value);
	zend_hash_next_index_insert_new(out, value);
}

/*
 * The reasons-array combination both twins spell out in PHP:
 *   mergeKeys && unique   array_values(array_unique(array_merge(...$arrays)))
 *   mergeKeys && !unique  array_merge(...$arrays)
 *   !mergeKeys            the foreach-collect form (`$out[] = $v` over every
 *                         array, keys never read), then array_values(
 *                         array_unique()) when unique
 * The two forms differ only for string-keyed inputs (array_merge() lets a
 * later string key overwrite the earlier entry in place); for the lists the
 * twins document they are one pass. Owned result in *result; false = pending
 * exception (a non-string reason whose string cast throws).
 */
[[nodiscard]] bool pt_reasons_merge(zval *result, zval *const *arrays, uint32_t count, bool mergeKeys, bool unique)
{
	uint32_t total = 0;
	uint32_t nonEmpty = 0;
	zval *single = NULL;
	bool allPacked = true;
	for (uint32_t i = 0; i < count; i++) {
		HashTable *ht = Z_ARRVAL_P(arrays[i]);
		uint32_t n = zend_hash_num_elements(ht);
		if (n == 0) continue;
		total += n;
		nonEmpty++;
		single = arrays[i];
		if (!HT_IS_PACKED(ht)) {
			allPacked = false;
		}
	}

	if (total == 0) {
		ZVAL_EMPTY_ARRAY(result);
		return true;
	}

	if (nonEmpty == 1 && pt_is_plain_list(Z_ARRVAL_P(single))) {
		/* array_merge() of one plain list is that list, and so is
		 * array_values(array_unique()) of it when it has no duplicates:
		 * share it instead of copying (PHP arrays are values, the identity
		 * is unobservable) */
		if (!unique || total == 1) {
			ZVAL_COPY(result, single);
			return true;
		}
		zv::ScratchTable seen(total);
		zval built;
		ZVAL_UNDEF(&built);
		zv::TableRef source(Z_ARRVAL_P(single));
		for (auto entry : source) {
			zval *value = entry.value().raw();
			bool first = pt_seen_add(seen.table(), value);
			if (UNEXPECTED(EG(exception))) {
				zval_ptr_dtor(&built);
				return false;
			}
			if (Z_ISUNDEF(built)) {
				if (first) continue;
				/* first duplicate: materialize the unique prefix, then go on
				 * filtering into the copy */
				array_init_size(&built, total);
				zend_ulong stop = entry.indexKey();
				for (auto prefix : source) {
					if (prefix.indexKey() == stop) break;
					pt_push_copy(Z_ARRVAL(built), prefix.value().raw());
				}
				continue;
			}
			if (first) {
				pt_push_copy(Z_ARRVAL(built), value);
			}
		}
		if (Z_ISUNDEF(built)) {
			ZVAL_COPY(result, single);
		} else {
			ZVAL_COPY_VALUE(result, &built);
		}
		return true;
	}

	zval merged;
	array_init_size(&merged, total);
	HashTable *out = Z_ARRVAL(merged);

	if (mergeKeys && !allPacked) {
		/* array_merge(): a string key updates the earlier entry in place,
		 * integer keys append renumbered */
		for (uint32_t i = 0; i < count; i++) {
			for (auto entry : zv::TableRef(Z_ARRVAL_P(arrays[i]))) {
				zval *value = entry.value().raw();
				if (UNEXPECTED(Z_ISREF_P(value) && Z_REFCOUNT_P(value) == 1)) {
					value = Z_REFVAL_P(value);
				}
				Z_TRY_ADDREF_P(value);
				zend_string *key = entry.stringKeyOrNull();
				if (key != NULL) {
					zend_hash_update(out, key, value);
				} else {
					zend_hash_next_index_insert_new(out, value);
				}
			}
		}
		if (!unique) {
			ZVAL_COPY_VALUE(result, &merged);
			return true;
		}
		zval values;
		array_init_size(&values, zend_hash_num_elements(out));
		zv::ScratchTable seen(zend_hash_num_elements(out));
		for (auto entry : zv::TableRef(out)) {
			zval *value = entry.value().raw();
			bool first = pt_seen_add(seen.table(), value);
			if (UNEXPECTED(EG(exception))) {
				zval_ptr_dtor(&values);
				zval_ptr_dtor(&merged);
				return false;
			}
			if (first) {
				pt_push_copy(Z_ARRVAL(values), value);
			}
		}
		zval_ptr_dtor(&merged);
		ZVAL_COPY_VALUE(result, &values);
		return true;
	}

	/* keys play no role: array_merge() renumbers integer keys and the
	 * foreach-collect form never reads them */
	if (unique) {
		zv::ScratchTable seen(total);
		for (uint32_t i = 0; i < count; i++) {
			for (auto entry : zv::TableRef(Z_ARRVAL_P(arrays[i]))) {
				zval *value = entry.value().raw();
				bool first = pt_seen_add(seen.table(), value);
				if (UNEXPECTED(EG(exception))) {
					zval_ptr_dtor(&merged);
					return false;
				}
				if (first) {
					pt_push_copy(out, value);
				}
			}
		}
	} else {
		for (uint32_t i = 0; i < count; i++) {
			for (auto entry : zv::TableRef(Z_ARRVAL_P(arrays[i]))) {
				pt_push_copy(out, entry.value().raw());
			}
		}
	}
	ZVAL_COPY_VALUE(result, &merged);
	return true;
}

/* pt_reasons_merge() over one property slot of result objects: $self's
 * (when given) followed by each operand's, in order. The operands' ->result
 * must have been read first (pt_result_*), which is what proves they are
 * initialized. */
bool pt_reasons_merge_operands(zval *result, zend_object *self, zval *operands, uint32_t count, uint32_t slot, bool mergeKeys, bool unique)
{
	uint32_t n = count + (self != NULL ? 1 : 0);
	ALLOCA_FLAG(use_heap);
	zval **arrays = (zval **) do_alloca(sizeof(zval *) * n, use_heap);
	/* zeroed although the loops below fill all n: GCC cannot tell */
	memset(arrays, 0, sizeof(zval *) * n);
	uint32_t i = 0;
	if (self != NULL) {
		arrays[i++] = OBJ_PROP_NUM(self, slot);
	}
	for (uint32_t j = 0; j < count; j++) {
		arrays[i++] = OBJ_PROP_NUM(zv::Ref(&operands[j]).deref().asObject(), slot);
	}
	bool ok = pt_reasons_merge(result, arrays, n, mergeKeys, unique);
	free_alloca(arrays, use_heap);
	return ok;
}

/* Calls a zpp-parsed callable; false = pending exception (*retval is then
 * released). */
[[nodiscard]] bool pt_call_fci(zend_fcall_info *fci, zend_fcall_info_cache *fcc, uint32_t argc, zval *argv, zval *retval)
{
	fci->retval = retval;
	fci->param_count = argc;
	fci->params = argv;
	fci->named_params = NULL;
	if (UNEXPECTED(zend_call_function(fci, fcc) != SUCCESS || EG(exception))) {
		zval_ptr_dtor(retval);
		return false;
	}
	return true;
}

/* }}} */

namespace phpstanturbo {

/* Mirrors PHPStan\Type\AcceptsResult. State lives in the PHP object's
 * $result/$reasons slots. */
class AcceptsResult
{
public:
	explicit AcceptsResult(zend_object *self) : self(self) {}

	/* false = pending exception */
	[[nodiscard]] bool construct(zval *result, zval *reasons)
	{
		if (UNEXPECTED(!pt_readonly_construct_guard(self, "result"))) return false;
		zval copy;
		ZVAL_COPY(&copy, result);
		pt_readonly_slot_init(self, PT_RESULT_PROP_RESULT, &copy);
		ZVAL_COPY(&copy, reasons);
		pt_readonly_slot_init(self, PT_RESULT_PROP_REASONS, &copy);
		return true;
	}

	/* -1 = pending exception */
	[[nodiscard]] zend_long resultValue() const { return pt_result_value(self); }

	static zv::Val createYes() { return singleton(PT_TRI_YES); }

	/* reasons NULL = the default []; a no-reasons result is the singleton */
	static zv::Val createNo(zval *reasons)
	{
		if (reasons == NULL || zend_hash_num_elements(Z_ARRVAL_P(reasons)) == 0) return singleton(PT_TRI_NO);
		return create(pt_trinary_singleton(PT_TRI_NO), zv::Val::copyOf(zv::Ref(reasons)));
	}

	static zv::Val createMaybe() { return singleton(PT_TRI_MAYBE); }

	static zv::Val createFromBoolean(bool value) { return value ? createYes() : createNo(NULL); }

	/* and() — a C++ keyword, hence the underscore; UNDEF = pending exception */
	zv::Val and_(zval *other) const { return combine(other, true); }

	/* or() — a C++ keyword, hence the underscore; UNDEF = pending exception */
	zv::Val or_(zval *other) const { return combine(other, false); }

	/* UNDEF = pending exception */
	zv::Val decorateReasons(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zval *reasons = pt_result_array_slot(self, PT_RESULT_PROP_REASONS, "reasons");
		if (UNEXPECTED(reasons == NULL)) return zv::Val();
		zv::Val decorated = decorate(zv::ArrRef(reasons), fci, fcc);
		if (UNEXPECTED(decorated.isUndef())) return zv::Val();
		if (UNEXPECTED(resultValue() < 0)) return zv::Val();
		return create(OBJ_PROP_NUM(self, PT_RESULT_PROP_RESULT), std::move(decorated));
	}

	/* $cb($reason) for every reason, whatever the callback returns (the twin
	 * stores it unchecked); UNDEF = pending exception. Shared with
	 * IsSuperTypeOfResult::decorateReasons() through pt_reasons_decorate(). */
	static zv::Val decorate(zv::ArrRef reasons, zend_fcall_info *fci, zend_fcall_info_cache *fcc)
	{
		if (reasons.size() == 0) return zv::Val(zv::Arr::empty());
		zv::Arr decorated = zv::Arr::create(reasons.size());
		for (auto entry : reasons) {
			zval arg, decoratedReason;
			ZVAL_COPY_VALUE(&arg, entry.value().deref().raw());
			if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, &arg, &decoratedReason))) return zv::Val();
			decorated.push(zv::Val::adopt(decoratedReason));
		}
		return zv::Val(std::move(decorated));
	}

	/* count >= 1 (the glue throws for none); UNDEF = pending exception */
	static zv::Val extremeIdentity(zval *operands, uint32_t count)
	{
		return fromOperands(pt_result_extreme_identity(operands, count), operands, count);
	}

	/* count >= 1 (the glue throws for none); UNDEF = pending exception */
	static zv::Val maxMin(zval *operands, uint32_t count)
	{
		return fromOperands(pt_result_max_min(operands, count), operands, count);
	}

	/* UNDEF = pending exception */
	static zv::Val lazyMaxMin(zv::ArrRef objects, zend_fcall_info *fci, zend_fcall_info_cache *fcc)
	{
		zv::Arr collected;
		bool hasNo = false;
		for (auto entry : objects) {
			zval arg, callbackResult;
			ZVAL_COPY_VALUE(&arg, entry.value().deref().raw());
			if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, &arg, &callbackResult))) return zv::Val();
			if (UNEXPECTED(Z_TYPE(callbackResult) != IS_OBJECT || !instanceof_function(Z_OBJCE(callbackResult), pt_ce_accepts_result))) {
				zval_ptr_dtor(&callbackResult);
				zend_type_error("Return value of the callback must be of type %s", ZSTR_VAL(pt_ce_accepts_result->name));
				return zv::Val();
			}
			zv::Val isAcceptedBy = zv::Val::adopt(callbackResult);
			zend_long value = pt_result_value(zv::Ref(isAcceptedBy.raw()).asObject());
			if (UNEXPECTED(value < 0)) return zv::Val();
			if (value == PT_TRI_YES) return isAcceptedBy;
			if (value == PT_TRI_NO) {
				hasNo = true;
			}
			if (collected.isUndef()) {
				collected = zv::Arr::create(objects.size());
			}
			collected.push(std::move(isAcceptedBy));
		}

		zval reasons;
		if (collected.isUndef()) {
			ZVAL_EMPTY_ARRAY(&reasons);
		} else {
			/* built by pushes alone, so the slots are contiguous */
			HashTable *ht = collected.table();
			ZEND_ASSERT(HT_IS_PACKED(ht));
			if (UNEXPECTED(!pt_reasons_merge_operands(&reasons, NULL, ht->arPacked, zend_hash_num_elements(ht), PT_RESULT_PROP_REASONS, false, true))) {
				return zv::Val();
			}
		}
		/* new self(...) — a fresh instance, not the singleton, like the twin */
		return create(pt_trinary_singleton(hasNo ? PT_TRI_NO : PT_TRI_MAYBE), zv::Val::adopt(reasons));
	}

	/* new AcceptsResult($trinary, $reasons);
	 * reasons is consumed; UNDEF = pending exception */
	static zv::Val create(zval *trinary, zv::Val reasons)
	{
		zend_class_entry *ce = pt_ce_accepts_result;
		if (UNEXPECTED(ce == NULL)) return zv::Val();
		zval out;
		zval reasonsRaw = reasons.take();
		if (UNEXPECTED(!pt_result_object_create(&out, ce, trinary, &reasonsRaw, NULL))) return zv::Val();
		return zv::Val::adopt(out);
	}

	/* the per-request $YES/$MAYBE/$NO singletons, created on first use */
	static zv::Val singleton(zend_long value)
	{
		zval *slot = singletonSlot(value);
		if (UNEXPECTED(Z_ISUNDEF_P(slot))) {
			zv::Val created = create(pt_trinary_singleton(value), zv::Val(zv::Arr::empty()));
			if (UNEXPECTED(created.isUndef())) return zv::Val();
			*slot = created.take();
		}
		return zv::Val::copyOf(zv::Ref(slot));
	}

	static void rinit()
	{
		ZVAL_UNDEF(&singletons[0]);
		ZVAL_UNDEF(&singletons[1]);
		ZVAL_UNDEF(&singletons[2]);
	}

	static void rshutdown()
	{
		for (zval &singleton : singletons) {
			if (!Z_ISUNDEF(singleton)) {
				zval_ptr_dtor(&singleton);
				ZVAL_UNDEF(&singleton);
			}
		}
	}

private:
	zend_object *self;

	static zval singletons[3]; /* yes, maybe, no */

	static zval *singletonSlot(zend_long value)
	{
		if (value == PT_TRI_YES) return &singletons[0];
		if (value == PT_TRI_MAYBE) return &singletons[1];
		return &singletons[2];
	}

	/* and()/or(): the trinary fold and the merged, deduplicated reasons */
	zv::Val combine(zval *other, bool isAnd) const
	{
		zend_long value = resultValue();
		if (UNEXPECTED(value < 0)) return zv::Val();
		zend_long folded = isAnd ? pt_result_and(value, other, 1) : pt_result_or(value, other, 1);
		if (UNEXPECTED(folded < 0)) return zv::Val();
		zval reasons;
		if (UNEXPECTED(!pt_reasons_merge_operands(&reasons, self, other, 1, PT_RESULT_PROP_REASONS, true, true))) return zv::Val();
		return create(pt_trinary_singleton(folded), zv::Val::adopt(reasons));
	}

	/* extremeIdentity()/maxMin(): a folded value plus the operands' reasons
	 * collected and deduplicated */
	static zv::Val fromOperands(zend_long folded, zval *operands, uint32_t count)
	{
		if (UNEXPECTED(folded < 0)) return zv::Val();
		zval reasons;
		if (UNEXPECTED(!pt_reasons_merge_operands(&reasons, NULL, operands, count, PT_RESULT_PROP_REASONS, false, true))) return zv::Val();
		return create(pt_trinary_singleton(folded), zv::Val::adopt(reasons));
	}
};

zval AcceptsResult::singletons[3];

} // namespace phpstanturbo

using phpstanturbo::AcceptsResult;

/* {{{ shared entry points for IsSuperTypeOfResult.cpp */

/* new AcceptsResult($trinary, $reasons) — IsSuperTypeOfResult::toAcceptsResult();
 * reasons is owned and consumed; false = pending exception */
[[nodiscard]] bool pt_accepts_result_create(zval *out, zval *trinary, zval *reasons)
{
	zv::Val created = AcceptsResult::create(trinary, zv::Val::adopt(*reasons));
	if (UNEXPECTED(created.isUndef())) return false;
	*out = created.take();
	return true;
}

/* $cb($reason) over an array of reasons — the decorateReasons() loop both
 * twins share; false = pending exception */
[[nodiscard]] bool pt_reasons_decorate(zval *out, zval *reasons, zend_fcall_info *fci, zend_fcall_info_cache *fcc)
{
	zv::Val decorated = AcceptsResult::decorate(zv::ArrRef(reasons), fci, fcc);
	if (UNEXPECTED(decorated.isUndef())) return false;
	*out = decorated.take();
	return true;
}

void pt_accepts_result_rinit()
{
	AcceptsResult::rinit();
}

void pt_accepts_result_rshutdown()
{
	AcceptsResult::rshutdown();
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define ACCEPTS_RESULT_CLASS "PHPStanTurbo\\AcceptsResult"
#define TRINARY_CLASS "PHPStanTurbo\\TrinaryLogic"

static zend_result pt_verify_accepts_result_variadic(zval *args, uint32_t count, uint32_t offset)
{
	for (uint32_t i = 0; i < count; i++) {
		if (UNEXPECTED(!zv::Ref(&args[i]).deref().instanceOf(pt_ce_accepts_result))) {
			zend_argument_type_error(offset + i, "must be of type %s", ZSTR_VAL(pt_ce_accepts_result->name));
			return FAILURE;
		}
	}
	return SUCCESS;
}

static void pt_accepts_result_and_or(INTERNAL_FUNCTION_PARAMETERS, bool isAnd)
{
	zval *other;
	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_OBJECT_OF_CLASS(other, pt_ce_accepts_result)
	ZEND_PARSE_PARAMETERS_END();

	AcceptsResult self(Z_OBJ_P(ZEND_THIS));
	zv::Val result = isAnd ? self.and_(other) : self.or_(other);
	if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
	result.intoReturnValue(return_value);
}

static void pt_accepts_result_variadic_op(INTERNAL_FUNCTION_PARAMETERS, bool extremeIdentity)
{
	zval *operands = NULL;
	uint32_t count = 0;

	ZEND_PARSE_PARAMETERS_START(0, -1)
		Z_PARAM_VARIADIC('+', operands, count)
	ZEND_PARSE_PARAMETERS_END();

	if (UNEXPECTED(count == 0)) {
		pt_throw_should_not_happen();
		RETURN_THROWS();
	}
	if (UNEXPECTED(pt_verify_accepts_result_variadic(operands, count, 1) != SUCCESS)) RETURN_THROWS();

	zv::Val result = extremeIdentity ? AcceptsResult::extremeIdentity(operands, count) : AcceptsResult::maxMin(operands, count);
	if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
	result.intoReturnValue(return_value);
}

static void pt_accepts_result_bool(INTERNAL_FUNCTION_PARAMETERS, zend_long expected)
{
	ZEND_PARSE_PARAMETERS_NONE();
	zend_long value = AcceptsResult(Z_OBJ_P(ZEND_THIS)).resultValue();
	if (UNEXPECTED(value < 0)) RETURN_THROWS();
	RETURN_BOOL(value == expected);
}

void pt_register_accepts_result()
{
	reg::Class cls("PHPStan\\Type\\AcceptsResult");
	ptdecl::AcceptsResult::declareClass(cls);

	cls.method("__construct", reg::Public, 2, { reg::obj("result", TRINARY_CLASS), reg::arrayArg("reasons") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *result, *reasons;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT_OF_CLASS(result, pt_ce_trinary)
			Z_PARAM_ARRAY(reasons)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!AcceptsResult(Z_OBJ_P(ZEND_THIS)).construct(result, reasons))) RETURN_THROWS();
	});

	cls.method("yes", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_accepts_result_bool(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_TRI_YES);
	});

	cls.method("maybe", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_accepts_result_bool(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_TRI_MAYBE);
	});

	cls.method("no", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_accepts_result_bool(INTERNAL_FUNCTION_PARAM_PASSTHRU, PT_TRI_NO);
	});

	cls.method("createYes", reg::PublicStatic, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zv::Val result = AcceptsResult::createYes();
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.method("createNo", reg::PublicStatic, 0, { reg::withDefault(reg::arrayArg("reasons"), "[]") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *reasons = NULL;
		if (!zp::parse<zp::Opt<zp::Arr>>(execute_data, reasons)) RETURN_THROWS();
		zv::Val result = AcceptsResult::createNo(reasons);
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.method("createMaybe", reg::PublicStatic, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zv::Val result = AcceptsResult::createMaybe();
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.method("createFromBoolean", reg::PublicStatic, 1, { reg::boolArg("value") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool value;
		if (!zp::parse<zp::Bool>(execute_data, value)) RETURN_THROWS();
		zv::Val result = AcceptsResult::createFromBoolean(value);
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.method("and", reg::Public, 1, { reg::obj("other", ACCEPTS_RESULT_CLASS) }, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_accepts_result_and_or(INTERNAL_FUNCTION_PARAM_PASSTHRU, true);
	});

	cls.method("or", reg::Public, 1, { reg::obj("other", ACCEPTS_RESULT_CLASS) }, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_accepts_result_and_or(INTERNAL_FUNCTION_PARAM_PASSTHRU, false);
	});

	cls.method("decorateReasons", reg::Public, 1, { reg::callableArg("cb") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		zv::Val result = AcceptsResult(Z_OBJ_P(ZEND_THIS)).decorateReasons(&fci, &fcc);
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.method("extremeIdentity", reg::PublicStatic, 0, { reg::variadicObj("operands", ACCEPTS_RESULT_CLASS) }, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_accepts_result_variadic_op(INTERNAL_FUNCTION_PARAM_PASSTHRU, true);
	});

	cls.method("maxMin", reg::PublicStatic, 0, { reg::variadicObj("operands", ACCEPTS_RESULT_CLASS) }, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_accepts_result_variadic_op(INTERNAL_FUNCTION_PARAM_PASSTHRU, false);
	});

	cls.method("lazyMaxMin", reg::PublicStatic, 2, { reg::arrayArg("objects"), reg::callableArg("callback") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *objects;
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_ARRAY(objects)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		zv::Val result = AcceptsResult::lazyMaxMin(zv::ArrRef(objects), &fci, &fcc);
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.publicReadonlyProperty("result", MAY_BE_OBJECT);
	cls.publicReadonlyProperty("reasons", MAY_BE_ARRAY);
	cls.shadow(&pt_ce_accepts_result);
}

/* }}} */
