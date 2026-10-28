/*
 * The version-specific side of abi.h: the only translation unit reading the
 * engine globals by their compiled-in layout.
 */

#define PHPSTANTURBO_ABI_IMPL
#include "support.h"
#include "reg.h"

#include "Engine.h"
#include "zend_closures.h"
#include "zend_fibers.h"
#include "zend_weakrefs.h"

#if PHP_VERSION_ID < 80300
#ifdef PHP_WIN32
#include <windows.h>
#else
#include <pthread.h>
#include <stdio.h>
#include <sys/resource.h>
#include <unistd.h>
#endif
#ifdef __linux__
#include <inttypes.h>
#include <sys/syscall.h>
#endif
#endif


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
#if PHP_VERSION_ID < 80300
		/* before 8.3, an internal arginfo's class name is a C string under
		 * the plain name bit; the return type is entry 0 */
		if (entries[i].argInfo != NULL) {
			for (uint32_t arg = 0; arg <= entries[i].numArgs; arg++) {
				zend_type *type = &entries[i].argInfo[arg].type;
				if (type->type_mask & _ZEND_TYPE_LITERAL_NAME_BIT) {
					type->type_mask = (type->type_mask & ~_ZEND_TYPE_LITERAL_NAME_BIT) | _ZEND_TYPE_NAME_BIT;
				}
			}
		}
#endif
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

/* {{{ the C stack limit before PHP 8.3
 *
 * 8.3's engine knows the bounds of the C stack (EG(stack_base),
 * EG(stack_limit)); before, this pair stands in for them, determined like
 * zend_call_stack_init() determines them with the default settings, and
 * switched along with the fresh stack segments of abi_run_on_fresh_stack().
 * Plain statics: PHPStan's processes run PHP on one thread. */

#if PHP_VERSION_ID < 80300

/* zend.reserved_stack_size's default in 8.3 (ZEND_ALLOCA_MAX_SIZE + 16 KiB) */
#define PT_ABI_RESERVED_STACK_SIZE_LIMIT ((size_t) 48 * 1024)
/* zend_call_stack_default_size() of 8.3, for when the stack cannot be found */
#define PT_ABI_DEFAULT_STACK_SIZE_LIMIT ((size_t) 2 * 1024 * 1024)

static void *abi_stack_base = nullptr;
static void *abi_stack_limit = nullptr;

/* the high address and the usable size of the current thread's stack */
static bool abi_call_stack_get(void **base, size_t *maxSize)
{
#if defined(__linux__)
	if (getpid() == (pid_t) syscall(SYS_gettid)) {
		/* the main thread: its mapping ends at the base, and it may grow by
		 * RLIMIT_STACK, up to the mapping below it */
		uintptr_t position = (uintptr_t) zend_call_stack_position();
		uintptr_t start, end, previousEnd = 0;
		bool found = false;
		char buffer[4096];
		FILE *maps = fopen("/proc/self/maps", "r");
		if (maps == NULL) return false;
		while (fgets(buffer, sizeof(buffer), maps) && sscanf(buffer, "%" SCNxPTR "-%" SCNxPTR, &start, &end) == 2) {
			if (start <= position && end >= position) {
				found = true;
				break;
			}
			previousEnd = end;
		}
		fclose(maps);
		struct rlimit limit;
		if (!found || getrlimit(RLIMIT_STACK, &limit) != 0 || limit.rlim_cur == RLIM_INFINITY) return false;
		size_t size = limit.rlim_cur;
		if (end - size < previousEnd) {
			size = previousEnd - end;
		}
		*base = (void *) end;
		*maxSize = size;
		return true;
	}
	pthread_attr_t attr;
	void *address;
	size_t size;
	if (pthread_getattr_np(pthread_self(), &attr) != 0) return false;
	bool ok = pthread_attr_getstack(&attr, &address, &size) == 0;
	if (ok) {
		size_t guard = 0;
		/* glibc counts the guard area into the size, musl does not */
#ifdef __GLIBC__
		if (pthread_attr_getguardsize(&attr, &guard) != 0) guard = 0;
#endif
		*base = (int8_t *) address + size;
		*maxSize = size - guard;
	}
	pthread_attr_destroy(&attr);
	return ok;
#elif defined(__APPLE__)
	*base = pthread_get_stackaddr_np(pthread_self());
	*maxSize = pthread_get_stacksize_np(pthread_self());
	return true;
#elif defined(PHP_WIN32)
	/* the reserved region less its guard pages and the last uncommitted
	 * page, as 8.3 computes it. 8.3 reads the limits with
	 * GetCurrentThreadStackLimits, which 8.2's _WIN32_WINNT does not declare;
	 * the stack's allocation base and the TEB's StackBase are the same limits */
	MEMORY_BASIC_INFORMATION stack = {0}, guard = {0}, uncommitted = {0};
	if (VirtualQuery(&stack, &stack, sizeof(stack)) < sizeof(stack)) return false;
	ULONG_PTR lowLimit = (ULONG_PTR) stack.AllocationBase;
	ULONG_PTR highLimit = (ULONG_PTR) ((NT_TIB *) NtCurrentTeb())->StackBase;
	if (VirtualQuery((void *) lowLimit, &uncommitted, sizeof(uncommitted)) < sizeof(uncommitted)) return false;
	if (VirtualQuery((int8_t *) uncommitted.BaseAddress + uncommitted.RegionSize, &guard, sizeof(guard)) < sizeof(guard)) return false;
	size_t size = (size_t) (highLimit - lowLimit);
	size_t pageSize = zend_get_page_size();
	if (size <= guard.RegionSize + pageSize) return false;
	*base = (void *) highLimit;
	*maxSize = size - guard.RegionSize - pageSize;
	return true;
#else
	(void) base;
	(void) maxSize;
	return false;
#endif
}

