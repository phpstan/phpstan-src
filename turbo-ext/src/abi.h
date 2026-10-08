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

struct pt_abi_globals
{
#define PT_ABI_EG_FIELD(field) decltype(((zend_executor_globals *) nullptr)->field) *eg_##field;
#define PT_ABI_CG_FIELD(field) decltype(((zend_compiler_globals *) nullptr)->field) *cg_##field;
#define PT_ABI_KNOWN_STRING(id) zend_string *known_##id;
#define PT_ABI_OBJECT_HANDLER(member) size_t handler_offset_##member;
	PT_ABI_EG_FIELDS(PT_ABI_EG_FIELD)
	PT_ABI_CG_FIELDS(PT_ABI_CG_FIELD)
	PT_ABI_KNOWN_STRINGS(PT_ABI_KNOWN_STRING)
	PT_ABI_OBJECT_HANDLERS(PT_ABI_OBJECT_HANDLER)
#undef PT_ABI_EG_FIELD
#undef PT_ABI_CG_FIELD
#undef PT_ABI_KNOWN_STRING
#undef PT_ABI_OBJECT_HANDLER

	/* member offsets that moved between minors */
	size_t offset_internal_function_handler; /* 8.4 inserted frameless_function_infos and doc_comment before it */
	size_t offset_class_entry_info;          /* 8.4 grew zend_class_entry before it */

	/* constants whose value differs between minors */
	uint32_t php_version_id;
	uint32_t object_lazy_flags; /* IS_OBJ_LAZY_* (8.4+), 0 before */
	uint32_t is_reference_ex;   /* 8.5 made references collectable */
	uint32_t acc_use_guards;    /* (1 << 11) up to 8.4, (1 << 30) from 8.5 */

	/* engine inline functions whose behaviour differs between minors: the
	 * running PHP's own, compiled by Abi.cpp */
	zend_long (*dval_to_lval)(double d); /* 8.5 warns out of range */
};

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

/* a persistent copy of the standard handlers with the given overrides (the
 * null members keep the standard handler) */
const zend_object_handlers *pt_abi_object_handlers(const pt_abi_handlers &overrides);

/* engine API that differs between minors, implemented once per minor */
void pt_abi_create_closure(zval *out, zend_function *fn, zend_class_entry *scope, zend_class_entry *calledScope, zend_object *thisObject);
zend_object *pt_abi_closure_this(zval *closure);
void pt_abi_weakrefs_hash_clean(HashTable *ht);
void pt_abi_weakrefs_hash_destroy(HashTable *ht);
void pt_abi_call_stack_size_error();
void pt_abi_try_assign_ref_str(zval *zv, zend_string *str);
void pt_abi_try_assign_ref_true(zval *zv);

/* `then` from the given PHP_VERSION_ID on, `otherwise` before it — the
 * runtime form of an #if PHP_VERSION_ID gate */
#define PT_ABI_SINCE(version, then, otherwise) (pt_abi.php_version_id >= (version) ? (then) : (otherwise))

extern pt_abi_globals pt_abi;

/* fills pt_abi from the running engine; first thing MINIT (and, in ZTS
 * builds, RINIT) does */
void pt_abi_init();

namespace reg {
struct FunctionEntry;
}

/* the engine's function table for the builder's entries: an emalloc()ed
 * array terminated by the sentinel the engine expects; the engine copies the
 * entries and keeps referencing their arg_info */
zend_function_entry *pt_abi_function_entries(const reg::FunctionEntry *entries, size_t count);

/* zend_register_internal_class() for a class whose methods are the entries */
zend_class_entry *pt_abi_register_internal_class(const char *name, const reg::FunctionEntry *entries, size_t count);

#ifndef PHPSTANTURBO_ABI_IMPL
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
static zend_always_inline zval *pt_abi_default_properties_table(zend_class_entry *ce)
{
	if ((ce->ce_flags & ZEND_ACC_HAS_AST_PROPERTIES) && ZEND_MAP_PTR(ce->mutable_data)) {
		zend_class_mutable_data *mutable_data = (zend_class_mutable_data *) ZEND_MAP_PTR_GET_IMM(ce->mutable_data);
		return mutable_data->default_properties_table;
	}
	return ce->default_properties_table;
}
#undef CE_DEFAULT_PROPERTIES_TABLE
#define CE_DEFAULT_PROPERTIES_TABLE(ce) pt_abi_default_properties_table(ce)

static zend_always_inline void *pt_abi_object_alloc(size_t obj_size, zend_class_entry *ce)
{
	size_t properties = sizeof(zval) * (ce->default_properties_count - ((ce->ce_flags & ZEND_ACC_USE_GUARDS) ? 0 : 1));
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

/* fn->internal_function.handler */
#define PT_INTERNAL_HANDLER(fn) (*(zif_handler *) ((const char *) (fn) + pt_abi.offset_internal_function_handler))
/* ce->info */
#define PT_CE_INFO(ce) (*(decltype(((zend_class_entry *) nullptr)->info) *) ((const char *) (ce) + pt_abi.offset_class_entry_info))

/* zend_object_is_lazy(): zend_object.extra_flags is 8.4's name for the
 * padding after `handle`, which 8.3 leaves unset — the mask is 0 there */
static zend_always_inline bool pt_abi_object_is_lazy(const zend_object *obj)
{
	uint32_t extraFlags;
	memcpy(&extraFlags, (const char *) obj + offsetof(zend_object, handle) + sizeof(uint32_t), sizeof(extraFlags));
	return (extraFlags & pt_abi.object_lazy_flags) != 0;
}

/* zend_is_true() returned int up to 8.4 and bool since; declared bool —
 * the int it returned was always 0 or 1, so reading the low byte is exact */
#if defined(__GNUC__)
#define PT_ABI_SYMBOL_STR2(prefix, name) #prefix #name
#define PT_ABI_SYMBOL_STR(prefix, name) PT_ABI_SYMBOL_STR2(prefix, name)
extern "C" ZEND_API bool ZEND_FASTCALL pt_abi_zend_is_true(const zval *op) __asm__(PT_ABI_SYMBOL_STR(__USER_LABEL_PREFIX__, zend_is_true));
#define zend_is_true(op) pt_abi_zend_is_true(op)
#endif
#endif

#endif
