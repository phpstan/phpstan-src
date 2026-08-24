/*
 * PHPStanTurbo\TypeCombinatorCache — native implementation of
 * PHPStan\Type\TypeCombinatorCache.
 *
 * TypeCombinator::union()/intersect()/remove() route through this class when the
 * extension is active. Roughly 91% of the calls in an analysis run repeat an
 * argument tuple whose result was already computed, so each operation is memoized
 * on a structural key of its arguments; a miss calls back into the PHP twin
 * (TypeCombinator::doUnion() and friends), which stays the reference implementation.
 *
 * On top of that the results are *interned*: one canonical instance is kept per
 * distinct type value, so operations that arrive at the same value by different
 * routes hand back the same object. In a self-analysis run 60% of the results the
 * memo does not already cover turn out to be values that already exist (110k of
 * 184k). Sharing them makes `$a === $b` a common outcome for equal types, which
 * is what the identity fast paths already sitting on the hot paths —
 * pt_types_identical_or_equal() on scope merges, `$a === $b` in
 * TypeCombinator::doUnion() — then collect on: -3.1% user CPU on a serial
 * self-analysis, measured over three interleaved rounds. Peak RSS is unchanged;
 * the results being deduplicated are short-lived, and peak RSS is set by
 * reflection and parser state instead.
 *
 * PRECONDITION: TypeCombinator::doUnion() must return the same type for equal
 * operands as it does for identical ones, since interning decides which of the
 * two an operand pair is. Its two-operand fast path covers equal array operands
 * for exactly this reason — without it, processArrayTypes() rebuilds such a pair
 * through ArrayType::getIterableKeyType() and drops a subtracted key type, so
 * union(array<mixed~'User', mixed>, <equal>) would widen to array here and stay
 * precise under interning, breaking the rule that analysis output is identical
 * with the extension on and off.
 *
 * Three structures back this:
 *
 *  - a per-object 128-bit structural hash of every Type, cached in a *weak* map so
 *    the entry disappears with the object: the cache retains nothing and an object
 *    address can never be mistaken for a freed one's. Composite types hash from
 *    their children's cached hashes, so hashing a new type costs O(#properties),
 *    not O(tree). Property-less types are not cached at all — their class-only
 *    hash is cheaper to recompute than to look up.
 *  - the memo itself: a flat open-addressing table whose 128-bit key folds the
 *    operation, the argument count and the arguments' hashes together, 24 bytes
 *    per slot. Results are borrowed, not owned: each result object carries the
 *    list of memo keys mapping to it (an entry in the weak pt_memo_results
 *    hash), and its death tombstones those slots. The memo therefore pins no
 *    graphs — an entry lives exactly as long as some live scope holds the
 *    result anyway. Owning the results instead was measured (July 2026) to
 *    cost ~100MB of summed worker peaks on parallel runs, while the entries
 *    that die are the cheap ones: recomputing them is CPU-neutral even at a
 *    hit rate drop from ~90% to ~70%.
 *  - the intern table: the same flat table, keyed by a type's own structural
 *    hash and holding the canonical instance for that value, borrowed the same
 *    way (weak pt_intern_objs, whose dtor releases the slot).
 *
 * The hash is 128 bits precisely so a key can be trusted without keeping the
 * arguments alive to re-verify a hit: over the ~10^5 distinct keys of a run the
 * collision probability is ~10^-28. A 64-bit key would be ~10^-9 per run, which
 * across a user base is a silently wrong analysis result — not acceptable.
 *
 * Both tables are cleared whenever a container is created (TypeCombinator::clearCache()).
 * They hand back *shared* Type instances, and a Type lazily resolves a
 * ClassReflection belonging to the container that created it — so entries must not
 * outlive their container. Production runs one container per process; the test
 * suite does not.
 */

#include "support.h"
#include "zv.h"

#include <Zend/zend_weakrefs.h>

/* zend_weakrefs_hash_clean()/_destroy() only exist since PHP 8.5; on 8.4 the
 * same unregister-then-destroy is spelled out with the 8.4-available API. */
#if PHP_VERSION_ID < 80500
static zend_always_inline void pt_weakrefs_hash_destroy(HashTable *ht)
{
	zend_ulong objKey;
	ZEND_HASH_MAP_FOREACH_NUM_KEY(ht, objKey) {
		zend_weakrefs_hash_del(ht, zend_weakref_key_to_object(objKey));
	} ZEND_HASH_FOREACH_END();
	zend_hash_destroy(ht);
}
#else
static zend_always_inline void pt_weakrefs_hash_destroy(HashTable *ht)
{
	zend_weakrefs_hash_destroy(ht);
}
#endif

