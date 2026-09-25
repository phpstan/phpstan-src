/*
 * PHPStanTurbo\TypeCombinatorCache — native implementation of
 * PHPStan\Type\TypeCombinatorCache.
 *
 * TypeCombinator::union()/intersect()/remove() route through this class when the
 * extension is active. Roughly 91% of the calls in an analysis run repeat an
 * argument tuple whose result was already computed, so each operation is memoized
 * on a structural key of its arguments; a miss computes the operation through
 * the native TypeCombinator's doUnion() and friends (TypeCombinator.cpp) — a
 * direct C++ call, no engine frame.
 *
 * The results are NOT interned: no canonical instance per type value is kept, and
 * operations that arrive at the same value by different routes hand back
 * different objects, exactly as the PHP implementation does. Interning (tried in
 * 873ede9b0a, reverted) made equal types share one instance across unrelated
 * call sites, and every place in the analyser comparing Type objects by identity
 * - a proxy for "the same reference" that only holds while equal values stay
 * distinct objects - then narrowed or resolved types it never meant to
 * (phpstan/phpstan#15151).
 *
 * The memo does hand one result object of its own to all the structurally
 * equal argument tuples of the same operation, where the PHP implementation
 * computes a fresh object per call — and the objects nested in the shared
 * result (a new union's members, say) are the ones of the call that computed
 * it. That sharing is what memoization is; nothing compares such results by
 * identity with the ones of other calls.
 *
 * A result that IS one of the operands, or a member of a union or intersection
 * operand, is never shared: TypeCombinator hands back that object of the call
 * at hand (union() of one type, remove() of nothing, removeNull() of `T|null`
 * returning the `T` member), and callers test that identity
 * (ArrayType::setExistingOffsetValueType() treats `union(...) === $this->itemType`
 * as "nothing was written"). An operand result records the operand's position
 * instead, and a hit returns the operand at that position of the new call; a
 * member result is marked as one, and a hit returns the member of the new
 * call's operands with the recorded member's structural hash (unique among
 * all the operands' members, or the call is not memoized) — in both cases the
 * object the PHP implementation returns.
 *
 * Two structures back this:
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
 *
 * The hash is 128 bits precisely so the key can be trusted without keeping the
 * arguments alive to re-verify a hit: over the ~10^5 distinct keys of a run the
 * collision probability is ~10^-28. A 64-bit key would be ~10^-9 per run, which
 * across a user base is a silently wrong analysis result — not acceptable.
 *
 * The memo is cleared whenever a container is created (TypeCombinator::clearCache()).
 * Memoization hands back *shared* Type instances, and a Type lazily resolves a
 * ClassReflection belonging to the container that created it — so entries must not
 * outlive their container. Production runs one container per process; the test
 * suite does not.
 */

#include "support.h"
#include "TypeTraits.h"
#include "generated/IntersectionType.h"
#include "generated/TypeCombinatorCache.h"
#include "generated/UnionType.h"

namespace sigs = ptdecl::TypeCombinatorCache::sig;
#include "zv.h"

#include <Zend/zend_weakrefs.h>
#if PHP_VERSION_ID >= 80400
#include <Zend/zend_lazy_objects.h>
#endif

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

/* Initial slot count of the memo table; must be a power of two. */
static constexpr uint32_t MEMO_INITIAL_CAPACITY_LIMIT = 1 << 13;

struct Hash128
{
	uint64_t a;
	uint64_t b;
};

/* An occupied memo slot borrows its result; result == NULL marks an empty
 * slot, result == MEMO_TOMBSTONE a deleted one (the probe chain must stay
 * intact, so deletion cannot empty a slot). A result that was an operand of
 * the call carries the operand's position + 1 in the pointer's alignment bits,
 * a result that was a member of an operand MEMO_MEMBER_TAG (0 = a result of
 * its own); the slot stays 24 bytes. */
struct MemoSlot
{
	Hash128 key;
	zend_object *result;
};

#define MEMO_TOMBSTONE ((zend_object *) 1)

/* The alignment bits of a zend_object pointer: the highest value marks a
 * member result, operand positions up to the count below it fit next to the
 * pointer; a call returning a later operand is not memoized. */
