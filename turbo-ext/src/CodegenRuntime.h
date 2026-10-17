/*
 * The runtime the PHP-to-C++ compiler's output links against
 * (build/PHPStan/Build/TurboCodegen, turbo-ext/bin/compile-twins.php).
 *
 * Everything a generated class does that is not plain C++ — a per-request
 * cached class, static property or function lookup, a call into the engine,
 * a typed-property or return-type check, an array write — goes through one
 * of these helpers, so the generated sources stay a mechanical rendering of
 * the PHP twin. The helpers implement PHP's own semantics (strict_types
 * callers, the engine's error messages); a check the compiler proved
 * unnecessary from the native types is simply not emitted.
 */

#ifndef PHPSTANTURBO_CODEGEN_RUNTIME_H
#define PHPSTANTURBO_CODEGEN_RUNTIME_H

#include "support.h"
#include "zv.h"
#include "Engine.h"
#include "TypeOps.h"
#include "ParameterValues.h"
#include "zend_smart_str.h"

#pragma GCC diagnostic push
#pragma GCC diagnostic ignored "-Wpragmas"
#pragma GCC diagnostic ignored "-Wunknown-warning-option"
#pragma GCC diagnostic ignored "-Wunused-parameter"
#pragma GCC diagnostic ignored "-Wignored-qualifiers"
#pragma GCC diagnostic ignored "-Wdeprecated-declarations"
#pragma GCC diagnostic ignored "-Wattributes"
#include "zend_closures.h" /* zend_ce_closure */
#pragma GCC diagnostic pop

/* provided by AcceptsResult.cpp */
[[nodiscard]] bool pt_call_fci(zend_fcall_info *fci, zend_fcall_info_cache *fcc, uint32_t argc, zval *argv, zval *retval);

