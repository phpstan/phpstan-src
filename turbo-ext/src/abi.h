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

#define PT_ABI_EG_FIELDS(X) \
	X(exception) \
	X(uninitialized_zval) \
	X(class_table) \
	X(function_table) \
	X(fake_scope) \
	X(stack_limit) \
	X(stack_base) \
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
#define PT_CORE_API
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
};

/* Defined by the shared core, filled by the version-specific library. */
PT_CORE_API extern pt_abi_globals pt_abi;

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

/* 8.5 destroys the previous value with zval_ptr_safe_dtor() */
#undef ZEND_TRY_ASSIGN_REF_STR
#define ZEND_TRY_ASSIGN_REF_STR(zv, str) pt_abi_try_assign_ref_str(zv, str)
#undef ZEND_TRY_ASSIGN_REF_TRUE
#define ZEND_TRY_ASSIGN_REF_TRUE(zv) pt_abi_try_assign_ref_true(zv)

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
#endif
#endif

#endif