static constexpr uintptr_t MEMO_OPERAND_TAG_MASK = alignof(zend_object) - 1;
static constexpr uintptr_t MEMO_MEMBER_TAG = MEMO_OPERAND_TAG_MASK;
static constexpr uint32_t MEMO_OPERAND_POSITIONS_LIMIT = (uint32_t) MEMO_MEMBER_TAG - 1;
static_assert(MEMO_OPERAND_POSITIONS_LIMIT >= 3, "zend_object pointers must leave alignment bits for the operand position");

static zend_always_inline zend_object *memoSlotObject(const zend_object *result)
{
	return (zend_object *) ((uintptr_t) result & ~MEMO_OPERAND_TAG_MASK);
}

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

static HashTable pt_type_hashes;   /* weak: zend_object* -> Hash128 packed in the bucket zval */
static HashTable pt_ce_kinds;      /* zend_class_entry* -> kind|slots */
static HashTable pt_obj_serials;   /* weak: zend_object* -> IS_LONG serial (identity-hashed objects) */
static HashTable pt_memo_results;  /* weak: zend_object* -> IS_PTR KeyList */
static MemoSlot *pt_memo_slots = NULL;
static uint32_t pt_memo_mask = 0;
static uint32_t pt_memo_count = 0;
static uint32_t pt_memo_tombstones = 0;
static uint64_t pt_next_serial = 1;
static bool pt_cache_inited = false;
static bool pt_invalidate_active = false;

/* The objects of the structurally hashed classes carry this header in front
 * of their zend_object (the classes get typeObjectCreate() as their
 * create_object when they are declared, see
 * pt_type_combinator_cache_adopt_class()): the object's structural hash once
 * computed, and the list of memo keys mapping to it as a result. For those
 * objects it replaces the weak maps pt_type_hashes and pt_memo_results — no
 * EG(weakrefs) registration and no weak-reference notification when the
 * object dies, and the lookup is a load instead of a hash probe. An object
 * without the header (a class with a create_object of its own) keeps using
 * the weak maps. memoGeneration ties the key list to the memo it was built
 * against: clear() bumps pt_memo_generation, and a list from an older
 * generation names slots that no longer exist. */
struct TypeObjectHeader
{
	Hash128 hash;
	KeyList *memoKeys;
	uint32_t hashComputed;
	uint32_t memoGeneration;
	zend_object std;
};

static zend_object_handlers pt_type_object_handlers;
static bool pt_type_object_handlers_inited = false;
static uint32_t pt_memo_generation = 1;