namespace pgrt {

/* {{{ per-request cached lookups */

struct ClassSite
{
	zend_class_entry *ce;
	uint32_t generation;
	/* lookupClassQuiet() of a class not loaded yet: the class table's size
	 * then — it only grows within a request, so an unchanged size means the
	 * class is still not there */
	uint32_t missedAt;
};

/* the class of a name (autoloading it); NULL with an Error pending */
zend_class_entry *lookupClassSlow(ClassSite &site, const char *name, size_t len);

static zend_always_inline zend_class_entry *lookupClass(ClassSite &site, const char *name, size_t len)
{
	if (EXPECTED(site.ce != NULL && site.generation == pt_engine_generation)) return site.ce;
	return lookupClassSlow(site, name, len);
}

/* the class of a name if it is loaded (no autoloading: instanceof and type
 * checks never load a class), cached once found; NULL otherwise */
zend_class_entry *lookupClassQuietSlow(ClassSite &site, const char *name, size_t len);

static zend_always_inline zend_class_entry *lookupClassQuiet(ClassSite &site, const char *name, size_t len)
{
	if (EXPECTED(site.generation == pt_engine_generation)) {
		if (EXPECTED(site.ce != NULL)) return site.ce;
		if (site.missedAt == zend_hash_num_elements(EG(class_table))) return NULL;
	}
	return lookupClassQuietSlow(site, name, len);
}

/* $value instanceof <ce>, where an unloaded class (NULL) has no instances */
static zend_always_inline bool instanceOfClass(zval *value, zend_class_entry *ce)
{
	return ce != NULL && Z_TYPE_P(value) == IS_OBJECT && instanceof_function(Z_OBJCE_P(value), ce);
}

static zend_always_inline bool objectInstanceOf(zend_object *object, zend_class_entry *ce)
{
	return ce != NULL && instanceof_function(object->ce, ce);
}

struct StaticSite
{
	zend_class_entry *ce;
	zval *slot;
	uint32_t generation;
};

/* the slot of a declared static property of ce (not dereferenced) */
zval *staticSlotSlow(StaticSite &site, zend_class_entry *ce, const char *name, size_t len);

static zend_always_inline zval *staticSlot(StaticSite &site, zend_class_entry *ce, const char *name, size_t len)
{
	if (EXPECTED(site.ce == ce && site.generation == pt_engine_generation)) return site.slot;
	return staticSlotSlow(site, ce, name, len);
}

struct FunctionSite
{
	zend_function *fn;
	uint32_t generation;
};

/* name(...$argv) of a global function, called with `scope` as the
 * executing class (EG(fake_scope)) — a frameless native-to-native call
 * leaves the caller's frame current, and scope-sensitive builtins
 * (array_column() reading a private property) must still see the lexical
 * class; false = pending exception (*ret is then UNDEF) */
[[nodiscard]] bool callFunction(FunctionSite &site, const char *lcname, size_t len, zend_class_entry *scope, uint32_t argc, zval *argv, zval *ret, HashTable *named = NULL);

/* Class::method(...$argv) through the engine; false = pending exception */
[[nodiscard]] bool callStatic(pt_method_site &site, zend_class_entry *ce, const char *lcname, size_t len, uint32_t argc, zval *argv, zval *ret, HashTable *named = NULL);

/* $object->method(...$argv) through the engine; false = pending exception */
[[nodiscard]] bool callMethod(pt_method_site &site, zval *object, const char *name, const char *lcname, size_t len, uint32_t argc, zval *argv, zval *ret, HashTable *named = NULL);

/* <ce>::name(...) of an instance method bound to $object (parent::/self::):
 * that class's function, without virtual dispatch; false = pending exception */
[[nodiscard]] bool callBound(pt_method_site &site, zend_class_entry *ce, zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv, zval *ret, HashTable *named = NULL);

/* }}} */

/* {{{ objects */

/* the parameterless constructor a class inherits (a hand-written parent's),
 * through the engine; false = pending exception */
[[nodiscard]] bool callInheritedConstructor(zend_object *object);

/* new ce(...$argv) through the engine's constructor; false = pending
 * exception (*out is then UNDEF) */
[[nodiscard]] bool newObject(zend_class_entry *ce, uint32_t argc, zval *argv, zval *out, HashTable *named = NULL);

/* the OBJ_PROP_NUM slot of a declared instance property that has no
 * generated slot constant (a trait's), looked up once per receiver class;
 * compiled code is the declaring class's own, so any visibility applies */
struct PropIndexSite
{
	zend_class_entry *ce;
	uint32_t index;
	uint32_t generation;
};

uint32_t propIndexSlow(PropIndexSite &site, zend_object *object, const char *name, size_t len);

static zend_always_inline uint32_t propIndex(PropIndexSite &site, zend_object *object, const char *name, size_t len)
{
	if (EXPECTED(site.ce == object->ce && site.generation == pt_engine_generation)) return site.index;
	return propIndexSlow(site, object, name, len);
}

/* the slot of a public declared property of a class the compiler does not
 * compile (a php-parser node), per receiver class — a polymorphic inline
 * cache of PROP_SITE_WAYS classes; NULL when the object's class does not
 * declare it publicly. The slot is not dereferenced and may be UNDEF. */
constexpr uint32_t PROP_SITE_WAYS = 4;

struct PropSite
{
	zend_class_entry *ce[PROP_SITE_WAYS];
	uint32_t offset[PROP_SITE_WAYS];
	uint32_t generation;
	uint32_t next;
};

zval *propCachedSlow(PropSite &site, zend_object *object, const char *name, size_t len);

static zend_always_inline zval *propCached(PropSite &site, zend_object *object, const char *name, size_t len)
{
	if (EXPECTED(site.generation == pt_engine_generation)) {
		for (uint32_t i = 0; i < PROP_SITE_WAYS; i++) {
			if (site.ce[i] == object->ce) return OBJ_PROP(object, site.offset[i]);
		}
	}
	return propCachedSlow(site, object, name, len);
}

/* a declared property's slot of an object known to be ce or a subclass,
 * checked to be initialized; NULL with an Error pending */
ZEND_COLD void throwUninitializedProperty(zend_object *object, const char *name);

static zend_always_inline zval *readSlot(zend_object *object, uint32_t slot, const char *name)
{
	zval *p = OBJ_PROP_NUM(object, slot);
	if (UNEXPECTED(Z_TYPE_P(p) == IS_UNDEF)) {
		throwUninitializedProperty(object, name);
		return NULL;
	}
	return p;
}

/* $value->name read through the object handlers with `scope` as the
 * calling class (any value: a non-object warns and reads null, like the
 * VM); *rv is owned; false = pending exception */
[[nodiscard]] bool readPropertyGeneric(zval *value, zend_class_entry *scope, const char *name, size_t len, zval *rv);

/* the constructor's first write of a readonly property slot; false =
 * pending exception (a second initialization, as the engine reports it) */
[[nodiscard]] bool initReadonly(zend_object *object, uint32_t slot, const char *name, zval *owned);

/* a promoted/plain typed non-readonly property slot write of a value the
 * compiler proved to match the property type */
static zend_always_inline void writeSlot(zend_object *object, uint32_t slot, zval *owned)
{
	zval *p = OBJ_PROP_NUM(object, slot);
	zval old;
	ZVAL_COPY_VALUE(&old, p);
	ZVAL_COPY_VALUE(p, owned);
	Z_PROP_FLAG_P(p) = 0;
	zval_ptr_dtor(&old);
}

/* }}} */

/* {{{ callables */

struct Callable
{
	zend_fcall_info fci;
	zend_fcall_info_cache fcc;
};

static zend_always_inline bool callCallable(Callable &callable, uint32_t argc, zval *argv, zval *ret, HashTable *named = NULL)
{
	if (EXPECTED(named == NULL)) {
		/* pt_call_fci() releases *ret on failure: reset it for the caller's zv::Val */
		if (UNEXPECTED(!pt_call_fci(&callable.fci, &callable.fcc, argc, argv, ret))) {
			ZVAL_UNDEF(ret);
			return false;
		}
		return true;
	}
	callable.fci.retval = ret;
	callable.fci.param_count = argc;
	callable.fci.params = argv;
	callable.fci.named_params = named;
	bool ok = zend_call_function(&callable.fci, &callable.fcc) == SUCCESS && !EG(exception);
	callable.fci.named_params = NULL;
	if (!ok) {
		zval_ptr_dtor(ret);
		ZVAL_UNDEF(ret);
	}
	return ok;
}

/* a callable value as a parsed `callable` parameter; false = a TypeError
 * pending (not callable) */
[[nodiscard]] bool callableOf(zval *value, Callable &out);

/* $value(...$argv) of any callable value; false = pending exception */
[[nodiscard]] bool callValue(zval *value, uint32_t argc, zval *argv, zval *ret, HashTable *named = NULL);

/* }}} */

/* {{{ errors */

ZEND_COLD void throwReturnType(const char *function, const char *expected, zval *given);
ZEND_COLD void throwArgumentType(const char *function, uint32_t position, const char *parameter, const char *expected, zval *given);
ZEND_COLD void throwTooFewArguments(const char *function, uint32_t passed, uint32_t required);
ZEND_COLD void warnUndefinedIndex(zend_long key);

/* }}} */

/* {{{ arrays */

/* $array[] = $owned on an array zval the caller owns the write to */
static zend_always_inline void append(zval *array, zval *owned)
{
	SEPARATE_ARRAY(array);
	zend_hash_next_index_insert(Z_ARRVAL_P(array), owned);
}

/* $array[$key] = $owned */
static zend_always_inline void setIndex(zval *array, zend_long key, zval *owned)
{
	SEPARATE_ARRAY(array);
	zend_hash_index_update(Z_ARRVAL_P(array), key, owned);
}

/* A call's argument vector built at run time (spread arguments): borrowed
 * values, inline storage for the common sizes. */
class ArgVec
{
public:
	ArgVec() : data(inlineData), count(0), capacity(INLINE) {}
	ArgVec(const ArgVec &) = delete;
	ArgVec &operator=(const ArgVec &) = delete;
	~ArgVec()
	{
		if (data != inlineData) efree(data);
	}