static void *abi_call_stack_limit(void *base, size_t size, size_t reserved)
{
	if (UNEXPECTED(size > (uintptr_t) base)) {
		return nullptr;
	}
	return (int8_t *) base - size + reserved;
}

static void abi_call_stack_init()
{
	void *base;
	size_t size;
	if (!abi_call_stack_get(&base, &size)) {
		/* as 8.3 falls back: from here, which is not the actual base */
		base = zend_call_stack_position();
		size = PT_ABI_DEFAULT_STACK_SIZE_LIMIT - 32 * 1024;
	}
	abi_stack_base = base;
	abi_stack_limit = abi_call_stack_limit(base, size, PT_ABI_RESERVED_STACK_SIZE_LIMIT);
}

/* zend_fiber_stack is private to zend_fibers.c; 8.2's begins with these */
struct AbiFiberStack
{
	void *pointer;
	size_t size;
};

#endif

/* }}} */

static void abi_call_stack_size_error()
{
#if PHP_VERSION_ID >= 80400
	zend_call_stack_size_error();
#elif PHP_VERSION_ID >= 80300
	/* static in PHP 8.3; the same message */
	zend_throw_error(nullptr, "Maximum call stack size of %zu bytes (zend.max_allowed_stack_size - zend.reserved_stack_size) reached. Infinite recursion?",
		(size_t) ((uintptr_t) EG(stack_base) - (uintptr_t) EG(stack_limit)));
#else
	/* 8.3's message, without the settings 8.2 does not have */
	zend_throw_error(nullptr, "Maximum call stack size of %zu bytes reached. Infinite recursion?",
		(size_t) ((uintptr_t) abi_stack_base - (uintptr_t) abi_stack_limit));
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

static void abi_try_assign_ref_copy(zval *zv, zval *value)
{
	ZEND_TRY_ASSIGN_REF_COPY(zv, value);
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
#if PHP_VERSION_ID >= 80300
	Z_EXPECTED_NUMBER_OR_STRING,
	Z_EXPECTED_NUMBER_OR_STRING_OR_NULL,
#else
	Z_EXPECTED_LONG, /* not produced before 8.3 */
	Z_EXPECTED_LONG, /* not produced before 8.3 */
#endif
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
	/* the zval was not const up to 8.2; neither function writes it */
	return zend_parse_arg_bool_slow((zval *) arg, dest, num);
#endif
}

static bool abi_parse_arg_double_slow(const zval *arg, double *dest, uint32_t num)
{
#if PHP_VERSION_ID >= 80600
	*dest = zend_parse_arg_double_slow(arg, num);
	return !zend_isnan(*dest);
#else
	return zend_parse_arg_double_slow((zval *) arg, dest, num);
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

/* {{{ continuing a call on a fresh C stack (pt_engine_with_stack()) */

namespace {

/* one call continued on a fresh stack: the body, and the VM state it left
 * behind — zend_fiber_switch_context() restores the caller's state from
 * before the switch, which the body may legitimately have changed */
struct FreshStackCall
{
	void (*body)(void *);
	void *data;
	bool bailout;
	zend_vm_stack vmStack;
	zval *vmStackTop;
	zval *vmStackEnd;
	size_t vmStackPageSize;
	int errorReporting;
};

/* the call the coroutine starting next runs (read once, at its start) */
FreshStackCall *pt_fresh_stack_call = nullptr;

/* identifies the contexts in fiber observers */
char pt_fresh_stack_kind;

ZEND_STACK_ALIGNED void freshStackCoroutine(zend_fiber_transfer *transfer)
{
	(void) transfer; /* already addresses the caller, which is where it returns */
	FreshStackCall *call = pt_fresh_stack_call;
	zend_fiber_context *context = EG(current_fiber_context);
#if PHP_VERSION_ID >= 80300
	EG(stack_base) = zend_fiber_stack_base(context->stack);
	EG(stack_limit) = zend_fiber_stack_limit(context->stack);
#else
	/* zend_fiber_stack_base() / _limit() of 8.3 */
	const AbiFiberStack *stack = (const AbiFiberStack *) context->stack;
	abi_stack_base = (int8_t *) stack->pointer + stack->size;
#ifdef __APPLE__
	/* Apple clang's ___chkstk_darwin probes twice the frame size away on
	 * an alternative stack (8.3 reserves twice as much there) */
	abi_stack_limit = (int8_t *) stack->pointer + 2 * PT_ABI_RESERVED_STACK_SIZE_LIMIT;
#else
	abi_stack_limit = (int8_t *) stack->pointer + PT_ABI_RESERVED_STACK_SIZE_LIMIT;
#endif
#endif
	zend_try {
		call->body(call->data);
	} zend_catch {
		call->bailout = true;
	} zend_end_try();
	call->vmStack = EG(vm_stack);
	call->vmStackTop = EG(vm_stack_top);
	call->vmStackEnd = EG(vm_stack_end);
	call->vmStackPageSize = EG(vm_stack_page_size);
	call->errorReporting = EG(error_reporting);
}

} // namespace

static void abi_run_on_fresh_stack(void (*body)(void *), void *data)
{
	zend_fiber_context context;
	memset(&context, 0, sizeof(context));
#if PHP_VERSION_ID >= 80300
	bool initialized = zend_fiber_init_context(&context, &pt_fresh_stack_kind, freshStackCoroutine, PT_ENGINE_FRESH_STACK_SIZE_LIMIT) == SUCCESS;
#else
	/* returned bool up to 8.2 */
	bool initialized = zend_fiber_init_context(&context, &pt_fresh_stack_kind, freshStackCoroutine, PT_ENGINE_FRESH_STACK_SIZE_LIMIT);
#endif
	if (UNEXPECTED(!initialized)) {
		/* no stack to be had: continue here, where the engine's own stack
		 * limit decides */
		if (EG(exception) != NULL) {
			zend_clear_exception();
		}
		body(data);
		return;
	}

	FreshStackCall call = { body, data, false, NULL, NULL, NULL, 0, 0 };
	FreshStackCall *previous = pt_fresh_stack_call;
	pt_fresh_stack_call = &call;
	zend_fiber_transfer transfer;
	transfer.context = &context;
	transfer.flags = 0;
	ZVAL_NULL(&transfer.value);
#if PHP_VERSION_ID < 80300
	/* 8.3's switch restores the engine's pair, this one is restored here */
	void *stackBase = abi_stack_base;
	void *stackLimit = abi_stack_limit;
#endif
	/* returns once the body finished; the dead context is destroyed then */
	zend_fiber_switch_context(&transfer);
	pt_fresh_stack_call = previous;
#if PHP_VERSION_ID < 80300
	abi_stack_base = stackBase;
	abi_stack_limit = stackLimit;
#endif

	EG(vm_stack) = call.vmStack;
	EG(vm_stack_top) = call.vmStackTop;
	EG(vm_stack_end) = call.vmStackEnd;
	EG(vm_stack_page_size) = call.vmStackPageSize;
	EG(error_reporting) = call.errorReporting;
	if (UNEXPECTED(call.bailout)) {
		zend_bailout();
	}
}

/* }}} */

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
#if PHP_VERSION_ID >= 80300
	pt_abi.eg_stack_base = &EG(stack_base);
	pt_abi.eg_stack_limit = &EG(stack_limit);
#else
	abi_call_stack_init();
	pt_abi.eg_stack_base = &abi_stack_base;
	pt_abi.eg_stack_limit = &abi_stack_limit;
#endif

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
	pt_abi.try_assign_ref_copy = abi_try_assign_ref_copy;
	pt_abi.wrong_parameter_error = abi_wrong_parameter_error;
	pt_abi.parse_arg_bool_slow = abi_parse_arg_bool_slow;
	pt_abi.parse_arg_double_slow = abi_parse_arg_double_slow;
	pt_abi.parse_arg_str_slow = abi_parse_arg_str_slow;
	pt_abi.call_known_function = abi_call_known_function;
	pt_abi.stream_free = abi_stream_free;
	pt_abi.stream_read = abi_stream_read;
	pt_abi.run_on_fresh_stack = abi_run_on_fresh_stack;
	pt_abi.is_true = [](const zval *op) -> bool { return zend_is_true((zval *) op); };
#if PHP_VERSION_ID >= 80300
	pt_abi.zval_value_name = zend_zval_value_name;
#else
	pt_abi.zval_value_name = zend_zval_type_name;
#endif
}