namespace phpstanturbo {

/* Beyond this argument count a call is computed without consulting the memo:
 * such calls are vanishingly rare and each argument adds hashing cost. */
static constexpr uint32_t MEMO_ARGS_LIMIT = 16;

/* Depth guard for the structural walk. Types nest ~10 deep; anything beyond this
 * is not hashed at all (the call bypasses the memo) rather than hashed coarsely,
 * because a coarse hash would be an unsound key. */
static constexpr uint32_t HASH_DEPTH_LIMIT = 64;

/* Safety net on memo growth; with dead results invalidating their entries, a
 * self-analysis run peaks at ~3.5e4 live entries. */
static constexpr uint32_t MEMO_ENTRIES_LIMIT = 1 << 19;

/* Same safety net for the intern table. Distinct type *values* are far fewer
 * than distinct argument tuples, so this is never reached in practice. */
static constexpr uint32_t INTERN_ENTRIES_LIMIT = 1 << 19;

/* Initial slot count of a table; must be a power of two. */
static constexpr uint32_t TABLE_INITIAL_CAPACITY_LIMIT = 1 << 13;

struct Hash128
{
	uint64_t a;
	uint64_t b;
};

/* An occupied slot borrows its object; obj == NULL marks an empty slot,
 * obj == SLOT_TOMBSTONE a deleted one (the probe chain must stay intact, so
 * deletion cannot empty a slot). */
struct Slot
{
	Hash128 key;
	zend_object *obj;
};

#define SLOT_TOMBSTONE ((zend_object *) 1)

/* Flat open-addressing table from a 128-bit structural key to a borrowed
 * object, backing both the memo (key = operation + arguments) and the intern
 * table (key = the value itself). Linear probing, 24 bytes per slot; the
 * live+tombstone load never reaches 1 (grow() keeps it at 3/4 at most), so
 * every probe terminates on an empty slot. */
struct SlotTable
{
	Slot *slots;
	uint32_t mask;
	uint32_t count;
	uint32_t tombstones;

	void init()
	{
		slots = (Slot *) ecalloc(TABLE_INITIAL_CAPACITY_LIMIT, sizeof(Slot));
		mask = TABLE_INITIAL_CAPACITY_LIMIT - 1;
		count = 0;
		tombstones = 0;
	}

	void destroy()
	{
		efree(slots);
		slots = NULL;
		mask = 0;
		count = 0;
		tombstones = 0;
	}

	/* Drop every entry, shrinking an overgrown table back to its initial size. */
	void reset()
	{
		if (mask + 1 > TABLE_INITIAL_CAPACITY_LIMIT) {
			efree(slots);
			slots = (Slot *) ecalloc(TABLE_INITIAL_CAPACITY_LIMIT, sizeof(Slot));
			mask = TABLE_INITIAL_CAPACITY_LIMIT - 1;
		} else {
			memset(slots, 0, (size_t) (mask + 1) * sizeof(Slot));
		}
		count = 0;
		tombstones = 0;
	}

	/* FNV-1a's low bits mix worst; fold the high half in before masking. */
	zend_always_inline uint32_t start(Hash128 key) const
	{
		return (uint32_t) (key.a ^ (key.a >> 32)) & mask;
	}

	/* The occupied slot holding the key, or NULL. */
	zend_always_inline Slot *lookup(Hash128 key) const
	{
		uint32_t idx = start(key);
		for (;;) {
			Slot *slot = &slots[idx];
			if (slot->obj == NULL) {
				return NULL;
			}
			if (slot->obj != SLOT_TOMBSTONE && slot->key.a == key.a && slot->key.b == key.b) {
				return slot;
			}
			idx = (idx + 1) & mask;
		}
	}

	/* Slot to insert the key into: the occupied slot already holding it, else
	 * the first tombstone on its probe path, else the terminating empty slot. */
	zend_always_inline Slot *insertPos(Hash128 key) const
	{
		uint32_t idx = start(key);
		Slot *tombstone = NULL;
		for (;;) {
			Slot *slot = &slots[idx];
			if (slot->obj == NULL) {
				return tombstone != NULL ? tombstone : slot;
			}
			if (slot->obj == SLOT_TOMBSTONE) {
				if (tombstone == NULL) {
					tombstone = slot;
				}
			} else if (slot->key.a == key.a && slot->key.b == key.b) {
				return slot;
			}
			idx = (idx + 1) & mask;
		}
	}