static zend_always_inline TypeObjectHeader *typeObjectHeader(zend_object *obj)
{
	if (obj->handlers != &pt_type_object_handlers) return NULL;
	return (TypeObjectHeader *) ((char *) obj - offsetof(TypeObjectHeader, std));
}



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
	if (known != NULL) return (uint64_t) Z_LVAL_P(known);

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
				if (!hashZval(slot, h, depth + 1)) return false;
			}
			return true;
		}
		case IS_OBJECT: {
			zend_object *obj = Z_OBJ_P(value);
			if (cePlan(obj->ce).kind == CE_STRUCTURAL) {
				Hash128 inner;
				if (!hashObject(obj, inner, depth + 1)) return false;
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
	if (obj->ce->default_properties_count != 0) return false;
	Hash128 h = { FNV_OFFSET_A, FNV_OFFSET_B };
	mixU64(h, (uint64_t) (uintptr_t) obj->ce);
	out = h;

	return true;
}

static bool hashObject(zend_object *obj, Hash128 &out, uint32_t depth)
{
	if (UNEXPECTED(depth > HASH_DEPTH_LIMIT)) return false;

	/* Argless leaf types (MixedType, NullType, …) are ~30% of hashed objects;
	 * recomputing their class-only hash is cheaper than a table lookup, and
	 * caching it would spend a map entry plus an EG(weakrefs) registration per
	 * instance to save nothing. */
	if (hashZeroSlotObject(obj, out)) return true;

	TypeObjectHeader *header = typeObjectHeader(obj);
	if (header != NULL) {
		if (header->hashComputed) {
			out = header->hash;
			return true;
		}
	} else {
		Hash128 *cached = (Hash128 *) zend_hash_index_find_ptr(&pt_type_hashes, zend_object_to_weakref_key(obj));
		if (cached != NULL) {
			out = *cached;
			return true;
		}
	}

	CePlan plan = cePlan(obj->ce);
	Hash128 h = { FNV_OFFSET_A, FNV_OFFSET_B };
	/* The class entry pointer identifies the class uniquely within the request. */
	mixU64(h, (uint64_t) (uintptr_t) obj->ce);

	for (uint32_t i = 0; i < plan.slots; i++) {
		if (!hashZval(OBJ_PROP_NUM(obj, i), h, depth + 1)) return false;
	}

	/* The 16 hash bytes live behind a real IS_PTR value; they cannot go into the
	 * bucket zval itself, because a bucket only carries 8 payload bytes — u1 is
	 * type_info the engine inspects (zend_hash_rehash treats Z_TYPE == IS_UNDEF
	 * as a hole) and u2 is Z_NEXT, the collision chain, overwritten on insert.
	 * NULL return = already registered by a re-entrant walk; the existing entry
	 * holds the same bytes (the hash is a pure function of the object's value). */
	if (header != NULL) {
		header->hash = h;
		header->hashComputed = 1;
	} else {
		Hash128 *stored = (Hash128 *) emalloc(sizeof(Hash128));
		*stored = h;
		if (zend_weakrefs_hash_add_ptr(&pt_type_hashes, obj, stored) == NULL) {
			efree(stored);
		}
	}

	out = h;

	return true;
}

/* }}} */

/* {{{ member results */

/* The members of a union or intersection argument (the declared $types
 * slot, borrowed), NULL for any other argument */
static zval *argumentMembers(zval *arg)
{
	zend_object *obj = Z_OBJ_P(arg);
	uint32_t slot;
	if (instanceof_function(obj->ce, pt_ce_union_type)) {
		slot = ptdecl::UnionType::slot::types;
	} else if (instanceof_function(obj->ce, pt_ce_intersection_type)) {
		slot = ptdecl::IntersectionType::slot::types;
	} else {
		return NULL;
	}
	zval *members = OBJ_PROP_NUM(obj, slot);
	return Z_TYPE_P(members) == IS_ARRAY ? members : NULL;
}

/* The structural hash of an argument's member; false when it has none (not
 * a structurally hashed object, or too deep) */
static bool memberHash(zval *member, Hash128 &out)
{
	ZVAL_DEREF(member);
	if (Z_TYPE_P(member) != IS_OBJECT || cePlan(Z_OBJCE_P(member)).kind != CE_STRUCTURAL) return false;
	return hashObject(Z_OBJ_P(member), out, 0);
}

/* Whether the computed result is a member of a union or intersection
 * argument, and if so whether a structurally equal call can find the member
 * it has to return again: the result is a member exactly once, and no other
 * member of any argument hashes like it. isMember false = not a member (the
 * result may still be memoized); findable false = do not memoize. */
static void classifyMemberResult(zend_object *result, zval *args, uint32_t argc, bool &isMember, bool &findable)
{
	isMember = false;
	findable = false;
	uint32_t occurrences = 0;
	for (uint32_t i = 0; i < argc; i++) {
		zval *arg = &args[i];
		ZVAL_DEREF(arg);
		zval *members = argumentMembers(arg);
		if (members == NULL) continue;
		for (zv::ArrayEntry entry : zv::ArrRef(members)) {
			zval *member = entry.value().deref().raw();
			if (Z_TYPE_P(member) == IS_OBJECT && Z_OBJ_P(member) == result) {
				occurrences++;
			}
		}
	}
	if (occurrences == 0) return;
	isMember = true;
	if (occurrences > 1) return;

	zval resultZv;
	ZVAL_OBJ(&resultZv, result);
	Hash128 resultHash;
	if (!memberHash(&resultZv, resultHash)) return;
	uint32_t sameHash = 0;
	for (uint32_t i = 0; i < argc; i++) {
		zval *arg = &args[i];
		ZVAL_DEREF(arg);
		zval *members = argumentMembers(arg);
		if (members == NULL) continue;
		for (zv::ArrayEntry entry : zv::ArrRef(members)) {
			Hash128 h;
			if (!memberHash(entry.value().raw(), h)) continue;
			if (h.a == resultHash.a && h.b == resultHash.b) {
				sameHash++;
			}
		}
	}
	findable = sameHash == 1;
}

/* The member of this call's union or intersection arguments hashing like
 * the recorded member result of a structurally equal call (borrowed), NULL
 * when there is none */
static zval *findMemberResult(zend_object *recorded, zval *args, uint32_t argc)
{
	zval recordedZv;
	ZVAL_OBJ(&recordedZv, recorded);
	Hash128 recordedHash;
	if (!memberHash(&recordedZv, recordedHash)) return NULL;
	for (uint32_t i = 0; i < argc; i++) {
		zval *arg = &args[i];
		ZVAL_DEREF(arg);
		zval *members = argumentMembers(arg);
		if (members == NULL) continue;
		for (zv::ArrayEntry entry : zv::ArrRef(members)) {
			zval *member = entry.value().deref().raw();
			Hash128 h;
			if (!memberHash(member, h)) continue;
			if (h.a == recordedHash.a && h.b == recordedHash.b) return member;
		}
	}
	return NULL;
}

/* }}} */

/* {{{ RecursionGuard

 * While RecursionGuard::$context is non-empty, run()/runOnObjectIdentity() short-circuit
 * to ErrorType, so a type operation's result depends on the call stack rather than only on
 * its arguments — and runOnObjectIdentity() keys on spl_object_id(), which memoization
 * itself perturbs by handing back shared instances. The memo is therefore bypassed whole
 * while a guard is active: entries are only ever produced and consumed with an empty
 * context, where the operations are pure functions of their arguments. The context is the
 * shadowing RecursionGuard's (pt_recursion_guard_active(), RecursionGuard.cpp), which
 * answers active when it cannot be read — disabling the memo rather than risking an
 * unsound entry. */

static bool guardActive()
{
	return pt_recursion_guard_active();
}

/* }}} */



static void typeHashDtor(zval *zv)
{
	efree(Z_PTR_P(zv));
}

/* {{{ the memo */

/* Occupied slot holding the key, or NULL. The table's live+tombstone load
 * never reaches 1 (memoGrow() keeps it at 3/4 at most), so the probe always
 * terminates. */
static zend_always_inline MemoSlot *memoLookup(Hash128 key)
{
	/* FNV-1a's low bits mix worst; fold the high half in before masking. */
	uint32_t idx = (uint32_t) (key.a ^ (key.a >> 32)) & pt_memo_mask;
	for (;;) {
		MemoSlot *slot = &pt_memo_slots[idx];
		if (slot->result == NULL) return NULL;
		if (slot->result != MEMO_TOMBSTONE && slot->key.a == key.a && slot->key.b == key.b) return slot;
		idx = (idx + 1) & pt_memo_mask;
	}
}

/* Slot to insert the key into: the occupied slot already holding it, else the
 * first tombstone on its probe path, else the terminating empty slot. */
static zend_always_inline MemoSlot *memoInsertPos(Hash128 key)
{
	uint32_t idx = (uint32_t) (key.a ^ (key.a >> 32)) & pt_memo_mask;
	MemoSlot *tombstone = NULL;
	for (;;) {
		MemoSlot *slot = &pt_memo_slots[idx];
		if (slot->result == NULL) return tombstone != NULL ? tombstone : slot;
		if (slot->result == MEMO_TOMBSTONE) {
			if (tombstone == NULL) {
				tombstone = slot;
			}
		} else if (slot->key.a == key.a && slot->key.b == key.b) {
			return slot;
		}
		idx = (idx + 1) & pt_memo_mask;
	}
}

static void memoGrow()
{
	uint32_t oldCapacity = pt_memo_mask + 1;
	MemoSlot *oldSlots = pt_memo_slots;

	/* Tombstones are dropped by the rehash; only grow the table when live
	 * entries alone justify it, otherwise rehash at the same size. */
	uint32_t newCapacity = ((uint64_t) pt_memo_count * 4 > (uint64_t) oldCapacity * 2) ? oldCapacity * 2 : oldCapacity;
	pt_memo_slots = (MemoSlot *) ecalloc(newCapacity, sizeof(MemoSlot));
	pt_memo_mask = newCapacity - 1;
	pt_memo_tombstones = 0;
	for (uint32_t i = 0; i < oldCapacity; i++) {
		if (oldSlots[i].result != NULL && oldSlots[i].result != MEMO_TOMBSTONE) {
			*memoInsertPos(oldSlots[i].key) = oldSlots[i];
		}
	}
	efree(oldSlots);
}

/* {{{ weak-result mode: invalidation on result death + key-list upkeep */

static void memoInvalidate(const KeyList *list)
{
	for (uint32_t i = 0; i < list->count; i++) {
		Hash128 key = list->keys[i];
		uint32_t idx = (uint32_t) (key.a ^ (key.a >> 32)) & pt_memo_mask;
		for (;;) {
			MemoSlot *slot = &pt_memo_slots[idx];
			if (slot->result == NULL) break;
			if (slot->result != MEMO_TOMBSTONE && memoSlotObject(slot->result) == list->obj && slot->key.a == key.a && slot->key.b == key.b) {
				slot->result = MEMO_TOMBSTONE;
				pt_memo_count--;
				pt_memo_tombstones++;
				break;
			}
			idx = (idx + 1) & pt_memo_mask;
		}
	}
}

/* Value dtor of pt_memo_results: runs when a memoized result object dies (and
 * on bulk cleanup, where pt_invalidate_active is off and the memo is reset
 * separately). */
static void memoResultDtor(zval *zv)
{
	KeyList *list = (KeyList *) Z_PTR_P(zv);
	if (pt_invalidate_active) {
		memoInvalidate(list);
	}
	efree(list);
}

static void memoTrackResult(zend_object *obj, Hash128 key)
{
	TypeObjectHeader *header = typeObjectHeader(obj);
	if (header != NULL) {
		KeyList *list = header->memoKeys;
		if (list == NULL) {
			list = (KeyList *) emalloc(sizeof(KeyList));
			list->obj = obj;
			list->count = 0;
			list->cap = 4;
			header->memoKeys = list;
		} else if (header->memoGeneration != pt_memo_generation) {
			list->count = 0;
		}
		header->memoGeneration = pt_memo_generation;
		if (list->count == list->cap) {
			list->cap *= 2;
			list = (KeyList *) erealloc(list, sizeof(KeyList) + (list->cap - 4) * sizeof(Hash128));
			header->memoKeys = list;
		}
		list->keys[list->count++] = key;
		return;
	}

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

/* Purge every key list without touching the memo slots (the caller resets
 * those wholesale). On 8.4 the unregister loop is spelled out, as in
 * pt_weakrefs_hash_destroy. */
static void memoResultsClean()
{
	pt_invalidate_active = false;
#if PHP_VERSION_ID < 80500
	zend_ulong objKey;
	ZEND_HASH_MAP_FOREACH_NUM_KEY(&pt_memo_results, objKey) {
		zend_weakrefs_hash_del(&pt_memo_results, zend_weakref_key_to_object(objKey));
	} ZEND_HASH_FOREACH_END();
#else
	zend_weakrefs_hash_clean(&pt_memo_results);
#endif
	pt_invalidate_active = true;
}

/* }}} */

/* {{{ the header-carrying objects */

static zend_object *typeObjectCreate(zend_class_entry *ce)
{
	TypeObjectHeader *header = (TypeObjectHeader *) zend_object_alloc(sizeof(TypeObjectHeader), ce);
	header->hashComputed = 0;
	header->memoKeys = NULL;
	header->memoGeneration = 0;
	zend_object_std_init(&header->std, ce);
	object_properties_init(&header->std, ce);
	header->std.handlers = &pt_type_object_handlers;

	return &header->std;
}

static void typeObjectFree(zend_object *obj)
{
	TypeObjectHeader *header = (TypeObjectHeader *) ((char *) obj - offsetof(TypeObjectHeader, std));
	if (header->memoKeys != NULL) {
		if (pt_invalidate_active && header->memoGeneration == pt_memo_generation) {
			memoInvalidate(header->memoKeys);
		}
		efree(header->memoKeys);
		header->memoKeys = NULL;
	}
	zend_object_std_dtor(obj);
}

/* zend_objects_clone_obj() with the header: the clone is a new object, its
 * hash is computed afresh and it is nobody's memo result. A lazy object is
 * cloned by the engine's own handler (zend_lazy_object_clone() is not
 * exported); that clone has no header and keeps to the weak maps. */
static zend_object *typeObjectClone(zend_object *old)
{
#if PHP_VERSION_ID >= 80400
	if (UNEXPECTED(zend_object_is_lazy(old))) {
		return zend_objects_clone_obj(old);
	}
#endif
	TypeObjectHeader *header = (TypeObjectHeader *) zend_object_alloc(sizeof(TypeObjectHeader), old->ce);
	header->hashComputed = 0;
	header->memoKeys = NULL;
	header->memoGeneration = 0;
	zend_object_std_init(&header->std, old->ce);
	header->std.handlers = &pt_type_object_handlers;
	if (old->ce->default_properties_count) {
		zval *p = header->std.properties_table;
		zval *end = p + old->ce->default_properties_count;
		do {
			ZVAL_UNDEF(p);
			p++;
		} while (p != end);
	}
	zend_objects_clone_members(&header->std, old);

	return &header->std;
}

static void typeObjectHandlersInit()
{
	if (pt_type_object_handlers_inited) return;
	memcpy(&pt_type_object_handlers, &std_object_handlers, sizeof(zend_object_handlers));
	pt_type_object_handlers.offset = offsetof(TypeObjectHeader, std);
	pt_type_object_handlers.free_obj = typeObjectFree;
	pt_type_object_handlers.clone_obj = typeObjectClone;
	pt_type_object_handlers_inited = true;
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

	/* the unmemoized computation of a miss: the native TypeCombinator's
	 * do*() body over the argument vector */
	typedef zv::Val (*Compute)(uint32_t argc, zval *argv);

	static zv::Val run(Op op, Compute compute, zval *args, uint32_t argc)
	{
		Hash128 key = { FNV_OFFSET_A, FNV_OFFSET_B };
		bool memoizable = argc > 0 && argc <= MEMO_ARGS_LIMIT && !guardActive();

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
			MemoSlot *slot = memoLookup(key);
			if (slot != NULL) {
				uintptr_t operandTag = (uintptr_t) slot->result & MEMO_OPERAND_TAG_MASK;
				if (operandTag == MEMO_MEMBER_TAG) {
					/* the member of this call's operands, found by the recorded
					 * member's hash (unique when the entry was made); computed
					 * afresh should it be missing */
					zval *member = findMemberResult(memoSlotObject(slot->result), args, argc);
					if (EXPECTED(member != NULL)) return zv::Val::copyOf(zv::Ref(member));
					memoizable = false;
				} else if (operandTag != 0) {
					/* every argument hashed above is an object */
					zval *operand = &args[operandTag - 1];
					ZVAL_DEREF(operand);
					return zv::Val::copyOf(zv::Ref(operand));
				} else {
					GC_ADDREF(slot->result);
					zval hit;
					ZVAL_OBJ(&hit, slot->result);
					return zv::Val::adopt(hit);
				}
			}
		}

		zv::Val result = compute(argc, args);
		if (UNEXPECTED(result.isUndef()) || Z_TYPE_P(result.raw()) != IS_OBJECT) return result;

		uintptr_t operandTag = 0;
		if (memoizable) {
			/* the operand the result is, by position; one object passed at two
			 * positions leaves the position a structurally equal call would
			 * return undecided, so such a call is not memoized */
			for (uint32_t i = 0; i < argc; i++) {
				zval *arg = &args[i];
				ZVAL_DEREF(arg);
				if (Z_OBJ_P(arg) != Z_OBJ_P(result.raw())) continue;
				if (operandTag != 0 || i >= MEMO_OPERAND_POSITIONS_LIMIT) {
					memoizable = false;
					break;
				}
				operandTag = (uintptr_t) i + 1;
			}
		}
		if (memoizable) {
			/* a member of a union or intersection operand is recorded as one;
			 * a member that is also an operand, or that a structurally equal
			 * call could not find again unambiguously, is not memoized */
			bool isMember, findable;
			classifyMemberResult(Z_OBJ_P(result.raw()), args, argc, isMember, findable);
			if (isMember) {
				if (operandTag != 0 || !findable) {
					memoizable = false;
				} else {
					operandTag = MEMO_MEMBER_TAG;
				}
			}
		}

		if (memoizable && pt_memo_count < MEMO_ENTRIES_LIMIT) {
			/* Fresh lookup: the callback re-enters these operations for nested
			 * types, which may have inserted this very key or grown the table. */
			MemoSlot *slot = memoInsertPos(key);
			if (slot->result == NULL || slot->result == MEMO_TOMBSTONE) {
				if (slot->result == MEMO_TOMBSTONE) {
					pt_memo_tombstones--;
				}
				slot->key = key;
				slot->result = (zend_object *) ((uintptr_t) Z_OBJ_P(result.raw()) | operandTag);
				pt_memo_count++;
				memoTrackResult(Z_OBJ_P(result.raw()), key);

				if ((uint64_t) (pt_memo_count + pt_memo_tombstones) * 4 > (uint64_t) (pt_memo_mask + 1) * 3) {
					memoGrow();
				}
			}
		}

		return result;
	}

	/* doRemove($fromType, $typeToRemove) over the two-argument vector */
	static zv::Val computeRemove(uint32_t argc, zval *argv)
	{
		(void) argc;
		return pt_type_combinator_do_remove(&argv[0], &argv[1]);
	}

	static void clear()
	{
		if (!pt_cache_inited) return;
		memoResultsClean();
		pt_memo_generation++;
		if (pt_memo_mask + 1 > MEMO_INITIAL_CAPACITY_LIMIT) {
			efree(pt_memo_slots);
			pt_memo_slots = (MemoSlot *) ecalloc(MEMO_INITIAL_CAPACITY_LIMIT, sizeof(MemoSlot));
			pt_memo_mask = MEMO_INITIAL_CAPACITY_LIMIT - 1;
		} else {
			memset(pt_memo_slots, 0, (size_t) (pt_memo_mask + 1) * sizeof(MemoSlot));
		}
		pt_memo_count = 0;
		pt_memo_tombstones = 0;
	}
};

} // namespace phpstanturbo