	void push(zval *borrowed)
	{
		if (UNEXPECTED(count == capacity)) grow();
		ZVAL_COPY_VALUE(&data[count++], borrowed);
	}

	void pushLong(zend_long value)
	{
		if (UNEXPECTED(count == capacity)) grow();
		ZVAL_LONG(&data[count++], value);
	}

	void pushBool(bool value)
	{
		if (UNEXPECTED(count == capacity)) grow();
		ZVAL_BOOL(&data[count++], value);
	}

	/* ...$array: the array's values in order (a list is spread as is) */
	void spread(zval *array)
	{
		ZEND_ASSERT(Z_TYPE_P(array) == IS_ARRAY);
		zval *value;
		ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(array), value) {
			push(value);
		}
		ZEND_HASH_FOREACH_END();
	}

	zval *argv() { return data; }
	uint32_t size() const { return count; }

private:
	static constexpr uint32_t INLINE = 8;
	zval inlineData[INLINE];
	zval *data;
	uint32_t count;
	uint32_t capacity;

	void grow()
	{
		uint32_t next = capacity * 2;
		zval *grown = (zval *) emalloc(sizeof(zval) * next);
		memcpy(grown, data, sizeof(zval) * count);
		if (data != inlineData) efree(data);
		data = grown;
		capacity = next;
	}
};

