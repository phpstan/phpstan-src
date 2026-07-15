/*
 * Zero-cost C++ wrappers over zvals, hashtables and zend objects, so the
 * native implementations can read like the PHP code they replace while
 * compiling to exactly the raw-macro instructions.
 *
 * Rules that keep this layer free:
 *  - everything is header-inline and trivially small; no virtuals anywhere
 *  - Ref/ArrRef/ObjRef/StrRef are borrowed views: a single pointer, trivially
 *    copyable, no ownership, no destructor work
 *  - Val (and Arr) are OWNED values: move-only RAII; the destructor releases.
 *    Ownership transfer is spelled std::move / take(), never a hidden copy
 *  - never allocate in this layer beyond what the wrapped zend call allocates
 *
 * The borrowed/owned discipline of the pn_ and pt_ helper families becomes
 * types: a function taking Ref borrows, a function taking Val consumes, a
 * function returning Val transfers ownership to the caller.
 */

#ifndef PHPSTANTURBO_ZV_H
#define PHPSTANTURBO_ZV_H

#include "support.h"

#include <utility>

namespace zv {

class Val;
class Arr;

/* Borrowed view of any zval. */
class Ref
{
protected:
	zval *z;

public:
	explicit Ref(zval *zvp) : z(zvp) {}

	zval *raw() const { return z; }

	bool isUndef() const { return Z_TYPE_P(z) == IS_UNDEF; }
	/* PHP null; UNDEF counts as null the same way the pn_ helpers treated it */
	bool isNull() const { return Z_TYPE_P(z) == IS_NULL || Z_TYPE_P(z) == IS_UNDEF; }
	bool isBool() const { return Z_TYPE_P(z) == IS_TRUE || Z_TYPE_P(z) == IS_FALSE; }
	bool isTrue() const { return Z_TYPE_P(z) == IS_TRUE; }
	bool isFalse() const { return Z_TYPE_P(z) == IS_FALSE; }
	bool isLong() const { return Z_TYPE_P(z) == IS_LONG; }
	bool isString() const { return Z_TYPE_P(z) == IS_STRING; }
	bool isArray() const { return Z_TYPE_P(z) == IS_ARRAY; }
	bool isObject() const { return Z_TYPE_P(z) == IS_OBJECT; }

	zend_long asLong() const { return Z_LVAL_P(z); }
	zend_long toLong() const { return zval_get_long(z); }
	zend_string *asString() const { return Z_STR_P(z); }
	zend_object *asObject() const { return Z_OBJ_P(z); }
	HashTable *asArrayTable() const { return Z_ARRVAL_P(z); }

	bool stringEquals(const char *literal, size_t len) const
	{
		return isString() && zend_string_equals_cstr(Z_STR_P(z), literal, len);
	}

	/* length-deducing overload for string literals */
	template <size_t N>
	bool stringEquals(const char (&literal)[N]) const
	{
		return stringEquals(literal, N - 1);
	}

	bool instanceOf(zend_class_entry *ce) const
	{
		return isObject() && instanceof_function(Z_OBJCE_P(z), ce);
	}

	/* strips references, mirroring ZVAL_DEREF at PHP boundaries */
	Ref deref() const
	{
		zval *d = z;
		ZVAL_DEREF(d);
		return Ref(d);
	}

	/* $slot = $value: overwrites the viewed slot with an owned value,
	 * releasing the previous one. The slot must be writable — an entry of a
	 * separated array, a property slot of an owned object. */
	inline void assign(Val owned);
};

/* Owned zval: move-only RAII. Adopts an already-owned raw zval. */
class Val
{
protected:
	zval z;

	struct Adopt
	{
	};
	Val(zval owned, Adopt) : z(owned) {}

public:
	Val() { ZVAL_UNDEF(&z); }
	Val(const Val &) = delete;
	Val &operator=(const Val &) = delete;
	Val(Val &&other) noexcept : z(other.z) { ZVAL_UNDEF(&other.z); }

	Val &operator=(Val &&other) noexcept
	{
		if (this != &other) {
			release();
			z = other.z;
			ZVAL_UNDEF(&other.z);
		}
		return *this;
	}

	~Val() { release(); }

	void release()
	{
		if (!Z_ISUNDEF(z)) {
			zval_ptr_dtor(&z);
			ZVAL_UNDEF(&z);
		}
	}

	/* takes over a raw zval the caller owns */
	static Val adopt(zval owned) { return Val(owned, Adopt{}); }

	/* addref-copy of a borrowed value */
	static Val copyOf(Ref r)
	{
		zval c;
		ZVAL_COPY(&c, r.raw());
		return Val(c, Adopt{});
	}

