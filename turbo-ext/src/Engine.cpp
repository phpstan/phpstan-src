/*
 * The foundation of the analysis-engine ports — see Engine.h: the
 * PHPStanTurbo\NativeClosure holder, the handler-entry table and the cached
 * method sites. (pt_expression_result_create() lives next to the native
 * ExpressionResult constructor, in ExpressionResult.cpp.)
 */

#include "support.h"
#include "Engine.h"
#include "TypeTraits.h"
#include "reg.h"

#include <cstring>

/* {{{ native closures */

zend_class_entry *pt_ce_native_closure = nullptr;

namespace {

/* the holder: the body and the capture count in front of the object header,
 * the captures inline after it (properties_table is the object's trailing
 * storage; the class declares no properties, so nothing else lives there) */
struct NativeClosureObject
{
	pt_native_closure_fn fn;
	uint32_t count;
	zend_object std;
};

zend_object_handlers pt_native_closure_handlers;

/* the class's __invoke() (a zend_class_entry has no slot for it) */
zend_function *pt_native_closure_invoke_fn = nullptr;

inline NativeClosureObject *closureOf(zend_object *object)
{
	return (NativeClosureObject *) ((char *) object - offsetof(NativeClosureObject, std));
}

inline zval *capturesOf(zend_object *object)
{
	return object->properties_table;
}

/* an uninitialized holder with room for `count` captures */
NativeClosureObject *allocateClosure(zend_class_entry *ce, pt_native_closure_fn fn, uint32_t count)
{
	/* sizeof(zend_object) already holds one zval of properties_table */
	size_t size = offsetof(NativeClosureObject, std) + sizeof(zend_object) + (count > 0 ? count - 1 : 0) * sizeof(zval);
	NativeClosureObject *closure = (NativeClosureObject *) emalloc(size);
	closure->fn = fn;
	closure->count = count;
	zend_object_std_init(&closure->std, ce);
	closure->std.handlers = &pt_native_closure_handlers;
	return closure;
}

zend_object *createClosureObject(zend_class_entry *ce)
{
	/* `new PHPStanTurbo\NativeClosure()` from userland: a holder without a
	 * body, whose invocation throws */
	return &allocateClosure(ce, NULL, 0)->std;
}

void freeClosureObject(zend_object *object)
{
	NativeClosureObject *closure = closureOf(object);
	zval *captures = capturesOf(object);
	for (uint32_t i = 0; i < closure->count; i++) {
		zval_ptr_dtor(&captures[i]);
	}
	closure->count = 0;
	zend_object_std_dtor(object);
}

HashTable *closureGc(zend_object *object, zval **table, int *n)
{
	*table = capturesOf(object);
	*n = (int) closureOf(object)->count;
	return object->properties;
}

zend_object *cloneClosureObject(zend_object *old)
{
	NativeClosureObject *source = closureOf(old);
	NativeClosureObject *clone = allocateClosure(old->ce, source->fn, source->count);
	zval *from = capturesOf(old);
	zval *to = capturesOf(&clone->std);
	for (uint32_t i = 0; i < source->count; i++) {
		ZVAL_COPY(&to[i], &from[i]);
	}
	return &clone->std;
}

/* `$closure(...)` / is_callable(): __invoke() without the function-table
 * lookup zend_std_get_closure() makes per call */
zend_result getClosure(zend_object *object, zend_class_entry **cePtr, zend_function **fnPtr, zend_object **objectPtr, bool checkOnly)
{
	(void) checkOnly;
	*fnPtr = pt_native_closure_invoke_fn;
	*cePtr = object->ce;
	if (objectPtr != NULL) {
		*objectPtr = object;
	}
	return SUCCESS;
}

/* `==` like two (non-fake) PHP closures (zend_closure_compare()): only the
 * very same object is equal — the engine answers that before asking */
int compareClosures(zval *a, zval *b)
{
	ZEND_COMPARE_OBJECTS_FALLBACK(a, b);
	return ZEND_UNCOMPARABLE;
}

void ZEND_FASTCALL invokeNativeClosure(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *args;
	uint32_t argc;
	ZEND_PARSE_PARAMETERS_START(0, -1)
		Z_PARAM_VARIADIC('*', args, argc)
	ZEND_PARSE_PARAMETERS_END();
	if (UNEXPECTED(Z_TYPE_P(ZEND_THIS) != IS_OBJECT || Z_OBJCE_P(ZEND_THIS) != pt_ce_native_closure)) {
		zend_throw_error(NULL, "phpstan_turbo: native closure called without its holder");
		RETURN_THROWS();
	}
	zval ret;
	if (UNEXPECTED(!pt_native_closure_invoke(Z_OBJ_P(ZEND_THIS), argc, args, &ret))) RETURN_THROWS();
	RETURN_COPY_VALUE(&ret);
}

} // namespace