/* }}} */

/* {{{ builtin intrinsics: the engine's own algorithms (ext/standard/array.c)
 * without the call frame, for the argument shapes the compiler proved; each
 * raises the builtin's own errors, named after it */

/* array_merge(...$argv) */
[[nodiscard]] bool arrayMerge(uint32_t argc, zval *argv, zval *ret);

/* array_unique($array) with the default SORT_STRING */
[[nodiscard]] bool arrayUnique(zval *array, zval *ret);

/* array_values($array) */
[[nodiscard]] bool arrayValues(zval *array, zval *ret);

/* min($array) / max($array): an int-only array natively, any other through
 * the builtin */
[[nodiscard]] bool arrayMinMax(FunctionSite &site, bool max, zval *array, zend_class_entry *scope, zval *ret);

/* A local list the compiler proved never escapes (only `[]`, `$x[] = ...`
 * and `...$x`): owned values in a native vector instead of a PHP array. */
/* borrowed zval pointers, for a native entry taking `zval *const *argv` */
class PtrVec
{
public:
	PtrVec() : items(inlineData), count(0), capacity(INLINE) {}
	PtrVec(const PtrVec &) = delete;
	PtrVec &operator=(const PtrVec &) = delete;
	~PtrVec()
	{
		if (items != inlineData) efree(items);
	}

	void push(zval *borrowed)
	{
		if (UNEXPECTED(count == capacity)) {
			uint32_t grown = capacity * 2;
			zval **bigger = (zval **) emalloc(sizeof(zval *) * grown);
			memcpy(bigger, items, sizeof(zval *) * count);
			if (items != inlineData) efree(items);
			items = bigger;
			capacity = grown;
		}
		items[count++] = borrowed;
	}

	zval *const *data() const { return items; }
	uint32_t size() const { return count; }

private:
	static constexpr uint32_t INLINE = 8;
	zval *inlineData[INLINE];
	zval **items;
	uint32_t count;
	uint32_t capacity;
};

class ValVec
{
public:
	ValVec() : data(inlineData), count(0), capacity(INLINE) {}
	ValVec(const ValVec &) = delete;
	ValVec &operator=(const ValVec &) = delete;
	~ValVec()
	{
		clear();
		if (data != inlineData) efree(data);
	}

	void clear()
	{
		for (uint32_t i = 0; i < count; i++) {
			zval_ptr_dtor(&data[i]);
		}
		count = 0;
	}

	/* takes over an owned value */
	void pushOwned(zval *owned)
	{
		if (UNEXPECTED(count == capacity)) grow();
		ZVAL_COPY_VALUE(&data[count++], owned);
	}

	zval *argv() { return data; }
	uint32_t size() const { return count; }

private:
	static constexpr uint32_t INLINE = 8;
	zval inlineData[INLINE];
	zval *data;
	uint32_t count;
	uint32_t capacity;