	void grow()
	{
		uint32_t oldCapacity = mask + 1;
		Slot *oldSlots = slots;

		/* Tombstones are dropped by the rehash; only grow the table when live
		 * entries alone justify it, otherwise rehash at the same size. */
		uint32_t newCapacity = ((uint64_t) count * 4 > (uint64_t) oldCapacity * 2) ? oldCapacity * 2 : oldCapacity;
		slots = (Slot *) ecalloc(newCapacity, sizeof(Slot));
		mask = newCapacity - 1;
		tombstones = 0;
		for (uint32_t i = 0; i < oldCapacity; i++) {
			if (oldSlots[i].obj != NULL && oldSlots[i].obj != SLOT_TOMBSTONE) {
				*insertPos(oldSlots[i].key) = oldSlots[i];
			}
		}
		efree(oldSlots);
	}

	/* Takes a slot returned by insertPos() that is empty or a tombstone. */
	void occupy(Slot *slot, Hash128 key, zend_object *obj)
	{
		if (slot->obj == SLOT_TOMBSTONE) {
			tombstones--;
		}
		slot->key = key;
		slot->obj = obj;
		count++;

		if ((uint64_t) (count + tombstones) * 4 > (uint64_t) (mask + 1) * 3) {
			grow();
		}
	}

	/* Deletes the entry mapping key to obj, if it is still the one present. */
	void release(Hash128 key, const zend_object *obj)
	{
		uint32_t idx = start(key);
		for (;;) {
			Slot *slot = &slots[idx];
			if (slot->obj == NULL) {
				return;
			}
			if (slot->obj == obj && slot->key.a == key.a && slot->key.b == key.b) {
				slot->obj = SLOT_TOMBSTONE;
				count--;
				tombstones++;
				return;
			}
			idx = (idx + 1) & mask;
		}
	}
};

/* Each memoized result object carries this list of the memo keys mapping to
 * it (several keys can produce the same shared instance), held as IS_PTR in
 * the weak pt_memo_results hash. The engine deletes the entry when the object
 * dies, and the value dtor tombstones the listed slots. */
struct KeyList
{
	zend_object *obj;
	uint32_t count;
	uint32_t cap;
	Hash128 keys[4]; /* inline head; grown by erealloc */
};

/* The intern-table counterpart of KeyList: a canonical instance is canonical
 * for exactly one key — its own structural hash. */
struct InternEntry
{
	zend_object *obj;
	Hash128 key;
};

static HashTable pt_type_hashes;   /* weak: zend_object* -> Hash128 packed in the bucket zval */
static HashTable pt_ce_kinds;      /* zend_class_entry* -> kind|slots */
static HashTable pt_obj_serials;   /* weak: zend_object* -> IS_LONG serial (identity-hashed objects) */
static HashTable pt_memo_results;  /* weak: zend_object* -> IS_PTR KeyList */
static HashTable pt_intern_objs;   /* weak: zend_object* -> IS_PTR InternEntry */
static SlotTable pt_memo;
static SlotTable pt_intern;
static uint64_t pt_next_serial = 1;
static bool pt_cache_inited = false;
static bool pt_invalidate_active = false;


static zend_class_entry *pt_guard_ce = NULL;
static uint32_t pt_guard_offset = 0;
static bool pt_guard_resolved = false;
static bool pt_guard_unavailable = false;

static zend_function *pt_fn_do_union = NULL;
static zend_function *pt_fn_do_intersect = NULL;
static zend_function *pt_fn_do_remove = NULL;

/* {{{ 128-bit FNV-1a, two independent accumulators fed by one walk */

static constexpr uint64_t FNV_OFFSET_A = 0xcbf29ce484222325ULL;
static constexpr uint64_t FNV_PRIME_A = 0x100000001b3ULL;
static constexpr uint64_t FNV_OFFSET_B = 0x9e3779b97f4a7c15ULL;
static constexpr uint64_t FNV_PRIME_B = 0xff51afd7ed558ccdULL;

static inline void mixByte(Hash128 &h, uint8_t byte)
{
	h.a = (h.a ^ byte) * FNV_PRIME_A;
	h.b = (h.b ^ byte) * FNV_PRIME_B;
}

static inline void mixBytes(Hash128 &h, const void *data, size_t len)
{
	const uint8_t *p = (const uint8_t *) data;
	for (size_t i = 0; i < len; i++) {
		mixByte(h, p[i]);
	}
}

static inline void mixU64(Hash128 &h, uint64_t value)
{
	mixBytes(h, &value, sizeof(value));
}

/* }}} */

/* {{{ per-class-entry plan: is this object hashed structurally, and how many slots */

enum CeKind : uint8_t {
	CE_IDENTITY = 0, /* not a type-system value object: hashed by address */
	CE_STRUCTURAL = 1,
};

struct CePlan
{
	CeKind kind;
	uint32_t slots;
};

static bool ceNameHasPrefix(const zend_class_entry *ce, const char *prefix, size_t len)
{
	return ZSTR_LEN(ce->name) >= len && memcmp(ZSTR_VAL(ce->name), prefix, len) == 0;
}

static CePlan cePlan(zend_class_entry *ce)
{
	zval *cached = zend_hash_index_find(&pt_ce_kinds, (zend_ulong) (uintptr_t) ce);
	if (cached != NULL) {
		zend_long packed = Z_LVAL_P(cached);
		return { (CeKind) (packed & 1), (uint32_t) (packed >> 1) };
	}

	zend_class_entry *typeCe = pt_class(PT_CLASS_TYPE);
	CeKind kind = CE_IDENTITY;
	if ((typeCe != NULL && instanceof_function(ce, typeCe))
		|| (pt_ce_trinary != NULL && instanceof_function(ce, pt_ce_trinary))
		|| ceNameHasPrefix(ce, "PHPStan\\Type\\", sizeof("PHPStan\\Type\\") - 1)
		|| ceNameHasPrefix(ce, "PHPStan\\Php\\", sizeof("PHPStan\\Php\\") - 1)) {
		kind = CE_STRUCTURAL;
	}

	CePlan plan = { kind, (uint32_t) ce->default_properties_count };
	zval packed;
	ZVAL_LONG(&packed, ((zend_long) plan.slots << 1) | (zend_long) plan.kind);
	zend_hash_index_add(&pt_ce_kinds, (zend_ulong) (uintptr_t) ce, &packed);

	return plan;
}

/* }}} */

/* {{{ structural hashing */

static uint64_t objSerial(zend_object *obj)
{
	/* Objects outside the type system (ClassReflection, method reflections held in
	 * ObjectType::$methodCache, …) take part in the hash by identity. They must NOT
	 * be hashed by address: addresses are reused once an object is freed, so two
	 * different types could hash alike — and they vary between runs, which made
	 * results non-deterministic. Each such object instead gets a serial that is
	 * never reused, held as a plain IS_LONG in the weak map. */
	zval *known = zend_hash_index_find(&pt_obj_serials, zend_object_to_weakref_key(obj));
	if (known != NULL) {
		return (uint64_t) Z_LVAL_P(known);
	}

	uint64_t serial = pt_next_serial;
	zval value;
	ZVAL_LONG(&value, (zend_long) serial);
	if (zend_weakrefs_hash_add(&pt_obj_serials, obj, &value) != NULL) {
		pt_next_serial++;
	}

	return serial;
}

static bool hashObject(zend_object *obj, Hash128 &out, uint32_t depth);

static bool hashZval(zval *value, Hash128 &h, uint32_t depth)
{
	ZVAL_DEREF(value);

	switch (Z_TYPE_P(value)) {
		case IS_UNDEF:
			/* An uninitialized typed property is NOT null — ConstantArrayType::$unsealed
			 * distinguishes the two, and conflating them merges sealed with unsealed. */
			mixByte(h, 1);
			return true;
		case IS_NULL:
			mixByte(h, 2);
			return true;
		case IS_FALSE:
			mixByte(h, 3);
			return true;
		case IS_TRUE:
			mixByte(h, 4);
			return true;
		case IS_LONG:
			mixByte(h, 5);
			mixU64(h, (uint64_t) Z_LVAL_P(value));
			return true;
		case IS_DOUBLE: {
			double d = Z_DVAL_P(value);
			uint64_t bits;
			memcpy(&bits, &d, sizeof(bits));
			mixByte(h, 6);
			mixU64(h, bits);
			return true;
		}
		case IS_STRING: {
			zend_string *str = Z_STR_P(value);
			mixByte(h, 7);
			mixU64(h, (uint64_t) ZSTR_LEN(str));
			mixBytes(h, ZSTR_VAL(str), ZSTR_LEN(str));
			return true;
		}
		case IS_ARRAY: {
			zv::ArrRef arr(value);
			mixByte(h, 8);
			mixU64(h, (uint64_t) zend_hash_num_elements(arr.table()));
			for (auto entry : arr) {
				zend_string *key = entry.stringKeyOrNull();
				if (key != NULL) {
					mixByte(h, 9);
					mixU64(h, (uint64_t) ZSTR_LEN(key));
					mixBytes(h, ZSTR_VAL(key), ZSTR_LEN(key));
				} else {
					mixByte(h, 10);
					mixU64(h, (uint64_t) entry.indexKey());
				}
				zval *slot = entry.value().raw();
				if (!hashZval(slot, h, depth + 1)) {
					return false;
				}
			}
			return true;
		}
		case IS_OBJECT: {
			zend_object *obj = Z_OBJ_P(value);
			if (cePlan(obj->ce).kind == CE_STRUCTURAL) {
				Hash128 inner;
				if (!hashObject(obj, inner, depth + 1)) {
					return false;
				}
				mixByte(h, 11);
				mixU64(h, inner.a);
				mixU64(h, inner.b);
				return true;
			}
			mixByte(h, 12);
			mixU64(h, objSerial(obj));
			return true;
		}
		default:
			return false;
	}
}

/* The hash of a property-less object is a pure function of its class, computed
 * here exactly as the walk below would (no slots to mix). Both hashObject callers
 * guarantee the object is CE_STRUCTURAL, so the plan does not need consulting. */
static zend_always_inline bool hashZeroSlotObject(zend_object *obj, Hash128 &out)
{
	if (obj->ce->default_properties_count != 0) {
		return false;
	}
	Hash128 h = { FNV_OFFSET_A, FNV_OFFSET_B };
	mixU64(h, (uint64_t) (uintptr_t) obj->ce);
	out = h;

	return true;
}

static bool hashObject(zend_object *obj, Hash128 &out, uint32_t depth)
{
	if (UNEXPECTED(depth > HASH_DEPTH_LIMIT)) {
		return false;
	}

	/* Argless leaf types (MixedType, NullType, …) are ~30% of hashed objects;
	 * recomputing their class-only hash is cheaper than a table lookup, and
	 * caching it would spend a map entry plus an EG(weakrefs) registration per
	 * instance to save nothing. */
	if (hashZeroSlotObject(obj, out)) {
		return true;
	}

	Hash128 *cached = (Hash128 *) zend_hash_index_find_ptr(&pt_type_hashes, zend_object_to_weakref_key(obj));
	if (cached != NULL) {
		out = *cached;
		return true;
	}

	CePlan plan = cePlan(obj->ce);
	Hash128 h = { FNV_OFFSET_A, FNV_OFFSET_B };
	/* The class entry pointer identifies the class uniquely within the request. */
	mixU64(h, (uint64_t) (uintptr_t) obj->ce);

	for (uint32_t i = 0; i < plan.slots; i++) {
		if (!hashZval(OBJ_PROP_NUM(obj, i), h, depth + 1)) {
			return false;
		}
	}

	/* The 16 hash bytes live behind a real IS_PTR value; they cannot go into the
	 * bucket zval itself, because a bucket only carries 8 payload bytes — u1 is
	 * type_info the engine inspects (zend_hash_rehash treats Z_TYPE == IS_UNDEF
	 * as a hole) and u2 is Z_NEXT, the collision chain, overwritten on insert.
	 * NULL return = already registered by a re-entrant walk; the existing entry
	 * holds the same bytes (the hash is a pure function of the object's value). */
	Hash128 *stored = (Hash128 *) emalloc(sizeof(Hash128));
	*stored = h;
	if (zend_weakrefs_hash_add_ptr(&pt_type_hashes, obj, stored) == NULL) {
		efree(stored);
	}

	out = h;

	return true;
}

/* }}} */

/* {{{ RecursionGuard

 * While RecursionGuard::$context is non-empty, run()/runOnObjectIdentity() short-circuit
 * to ErrorType, so a type operation's result depends on the call stack rather than only on
 * its arguments — and runOnObjectIdentity() keys on spl_object_id(), which memoization
 * itself perturbs by handing back shared instances. The memo is therefore bypassed whole
 * while a guard is active: entries are only ever produced and consumed with an empty
 * context, where the operations are pure functions of their arguments. Failing to read the
 * guard disables the memo rather than risking an unsound entry. */

static bool guardActive()
{
	if (UNEXPECTED(!pt_guard_resolved)) {
		pt_guard_resolved = true;
		pt_guard_unavailable = true;

		zend_class_entry *ce = pt_class(PT_CLASS_RECURSION_GUARD);
		if (ce == NULL) {
			return true;
		}
		zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&ce->properties_info, "context", sizeof("context") - 1);
		if (info == NULL || (info->flags & ZEND_ACC_STATIC) == 0) {
			return true;
		}

		pt_guard_ce = ce;
		pt_guard_offset = info->offset;
		pt_guard_unavailable = false;
	}