	static Val null()
	{
		zval c;
		ZVAL_NULL(&c);
		return Val(c, Adopt{});
	}

	static Val boolean(bool b)
	{
		zval c;
		ZVAL_BOOL(&c, b);
		return Val(c, Adopt{});
	}

	static Val integer(zend_long l)
	{
		zval c;
		ZVAL_LONG(&c, l);
		return Val(c, Adopt{});
	}

	static Val string(zend_string *borrowed)
	{
		zval c;
		ZVAL_STR_COPY(&c, borrowed);
		return Val(c, Adopt{});
	}

	static Val string(const char *s, size_t len)
	{
		zval c;
		ZVAL_STRINGL(&c, s, len);
		return Val(c, Adopt{});
	}

	/* takes over an owned zend_string (no addref), like RETURN_STR */
	static Val adoptString(zend_string *owned)
	{
		zval c;
		ZVAL_STR(&c, owned);
		return Val(c, Adopt{});
	}

	Ref ref() { return Ref(&z); }
	zval *raw() { return &z; }

	/* transfers ownership out as a raw zval (for RETVAL/stack slots) */
	zval take()
	{
		zval out = z;
		ZVAL_UNDEF(&z);
		return out;
	}

	/* moves this value into the engine's return_value slot */
	void intoReturnValue(zval *return_value) { ZVAL_COPY_VALUE(return_value, &z); ZVAL_UNDEF(&z); }

	bool isUndef() const { return Z_TYPE(z) == IS_UNDEF; }
	bool isNull() const { return Z_TYPE(z) == IS_NULL || Z_TYPE(z) == IS_UNDEF; }
};

inline void Ref::assign(Val owned)
{
	/* engine idiom (cf. zend_assign_to_variable): install the new value
	 * before releasing the old one — a __destruct re-reading the slot must
	 * observe the new value, not a freed zval */
	zval old;
	ZVAL_COPY_VALUE(&old, z);
	zval v = owned.take();
	ZVAL_COPY_VALUE(z, &v);
	zval_ptr_dtor(&old);
}

/* Owned zend_string: move-only RAII. NULL is the empty state, so a failed
 * producer (e.g. pt_node_key on exception) can be adopted and tested. */
class Str
{
	zend_string *s;

	explicit Str(zend_string *owned) : s(owned) {}

public:
	Str() : s(NULL) {}
	Str(const Str &) = delete;
	Str &operator=(const Str &) = delete;
	Str(Str &&other) noexcept : s(other.s) { other.s = NULL; }

	Str &operator=(Str &&other) noexcept
	{
		if (this != &other) {
			release();
			s = other.s;
			other.s = NULL;
		}
		return *this;
	}

	~Str() { release(); }

	void release()
	{
		if (s != NULL) {
			zend_string_release(s);
			s = NULL;
		}
	}

	/* takes over an owned string (may be NULL) */
	static Str adopt(zend_string *owned) { return Str(owned); }

	/* addref-copy of a borrowed string */
	static Str copyOf(zend_string *borrowed) { return Str(zend_string_copy(borrowed)); }

	zend_string *get() const { return s; }
	bool isNull() const { return s == NULL; }

	/* transfers ownership out */
	zend_string *take()
	{
		zend_string *out = s;
		s = NULL;
		return out;
	}
};

/*
 * Range-for iteration over a HashTable, handling both layouts: packed tables
 * (PHP >= 8.2) store plain zvals in arPacked, mixed tables store Buckets in
 * arData. Bucket's first member is its zval, so one byte-stride cursor walks
 * both — the same dual layout ZEND_HASH_FOREACH compiles to.
 */
class ArrayEntry
{
	zval *slot;
	zval *packedBase; /* start of arPacked for packed layout, NULL for Buckets */

public:
	ArrayEntry(zval *s, zval *packedBase) : slot(s), packedBase(packedBase) {}

	bool hasStringKey() const { return packedBase == NULL && ((Bucket *) slot)->key != NULL; }
	zend_string *stringKey() const { return ((Bucket *) slot)->key; }
	/* string key, or NULL for integer keys — the pt_ht_* dual-key convention,
	 * exactly what ZEND_HASH_FOREACH_KEY_VAL's key variable holds */
	zend_string *stringKeyOrNull() const { return packedBase != NULL ? NULL : ((Bucket *) slot)->key; }
	/* integer key; in packed layout that is the slot's position */
	zend_ulong indexKey() const { return packedBase != NULL ? (zend_ulong) (slot - packedBase) : ((Bucket *) slot)->h; }
	Ref value() const { return Ref(slot); }
};

class ArrayIter
{
	char *cur;
	char *end;
	size_t stride;
	zval *packedBase; /* NULL for Bucket layout */

