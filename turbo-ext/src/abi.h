/*
 * Engine globals through pointers instead of fixed offsets.
 *
 * EG() and CG() compile to a field offset inside zend_executor_globals /
 * zend_compiler_globals, and both structures change layout in every PHP
 * minor (EG(exception) sits at 0x360 in 8.3, 0x3a8 in 8.4, 0x3c0 in 8.5).
 * Redefined here to read through pointers that Abi.cpp fills in once at
 * module startup, every use compiles to the same instructions against each
 * supported PHP's headers — the same number of loads as before, the GOT
 * entry of the globals symbol traded for the pointer.
 *
 * A field missing from the lists below fails to compile ("no member named
 * eg_<field>"): add it to PT_ABI_EG_FIELDS / PT_ABI_CG_FIELDS.
 */

#ifndef PHPSTANTURBO_ABI_H
#define PHPSTANTURBO_ABI_H

#include <type_traits>

/* zend_fcall_info_cache in 8.3's layout over older headers (support.h) */
#ifdef PT_ABI_FCALL_INFO_CACHE_BEFORE_83
#undef _zend_fcall_info_cache
#undef zend_fcall_info_cache
struct _zend_fcall_info_cache : pt_abi_zend_fcall_info_cache_before_83_t
{
	zend_object *closure;
};
typedef struct _zend_fcall_info_cache zend_fcall_info_cache;
#endif

/* The C stack limit arrived in PHP 8.3 (zend_call_stack.h, EG(stack_base),
 * EG(stack_limit)). Against older headers, the two inline helpers the
 * shared code uses are spelled out as 8.3 has them, and the limit is
 * Abi.cpp's own (pt_abi_globals::eg_stack_limit). */
#if PHP_VERSION_ID >= 80300
#ifdef ZEND_CHECK_STACK_LIMIT
#include "zend_call_stack.h"
#endif
#else
#define ZEND_CHECK_STACK_LIMIT 1
#ifdef _MSC_VER
#include <intrin.h>
#endif
static zend_always_inline void *zend_call_stack_position(void)
{
#ifdef _MSC_VER
	return _AddressOfReturnAddress();
#else
	return __builtin_frame_address(0);
#endif
}
static zend_always_inline bool zend_call_stack_overflowed(void *stack_limit)
{
	return (uintptr_t) zend_call_stack_position() <= (uintptr_t) stack_limit;
}
#endif

/* 8.3 marks the class names of internal arginfo with a bit of their own;
 * Abi.cpp turns it into the name bit the older engines expect */
#ifndef _ZEND_TYPE_LITERAL_NAME_BIT
#define _ZEND_TYPE_LITERAL_NAME_BIT (1u << 23)
#endif

#define PT_ABI_EG_FIELDS(X) \
	X(exception) \
	X(uninitialized_zval) \
	X(class_table) \
	X(function_table) \
	X(fake_scope) \
	X(current_module) \
	X(weakrefs) \
	X(vm_stack) \
	X(vm_stack_top) \
	X(vm_stack_end) \
	X(vm_stack_page_size) \
	X(error_reporting) \
	X(exit_status) \
	X(current_fiber_context) \
	X(assertions) \
	X(bailout)

#define PT_ABI_CG_FIELDS(X) \
	X(skip_shebang) \
	X(short_tags) \
	X(function_table) \
	X(arena) \
	X(map_ptr_base)

/* the zend_known_strings the extension reads (ZSTR_KNOWN): the enum is
 * renumbered whenever a minor adds a string */
#define PT_ABI_KNOWN_STRINGS(X) \
	X(ZEND_STR_MESSAGE) \
	X(ZEND_STR_NAME) \
	X(ZEND_STR_THIS)

/* the zend_object_handlers members read through Z_OBJ_HANDLER(): 8.5
 * inserted a handler before `compare`, moving everything after it */
#define PT_ABI_OBJECT_HANDLERS(X) \
	X(compare) \
	X(clone_obj) \
	X(read_property) \
	X(get_properties) \
	X(get_closure)

/* The shared core's API for the version-specific library (main.cpp,
 * Abi.cpp, Shadow.cpp, TrustedTypes.cpp): everything else in the core stays
 * hidden. The dependency only runs that way — the core never references a
 * symbol of the version-specific library. */
#if defined(_WIN32)
#ifdef PHPSTANTURBO_CORE_BUILD
#define PT_CORE_API __declspec(dllexport)
#else
#define PT_CORE_API
#endif
#else
#define PT_CORE_API __attribute__((visibility("default")))
#endif

namespace reg {
struct FunctionEntry;
}

/* what a class's zend_object_handlers override over the standard ones */
struct pt_abi_handlers
{
	int offset;
	zend_object_free_obj_t free_obj;
	zend_object_get_gc_t get_gc;
	zend_object_clone_obj_t clone_obj;
	zend_object_get_closure_t get_closure;
	zend_object_compare_t compare;
};

/* the zend_class_entry members read through PT_CE(): 8.6 inserted
 * ce_flags2 after ce_flags, moving every member after it (the ones before —
 * type, name, parent, refcount, ce_flags — stay put and are read directly) */
#define PT_ABI_CLASS_ENTRY_MEMBERS(X) \
	X(default_properties_count) \
	X(default_properties_table) \
	X(static_members_table__ptr) \
	X(mutable_data__ptr) \
	X(function_table) \
	X(properties_info) \
	X(constants_table) \
	X(constructor) \
	X(create_object) \
	X(interfaces) \
	X(num_interfaces) \
	X(interface_names) \
	X(attributes) \
	X(info) \
	X(__tostring) \
	X(__serialize) \
	X(__unserialize)

