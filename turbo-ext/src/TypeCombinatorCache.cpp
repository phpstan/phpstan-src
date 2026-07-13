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
 * Two structures back this:
 *
 *  - a per-object 128-bit structural hash of every Type, cached in a *weak* map so
 *    the entry disappears with the object: the cache retains nothing and an object
 *    address can never be mistaken for a freed one's. Composite types hash from
 *    their children's cached hashes, so hashing a new type costs O(#properties),
 *    not O(tree).
 *  - the memo itself, keyed by the operation plus its arguments' hashes.
 *
 * The hash is 128 bits precisely so the key can be trusted without keeping the
 * arguments alive to re-verify a hit: over the ~10^5 distinct keys of a run the
 * collision probability is ~10^-27. A 64-bit key would be ~10^-9 per run, which
 * across a user base is a silently wrong analysis result — not acceptable.
 *
 * The memo is cleared whenever a container is created (TypeCombinator::clearCache()).
 * Memoization hands back *shared* Type instances, and a Type lazily resolves a
 * ClassReflection belonging to the container that created it — so entries must not
 * outlive their container. Production runs one container per process; the test
 * suite does not.
 */

#include "support.h"
#include "zv.h"

#include <Zend/zend_weakrefs.h>

namespace phpstanturbo {

/* Beyond this argument count a call is computed without consulting the memo:
 * the key would not fit the stack buffer and such calls are vanishingly rare. */
static constexpr uint32_t MEMO_ARGS_LIMIT = 16;

/* Depth guard for the structural walk. Types nest ~10 deep; anything beyond this
 * is not hashed at all (the call bypasses the memo) rather than hashed coarsely,
 * because a coarse hash would be an unsound key. */
static constexpr uint32_t HASH_DEPTH_LIMIT = 64;

/* Safety net on memo growth; a self-analysis run settles at ~1.3e5 entries. */
static constexpr uint32_t MEMO_ENTRIES_LIMIT = 1 << 19;

struct Hash128
{
	uint64_t a;
	uint64_t b;
};

/* A memo entry owns its result. */
struct MemoEntry
{
	zend_object *result;
};

/* The weak map derives its key from the object address the way the engine does
 * (see zend_weakrefs.c). That derivation is an engine internal, so the entry keeps
 * the object it belongs to and every hit is checked against it: should the engine
 * ever key differently, this degrades to a cache miss instead of handing back
 * another object's hash. */
struct TypeHash
{
	zend_object *obj;
	Hash128 hash;
};

/* Objects outside the type system (ClassReflection, method reflections held in
 * ObjectType::$methodCache, …) take part in the hash by identity. They must NOT be hashed
 * by address: addresses are reused once an object is freed, so two different types could
 * hash alike — and they vary between runs, which made results non-deterministic. Each such
 * object instead gets a serial that is never reused. */
struct ObjSerial
{
	zend_object *obj;
	uint64_t serial;
};

static inline zend_ulong weakKey(const zend_object *obj)
{
	return ((zend_ulong) (uintptr_t) obj) >> ZEND_MM_ALIGNMENT_LOG2;
}

static HashTable pt_type_hashes;   /* weak: zend_object* -> Hash128* */
static HashTable pt_memo;          /* binary key -> MemoEntry* */
static HashTable pt_ce_kinds;      /* zend_class_entry* -> kind|slots */
static HashTable pt_obj_serials;   /* weak: zend_object* -> ObjSerial* (identity-hashed objects) */
static uint64_t pt_next_serial = 1;
static bool pt_cache_inited = false;


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
	ObjSerial *known = (ObjSerial *) zend_hash_index_find_ptr(&pt_obj_serials, weakKey(obj));
	if (known != NULL && known->obj == obj) {
		return known->serial;
	}

	ObjSerial *entry = (ObjSerial *) emalloc(sizeof(ObjSerial));
	entry->obj = obj;
	entry->serial = pt_next_serial++;
	if (zend_weakrefs_hash_add_ptr(&pt_obj_serials, obj, entry) == NULL) {
		uint64_t serial = entry->serial;
		efree(entry);
		return serial;
	}