using phpstanturbo::TypeCombinatorCache;
using phpstanturbo::pt_cache_inited;
using phpstanturbo::pt_ce_kinds;
using phpstanturbo::pt_memo_slots;
using phpstanturbo::pt_memo_mask;
using phpstanturbo::pt_memo_count;
using phpstanturbo::pt_memo_results;
using phpstanturbo::pt_obj_serials;
using phpstanturbo::pt_next_serial;
using phpstanturbo::pt_type_hashes;
using phpstanturbo::typeHashDtor;
using phpstanturbo::MEMO_INITIAL_CAPACITY_LIMIT;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

/* {{{ lifecycle */

void pt_type_combinator_cache_rinit()
{
	if (pt_cache_inited) return;
	zend_hash_init(&pt_type_hashes, 4096, NULL, typeHashDtor, 0);
	zend_hash_init(&pt_ce_kinds, 128, NULL, NULL, 0);
	zend_hash_init(&pt_obj_serials, 1024, NULL, NULL, 0);
	zend_hash_init(&pt_memo_results, 4096, NULL, phpstanturbo::memoResultDtor, 0);
	pt_memo_slots = (phpstanturbo::MemoSlot *) ecalloc(MEMO_INITIAL_CAPACITY_LIMIT, sizeof(phpstanturbo::MemoSlot));
	pt_memo_mask = MEMO_INITIAL_CAPACITY_LIMIT - 1;
	pt_memo_count = 0;
	phpstanturbo::pt_memo_tombstones = 0;
	pt_next_serial = 1;
	phpstanturbo::pt_invalidate_active = true;
	pt_cache_inited = true;
}