	void skipHoles()
	{
		while (cur != end && Z_TYPE_P((zval *) cur) == IS_UNDEF) {
			cur += stride;
		}
	}

public:
	ArrayIter(char *c, char *e, size_t stride, bool packed) : cur(c), end(e), stride(stride), packedBase(packed ? (zval *) c : NULL)
	{
		skipHoles();
	}

	bool operator!=(const ArrayIter &other) const { return cur != other.cur; }

	void operator++()
	{
		cur += stride;
		skipHoles();
	}

	ArrayEntry operator*() const { return ArrayEntry((zval *) cur, packedBase); }
};

/* Borrowed view of a bare HashTable (an HT zpp argument, a scratch table) —
 * the same iteration ArrRef offers, without requiring a wrapping zval. */
class TableRef
{
	HashTable *ht;

public:
	explicit TableRef(HashTable *h) : ht(h) {}

	HashTable *table() const { return ht; }
	uint32_t size() const { return zend_hash_num_elements(ht); }

	ArrayIter begin() const
	{
		bool packed = HT_IS_PACKED(ht);
		char *base = packed ? (char *) ht->arPacked : (char *) ht->arData;
		size_t stride = packed ? sizeof(zval) : sizeof(Bucket);
		return ArrayIter(base, base + stride * ht->nNumUsed, stride, packed);
	}

	ArrayIter end() const
	{
		bool packed = HT_IS_PACKED(ht);
		char *base = packed ? (char *) ht->arPacked : (char *) ht->arData;
		size_t stride = packed ? sizeof(zval) : sizeof(Bucket);
		char *e = base + stride * ht->nNumUsed;
		return ArrayIter(e, e, stride, packed);
	}
};

/* Borrowed view of a PHP array. */
class ArrRef : public Ref
{
public:
	explicit ArrRef(zval *zvp) : Ref(zvp) {}

	HashTable *table() const { return Z_ARRVAL_P(z); }
	uint32_t size() const { return zend_hash_num_elements(table()); }

	ArrayIter begin() const { return TableRef(table()).begin(); }
	ArrayIter end() const { return TableRef(table()).end(); }

	/* symtable lookup: numeric strings behave like PHP array keys */
	Ref find(zend_string *key) const
	{
		zval *found = zend_symtable_find(table(), key);
		return Ref(found); /* raw() == NULL when absent */
	}

	bool exists(zend_string *key) const { return zend_symtable_find(table(), key) != NULL; }

	/* $a[] = $value; separates a shared table first (writes through the slot) */
	void push(Ref borrowed)
	{
		SEPARATE_ARRAY(z);
		Z_TRY_ADDREF_P(borrowed.raw());
		zend_hash_next_index_insert(table(), borrowed.raw());
	}

	/* $a[$index] ?? null */
	Ref findIndex(zend_ulong index) const
	{
		zval *found = zend_hash_index_find(table(), index);
		return Ref(found); /* raw() == NULL when absent */
	}

	/* $a[$index] = $value; separates a shared table first (writes through
	 * the slot), releases a replaced value */
	void setIndex(zend_ulong index, Ref borrowed)
	{
		SEPARATE_ARRAY(z);
		Z_TRY_ADDREF_P(borrowed.raw());
		zend_hash_index_update(table(), index, borrowed.raw());
	}
};

/* Owned PHP array under construction.
 *
 * Returning an Arr local from a Val-returning function must be spelled
 * `return zv::Val(std::move(x));` — the glibc-2.35 baseline compiler
 * (gcc 11) predates P1825's implicit derived-to-base move on return and
 * would require the deleted copy constructor. */
class Arr : public Val
{
public:
	Arr() = default;

	static Arr create(uint32_t sizeHint)
	{
		Arr a;
		array_init_size(a.raw(), sizeHint);
		return a;
	}

	/* the shared immutable empty array — what a PHP [] literal provides */
	static Arr empty()
	{
		Arr a;
		ZVAL_EMPTY_ARRAY(a.raw());
		return a;
	}

	/* takes over a raw HashTable the caller owns; immutable tables (a PHP []
	 * literal) are wrapped non-refcounted, like ZVAL_EMPTY_ARRAY */
	static Arr adoptTable(HashTable *owned)
	{
		Arr a;
		ZVAL_ARR(a.raw(), owned);
		if (GC_FLAGS(owned) & IS_ARRAY_IMMUTABLE) {
			Z_TYPE_INFO_P(a.raw()) = IS_ARRAY;
		}
		return a;
	}