	return entry->serial;
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

static bool hashObject(zend_object *obj, Hash128 &out, uint32_t depth)
{
	if (UNEXPECTED(depth > HASH_DEPTH_LIMIT)) {
		return false;
	}

	TypeHash *cached = (TypeHash *) zend_hash_index_find_ptr(&pt_type_hashes, weakKey(obj));
	if (cached != NULL && cached->obj == obj) {
		out = cached->hash;
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

	TypeHash *stored = (TypeHash *) emalloc(sizeof(TypeHash));
	stored->obj = obj;
	stored->hash = h;
	if (zend_weakrefs_hash_add_ptr(&pt_type_hashes, obj, stored) == NULL) {
		/* Already registered by a re-entrant walk; keep the existing entry. */
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



/* {{{ the memo */

static void memoEntryDtor(zval *zv)
{
	MemoEntry *entry = (MemoEntry *) Z_PTR_P(zv);
	OBJ_RELEASE(entry->result);
	efree(entry);
}

static void typeHashDtor(zval *zv)
{
	efree(Z_PTR_P(zv));
}

static void objSerialDtor(zval *zv)
{
	efree(Z_PTR_P(zv));
}

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
		uint8_t key[2 + MEMO_ARGS_LIMIT * sizeof(Hash128)];
		size_t keyLen = 0;
		bool memoizable = argc > 0 && argc <= MEMO_ARGS_LIMIT && !guardActive();

		if (memoizable) {
			key[keyLen++] = (uint8_t) op;
			key[keyLen++] = (uint8_t) argc;
			for (uint32_t i = 0; i < argc; i++) {
				zval *arg = &args[i];
				ZVAL_DEREF(arg);
				Hash128 h;
				if (UNEXPECTED(Z_TYPE_P(arg) != IS_OBJECT) || !hashObject(Z_OBJ_P(arg), h, 0)) {
					memoizable = false;
					break;
				}
				memcpy(key + keyLen, &h, sizeof(h));
				keyLen += sizeof(h);
			}
		}

		if (memoizable) {
			MemoEntry *hit = (MemoEntry *) zend_hash_str_find_ptr(&pt_memo, (const char *) key, keyLen);
			if (hit != NULL) {
				GC_ADDREF(hit->result);
				RETVAL_OBJ(hit->result);
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

		if (memoizable && zend_hash_num_elements(&pt_memo) < MEMO_ENTRIES_LIMIT) {
			MemoEntry *entry = (MemoEntry *) emalloc(sizeof(MemoEntry));
			entry->result = Z_OBJ_P(return_value);
			GC_ADDREF(entry->result);
			if (zend_hash_str_add_ptr(&pt_memo, (const char *) key, keyLen, entry) == NULL) {
				OBJ_RELEASE(entry->result);
				efree(entry);
			}
		}
	}

	static void clear()
	{
		if (pt_cache_inited) {
			zend_hash_clean(&pt_memo);
		}
	}
};

} // namespace phpstanturbo

using phpstanturbo::TypeCombinatorCache;
using phpstanturbo::pt_cache_inited;
using phpstanturbo::pt_ce_kinds;
using phpstanturbo::pt_fn_do_intersect;
using phpstanturbo::pt_fn_do_remove;
using phpstanturbo::pt_fn_do_union;
using phpstanturbo::pt_memo;
using phpstanturbo::pt_obj_serials;
using phpstanturbo::pt_next_serial;
using phpstanturbo::objSerialDtor;
using phpstanturbo::pt_guard_ce;
using phpstanturbo::pt_guard_resolved;
using phpstanturbo::pt_guard_unavailable;
using phpstanturbo::pt_type_hashes;
using phpstanturbo::memoEntryDtor;
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
	zend_hash_init(&pt_memo, 4096, NULL, memoEntryDtor, 0);
	zend_hash_init(&pt_ce_kinds, 128, NULL, NULL, 0);
	zend_hash_init(&pt_obj_serials, 1024, NULL, objSerialDtor, 0);
	pt_next_serial = 1;
	pt_guard_ce = NULL;
	pt_guard_resolved = false;
	pt_guard_unavailable = false;
	pt_cache_inited = true;
}

void pt_type_combinator_cache_rshutdown()
{
	if (!pt_cache_inited) {
		return;
	}
	zend_hash_destroy(&pt_memo);
	zend_weakrefs_hash_destroy(&pt_type_hashes);
	zend_weakrefs_hash_destroy(&pt_obj_serials);
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