void pt_type_combinator_cache_rshutdown()
{
	if (!pt_cache_inited) return;
	TypeCombinatorCache::clear();
	phpstanturbo::pt_invalidate_active = false;
	pt_weakrefs_hash_destroy(&pt_memo_results);
	efree(pt_memo_slots);
	pt_memo_slots = NULL;
	pt_memo_mask = 0;
	pt_memo_count = 0;
	phpstanturbo::pt_memo_tombstones = 0;
	pt_weakrefs_hash_destroy(&pt_type_hashes);
	pt_weakrefs_hash_destroy(&pt_obj_serials);
	zend_hash_destroy(&pt_ce_kinds);
	pt_cache_inited = false;
}

/* }}} */

/* {{{ registration */

zend_class_entry *pt_ce_type_combinator_cache = NULL;

zv::Val pt_type_combinator_cache_union(uint32_t argc, zval *argv)
{
	return TypeCombinatorCache::run(TypeCombinatorCache::UNION, pt_type_combinator_do_union, argv, argc);
}

zv::Val pt_type_combinator_cache_intersect(uint32_t argc, zval *argv)
{
	return TypeCombinatorCache::run(TypeCombinatorCache::INTERSECT, pt_type_combinator_do_intersect, argv, argc);
}

zv::Val pt_type_combinator_cache_remove(zval *fromType, zval *typeToRemove)
{
	zv::Args args{fromType, typeToRemove};
	return TypeCombinatorCache::run(TypeCombinatorCache::REMOVE, TypeCombinatorCache::computeRemove, args, 2);
}