	if (UNEXPECTED(pt_guard_unavailable)) {
		return true;
	}

	if (UNEXPECTED(CE_STATIC_MEMBERS(pt_guard_ce) == NULL)) {
		zend_class_init_statics(pt_guard_ce);
	}

	zval *context = &CE_STATIC_MEMBERS(pt_guard_ce)[pt_guard_offset];
	ZVAL_DEREF(context);

	return Z_TYPE_P(context) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL_P(context)) > 0;
}

/* }}} */



static void typeHashDtor(zval *zv)
{
	efree(Z_PTR_P(zv));
}

/* {{{ the memo */

/* {{{ weak-result mode: invalidation on result death + key-list upkeep */

/* Value dtor of pt_memo_results: runs when a memoized result object dies (and
 * on bulk cleanup, where pt_invalidate_active is off and the memo is reset
 * separately). */
static void memoResultDtor(zval *zv)
{
	KeyList *list = (KeyList *) Z_PTR_P(zv);
	if (pt_invalidate_active) {
		for (uint32_t i = 0; i < list->count; i++) {
			pt_memo.release(list->keys[i], list->obj);
		}
	}
	efree(list);
}

static void memoTrackResult(zend_object *obj, Hash128 key)
{
	zval *existing = zend_hash_index_find(&pt_memo_results, zend_object_to_weakref_key(obj));
	if (existing != NULL) {
		KeyList *list = (KeyList *) Z_PTR_P(existing);
		if (list->count == list->cap) {
			list->cap *= 2;
			list = (KeyList *) erealloc(list, sizeof(KeyList) + (list->cap - 4) * sizeof(Hash128));
			Z_PTR_P(existing) = list;
		}
		list->keys[list->count++] = key;
		return;
	}

	KeyList *list = (KeyList *) emalloc(sizeof(KeyList));
	list->obj = obj;
	list->count = 1;
	list->cap = 4;
	list->keys[0] = key;
	zval value;
	ZVAL_PTR(&value, list);
	if (zend_weakrefs_hash_add(&pt_memo_results, obj, &value) == NULL) {
		efree(list); /* unreachable: the find above showed no entry */
	}
}