struct pt_abi_globals
{
#define PT_ABI_EG_FIELD(field) decltype(((zend_executor_globals *) nullptr)->field) *eg_##field;
#define PT_ABI_CG_FIELD(field) decltype(((zend_compiler_globals *) nullptr)->field) *cg_##field;
#define PT_ABI_KNOWN_STRING(id) zend_string *known_##id;
#define PT_ABI_OBJECT_HANDLER(member) size_t handler_offset_##member;
#define PT_ABI_CLASS_ENTRY_MEMBER(member) size_t ce_offset_##member;
	PT_ABI_EG_FIELDS(PT_ABI_EG_FIELD)
	PT_ABI_CG_FIELDS(PT_ABI_CG_FIELD)
	PT_ABI_KNOWN_STRINGS(PT_ABI_KNOWN_STRING)
	PT_ABI_OBJECT_HANDLERS(PT_ABI_OBJECT_HANDLER)
	PT_ABI_CLASS_ENTRY_MEMBERS(PT_ABI_CLASS_ENTRY_MEMBER)
#undef PT_ABI_EG_FIELD
#undef PT_ABI_CLASS_ENTRY_MEMBER
#undef PT_ABI_CG_FIELD
#undef PT_ABI_KNOWN_STRING
#undef PT_ABI_OBJECT_HANDLER
	/* EG(stack_base) / EG(stack_limit) from 8.3 on; before, Abi.cpp's own
	 * pair, determined like 8.3's zend_call_stack_init() */
	void **eg_stack_base;
	void **eg_stack_limit;

	/* member offsets that moved between minors */
	size_t offset_internal_function_handler; /* 8.4 inserted frameless_function_infos and doc_comment before it */
	size_t sizeof_arg_info;                  /* zend_arg_info: 8.6 appended doc_comment */

	/* constants whose value differs between minors */
	uint32_t php_version_id;
	uint32_t object_lazy_flags; /* IS_OBJ_LAZY_* (8.4+), 0 before */
	uint32_t is_reference_ex;   /* 8.5 made references collectable */
	uint32_t acc_use_guards;    /* (1 << 11) up to 8.4, (1 << 30) from 8.5 */

	/* engine inline functions whose behaviour differs between minors: the
	 * running PHP's own, compiled by Abi.cpp */
	zend_long (*dval_to_lval)(double d); /* 8.5 warns out of range */

	/* engine API that differs between minors, implemented once per minor by
	 * Abi.cpp — function pointers rather than symbols, so the shared core
	 * never links against the version-specific library (a Windows DLL
	 * cannot leave a symbol for its loader to resolve) */
	zend_function_entry *(*function_entries)(const reg::FunctionEntry *entries, size_t count);
	zend_class_entry *(*register_internal_class)(const char *name, const reg::FunctionEntry *entries, size_t count);
	const zend_object_handlers *(*object_handlers)(const pt_abi_handlers &overrides);
	void (*create_closure)(zval *out, zend_function *fn, zend_class_entry *scope, zend_class_entry *calledScope, zend_object *thisObject);
	zend_object *(*closure_this)(zval *closure);
	void (*weakrefs_hash_clean)(HashTable *ht);
	void (*weakrefs_hash_destroy)(HashTable *ht);
	void (*call_stack_size_error)();
	void (*try_assign_ref_str)(zval *zv, zend_string *str);
	void (*try_assign_ref_true)(zval *zv);
	void (*try_assign_ref_copy)(zval *zv, zval *value);
	/* zend_wrong_parameter_error() with the neutral ZPP numbering (below) */
	void (*wrong_parameter_error)(int errorCode, uint32_t num, char *name, int expectedType, zval *arg);
	/* the slow paths of the bool/double/string parameter parsing: 8.6
	 * changed them to return the value instead of writing through dest */
	bool (*parse_arg_bool_slow)(const zval *arg, bool *dest, uint32_t num);
	bool (*parse_arg_double_slow)(const zval *arg, double *dest, uint32_t num);
	bool (*parse_arg_str_slow)(zval *arg, zend_string **dest, uint32_t num);
	/* exported up to 8.5, inline wrappers over renamed functions from 8.6:
	 * a core referencing the old symbols would not load on 8.6 */
	void (*call_known_function)(zend_function *fn, zend_object *object, zend_class_entry *calledScope, zval *retval, uint32_t paramCount, zval *params, HashTable *namedParams);
	int (*stream_free)(php_stream *stream, int closeOptions);
	ssize_t (*stream_read)(php_stream *stream, char *buf, size_t count);
	/* pt_engine_run_on_fresh_stack(): a fiber context, its switch and a
	 * zend_try around the body (8.6 moved SETJMP to sigsetjmp) */
	void (*run_on_fresh_stack)(void (*body)(void *), void *data);
	/* zend_is_true() where the declaration cannot be renamed (MSVC, below) */
	bool (*is_true)(const zval *op);
	/* what the engine's own type errors name a value by: its type up to 8.2
	 * ("bool given"), from 8.3 its value for true/false ("true given") */
	const char *(*zval_value_name)(const zval *arg);
};

/* Defined by the shared core, filled by the version-specific library, which
 * reaches it through pt_core_abi() (abi.h's PHPSTANTURBO_ABI_IMPL block) */
extern pt_abi_globals pt_abi;
PT_CORE_API pt_abi_globals *pt_core_abi();
PT_CORE_API const char *pt_core_version();
#ifdef PHPSTANTURBO_ABI_IMPL
#define pt_abi (*pt_core_abi())
#endif

/* fills pt_abi from the running engine; first thing MINIT (and, in ZTS
 * builds, RINIT) does — version-specific (Abi.cpp) */
void pt_abi_init();

/* the engine's function table for the builder's entries: an emalloc()ed
 * array terminated by the sentinel the engine expects; the engine copies the
 * entries and keeps referencing their arg_info */
inline zend_function_entry *pt_abi_function_entries(const reg::FunctionEntry *entries, size_t count) { return pt_abi.function_entries(entries, count); }
/* zend_register_internal_class() for a class whose methods are the entries */
inline zend_class_entry *pt_abi_register_internal_class(const char *name, const reg::FunctionEntry *entries, size_t count) { return pt_abi.register_internal_class(name, entries, count); }
/* a persistent copy of the standard handlers with the given overrides (the
 * null members keep the standard handler) */