/* Gives the objects of a structurally hashed shadowing class the header (see
 * TypeObjectHeader). Called for every class right after activation declares
 * it; PHP subclasses inherit create_object from it. A class with an allocator
 * of its own is left alone and keeps the weak maps. */
void pt_type_combinator_cache_adopt_class(zend_class_entry *ce, const char *realName)
{
	if (ce->create_object != NULL) return;
	size_t len = strlen(realName);
	bool structural = (len > sizeof("PHPStan\\Type\\") - 1 && memcmp(realName, "PHPStan\\Type\\", sizeof("PHPStan\\Type\\") - 1) == 0)
		|| (len > sizeof("PHPStan\\Php\\") - 1 && memcmp(realName, "PHPStan\\Php\\", sizeof("PHPStan\\Php\\") - 1) == 0)
		|| strcmp(realName, "PHPStan\\TrinaryLogic") == 0;
	if (!structural) return;
	phpstanturbo::typeObjectHandlersInit();
	ce->create_object = phpstanturbo::typeObjectCreate;
}

void pt_type_combinator_cache_clear()
{
	TypeCombinatorCache::clear();
}

PT_MINIT_REGISTRATION(pt_register_type_combinator_cache)
{
	reg::Class cls("PHPStan\\Type\\TypeCombinatorCache");
	ptdecl::TypeCombinatorCache::declareClass(cls);
	ptdecl::TypeCombinatorCache::declareProperties(cls);

	cls.method(sigs::union_, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *types;
		uint32_t count;
		ZEND_PARSE_PARAMETERS_START(0, -1)
			Z_PARAM_VARIADIC('*', types, count)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(pt_type_combinator_cache_union(count, types));
	});

	cls.method(sigs::intersect, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *types;
		uint32_t count;
		ZEND_PARSE_PARAMETERS_START(0, -1)
			Z_PARAM_VARIADIC('*', types, count)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(pt_type_combinator_cache_intersect(count, types));
	});

	cls.method(sigs::remove, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *fromType, *typeToRemove;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, fromType, typeToRemove)) RETURN_THROWS();
		PT_RETURN_VAL(pt_type_combinator_cache_remove(fromType, typeToRemove));
	});

	cls.method(sigs::clearCache, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		TypeCombinatorCache::clear();
	});

	cls.shadow(&pt_ce_type_combinator_cache);
}

/* }}} */