zv::Val pt_native_closure_new(pt_native_closure_fn fn, uint32_t count, zval *captures, uint32_t byReferenceMask)
{
	NativeClosureObject *closure = allocateClosure(pt_ce_native_closure, fn, count);
	zval *to = capturesOf(&closure->std);
	for (uint32_t i = 0; i < count; i++) {
		if (i < 32 && (byReferenceMask & (1u << i)) != 0 && Z_ISREF(captures[i])) {
			ZVAL_COPY(&to[i], &captures[i]);
		} else {
			ZVAL_COPY_DEREF(&to[i], &captures[i]);
		}
	}
	zval value;
	ZVAL_OBJ(&value, &closure->std);
	return zv::Val::adopt(value);
}

bool pt_native_closure_is(zval *value, pt_native_closure_fn fn)
{
	return pt_is_native_closure(value) && closureOf(Z_OBJ_P(value))->fn == fn;
}

zval *pt_native_closure_captures(zend_object *closure)
{
	return capturesOf(closure);
}

bool pt_native_closure_invoke(zend_object *closure, uint32_t argc, zval *argv, zval *retval)
{
	pt_native_closure_fn fn = closureOf(closure)->fn;
	if (UNEXPECTED(fn == NULL)) {
		zend_throw_error(NULL, "phpstan_turbo: native closure without a body");
		ZVAL_UNDEF(retval);
		return false;
	}
	/* the engine keeps a called closure alive until the call returns — a
	 * body releasing the last outside reference (a memo dropping its
	 * callback) must not free the captures it is reading */
	GC_ADDREF(closure);
	ZVAL_NULL(retval);
	fn(capturesOf(closure), argc, argv, retval);
	OBJ_RELEASE(closure);
	if (UNEXPECTED(EG(exception))) {
		zval_ptr_dtor(retval);
		ZVAL_UNDEF(retval);
		return false;
	}
	return true;
}

zif_handler pt_native_closure_invoke_handler()
{
	return invokeNativeClosure;
}

zv::Val pt_native_closure_to_closure(zval *closure)
{
	return pt_type_closure_over(pt_native_closure_invoke_fn, pt_ce_native_closure, Z_OBJ_P(closure));
}