inline const zend_object_handlers *pt_abi_object_handlers(const pt_abi_handlers &overrides) { return pt_abi.object_handlers(overrides); }
inline void pt_abi_create_closure(zval *out, zend_function *fn, zend_class_entry *scope, zend_class_entry *calledScope, zend_object *thisObject) { pt_abi.create_closure(out, fn, scope, calledScope, thisObject); }
inline zend_object *pt_abi_closure_this(zval *closure) { return pt_abi.closure_this(closure); }
inline void pt_abi_weakrefs_hash_clean(HashTable *ht) { pt_abi.weakrefs_hash_clean(ht); }
inline void pt_abi_weakrefs_hash_destroy(HashTable *ht) { pt_abi.weakrefs_hash_destroy(ht); }
inline void pt_abi_call_stack_size_error() { pt_abi.call_stack_size_error(); }
inline void pt_abi_try_assign_ref_str(zval *zv, zend_string *str) { pt_abi.try_assign_ref_str(zv, str); }
inline void pt_abi_try_assign_ref_true(zval *zv) { pt_abi.try_assign_ref_true(zv); }
inline void pt_abi_try_assign_ref_copy(zval *zv, zval *value) { pt_abi.try_assign_ref_copy(zv, value); }

/* `then` from the given PHP_VERSION_ID on, `otherwise` before it — the
 * runtime form of an #if PHP_VERSION_ID gate */
#define PT_ABI_SINCE(version, then, otherwise) (pt_abi.php_version_id >= (version) ? (then) : (otherwise))

/* ce->member for the members in PT_ABI_CLASS_ENTRY_MEMBERS — an lvalue;
 * refuses anything but a zend_class_entry pointer at compile time */
template <typename T>
static zend_always_inline zend_class_entry *pt_abi_class_entry(T *ce)
{
	static_assert(std::is_same<typename std::remove_cv<T>::type, zend_class_entry>::value, "PT_CE() takes a zend_class_entry *");
	return (zend_class_entry *) ce;
}
#define PT_CE(ce, member) \
	(*(decltype(((zend_class_entry *) nullptr)->member) *) ((char *) pt_abi_class_entry(ce) + pt_abi.ce_offset_##member))

#ifndef PHPSTANTURBO_ABI_IMPL
/* Not ABI, but codegen: before 8.6 the headers enable ZEND_COLD for GCC
 * only (clang identifies as GCC 4.2), from 8.6 for clang too. Pinned so
 * the shared code is compiled alike against every header set. */
#if defined(__GNUC__) || defined(__clang__)
#undef ZEND_COLD
#define ZEND_COLD __attribute__((cold))
#endif

/* Codegen pins: helpers whose result is the same in every minor but whose
 * spelling changed (8.6 turned macros into inline functions and back), so
 * the shared code would compile to different instructions per header set.
 * One definition for all — 8.6's. */
static zend_always_inline bool pt_abi_string_equals_cstr_ci(const zend_string *s1, const char *s2, size_t s2_length)
{
	return ZSTR_LEN(s1) == s2_length && !zend_binary_strcasecmp(ZSTR_VAL(s1), ZSTR_LEN(s1), s2, s2_length);
}
static zend_always_inline bool pt_abi_string_is_interned(const zend_string *s)
{
	return GC_FLAGS(s) & IS_STR_INTERNED;
}
#undef zend_string_equals_literal_ci
#define zend_string_equals_literal_ci(str, literal) pt_abi_string_equals_cstr_ci(str, "" literal, sizeof(literal) - 1)
#undef zend_string_starts_with_literal
#define zend_string_starts_with_literal(str, prefix) zend_string_starts_with_cstr(str, "" prefix, sizeof(prefix) - 1)
#undef zend_string_starts_with_literal_ci
#define zend_string_starts_with_literal_ci(str, prefix) zend_string_starts_with_cstr_ci(str, "" prefix, sizeof(prefix) - 1)
#undef ZSTR_IS_INTERNED
#define ZSTR_IS_INTERNED(s) pt_abi_string_is_interned(s)
#undef zend_string_equals_ci
#define zend_string_equals_ci(s1, s2) pt_abi_string_equals_cstr_ci(s1, ZSTR_VAL(s2), ZSTR_LEN(s2))
/* array_init(): a macro up to 8.5, an inline function from 8.6 */
static zend_always_inline void pt_abi_array_init_size(zval *arg, uint32_t size)
{
	ZVAL_ARR(arg, zend_new_array(size));
}
#undef array_init
#define array_init(arg) pt_abi_array_init_size((arg), 0)
#undef array_init_size
#define array_init_size(arg, size) pt_abi_array_init_size((arg), (size))
/* 8.5 evaluates the argument once */
#undef Z_TRY_ADDREF_P
#define Z_TRY_ADDREF_P(pz) do { \
		zval *_pz = (pz); \
		if (Z_REFCOUNTED_P(_pz)) { \
			Z_ADDREF_P(_pz); \
		} \
	} while (0)
#undef Z_TRY_DELREF_P
#define Z_TRY_DELREF_P(pz) do { \
		zval *_pz = (pz); \
		if (Z_REFCOUNTED_P(_pz)) { \
			Z_DELREF_P(_pz); \
		} \
	} while (0)

/* ZEND_ASSUME() (and ZEND_ASSERT() over it): from 8.3 on, clang's
 * __builtin_assume() — a hint clang compiles differently from the
 * __builtin_unreachable() form 8.2 spells for every compiler */
#if defined(__clang__) && defined(__has_builtin)
#if __has_builtin(__builtin_assume)
#pragma clang diagnostic ignored "-Wassume"
#undef ZEND_ASSUME
#define ZEND_ASSUME(c) __builtin_assume(c)
#endif
#endif

/* zend_string.h's allocating helpers: 8.2's late patch releases spell them
 * as wrappers over fallible variants (zend_string_falloc(), ...), the same
 * result with fallible = false. 8.5's definitions — also where the headers'
 * macros expand to them (ZVAL_STRINGL(), RETURN_STRING(), ...). */