	void grow()
	{
		uint32_t next = capacity * 2;
		zval *grown = (zval *) emalloc(sizeof(zval) * next);
		memcpy(grown, data, sizeof(zval) * count);
		if (data != inlineData) efree(data);
		data = grown;
		capacity = next;
	}
};

/* }}} */

/* {{{ integers */

/* $a >> $b with PHP's semantics; false = pending ArithmeticError */
static zend_always_inline bool shiftRight(zend_long a, zend_long b, zend_long *out)
{
	if (UNEXPECTED((zend_ulong) b >= SIZEOF_ZEND_LONG * 8)) {
		if (b < 0) {
			zend_throw_exception_ex(zend_ce_arithmetic_error, 0, "Bit shift by negative number");
			return false;
		}
		*out = a < 0 ? -1 : 0;
		return true;
	}
	*out = a >> b;
	return true;
}

/* }}} */

/* Class::NAME of a constant whose value is not a compile-time scalar (an
 * array, an enum case): the engine's evaluated value, borrowed; NULL with an
 * Error pending */
zval *classConstant(zend_class_entry *ce, zval *name, zend_class_entry *scope);

/* {{{ writes and isset reads */

/* $array[$key] for a write: the element, added as null when missing; NULL
 * with a TypeError pending for an illegal offset type */
zval *dimWrite(zval *array, zval *key);

static zend_always_inline zval *dimWriteLong(zval *array, zend_long key)
{
	zval *found = zend_hash_index_find(Z_ARRVAL_P(array), key);
	if (found != NULL) return found;
	zval none;
	ZVAL_NULL(&none);
	return zend_hash_index_add_new(Z_ARRVAL_P(array), key, &none);
}

/* $object->name = $value through the handlers, `scope` as the calling class;
 * false = pending exception */
[[nodiscard]] bool writePropertyGeneric(zval *object, zend_class_entry *scope, zval *name, zval *value);

/* $base[$key] read like the VM (BP_VAR_R): the element (borrowed, or in
 * *tmp), &EG(uninitialized_zval) after the VM's warning for a missing key or
 * an offset on a scalar; NULL with an exception pending */
zval *dimRead(zval *base, zval *key, zval *tmp);

/* clone $value; false = pending exception */
[[nodiscard]] bool cloneObject(zval *value, zval *out);

/* [...$value] into an array literal under construction */
[[nodiscard]] bool spreadInto(zval *array, zval *value);

/* the value isset($base[$key]) / ?? reads, NULL when there is none; *tmp
 * holds a value read from an object */
zval *dimIs(zval *base, zval *key, zval *tmp);

/* the value isset($base->name) / ?? reads, NULL when there is none */
zval *propIs(zval *base, zend_class_entry *scope, zval *name, zval *tmp);

/* }}} */

/* {{{ strings */

/* $a . $b; false = pending exception (a failed __toString()) */
[[nodiscard]] bool concat(zval *a, zval *b, zval *out);

/* (string) in place; false = pending exception */
[[nodiscard]] bool castString(zval *value);

/* an interpolated string, built part by part */
class StringBuilder
{
public:
	StringBuilder() { memset(&str, 0, sizeof(str)); }
	StringBuilder(const StringBuilder &) = delete;
	StringBuilder &operator=(const StringBuilder &) = delete;
	~StringBuilder() { smart_str_free(&str); }

	[[nodiscard]] bool append(zval *part)
	{
		if (Z_TYPE_P(part) == IS_STRING) {
			smart_str_append(&str, Z_STR_P(part));
			return true;
		}
		zend_string *tmp;
		zend_string *converted = zval_try_get_tmp_string(part, &tmp);
		if (UNEXPECTED(converted == NULL)) return false;
		smart_str_append(&str, converted);
		zend_tmp_string_release(tmp);
		return true;
	}

	void appendLiteral(const char *s, size_t len) { smart_str_appendl(&str, s, len); }
	void appendLong(zend_long value) { smart_str_append_long(&str, value); }

	zv::Val finish()
	{
		smart_str_0(&str);
		zend_string *result = str.s != NULL ? str.s : ZSTR_EMPTY_ALLOC();
		str.s = NULL;
		return zv::Val::adoptString(result);
	}

private:
	smart_str str;
};

