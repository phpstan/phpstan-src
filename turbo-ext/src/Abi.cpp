/*
 * The version-specific side of abi.h: the only translation unit reading the
 * engine globals by their compiled-in layout.
 */

#define PHPSTANTURBO_ABI_IMPL
#include "support.h"
#include "reg.h"

#include "zend_closures.h"
#include "zend_weakrefs.h"

pt_abi_globals pt_abi;

void pt_abi_init()
{
#define PT_ABI_EG_FIELD(field) pt_abi.eg_##field = &EG(field);
#define PT_ABI_CG_FIELD(field) pt_abi.cg_##field = &CG(field);
#define PT_ABI_KNOWN_STRING(id) pt_abi.known_##id = ZSTR_KNOWN(id);
#define PT_ABI_OBJECT_HANDLER(member) pt_abi.handler_offset_##member = offsetof(zend_object_handlers, member);
	PT_ABI_EG_FIELDS(PT_ABI_EG_FIELD)
	PT_ABI_CG_FIELDS(PT_ABI_CG_FIELD)
	PT_ABI_KNOWN_STRINGS(PT_ABI_KNOWN_STRING)
	PT_ABI_OBJECT_HANDLERS(PT_ABI_OBJECT_HANDLER)
#undef PT_ABI_EG_FIELD
#undef PT_ABI_CG_FIELD
#undef PT_ABI_KNOWN_STRING
#undef PT_ABI_OBJECT_HANDLER

	pt_abi.offset_internal_function_handler = offsetof(zend_internal_function, handler);
	pt_abi.offset_class_entry_info = offsetof(zend_class_entry, info);
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
}

const zend_object_handlers *pt_abi_object_handlers(const pt_abi_handlers &overrides)
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

zend_function_entry *pt_abi_function_entries(const reg::FunctionEntry *entries, size_t count)
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

zend_class_entry *pt_abi_register_internal_class(const char *name, const reg::FunctionEntry *entries, size_t count)
{
	zend_function_entry *functions = pt_abi_function_entries(entries, count);
	zend_class_entry ce;
	INIT_CLASS_ENTRY_EX(ce, name, strlen(name), functions);
	zend_class_entry *registered = zend_register_internal_class(&ce);
	efree(functions);
	return registered;
}

void pt_abi_create_closure(zval *out, zend_function *fn, zend_class_entry *scope, zend_class_entry *calledScope, zend_object *thisObject)
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

zend_object *pt_abi_closure_this(zval *closure)
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
void pt_abi_weakrefs_hash_clean(HashTable *ht)
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

void pt_abi_weakrefs_hash_destroy(HashTable *ht)
{
#if PHP_VERSION_ID >= 80500
	zend_weakrefs_hash_destroy(ht);
#else
	pt_abi_weakrefs_hash_clean(ht);
	zend_hash_destroy(ht);
#endif
}

void pt_abi_call_stack_size_error()
{
#if PHP_VERSION_ID >= 80400
	zend_call_stack_size_error();
#else
	/* static in PHP 8.3; the same message */
	zend_throw_error(nullptr, "Maximum call stack size of %zu bytes (zend.max_allowed_stack_size - zend.reserved_stack_size) reached. Infinite recursion?",
		(size_t) ((uintptr_t) EG(stack_base) - (uintptr_t) EG(stack_limit)));
#endif
}

void pt_abi_try_assign_ref_str(zval *zv, zend_string *str)
{
	ZEND_TRY_ASSIGN_REF_STR(zv, str);
}

void pt_abi_try_assign_ref_true(zval *zv)
{
	ZEND_TRY_ASSIGN_REF_TRUE(zv);
}
