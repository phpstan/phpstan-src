/*
 * The version-specific side of abi.h: the only translation unit reading the
 * engine globals by their compiled-in layout.
 */

#define PHPSTANTURBO_ABI_IMPL
#include "support.h"
#include "reg.h"

#include "zend_closures.h"
#include "zend_weakrefs.h"


static const zend_object_handlers *abi_object_handlers(const pt_abi_handlers &overrides)
{
	auto *handlers = (zend_object_handlers *) pemalloc(sizeof(zend_object_handlers), 1);
	memcpy(handlers, &std_object_handlers, sizeof(zend_object_handlers));
	handlers->offset = overrides.offset;
	if (overrides.free_obj != NULL) handlers->free_obj = overrides.free_obj;
	if (overrides.get_gc != NULL) handlers->get_gc = overrides.get_gc;
	if (overrides.clone_obj != NULL) handlers->clone_obj = overrides.clone_obj;
	if (overrides.get_closure != NULL) handlers->get_closure = overrides.get_closure;
	if (overrides.compare != NULL) handlers->compare = overrides.compare;
	return handlers;
}

static zend_function_entry *abi_function_entries(const reg::FunctionEntry *entries, size_t count)
{
	zend_function_entry *functions = (zend_function_entry *) ecalloc(count + 1, sizeof(zend_function_entry));
	for (size_t i = 0; i < count; i++) {
		functions[i].fname = entries[i].fname;
		functions[i].handler = entries[i].handler;
		functions[i].arg_info = entries[i].argInfo;
		functions[i].num_args = entries[i].numArgs;
		functions[i].flags = entries[i].flags;
	}
	return functions;
}

static zend_class_entry *abi_register_internal_class(const char *name, const reg::FunctionEntry *entries, size_t count)
{
	zend_function_entry *functions = abi_function_entries(entries, count);
	zend_class_entry ce;
	INIT_CLASS_ENTRY_EX(ce, name, strlen(name), functions);
	zend_class_entry *registered = zend_register_internal_class(&ce);
	efree(functions);
	return registered;
}

static void abi_create_closure(zval *out, zend_function *fn, zend_class_entry *scope, zend_class_entry *calledScope, zend_object *thisObject)
{
#if PHP_VERSION_ID >= 80600
	/* php-src fbb2e1f23d6: $this is passed as zend_object* from 8.6 on */
	zend_create_closure(out, fn, scope, calledScope, thisObject);
#else
	zval thisZv;
	if (thisObject != NULL) {
		ZVAL_OBJ(&thisZv, thisObject);
	}
	zend_create_closure(out, fn, scope, calledScope, thisObject != NULL ? &thisZv : NULL);
#endif
}

static zend_object *abi_closure_this(zval *closure)
{
#if PHP_VERSION_ID >= 80600
	/* PHP 8.6 holds the bound $this as a zend_object (NULL when unbound) */
	return zend_get_closure_this_ptr(closure);
#else
	zval *thisZv = zend_get_closure_this_ptr(closure);
	return thisZv != NULL && Z_TYPE_P(thisZv) == IS_OBJECT ? Z_OBJ_P(thisZv) : NULL;
#endif
}

/* zend_weakrefs_hash_clean()/_destroy() only exist since PHP 8.5; before,
 * the same unregister-then-destroy is spelled out with the API there is */
static void abi_weakrefs_hash_clean(HashTable *ht)
{
#if PHP_VERSION_ID >= 80500
	zend_weakrefs_hash_clean(ht);
#else
	zend_ulong objKey;
	ZEND_HASH_MAP_FOREACH_NUM_KEY(ht, objKey) {
		zend_weakrefs_hash_del(ht, zend_weakref_key_to_object(objKey));
	} ZEND_HASH_FOREACH_END();
#endif
}

static void abi_weakrefs_hash_destroy(HashTable *ht)
{
#if PHP_VERSION_ID >= 80500
	zend_weakrefs_hash_destroy(ht);
#else
	abi_weakrefs_hash_clean(ht);
	zend_hash_destroy(ht);
#endif
}

static void abi_call_stack_size_error()
{
#if PHP_VERSION_ID >= 80400
	zend_call_stack_size_error();
#else
	/* static in PHP 8.3; the same message */
	zend_throw_error(nullptr, "Maximum call stack size of %zu bytes (zend.max_allowed_stack_size - zend.reserved_stack_size) reached. Infinite recursion?",
		(size_t) ((uintptr_t) EG(stack_base) - (uintptr_t) EG(stack_limit)));
#endif
}