/* Purge every weak entry of a table without touching the slot tables (the
 * caller resets those wholesale). On 8.4 the unregister loop is spelled out,
 * as in pt_weakrefs_hash_destroy. */
static void weakResultsClean(HashTable *ht)
{
#if PHP_VERSION_ID < 80500
	zend_ulong objKey;
	ZEND_HASH_MAP_FOREACH_NUM_KEY(ht, objKey) {
		zend_weakrefs_hash_del(ht, zend_weakref_key_to_object(objKey));
	} ZEND_HASH_FOREACH_END();
#else
	zend_weakrefs_hash_clean(ht);
#endif
}

/* }}} */

/* {{{ interning

 * The memo deduplicates *calls*; interning deduplicates *values*. Countless
 * different argument tuples converge on the same result — `int|string` is
 * produced by unions of wildly different operands — and each one otherwise
 * materializes its own object graph. Keeping one canonical instance per
 * structural hash and handing that back means equal types are usually the
 * *same* type, so the identity checks the hot paths already perform decide the
 * comparison instead of recursing into the two graphs.
 *
 * Substituting is sound because the hash covers every declared property: two
 * objects hashing alike are indistinguishable to any reader of their value.
 * Lazily populated memo properties (ObjectType::$classReflection and friends)
 * are part of the hash too, so a populated instance simply hashes differently
 * from a bare one — never a false merge, only a missed one.
 *
 * Entries are borrowed exactly like the memo's: pt_intern_objs is a weak map
 * whose dtor releases the slot, so a canonical instance is retained only as
 * long as some live scope holds it anyway. */