	/* re-types an owned Val that is known to hold an array */
	static Arr adoptVal(Val ownedArray)
	{
		Arr a;
		a.z = ownedArray.take();
		return a;
	}

	/* addref-copy of a borrowed HashTable; immutable tables (a PHP []
	 * literal) are wrapped non-refcounted so no addref ever touches them */
	static Arr copyOfTable(HashTable *borrowed)
	{
		Arr a;
		ZVAL_ARR(a.raw(), borrowed);
		if (!(GC_FLAGS(borrowed) & IS_ARRAY_IMMUTABLE)) {
			GC_ADDREF(borrowed);
		} else {
			Z_TYPE_INFO_P(a.raw()) = IS_ARRAY;
		}
		return a;
	}

	ArrRef arrRef() { return ArrRef(raw()); }
	HashTable *table() { return Z_ARRVAL(z); }

	void separate() { SEPARATE_ARRAY(raw()); }

	/* $a[] = $value */
	void push(Ref borrowed)
	{
		separate();
		Z_TRY_ADDREF_P(borrowed.raw());
		zend_hash_next_index_insert(table(), borrowed.raw());
	}

	void push(Val owned)
	{
		separate();
		zval v = owned.take();
		zend_hash_next_index_insert(table(), &v);
	}

	/* $a[$key] = $value (symtable semantics) */
	void set(zend_string *key, Val owned)
	{
		separate();
		zval v = owned.take();
		zend_symtable_update(table(), key, &v);
	}

	/* $a['key'] = $value for a literal, never-numeric key */
	template <size_t N>
	void set(const char (&key)[N], Val owned)
	{
		separate();
		zval v = owned.take();
		zend_hash_str_update(table(), key, N - 1, &v);
	}
};

/*
 * Stack-local scratch HashTable with no per-entry destructor: for tables of
 * scalars or borrowed zvals that are never owned. Cheaper than a zval-wrapped
 * Arr — the header lives on the C stack and nothing is refcounted.
 */
class ScratchTable
{
	HashTable ht;

public:
	explicit ScratchTable(uint32_t sizeHint) { zend_hash_init(&ht, sizeHint, NULL, NULL, 0); }
	ScratchTable(const ScratchTable &) = delete;
	ScratchTable &operator=(const ScratchTable &) = delete;
	~ScratchTable() { zend_hash_destroy(&ht); }

	HashTable *table() { return &ht; }
	uint32_t size() const { return zend_hash_num_elements(&ht); }
};

/* Borrowed view of a zend object: property access via cached slots. */
class ObjRef
{
protected:
	zend_object *obj;

public:
	explicit ObjRef(zend_object *o) : obj(o) {}
	explicit ObjRef(zval *zvp) : obj(Z_OBJ_P(zvp)) {}

	zend_object *raw() const { return obj; }
	zend_class_entry *ce() const { return obj->ce; }
	uint32_t handle() const { return obj->handle; }

	bool instanceOf(zend_class_entry *classEntry) const { return instanceof_function(obj->ce, classEntry); }

	/* property at a known slot (OBJ_PROP_NUM) — the fast path for own classes */
	Ref propAt(uint32_t slot) const { return Ref(OBJ_PROP_NUM(obj, slot)); }

	/* property at a cached byte offset (OBJ_PROP; pt_instance_prop_offset) */
	Ref propAtOffset(uint32_t offset) const { return Ref(OBJ_PROP(obj, offset)); }

	/* property by name through the class's property table (pays a lookup) */
	Ref prop(const char *name, size_t len) const
	{
		int32_t offset = pt_instance_prop_offset(obj->ce, name, len);
		if (offset < 0) {
			return Ref(NULL);
		}
		zval *slot = OBJ_PROP(obj, (uint32_t) offset);
		ZVAL_DEINDIRECT(slot);
		return Ref(slot);
	}

	void propAtWrite(uint32_t slot, Val owned)
	{
		/* new value in before the old one's dtor runs — see Ref::assign() */
		zval *p = OBJ_PROP_NUM(obj, slot);
		zval old;
		ZVAL_COPY_VALUE(&old, p);
		zval v = owned.take();
		ZVAL_COPY_VALUE(p, &v);
		zval_ptr_dtor(&old);
	}

	/* $obj->$name = $value through the engine write path (magic methods and
	 * typed-property checks apply; may leave an exception pending) */
	void propWrite(zend_string *name, Ref value)
	{
		zend_update_property_ex(obj->ce, obj, name, value.raw());
	}
};

} // namespace zv

#endif