void pt_register_native_closure()
{
	memcpy(&pt_native_closure_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
	pt_native_closure_handlers.offset = offsetof(NativeClosureObject, std);
	pt_native_closure_handlers.free_obj = freeClosureObject;
	pt_native_closure_handlers.get_gc = closureGc;
	pt_native_closure_handlers.clone_obj = cloneClosureObject;
	pt_native_closure_handlers.get_closure = getClosure;
	pt_native_closure_handlers.compare = compareClosures;

	/* registered under a builder name other than `cls` on purpose — the
	 * side-by-side parity scan pairs `cls.method(...)` lines with the
	 * twins' methods, and __invoke() has none */
	reg::Class holder("PHPStanTurbo\\NativeClosure");
	holder.method("__invoke", reg::Public, 0, { reg::Arg{ "args", reg::detail::flagBits(false, true), nullptr } }, invokeNativeClosure);
	pt_ce_native_closure = holder.register_();
	pt_ce_native_closure->ce_flags |= ZEND_ACC_FINAL | ZEND_ACC_NO_DYNAMIC_PROPERTIES | ZEND_ACC_NOT_SERIALIZABLE;
	pt_ce_native_closure->create_object = createClosureObject;
	pt_native_closure_invoke_fn = (zend_function *) zend_hash_str_find_ptr(&pt_ce_native_closure->function_table, PT_LC("__invoke"));
	ZEND_ASSERT(pt_native_closure_invoke_fn != NULL);
}

/* }}} */

/* {{{ handler entries */

namespace {

struct HandlerEntryRegistration
{
	zend_class_entry **ce;
	pt_expr_handler_entry expr;
	pt_stmt_handler_entry stmt;
};

/* the registrations (module startup; at most one per native handler class) */
#define PT_HANDLER_REGISTRATIONS_LIMIT 256
HandlerEntryRegistration pt_handler_registrations[PT_HANDLER_REGISTRATIONS_LIMIT];
uint32_t pt_handler_registration_count = 0;

HandlerEntryRegistration &registrationFor(zend_class_entry **ce)
{
	for (uint32_t i = 0; i < pt_handler_registration_count; i++) {
		if (pt_handler_registrations[i].ce == ce) return pt_handler_registrations[i];
	}
	if (UNEXPECTED(pt_handler_registration_count >= PT_HANDLER_REGISTRATIONS_LIMIT)) {
		zend_error_noreturn(E_CORE_ERROR, "phpstan_turbo: too many native handler entries");
	}
	HandlerEntryRegistration &registration = pt_handler_registrations[pt_handler_registration_count++];
	registration = { ce, NULL, NULL };
	return registration;
}

/* the dispatch of one handler class: its native bodies (NULL = none) and
 * the engine functions of processExpr() / processStmt() (NULL = not
 * resolved yet or not declared) */
struct HandlerDispatch
{
	zend_class_entry *ce;
	pt_expr_handler_entry expr;
	pt_stmt_handler_entry stmt;
	zend_function *processExpr;
	zend_function *processStmt;
};

/* the class-entry keyed table: 2^PT_HANDLER_TABLE_BITS slots, linear
 * probing; a full table falls back to an uncached lookup */
#define PT_HANDLER_TABLE_BITS 9
#define PT_HANDLER_TABLE_SIZE (1u << PT_HANDLER_TABLE_BITS)
#define PT_HANDLER_TABLE_FILL_LIMIT (PT_HANDLER_TABLE_SIZE / 2)
HandlerDispatch pt_handler_table[PT_HANDLER_TABLE_SIZE];
uint32_t pt_handler_table_fill = 0;

inline size_t handlerHash(const zend_class_entry *ce)
{
	uintptr_t h = ((uintptr_t) ce >> 4) * (uintptr_t) 0x9E3779B97F4A7C15ull;
	return (size_t) (h >> (sizeof(uintptr_t) * 8 - PT_HANDLER_TABLE_BITS));
}

/* the dispatch of a class, created on first sight; `scratch` holds it when
 * the table is full */
HandlerDispatch *dispatchOf(zend_class_entry *ce, HandlerDispatch &scratch)
{
	size_t i = handlerHash(ce);
	for (;;) {
		HandlerDispatch &slot = pt_handler_table[i];
		if (EXPECTED(slot.ce == ce)) return &slot;
		if (slot.ce == NULL) break;
		i = (i + 1) & (PT_HANDLER_TABLE_SIZE - 1);
	}

	HandlerDispatch *dispatch = pt_handler_table_fill < PT_HANDLER_TABLE_FILL_LIMIT ? &pt_handler_table[i] : &scratch;
	*dispatch = { ce, NULL, NULL, NULL, NULL };
	for (uint32_t r = 0; r < pt_handler_registration_count; r++) {
		const HandlerEntryRegistration &registration = pt_handler_registrations[r];
		if (*registration.ce == ce) {
			dispatch->expr = registration.expr;
			dispatch->stmt = registration.stmt;
			break;
		}
	}
	if (dispatch != &scratch) {
		pt_handler_table_fill++;
	}
	return dispatch;
}

zend_function *resolveMethod(zend_class_entry *ce, zend_function *&cached, const char *lcname, size_t len)
{
	if (EXPECTED(cached != NULL)) return cached;
	cached = pt_find_method(ce, lcname, len);
	return cached;
}

zv::Val callKnown(zend_function *fn, zend_object *object, uint32_t argc, zval *argv)
{
	zval ret;
	zend_call_known_function(fn, object, object->ce, &ret, argc, argv, NULL);
	if (UNEXPECTED(EG(exception))) {
		zval_ptr_dtor(&ret);
		return zv::Val();
	}
	return zv::Val::adopt(ret);
}

} // namespace

void pt_expr_handler_entry_register(zend_class_entry **ce, pt_expr_handler_entry entry)
{
	registrationFor(ce).expr = entry;
}

void pt_stmt_handler_entry_register(zend_class_entry **ce, pt_stmt_handler_entry entry)
{
	registrationFor(ce).stmt = entry;
}

zv::Val pt_expr_handler_process(zval *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
{
	zend_object *object = Z_OBJ_P(handler);
	HandlerDispatch scratch;
	HandlerDispatch *dispatch = dispatchOf(object->ce, scratch);
	if (EXPECTED(dispatch->expr != NULL)) return dispatch->expr(object, nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);

	zend_function *fn = resolveMethod(object->ce, dispatch->processExpr, PT_LC("processexpr"));
	if (UNEXPECTED(fn == NULL)) return zv::Val();
	zv::Args argv{nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context};
	return callKnown(fn, object, 7, argv);
}

zv::Val pt_stmt_handler_process(zval *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
{
	zend_object *object = Z_OBJ_P(handler);
	HandlerDispatch scratch;
	HandlerDispatch *dispatch = dispatchOf(object->ce, scratch);
	if (EXPECTED(dispatch->stmt != NULL)) return dispatch->stmt(object, nodeScopeResolver, stmt, scope, storage, nodeCallback, context);

	zend_function *fn = resolveMethod(object->ce, dispatch->processStmt, PT_LC("processstmt"));
	if (UNEXPECTED(fn == NULL)) return zv::Val();
	zv::Args argv{nodeScopeResolver, stmt, scope, storage, nodeCallback, context};
	return callKnown(fn, object, 6, argv);
}

/* }}} */

/* {{{ cached method sites */

/* bumped per request: a site resolved in an earlier request re-resolves
 * (class entries do not survive a request without opcache) */
uint32_t pt_engine_generation = 1;

zval *pt_property_cached_resolve(pt_property_site &site, zend_object *object, const char *name, size_t len)
{
	int32_t offset = pt_instance_prop_offset(object->ce, name, len);
	if (UNEXPECTED(offset < 0)) return NULL;
	site = { object->ce, (uint32_t) offset, pt_engine_generation };
	return OBJ_PROP(object, site.offset);
}

zv::Val pt_call_method_cached(pt_method_site &site, zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	if (UNEXPECTED(site.ce != object->ce || site.generation != pt_engine_generation || site.fn == NULL)) {
		zend_function *fn = pt_find_method(object->ce, lcname, len);
		if (UNEXPECTED(fn == NULL)) return zv::Val();
		site = { object->ce, fn, pt_engine_generation };
	}
	return callKnown(site.fn, object, argc, argv);
}

zv::Val pt_call_static_cached(pt_method_site &site, int classIdx, const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	zend_class_entry *ce = pt_class(classIdx);
	if (UNEXPECTED(ce == NULL)) return zv::Val();
	if (UNEXPECTED(site.ce != ce || site.generation != pt_engine_generation || site.fn == NULL)) {
		zend_function *fn = pt_find_method(ce, lcname, len);
		if (UNEXPECTED(fn == NULL)) return zv::Val();
		site = { ce, fn, pt_engine_generation };
	}
	zval ret;
	zend_call_known_function(site.fn, NULL, ce, &ret, argc, argv, NULL);
	if (UNEXPECTED(EG(exception))) {
		zval_ptr_dtor(&ret);
		return zv::Val();
	}
	return zv::Val::adopt(ret);
}

void pt_engine_rinit()
{
	pt_engine_generation++;
	memset(pt_handler_table, 0, sizeof(pt_handler_table));
	pt_handler_table_fill = 0;
}

void pt_engine_rshutdown()
{
	pt_expression_result_rshutdown();
}

/* }}} */