static void internEntryDtor(zval *zv)
{
	InternEntry *entry = (InternEntry *) Z_PTR_P(zv);
	if (pt_invalidate_active) {
		pt_intern.release(entry->key, entry->obj);
	}
	efree(entry);
}

/* Replaces a freshly computed result with the canonical instance of its value,
 * or makes it the canonical one. */
static void internResult(zval *return_value)
{
	zend_object *obj = Z_OBJ_P(return_value);
	Hash128 hash;
	if (!hashObject(obj, hash, 0)) {
		return;
	}

	Slot *slot = pt_intern.lookup(hash);
	if (slot != NULL) {
		zend_object *canonical = slot->obj;
		if (canonical == obj) {
			return;
		}
		GC_ADDREF(canonical);
		/* Dropping the fresh graph can run internEntryDtor/memoResultDtor for
		 * its children, which only tombstone slots — no table ever rehashes
		 * under a borrowed Slot pointer. */
		zval_ptr_dtor(return_value);
		ZVAL_OBJ(return_value, canonical);
		return;
	}

	if (pt_intern.count >= INTERN_ENTRIES_LIMIT) {
		return;
	}

	InternEntry *entry = (InternEntry *) emalloc(sizeof(InternEntry));
	entry->obj = obj;
	entry->key = hash;
	zval value;
	ZVAL_PTR(&value, entry);
	if (zend_weakrefs_hash_add(&pt_intern_objs, obj, &value) == NULL) {
		efree(entry); /* unreachable: the lookup above showed no entry */
		return;
	}

	pt_intern.occupy(pt_intern.insertPos(hash), hash, obj);
}