static zend_always_inline zend_string *pt_abi_zend_string_alloc(size_t len, bool persistent)
{
	zend_string *ret = (zend_string *) pemalloc(ZEND_MM_ALIGNED_SIZE(_ZSTR_STRUCT_SIZE(len)), persistent);
	GC_SET_REFCOUNT(ret, 1);
	GC_TYPE_INFO(ret) = GC_STRING | ((persistent ? IS_STR_PERSISTENT : 0) << GC_FLAGS_SHIFT);
	ZSTR_H(ret) = 0;
	ZSTR_LEN(ret) = len;
	return ret;
}
static zend_always_inline zend_string *pt_abi_zend_string_init(const char *str, size_t len, bool persistent)
{
	zend_string *ret = pt_abi_zend_string_alloc(len, persistent);
	memcpy(ZSTR_VAL(ret), str, len);
	ZSTR_VAL(ret)[len] = '\0';
	return ret;
}
static zend_always_inline zend_string *pt_abi_zend_string_dup(zend_string *s, bool persistent)
{
	if (GC_FLAGS(s) & IS_STR_INTERNED) {
		return s;
	}
	return pt_abi_zend_string_init(ZSTR_VAL(s), ZSTR_LEN(s), persistent);
}
static zend_always_inline zend_string *pt_abi_zend_string_realloc(zend_string *s, size_t len, bool persistent)
{
	zend_string *ret;
	if (!(GC_FLAGS(s) & IS_STR_INTERNED)) {
		if (EXPECTED(GC_REFCOUNT(s) == 1)) {
			ret = (zend_string *) perealloc(s, ZEND_MM_ALIGNED_SIZE(_ZSTR_STRUCT_SIZE(len)), persistent);
			ZSTR_LEN(ret) = len;
			zend_string_forget_hash_val(ret);
			return ret;
		}
	}
	ret = pt_abi_zend_string_alloc(len, persistent);
	memcpy(ZSTR_VAL(ret), ZSTR_VAL(s), MIN(len, ZSTR_LEN(s)) + 1);
	if (!(GC_FLAGS(s) & IS_STR_INTERNED)) {
		GC_DELREF(s);
	}
	return ret;
}
static zend_always_inline zend_string *pt_abi_smart_str_extract(smart_str *str)
{
	if (str->s == NULL) {
		return ZSTR_EMPTY_ALLOC();
	}
	ZSTR_VAL(str->s)[ZSTR_LEN(str->s)] = '\0';
	if (str->a > ZSTR_LEN(str->s)) {
		str->s = pt_abi_zend_string_realloc(str->s, ZSTR_LEN(str->s), false);
		str->a = ZSTR_LEN(str->s);
	}
	zend_string *res = str->s;
	str->s = NULL;
	return res;
}
/* variadic, like the smart_str ones below: PT_LC() expands to two arguments */
#define zend_string_alloc(...) pt_abi_zend_string_alloc(__VA_ARGS__)
#define zend_string_init(...) pt_abi_zend_string_init(__VA_ARGS__)
#define zend_string_dup(...) pt_abi_zend_string_dup(__VA_ARGS__)
#define zend_string_realloc(...) pt_abi_zend_string_realloc(__VA_ARGS__)
#define smart_str_extract(...) pt_abi_smart_str_extract(__VA_ARGS__)

/* 8.3's packed fill keeps nNumOfElements right when the array is not empty
 * to begin with; 8.2's sets it to the fill index (the same from an empty
 * array). 8.5's definitions. */
#undef ZEND_HASH_FILL_GROW
#define ZEND_HASH_FILL_GROW() do { \
		if (UNEXPECTED(__fill_idx >= __fill_ht->nTableSize)) { \
			__fill_ht->nNumOfElements += __fill_idx - __fill_ht->nNumUsed; \
			__fill_ht->nNumUsed = __fill_idx; \
			__fill_ht->nNextFreeElement = __fill_idx; \
			zend_hash_packed_grow(__fill_ht); \
			__fill_val = __fill_ht->arPacked + __fill_idx; \
		} \
	} while (0);
#undef ZEND_HASH_FILL_FINISH
#define ZEND_HASH_FILL_FINISH() do { \
		__fill_ht->nNumOfElements += __fill_idx - __fill_ht->nNumUsed; \
		__fill_ht->nNumUsed = __fill_idx; \
		__fill_ht->nNextFreeElement = __fill_idx; \
		__fill_ht->nInternalPointer = 0; \
	} while (0)

/* zend_smart_str.h's appending helpers: recent patch releases (8.3.36 and
 * its siblings) grow the buffer through smart_str_ferealloc(), which the
 * earlier patch releases of every minor do not export — a core built
 * against the new headers does not load there (LoadLibrary error 127 on
 * Windows, an unresolved symbol on Linux). These grow it through
 * smart_str_erealloc(), which every release exports, as the helpers did
 * before. The header is already included (support.h), so the macros below
 * only redirect the shared code's calls, not the header's definitions;
 * variadic, so an argument macro expanding to several (PT_LC) expands
 * first. */