static void abi_try_assign_ref_str(zval *zv, zend_string *str)
{
	ZEND_TRY_ASSIGN_REF_STR(zv, str);
}

static void abi_try_assign_ref_true(zval *zv)
{
	ZEND_TRY_ASSIGN_REF_TRUE(zv);
}

/* the neutral ZPP numbering of abi.h, translated for this engine */
static const int abi_zpp_errors[] = {
	ZPP_ERROR_OK,
	ZPP_ERROR_FAILURE,
	ZPP_ERROR_WRONG_CALLBACK,
	ZPP_ERROR_WRONG_CLASS,
	ZPP_ERROR_WRONG_CLASS_OR_NULL,
	ZPP_ERROR_WRONG_CLASS_OR_STRING,
	ZPP_ERROR_WRONG_CLASS_OR_STRING_OR_NULL,
	ZPP_ERROR_WRONG_CLASS_OR_LONG,
	ZPP_ERROR_WRONG_CLASS_OR_LONG_OR_NULL,
	ZPP_ERROR_WRONG_ARG,
	ZPP_ERROR_UNEXPECTED_EXTRA_NAMED,
	ZPP_ERROR_WRONG_CALLBACK_OR_NULL,
};
static const zend_expected_type abi_expected_types[] = {
	Z_EXPECTED_LONG,
	Z_EXPECTED_LONG_OR_NULL,
	Z_EXPECTED_BOOL,
	Z_EXPECTED_BOOL_OR_NULL,
	Z_EXPECTED_STRING,
	Z_EXPECTED_STRING_OR_NULL,
	Z_EXPECTED_ARRAY,
	Z_EXPECTED_ARRAY_OR_NULL,
	Z_EXPECTED_ARRAY_OR_LONG,
	Z_EXPECTED_ARRAY_OR_LONG_OR_NULL,
	Z_EXPECTED_ITERABLE,
	Z_EXPECTED_ITERABLE_OR_NULL,
	Z_EXPECTED_FUNC,
	Z_EXPECTED_FUNC_OR_NULL,
	Z_EXPECTED_RESOURCE,
	Z_EXPECTED_RESOURCE_OR_NULL,
	Z_EXPECTED_PATH,
	Z_EXPECTED_PATH_OR_NULL,
	Z_EXPECTED_OBJECT,
	Z_EXPECTED_OBJECT_OR_NULL,
	Z_EXPECTED_DOUBLE,
	Z_EXPECTED_DOUBLE_OR_NULL,
	Z_EXPECTED_NUMBER,
	Z_EXPECTED_NUMBER_OR_NULL,
	Z_EXPECTED_NUMBER_OR_STRING,
	Z_EXPECTED_NUMBER_OR_STRING_OR_NULL,
	Z_EXPECTED_ARRAY_OR_STRING,
	Z_EXPECTED_ARRAY_OR_STRING_OR_NULL,
	Z_EXPECTED_STRING_OR_LONG,
	Z_EXPECTED_STRING_OR_LONG_OR_NULL,
#if PHP_VERSION_ID >= 80600
	Z_EXPECTED_CLASS_NAME,
#else
	Z_EXPECTED_LONG, /* not produced before 8.6 */
#endif
#if PHP_VERSION_ID >= 80600
	Z_EXPECTED_CLASS_NAME_OR_NULL,
#else
	Z_EXPECTED_LONG, /* not produced before 8.6 */
#endif
	Z_EXPECTED_OBJECT_OR_CLASS_NAME,
	Z_EXPECTED_OBJECT_OR_CLASS_NAME_OR_NULL,
	Z_EXPECTED_OBJECT_OR_STRING,
	Z_EXPECTED_OBJECT_OR_STRING_OR_NULL,
};

static void abi_wrong_parameter_error(int errorCode, uint32_t num, char *name, int expectedType, zval *arg)
{
#if PHP_VERSION_ID >= 80600
	zend_wrong_parameter_error((zpp_error) abi_zpp_errors[errorCode], num, name, abi_expected_types[expectedType], arg);
#else
	zend_wrong_parameter_error(abi_zpp_errors[errorCode], num, name, abi_expected_types[expectedType], arg);
#endif
}

