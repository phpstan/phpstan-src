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
};

/* the class of a name (autoloading it); NULL with an Error pending */
zend_class_entry *lookupClassSlow(ClassSite &site, const char *name, size_t len);

static zend_always_inline zend_class_entry *lookupClass(ClassSite &site, const char *name, size_t len)
{
	if (EXPECTED(site.ce != NULL && site.generation == pt_engine_generation)) return site.ce;
	return lookupClassSlow(site, name, len);
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
[[nodiscard]] bool callFunction(FunctionSite &site, const char *lcname, size_t len, zend_class_entry *scope, uint32_t argc, zval *argv, zval *ret);

/* Class::method(...$argv) through the engine; false = pending exception */
[[nodiscard]] bool callStatic(pt_method_site &site, zend_class_entry *ce, const char *lcname, size_t len, uint32_t argc, zval *argv, zval *ret);

/* $object->method(...$argv) through the engine; false = pending exception */
[[nodiscard]] bool callMethod(pt_method_site &site, zval *object, const char *name, const char *lcname, size_t len, uint32_t argc, zval *argv, zval *ret);

/* }}} */

/* {{{ objects */

/* new ce(...$argv) through the engine's constructor; false = pending
 * exception (*out is then UNDEF) */
[[nodiscard]] bool newObject(zend_class_entry *ce, uint32_t argc, zval *argv, zval *out);

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

static zend_always_inline bool callCallable(Callable &callable, uint32_t argc, zval *argv, zval *ret)
{
	return pt_call_fci(&callable.fci, &callable.fcc, argc, argv, ret);
}

/* $value(...$argv) of any callable value; false = pending exception */
[[nodiscard]] bool callValue(zval *value, uint32_t argc, zval *argv, zval *ret);

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

/* {{{ literals: interned at module startup, never refcounted */

zend_string *internLiteral(const char *s, size_t len);

/* }}} */

} // namespace pgrt

#endif