static zend_always_inline size_t pt_abi_smart_str_alloc(smart_str *str, size_t len)
{
	if (UNEXPECTED(str->s == NULL)) {
		smart_str_erealloc(str, len);
		return len;
	}
	len += ZSTR_LEN(str->s);
	if (UNEXPECTED(len >= str->a)) {
		smart_str_erealloc(str, len);
	}
	return len;
}
static zend_always_inline void pt_abi_smart_str_appendl(smart_str *dest, const char *str, size_t len)
{
	size_t newLength = pt_abi_smart_str_alloc(dest, len);
	memcpy(ZSTR_VAL(dest->s) + ZSTR_LEN(dest->s), str, len);
	ZSTR_LEN(dest->s) = newLength;
}
static zend_always_inline void pt_abi_smart_str_append(smart_str *dest, const zend_string *src)
{
	pt_abi_smart_str_appendl(dest, ZSTR_VAL(src), ZSTR_LEN(src));
}
static zend_always_inline void pt_abi_smart_str_appends(smart_str *dest, const char *src)
{
	pt_abi_smart_str_appendl(dest, src, strlen(src));
}
static zend_always_inline void pt_abi_smart_str_append_smart_str(smart_str *dest, const smart_str *src)
{
	if (src->s != NULL && ZSTR_LEN(src->s) != 0) {
		pt_abi_smart_str_append(dest, src->s);
	}
}
static zend_always_inline void pt_abi_smart_str_appendc(smart_str *dest, char ch)
{
	size_t newLength = pt_abi_smart_str_alloc(dest, 1);
	ZSTR_VAL(dest->s)[newLength - 1] = ch;
	ZSTR_LEN(dest->s) = newLength;
}
static zend_always_inline void pt_abi_smart_str_append_long(smart_str *dest, zend_long num)
{
	char buf[32];
	char *result = zend_print_long_to_buf(buf + sizeof(buf) - 1, num);
	pt_abi_smart_str_appendl(dest, result, buf + sizeof(buf) - 1 - result);
}
static zend_always_inline void pt_abi_smart_str_append_unsigned(smart_str *dest, zend_ulong num)
{
	char buf[32];
	char *result = zend_print_ulong_to_buf(buf + sizeof(buf) - 1, num);
	pt_abi_smart_str_appendl(dest, result, buf + sizeof(buf) - 1 - result);
}
#undef smart_str_appendl
#define smart_str_appendl(...) pt_abi_smart_str_appendl(__VA_ARGS__)
#undef smart_str_appendc
#define smart_str_appendc(...) pt_abi_smart_str_appendc(__VA_ARGS__)
#undef smart_str_append
#define smart_str_append(...) pt_abi_smart_str_append(__VA_ARGS__)
#undef smart_str_appends
#define smart_str_appends(...) pt_abi_smart_str_appends(__VA_ARGS__)
#undef smart_str_append_long
#define smart_str_append_long(...) pt_abi_smart_str_append_long(__VA_ARGS__)
#undef smart_str_append_unsigned
#define smart_str_append_unsigned(...) pt_abi_smart_str_append_unsigned(__VA_ARGS__)
#undef smart_str_append_smart_str
#define smart_str_append_smart_str(...) pt_abi_smart_str_append_smart_str(__VA_ARGS__)

#undef EG
#undef CG
#define EG(field) (*pt_abi.eg_##field)
#define CG(field) (*pt_abi.cg_##field)

#undef ZSTR_KNOWN
#define ZSTR_KNOWN(id) (pt_abi.known_##id)