/* }}} */

/* Mirrors PHPStan\Type\TypeCombinatorCache. */
class TypeCombinatorCache
{
public:
	enum Op : uint8_t {
		UNION = 1,
		INTERSECT = 2,
		REMOVE = 3,
	};

	static void run(INTERNAL_FUNCTION_PARAMETERS, Op op, zend_function *fn, zval *args, uint32_t argc)
	{
		Hash128 key = { FNV_OFFSET_A, FNV_OFFSET_B };
		/* Both tables are only fed and read while no recursion guard is active,
		 * where the operations are pure functions of their arguments. */
		bool pure = !guardActive();
		bool memoizable = argc > 0 && argc <= MEMO_ARGS_LIMIT && pure;

		if (memoizable) {
			mixByte(key, (uint8_t) op);
			mixByte(key, (uint8_t) argc);
			for (uint32_t i = 0; i < argc; i++) {
				zval *arg = &args[i];
				ZVAL_DEREF(arg);
				Hash128 h;
				if (UNEXPECTED(Z_TYPE_P(arg) != IS_OBJECT) || !hashObject(Z_OBJ_P(arg), h, 0)) {
					memoizable = false;
					break;
				}
				mixU64(key, h.a);
				mixU64(key, h.b);
			}
		}

		if (memoizable) {
			Slot *slot = pt_memo.lookup(key);
			if (slot != NULL) {
				GC_ADDREF(slot->obj);
				RETVAL_OBJ(slot->obj);
				return;
			}
		}

		zend_class_entry *ce = pt_class(PT_CLASS_TYPE_COMBINATOR);
		if (UNEXPECTED(ce == NULL || fn == NULL)) {
			return;
		}
		zend_call_known_function(fn, NULL, ce, return_value, argc, args, NULL);
		if (UNEXPECTED(EG(exception)) || Z_TYPE_P(return_value) != IS_OBJECT) {
			return;
		}

		if (pure) {
			internResult(return_value);
		}

		if (memoizable && pt_memo.count < MEMO_ENTRIES_LIMIT) {
			/* Fresh lookup: the callback re-enters these operations for nested
			 * types, which may have inserted this very key or grown the table. */
			Slot *slot = pt_memo.insertPos(key);
			if (slot->obj == NULL || slot->obj == SLOT_TOMBSTONE) {
				zend_object *result = Z_OBJ_P(return_value);
				memoTrackResult(result, key);
				pt_memo.occupy(slot, key, result);
			}
		}
	}

	static void clear()
	{
		if (!pt_cache_inited) {
			return;
		}
		pt_invalidate_active = false;
		weakResultsClean(&pt_memo_results);
		weakResultsClean(&pt_intern_objs);
		pt_invalidate_active = true;
		pt_memo.reset();
		pt_intern.reset();
	}
};

} // namespace phpstanturbo

using phpstanturbo::TypeCombinatorCache;
using phpstanturbo::pt_cache_inited;
using phpstanturbo::pt_ce_kinds;
using phpstanturbo::pt_fn_do_intersect;
using phpstanturbo::pt_fn_do_remove;
using phpstanturbo::pt_fn_do_union;
using phpstanturbo::pt_intern;
using phpstanturbo::pt_intern_objs;
using phpstanturbo::pt_memo;
using phpstanturbo::pt_memo_results;
using phpstanturbo::pt_obj_serials;
using phpstanturbo::pt_next_serial;
using phpstanturbo::pt_guard_ce;
using phpstanturbo::pt_guard_resolved;
using phpstanturbo::pt_guard_unavailable;
using phpstanturbo::pt_type_hashes;
using phpstanturbo::typeHashDtor;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

/* {{{ lifecycle */