/* }}} */

/* {{{ integer arithmetic of int-proven operands: PHP turns an overflow into
 * a float, which an int-typed C++ home cannot hold — compiled code raises an
 * Error instead (never reached by PHPStan's counters and offsets) */

ZEND_COLD bool throwIntegerOverflow();

static zend_always_inline bool addLong(zend_long a, zend_long b, zend_long *out)
{
	if (UNEXPECTED(__builtin_add_overflow(a, b, out))) return throwIntegerOverflow();
	return true;
}

static zend_always_inline bool subLong(zend_long a, zend_long b, zend_long *out)
{
	if (UNEXPECTED(__builtin_sub_overflow(a, b, out))) return throwIntegerOverflow();
	return true;
}

static zend_always_inline bool mulLong(zend_long a, zend_long b, zend_long *out)
{
	if (UNEXPECTED(__builtin_mul_overflow(a, b, out))) return throwIntegerOverflow();
	return true;
}

static zend_always_inline bool negateLong(zend_long a, zend_long *out)
{
	if (UNEXPECTED(a == ZEND_LONG_MIN)) return throwIntegerOverflow();
	*out = -a;
	return true;
}

static zend_always_inline bool modLong(zend_long a, zend_long b, zend_long *out)
{
	if (UNEXPECTED(b == 0)) {
		zend_throw_exception_ex(zend_ce_division_by_zero_error, 0, "Modulo by zero");
		return false;
	}
	*out = b == -1 ? 0 : a % b;
	return true;
}

/* }}} */

/* get_class($object) of one argument; false = pending TypeError */
[[nodiscard]] bool getClass(zval *object, zval *ret);

/* in_array($needle, $array, true) */
bool inArrayStrict(zval *needle, zval *array);

/* array_last() / array_first(): the engine for a non-array (its TypeError) */
[[nodiscard]] bool arrayEdge(FunctionSite &site, const char *name, size_t len, bool last, zval *array, zval *ret);

/* str_starts_with() / str_ends_with() / str_contains() on two strings, the
 * engine for anything else (coercion, TypeError) */
enum class StrMatch : uint8_t { STARTS, ENDS, CONTAINS };
[[nodiscard]] bool strMatch(FunctionSite &site, const char *name, size_t len, StrMatch kind, zval *haystack, zval *needle, zval *ret);

/* array_reverse($list) of a list (keys renumbered, as the builtin does for
 * integer keys) */
void listReverse(zval *list, zval *ret);

/* implode($glue, $pieces) of a string and an array; false = pending exception */
[[nodiscard]] bool implode(zval *glue, zval *pieces, zval *ret);

/* array_pop() on a separated array in place */
void arrayPop(zval *array, zval *ret);

/* {{{ php-parser node methods
 *
 * The node methods the parser visitors call per node, done on the node's
 * slots when the receiver's class keeps php-parser's own implementation
 * (checked once per class); any other class goes through the engine like
 * a plain method call. Each returns false with an exception pending. */

[[nodiscard]] bool nodeSetAttribute(pt_method_site &site, zval *node, zval *key, zval *value, zval *ret);
[[nodiscard]] bool nodeGetAttribute(pt_method_site &site, zval *node, zval *key, zval *defaultValue, zval *ret);
[[nodiscard]] bool nodeHasAttribute(pt_method_site &site, zval *node, zval *key, zval *ret);
/* Name/Identifier::toString(), ::toLowerString() */
[[nodiscard]] bool nameToString(pt_method_site &site, zval *node, zval *ret);
[[nodiscard]] bool nameToLowerString(pt_method_site &site, zval *node, zval *ret);
/* CallLike::isFirstClassCallable(), ::getArgs() */
[[nodiscard]] bool callIsFirstClassCallable(pt_method_site &site, zval *node, zval *ret);
[[nodiscard]] bool callGetArgs(pt_method_site &site, zval *node, zval *ret);

/* }}} */

/* {{{ literals: interned at module startup, never refcounted */

zend_string *internLiteral(const char *s, size_t len);

/* }}} */

} // namespace pgrt

#endif