/* obj->handlers->member */
#define PT_OBJ_HANDLER(obj, member) \
	(*(decltype(((zend_object_handlers *) nullptr)->member) *) ((const char *) (obj)->handlers + pt_abi.handler_offset_##member))
#undef Z_OBJ_HANDLER
#define Z_OBJ_HANDLER(zval, member) PT_OBJ_HANDLER(Z_OBJ(zval), member)
#undef ZEND_COMPARE_OBJECTS_FALLBACK
#define ZEND_COMPARE_OBJECTS_FALLBACK(op1, op2) \
	if (Z_TYPE_P(op1) != IS_OBJECT || \
			Z_TYPE_P(op2) != IS_OBJECT || \
			Z_OBJ_HANDLER_P(op1, compare) != Z_OBJ_HANDLER_P(op2, compare)) { \
		return zend_std_compare_objects(op1, op2); \
	}

/* Argument parsing. The ZEND_PARSE_PARAMETERS / Z_PARAM_* macros expand in
 * our code with two numberings that move between minors: 8.6 turned the
 * ZPP_ERROR_* codes into a uint8_t enum without ZPP_ERROR_WRONG_COUNT, and
 * inserted Z_EXPECTED_CLASS_NAME(_OR_NULL) in the middle of the expected
 * types. The shared code carries the neutral numbering below (8.6's lists),
 * and the error report goes through pt_abi, which translates it for the
 * running engine. */
enum pt_abi_zpp_error : int {
	PT_ABI_ZPP_ERROR_OK,
	PT_ABI_ZPP_ERROR_FAILURE,
	PT_ABI_ZPP_ERROR_WRONG_CALLBACK,
	PT_ABI_ZPP_ERROR_WRONG_CLASS,
	PT_ABI_ZPP_ERROR_WRONG_CLASS_OR_NULL,
	PT_ABI_ZPP_ERROR_WRONG_CLASS_OR_STRING,
	PT_ABI_ZPP_ERROR_WRONG_CLASS_OR_STRING_OR_NULL,
	PT_ABI_ZPP_ERROR_WRONG_CLASS_OR_LONG,
	PT_ABI_ZPP_ERROR_WRONG_CLASS_OR_LONG_OR_NULL,
	PT_ABI_ZPP_ERROR_WRONG_ARG,
	PT_ABI_ZPP_ERROR_UNEXPECTED_EXTRA_NAMED,
	PT_ABI_ZPP_ERROR_WRONG_CALLBACK_OR_NULL,
};
enum pt_abi_expected_type : int {
	PT_ABI_Z_EXPECTED_LONG,
	PT_ABI_Z_EXPECTED_LONG_OR_NULL,
	PT_ABI_Z_EXPECTED_BOOL,
	PT_ABI_Z_EXPECTED_BOOL_OR_NULL,
	PT_ABI_Z_EXPECTED_STRING,
	PT_ABI_Z_EXPECTED_STRING_OR_NULL,
	PT_ABI_Z_EXPECTED_ARRAY,
	PT_ABI_Z_EXPECTED_ARRAY_OR_NULL,
	PT_ABI_Z_EXPECTED_ARRAY_OR_LONG,
	PT_ABI_Z_EXPECTED_ARRAY_OR_LONG_OR_NULL,
	PT_ABI_Z_EXPECTED_ITERABLE,
	PT_ABI_Z_EXPECTED_ITERABLE_OR_NULL,
	PT_ABI_Z_EXPECTED_FUNC,
	PT_ABI_Z_EXPECTED_FUNC_OR_NULL,
	PT_ABI_Z_EXPECTED_RESOURCE,
	PT_ABI_Z_EXPECTED_RESOURCE_OR_NULL,
	PT_ABI_Z_EXPECTED_PATH,
	PT_ABI_Z_EXPECTED_PATH_OR_NULL,
	PT_ABI_Z_EXPECTED_OBJECT,
	PT_ABI_Z_EXPECTED_OBJECT_OR_NULL,
	PT_ABI_Z_EXPECTED_DOUBLE,
	PT_ABI_Z_EXPECTED_DOUBLE_OR_NULL,
	PT_ABI_Z_EXPECTED_NUMBER,
	PT_ABI_Z_EXPECTED_NUMBER_OR_NULL,
	PT_ABI_Z_EXPECTED_NUMBER_OR_STRING,
	PT_ABI_Z_EXPECTED_NUMBER_OR_STRING_OR_NULL,
	PT_ABI_Z_EXPECTED_ARRAY_OR_STRING,
	PT_ABI_Z_EXPECTED_ARRAY_OR_STRING_OR_NULL,
	PT_ABI_Z_EXPECTED_STRING_OR_LONG,
	PT_ABI_Z_EXPECTED_STRING_OR_LONG_OR_NULL,
	PT_ABI_Z_EXPECTED_CLASS_NAME,
	PT_ABI_Z_EXPECTED_CLASS_NAME_OR_NULL,
	PT_ABI_Z_EXPECTED_OBJECT_OR_CLASS_NAME,
	PT_ABI_Z_EXPECTED_OBJECT_OR_CLASS_NAME_OR_NULL,
	PT_ABI_Z_EXPECTED_OBJECT_OR_STRING,
	PT_ABI_Z_EXPECTED_OBJECT_OR_STRING_OR_NULL,
};
#undef ZPP_ERROR_OK
#define ZPP_ERROR_OK PT_ABI_ZPP_ERROR_OK
#undef ZPP_ERROR_FAILURE
#define ZPP_ERROR_FAILURE PT_ABI_ZPP_ERROR_FAILURE
#undef ZPP_ERROR_WRONG_CALLBACK
#define ZPP_ERROR_WRONG_CALLBACK PT_ABI_ZPP_ERROR_WRONG_CALLBACK
#undef ZPP_ERROR_WRONG_CLASS
#define ZPP_ERROR_WRONG_CLASS PT_ABI_ZPP_ERROR_WRONG_CLASS
#undef ZPP_ERROR_WRONG_CLASS_OR_NULL
#define ZPP_ERROR_WRONG_CLASS_OR_NULL PT_ABI_ZPP_ERROR_WRONG_CLASS_OR_NULL
#undef ZPP_ERROR_WRONG_CLASS_OR_STRING
#define ZPP_ERROR_WRONG_CLASS_OR_STRING PT_ABI_ZPP_ERROR_WRONG_CLASS_OR_STRING
#undef ZPP_ERROR_WRONG_CLASS_OR_STRING_OR_NULL
#define ZPP_ERROR_WRONG_CLASS_OR_STRING_OR_NULL PT_ABI_ZPP_ERROR_WRONG_CLASS_OR_STRING_OR_NULL
#undef ZPP_ERROR_WRONG_CLASS_OR_LONG
#define ZPP_ERROR_WRONG_CLASS_OR_LONG PT_ABI_ZPP_ERROR_WRONG_CLASS_OR_LONG
#undef ZPP_ERROR_WRONG_CLASS_OR_LONG_OR_NULL
#define ZPP_ERROR_WRONG_CLASS_OR_LONG_OR_NULL PT_ABI_ZPP_ERROR_WRONG_CLASS_OR_LONG_OR_NULL
#undef ZPP_ERROR_WRONG_ARG
#define ZPP_ERROR_WRONG_ARG PT_ABI_ZPP_ERROR_WRONG_ARG
#undef ZPP_ERROR_UNEXPECTED_EXTRA_NAMED
#define ZPP_ERROR_UNEXPECTED_EXTRA_NAMED PT_ABI_ZPP_ERROR_UNEXPECTED_EXTRA_NAMED
#undef ZPP_ERROR_WRONG_CALLBACK_OR_NULL
#define ZPP_ERROR_WRONG_CALLBACK_OR_NULL PT_ABI_ZPP_ERROR_WRONG_CALLBACK_OR_NULL
#define Z_EXPECTED_LONG PT_ABI_Z_EXPECTED_LONG
#define Z_EXPECTED_LONG_OR_NULL PT_ABI_Z_EXPECTED_LONG_OR_NULL
#define Z_EXPECTED_BOOL PT_ABI_Z_EXPECTED_BOOL
#define Z_EXPECTED_BOOL_OR_NULL PT_ABI_Z_EXPECTED_BOOL_OR_NULL
#define Z_EXPECTED_STRING PT_ABI_Z_EXPECTED_STRING
#define Z_EXPECTED_STRING_OR_NULL PT_ABI_Z_EXPECTED_STRING_OR_NULL
#define Z_EXPECTED_ARRAY PT_ABI_Z_EXPECTED_ARRAY
#define Z_EXPECTED_ARRAY_OR_NULL PT_ABI_Z_EXPECTED_ARRAY_OR_NULL
#define Z_EXPECTED_ARRAY_OR_LONG PT_ABI_Z_EXPECTED_ARRAY_OR_LONG
#define Z_EXPECTED_ARRAY_OR_LONG_OR_NULL PT_ABI_Z_EXPECTED_ARRAY_OR_LONG_OR_NULL
#define Z_EXPECTED_ITERABLE PT_ABI_Z_EXPECTED_ITERABLE
#define Z_EXPECTED_ITERABLE_OR_NULL PT_ABI_Z_EXPECTED_ITERABLE_OR_NULL
#define Z_EXPECTED_FUNC PT_ABI_Z_EXPECTED_FUNC
#define Z_EXPECTED_FUNC_OR_NULL PT_ABI_Z_EXPECTED_FUNC_OR_NULL
#define Z_EXPECTED_RESOURCE PT_ABI_Z_EXPECTED_RESOURCE
#define Z_EXPECTED_RESOURCE_OR_NULL PT_ABI_Z_EXPECTED_RESOURCE_OR_NULL
#define Z_EXPECTED_PATH PT_ABI_Z_EXPECTED_PATH
#define Z_EXPECTED_PATH_OR_NULL PT_ABI_Z_EXPECTED_PATH_OR_NULL
#define Z_EXPECTED_OBJECT PT_ABI_Z_EXPECTED_OBJECT
#define Z_EXPECTED_OBJECT_OR_NULL PT_ABI_Z_EXPECTED_OBJECT_OR_NULL
#define Z_EXPECTED_DOUBLE PT_ABI_Z_EXPECTED_DOUBLE
#define Z_EXPECTED_DOUBLE_OR_NULL PT_ABI_Z_EXPECTED_DOUBLE_OR_NULL
#define Z_EXPECTED_NUMBER PT_ABI_Z_EXPECTED_NUMBER
#define Z_EXPECTED_NUMBER_OR_NULL PT_ABI_Z_EXPECTED_NUMBER_OR_NULL
#define Z_EXPECTED_NUMBER_OR_STRING PT_ABI_Z_EXPECTED_NUMBER_OR_STRING
#define Z_EXPECTED_NUMBER_OR_STRING_OR_NULL PT_ABI_Z_EXPECTED_NUMBER_OR_STRING_OR_NULL
#define Z_EXPECTED_ARRAY_OR_STRING PT_ABI_Z_EXPECTED_ARRAY_OR_STRING
#define Z_EXPECTED_ARRAY_OR_STRING_OR_NULL PT_ABI_Z_EXPECTED_ARRAY_OR_STRING_OR_NULL
#define Z_EXPECTED_STRING_OR_LONG PT_ABI_Z_EXPECTED_STRING_OR_LONG
#define Z_EXPECTED_STRING_OR_LONG_OR_NULL PT_ABI_Z_EXPECTED_STRING_OR_LONG_OR_NULL
#define Z_EXPECTED_CLASS_NAME PT_ABI_Z_EXPECTED_CLASS_NAME
#define Z_EXPECTED_CLASS_NAME_OR_NULL PT_ABI_Z_EXPECTED_CLASS_NAME_OR_NULL
#define Z_EXPECTED_OBJECT_OR_CLASS_NAME PT_ABI_Z_EXPECTED_OBJECT_OR_CLASS_NAME
#define Z_EXPECTED_OBJECT_OR_CLASS_NAME_OR_NULL PT_ABI_Z_EXPECTED_OBJECT_OR_CLASS_NAME_OR_NULL
#define Z_EXPECTED_OBJECT_OR_STRING PT_ABI_Z_EXPECTED_OBJECT_OR_STRING
#define Z_EXPECTED_OBJECT_OR_STRING_OR_NULL PT_ABI_Z_EXPECTED_OBJECT_OR_STRING_OR_NULL

#undef ZEND_PARSE_PARAMETERS_START_EX
#define ZEND_PARSE_PARAMETERS_START_EX(flags, min_num_args, max_num_args) do { \
		const int _flags = (flags); \
		uint32_t _min_num_args = (min_num_args); \
		uint32_t _max_num_args = (uint32_t) (max_num_args); \
		uint32_t _num_args = EX_NUM_ARGS(); \
		uint32_t _i = 0; \
		zval *_real_arg, *_arg = NULL; \
		int _expected_type = Z_EXPECTED_LONG; \
		char *_error = NULL; \
		bool _dummy = 0; \
		bool _optional = 0; \
		int _error_code = ZPP_ERROR_OK; \
		((void)_i); \
		((void)_real_arg); \
		((void)_arg); \
		((void)_expected_type); \
		((void)_error); \
		((void)_optional); \
		((void)_dummy); \
		\
		do { \
			if (UNEXPECTED(_num_args < _min_num_args) || \
			    UNEXPECTED(_num_args > _max_num_args)) { \
				if (!(_flags & ZEND_PARSE_PARAMS_QUIET)) { \
					zend_wrong_parameters_count_error(_min_num_args, _max_num_args); \
				} \
				_error_code = ZPP_ERROR_FAILURE; \
				break; \
			} \
			_real_arg = ZEND_CALL_ARG(execute_data, 0);

#undef ZEND_PARSE_PARAMETERS_END_EX
#define ZEND_PARSE_PARAMETERS_END_EX(failure) \
			ZEND_ASSERT(_i == _max_num_args || _max_num_args == (uint32_t) -1); \
		} while (0); \
		if (UNEXPECTED(_error_code != ZPP_ERROR_OK)) { \
			if (!(_flags & ZEND_PARSE_PARAMS_QUIET)) { \
				pt_abi.wrong_parameter_error(_error_code, _i, _error, _expected_type, _arg); \
			} \
			failure; \
		} \
	} while (0)

/* the fast paths of zend_parse_arg_bool/double/str() (identical in every
 * minor), over the slow paths in pt_abi */
static zend_always_inline bool pt_abi_parse_arg_bool(const zval *arg, bool *dest, bool *is_null, bool check_null, uint32_t arg_num)
{
	if (check_null) {
		*is_null = 0;
	}
	if (EXPECTED(Z_TYPE_P(arg) == IS_TRUE)) {
		*dest = 1;
	} else if (EXPECTED(Z_TYPE_P(arg) == IS_FALSE)) {
		*dest = 0;
	} else if (check_null && Z_TYPE_P(arg) == IS_NULL) {
		*is_null = 1;
		*dest = 0;
	} else {
		return pt_abi.parse_arg_bool_slow(arg, dest, arg_num);
	}
	return 1;
}
static zend_always_inline bool pt_abi_parse_arg_double(const zval *arg, double *dest, bool *is_null, bool check_null, uint32_t arg_num)
{
	if (check_null) {
		*is_null = 0;
	}
	if (EXPECTED(Z_TYPE_P(arg) == IS_DOUBLE)) {
		*dest = Z_DVAL_P(arg);
	} else if (check_null && Z_TYPE_P(arg) == IS_NULL) {
		*is_null = 1;
		*dest = 0.0;
	} else {
		return pt_abi.parse_arg_double_slow(arg, dest, arg_num);
	}
	return 1;
}
static zend_always_inline bool pt_abi_parse_arg_str(zval *arg, zend_string **dest, bool check_null, uint32_t arg_num)
{
	if (EXPECTED(Z_TYPE_P(arg) == IS_STRING)) {
		*dest = Z_STR_P(arg);
	} else if (check_null && Z_TYPE_P(arg) == IS_NULL) {
		*dest = NULL;
	} else {
		return pt_abi.parse_arg_str_slow(arg, dest, arg_num);
	}
	return 1;
}
/* zend_is_callable() is an inline wrapper over zend_is_callable_ex() from
 * 8.6 on, and no longer exported; _ex is exported by every minor */
#define zend_is_callable(callable, checkFlags, callableName) zend_is_callable_ex((callable), NULL, (checkFlags), (callableName), NULL, NULL)
#define zend_call_known_function(fn, object, calledScope, retval, paramCount, params, namedParams) \
	pt_abi.call_known_function((fn), (object), (calledScope), (retval), (paramCount), (params), (namedParams))
#define zend_call_known_instance_method(fn, object, retval, paramCount, params) \
	pt_abi.call_known_function((fn), (object), (object)->ce, (retval), (paramCount), (params), NULL)
/* 8.6 dropped the underscore of _php_stream_free() / _php_stream_read() */
#undef php_stream_close
#define php_stream_close(stream) pt_abi.stream_free((stream), PHP_STREAM_FREE_CLOSE)
#undef php_stream_read
#define php_stream_read(stream, buf, count) pt_abi.stream_read((stream), (buf), (count))

#define zend_parse_arg_bool pt_abi_parse_arg_bool
#define zend_parse_arg_double pt_abi_parse_arg_double
#define zend_parse_arg_str pt_abi_parse_arg_str

#undef IS_REFERENCE_EX
#define IS_REFERENCE_EX (pt_abi.is_reference_ex)
#undef ZEND_ACC_USE_GUARDS
#define ZEND_ACC_USE_GUARDS (pt_abi.acc_use_guards)

/* 8.4 made map pointer offsets signed (static map pointers sit below the
 * base); every offset 8.3 hands out is positive, so the signed form reads
 * both */
#undef ZEND_MAP_PTR_GET_IMM
#define ZEND_MAP_PTR_GET_IMM(ptr) (*ZEND_MAP_PTR_OFFSET2PTR((intptr_t) ZEND_MAP_PTR(ptr)))

/* the engine's inline helpers below were compiled against their own
 * header's layout — redefined over the neutral accessors above */
static zend_always_inline void *pt_abi_map_ptr_get(void *ptr)
{
	return ((uintptr_t) ptr & 1) ? *(void **) ((char *) CG(map_ptr_base) + (intptr_t) ptr) : ptr;
}
static zend_always_inline zval *pt_abi_default_properties_table(zend_class_entry *ce)
{
	void *mutableData = PT_CE(ce, mutable_data__ptr);
	if ((ce->ce_flags & ZEND_ACC_HAS_AST_PROPERTIES) && mutableData != NULL) {
		return ((zend_class_mutable_data *) *(void **) ((char *) CG(map_ptr_base) + (intptr_t) mutableData))->default_properties_table;
	}
	return PT_CE(ce, default_properties_table);
}
#undef CE_DEFAULT_PROPERTIES_TABLE
#define CE_DEFAULT_PROPERTIES_TABLE(ce) pt_abi_default_properties_table(ce)
#undef CE_STATIC_MEMBERS
#define CE_STATIC_MEMBERS(ce) ((zval *) pt_abi_map_ptr_get(PT_CE(ce, static_members_table__ptr)))

static zend_always_inline void *pt_abi_object_alloc(size_t obj_size, zend_class_entry *ce)
{
	size_t properties = sizeof(zval) * (PT_CE(ce, default_properties_count) - ((ce->ce_flags & ZEND_ACC_USE_GUARDS) ? 0 : 1));
	void *obj = emalloc(obj_size + properties);
	memset(obj, 0, obj_size - sizeof(zend_object));
	return obj;
}
#define zend_object_alloc(obj_size, ce) pt_abi_object_alloc(obj_size, ce)

#define zend_dval_to_lval(d) (pt_abi.dval_to_lval(d))

/* exported from 8.3 on; the type errors the shared code raises name the
 * value the way the running engine's own do */
#define zend_zval_value_name(arg) (pt_abi.zval_value_name(arg))

/* 8.5 destroys the previous value with zval_ptr_safe_dtor() */
#undef ZEND_TRY_ASSIGN_REF_STR
#define ZEND_TRY_ASSIGN_REF_STR(zv, str) pt_abi_try_assign_ref_str(zv, str)
#undef ZEND_TRY_ASSIGN_REF_TRUE
#define ZEND_TRY_ASSIGN_REF_TRUE(zv) pt_abi_try_assign_ref_true(zv)
#undef ZEND_TRY_ASSIGN_REF_COPY
#define ZEND_TRY_ASSIGN_REF_COPY(zv, value) pt_abi_try_assign_ref_copy(zv, value)

/* arg_info[i] of a user function (zend_arg_info; name and type keep their
 * offsets, the stride moved) */
#define PT_ARG_INFO(argInfo, i) ((zend_arg_info *) ((char *) (argInfo) + (size_t) (i) * pt_abi.sizeof_arg_info))

/* fn->internal_function.handler */
#define PT_INTERNAL_HANDLER(fn) (*(zif_handler *) ((const char *) (fn) + pt_abi.offset_internal_function_handler))

/* zend_object_is_lazy(): zend_object.extra_flags is 8.4's name for the
 * padding after `handle`, which 8.3 leaves unset — the mask is 0 there */
static zend_always_inline bool pt_abi_object_is_lazy(const zend_object *obj)
{
	uint32_t extraFlags;
	memcpy(&extraFlags, (const char *) obj + offsetof(zend_object, handle) + sizeof(uint32_t), sizeof(extraFlags));
	return (extraFlags & pt_abi.object_lazy_flags) != 0;
}

/* zend_is_true() returned int up to 8.3 and bool since; declared bool —
 * the int it returned was always 0 or 1, so reading the low byte is exact */
#if defined(__GNUC__)
#define PT_ABI_SYMBOL_STR2(prefix, name) #prefix #name
#define PT_ABI_SYMBOL_STR(prefix, name) PT_ABI_SYMBOL_STR2(prefix, name)
extern "C" ZEND_API bool ZEND_FASTCALL pt_abi_zend_is_true(const zval *op) __asm__(PT_ABI_SYMBOL_STR(__USER_LABEL_PREFIX__, zend_is_true));
#define zend_is_true(op) pt_abi_zend_is_true(op)
#else
/* MSVC has no assembler names for declarations: through Abi.cpp, which
 * calls the running engine's own */
#define zend_is_true(op) pt_abi.is_true(op)
#endif
#endif

#endif