static bool abi_parse_arg_bool_slow(const zval *arg, bool *dest, uint32_t num)
{
#if PHP_VERSION_ID >= 80600
	zpp_parse_bool_status status = zend_parse_arg_bool_slow(arg, num);
	if (UNEXPECTED(status == ZPP_PARSE_BOOL_STATUS_ERROR)) {
		return false;
	}
	*dest = status;
	return true;
#else
	return zend_parse_arg_bool_slow(arg, dest, num);
#endif
}

static bool abi_parse_arg_double_slow(const zval *arg, double *dest, uint32_t num)
{
#if PHP_VERSION_ID >= 80600
	*dest = zend_parse_arg_double_slow(arg, num);
	return !zend_isnan(*dest);
#else
	return zend_parse_arg_double_slow(arg, dest, num);
#endif
}

static bool abi_parse_arg_str_slow(zval *arg, zend_string **dest, uint32_t num)
{
#if PHP_VERSION_ID >= 80600
	*dest = zend_parse_arg_str_slow(arg, num);
	return *dest != NULL;
#else
	return zend_parse_arg_str_slow(arg, dest, num);
#endif
}

static void abi_call_known_function(zend_function *fn, zend_object *object, zend_class_entry *calledScope, zval *retval, uint32_t paramCount, zval *params, HashTable *namedParams)
{
	zend_call_known_function(fn, object, calledScope, retval, paramCount, params, namedParams);
}

static int abi_stream_free(php_stream *stream, int closeOptions)
{
	return php_stream_free(stream, closeOptions);
}

static ssize_t abi_stream_read(php_stream *stream, char *buf, size_t count)
{
	return php_stream_read(stream, buf, count);
}

void pt_abi_init()
{
#define PT_ABI_EG_FIELD(field) pt_abi.eg_##field = &EG(field);
#define PT_ABI_CG_FIELD(field) pt_abi.cg_##field = &CG(field);
#define PT_ABI_KNOWN_STRING(id) pt_abi.known_##id = ZSTR_KNOWN(id);
#define PT_ABI_OBJECT_HANDLER(member) pt_abi.handler_offset_##member = offsetof(zend_object_handlers, member);
#define PT_ABI_CLASS_ENTRY_MEMBER(member) pt_abi.ce_offset_##member = offsetof(zend_class_entry, member);
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

	pt_abi.offset_internal_function_handler = offsetof(zend_internal_function, handler);
	pt_abi.sizeof_arg_info = sizeof(zend_arg_info);
	pt_abi.php_version_id = PHP_VERSION_ID;
#if PHP_VERSION_ID >= 80400
	static_assert(offsetof(zend_object, extra_flags) == offsetof(zend_object, handle) + sizeof(uint32_t), "pt_abi_object_is_lazy() reads extra_flags right after handle");
	pt_abi.object_lazy_flags = IS_OBJ_LAZY_UNINITIALIZED | IS_OBJ_LAZY_PROXY;
#else
	pt_abi.object_lazy_flags = 0;
#endif
	pt_abi.is_reference_ex = IS_REFERENCE_EX;
	pt_abi.acc_use_guards = ZEND_ACC_USE_GUARDS;
	pt_abi.dval_to_lval = [](double d) -> zend_long { return zend_dval_to_lval(d); };
	pt_abi.function_entries = abi_function_entries;
	pt_abi.register_internal_class = abi_register_internal_class;
	pt_abi.object_handlers = abi_object_handlers;
	pt_abi.create_closure = abi_create_closure;
	pt_abi.closure_this = abi_closure_this;
	pt_abi.weakrefs_hash_clean = abi_weakrefs_hash_clean;
	pt_abi.weakrefs_hash_destroy = abi_weakrefs_hash_destroy;
	pt_abi.call_stack_size_error = abi_call_stack_size_error;
	pt_abi.try_assign_ref_str = abi_try_assign_ref_str;
	pt_abi.try_assign_ref_true = abi_try_assign_ref_true;
	pt_abi.wrong_parameter_error = abi_wrong_parameter_error;
	pt_abi.parse_arg_bool_slow = abi_parse_arg_bool_slow;
	pt_abi.parse_arg_double_slow = abi_parse_arg_double_slow;
	pt_abi.parse_arg_str_slow = abi_parse_arg_str_slow;
	pt_abi.call_known_function = abi_call_known_function;
	pt_abi.stream_free = abi_stream_free;
	pt_abi.stream_read = abi_stream_read;
}