void pt_type_combinator_cache_rinit()
{
	if (pt_cache_inited) {
		return;
	}
	zend_hash_init(&pt_type_hashes, 4096, NULL, typeHashDtor, 0);
	zend_hash_init(&pt_ce_kinds, 128, NULL, NULL, 0);
	zend_hash_init(&pt_obj_serials, 1024, NULL, NULL, 0);
	zend_hash_init(&pt_memo_results, 4096, NULL, phpstanturbo::memoResultDtor, 0);
	zend_hash_init(&pt_intern_objs, 4096, NULL, phpstanturbo::internEntryDtor, 0);
	pt_memo.init();
	pt_intern.init();
	pt_next_serial = 1;
	pt_guard_ce = NULL;
	pt_guard_resolved = false;
	pt_guard_unavailable = false;
	phpstanturbo::pt_invalidate_active = true;
	pt_cache_inited = true;
}

void pt_type_combinator_cache_rshutdown()
{
	if (!pt_cache_inited) {
		return;
	}
	TypeCombinatorCache::clear();
	phpstanturbo::pt_invalidate_active = false;
	pt_weakrefs_hash_destroy(&pt_memo_results);
	pt_weakrefs_hash_destroy(&pt_intern_objs);
	pt_memo.destroy();
	pt_intern.destroy();
	pt_weakrefs_hash_destroy(&pt_type_hashes);
	pt_weakrefs_hash_destroy(&pt_obj_serials);
	zend_hash_destroy(&pt_ce_kinds);
	pt_fn_do_union = NULL;
	pt_fn_do_intersect = NULL;
	pt_fn_do_remove = NULL;
	pt_guard_ce = NULL;
	pt_guard_resolved = false;
	pt_cache_inited = false;
}

/* }}} */

/* {{{ registration */

zend_class_entry *pt_ce_type_combinator_cache = NULL;

static zend_function *resolveOp(zend_function **slot, const char *lcname, size_t len)
{
	if (*slot == NULL) {
		zend_class_entry *ce = pt_class(PT_CLASS_TYPE_COMBINATOR);
		if (ce == NULL) {
			return NULL;
		}
		*slot = pt_find_method(ce, lcname, len);
	}
	return *slot;
}

void pt_register_type_combinator_cache()
{
	static const char *TYPE_CLASS = "PHPStan\\Type\\Type";

	reg::Class cls("PHPStanTurbo\\TypeCombinatorCache");

	cls.method("union", reg::PublicStatic, 0, { reg::variadicObj("types", TYPE_CLASS) }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *types;
		uint32_t count;
		ZEND_PARSE_PARAMETERS_START(0, -1)
			Z_PARAM_VARIADIC('*', types, count)
		ZEND_PARSE_PARAMETERS_END();
		TypeCombinatorCache::run(
			INTERNAL_FUNCTION_PARAM_PASSTHRU,
			TypeCombinatorCache::UNION,
			resolveOp(&pt_fn_do_union, "dounion", sizeof("dounion") - 1),
			types,
			count);
	});

	cls.method("intersect", reg::PublicStatic, 0, { reg::variadicObj("types", TYPE_CLASS) }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *types;
		uint32_t count;
		ZEND_PARSE_PARAMETERS_START(0, -1)
			Z_PARAM_VARIADIC('*', types, count)
		ZEND_PARSE_PARAMETERS_END();
		TypeCombinatorCache::run(
			INTERNAL_FUNCTION_PARAM_PASSTHRU,
			TypeCombinatorCache::INTERSECT,
			resolveOp(&pt_fn_do_intersect, "dointersect", sizeof("dointersect") - 1),
			types,
			count);
	});

	cls.method("remove", reg::PublicStatic, 2, { reg::obj("fromType", TYPE_CLASS), reg::obj("typeToRemove", TYPE_CLASS) }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *fromType;
		zval *typeToRemove;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(fromType)
			Z_PARAM_OBJECT(typeToRemove)
		ZEND_PARSE_PARAMETERS_END();
		zval args[2];
		ZVAL_COPY_VALUE(&args[0], fromType);
		ZVAL_COPY_VALUE(&args[1], typeToRemove);
		TypeCombinatorCache::run(
			INTERNAL_FUNCTION_PARAM_PASSTHRU,
			TypeCombinatorCache::REMOVE,
			resolveOp(&pt_fn_do_remove, "doremove", sizeof("doremove") - 1),
			args,
			2);
	});

	cls.method("clearCache", reg::PublicStatic, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		TypeCombinatorCache::clear();
	});

	pt_ce_type_combinator_cache = cls.register_();
}

/* }}} */
