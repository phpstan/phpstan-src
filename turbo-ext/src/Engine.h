/*
 * The foundation of the analysis-engine ports (NodeScopeResolver, the
 * ExprHandler / StmtHandler classes, their processors and helpers): what a
 * handler twin does with closures, ExpressionResultFactory::create(),
 * handler dispatch and its PHP collaborators, spelled natively without a
 * by-name lookup, an extra allocation or an engine frame on the hot path.
 * turbo-ext/CLAUDE.md ("Porting analysis-engine classes") is the guide.
 *
 * - native closures (Engine.cpp): the `fn (...) => ...` / `function (...)
 *   use (...)` values a twin creates, as a PHPStanTurbo\NativeClosure — a C++
 *   body plus the captured values in one allocation;
 * - pt_expression_result_create() (ExpressionResult.cpp): the twin's
 *   `$this->expressionResultFactory->create(...)`;
 * - handler entries (Engine.cpp): `$handler->processExpr(...)` /
 *   `->processStmt(...)` without an engine frame for native handlers;
 * - cached method sites (Engine.cpp): the calls into PHP collaborators that
 *   stay PHP for now, each resolving its zend_function once per class.
 */

#ifndef PHPSTANTURBO_ENGINE_H
#define PHPSTANTURBO_ENGINE_H

#include "support.h"
#include "zv.h"
#include "AnalyserValues.h"

#include <type_traits>
#include <utility>

#include "zend_exceptions.h"
#ifdef ZEND_CHECK_STACK_LIMIT
#include "zend_call_stack.h"
#endif

/* {{{ native closures
 *
 * A PHPStanTurbo\NativeClosure is callable from PHP exactly like the closure
 * it replaces — `$cb(...)`, call_user_func(), `callable` parameters,
 * is_callable() — through its __invoke(...$args), and native code enters its
 * body directly: pt_type_call_callable(), pt_call_fci() and
 * pt_direct_invoke() recognise the holder (and a \Closure over its
 * __invoke()) and call the body without an engine frame.
 *
 * The captures live inline after the object header (one emalloc for the
 * holder and all captured values — a PHP closure allocates the closure and
 * its static-variables table). Captured by value like `use ($x)` / an arrow
 * function: each value is copied (references dereferenced) when the holder
 * is created; a bit set in byReferenceMask keeps that capture as the
 * IS_REFERENCE zval the caller passed — `use (&$x)`. Capture only what the
 * twin's closure captures, `$this` included when the body needs it: a holder
 * holds its captures exactly as long as the closure would, so it creates no
 * reference cycle the twin does not.
 *
 * Where a PHP parameter or property is typed `Closure`, hand it
 * pt_native_closure_to_closure(holder): a real \Closure bound to the holder
 * (one more allocation, still entered directly by native callers). */

/* the body: the captures in creation order (borrowed; a by-reference capture
 * is an IS_REFERENCE zval), the call's arguments as the caller passed them
 * (borrowed — surplus arguments are ignored like a PHP closure ignores them,
 * missing ones are the body's to check), and the return value (initialized
 * to null). An exception left pending propagates. */
typedef void (*pt_native_closure_fn)(zval *captures, uint32_t argc, zval *argv, zval *return_value);

extern zend_class_entry *pt_ce_native_closure;

/* a holder over fn with `count` captures copied from captures[0..count);
 * never UNDEF (only the allocation can fail, and that is fatal) */
zv::Val pt_native_closure_new(pt_native_closure_fn fn, uint32_t count, zval *captures, uint32_t byReferenceMask = 0);

/* the same with the captures spelled as values (zval *, zend_object *,
 * zend_string *, bool, zend_long — zv::Args' kinds; borrowed and copied) */
template <typename... T>
inline zv::Val pt_native_closure(pt_native_closure_fn fn, T &&...captures)
{
	if constexpr (sizeof...(T) == 0) {
		return pt_native_closure_new(fn, 0, NULL);
	} else {
		zv::Args argv{std::forward<T>(captures)...};
		return pt_native_closure_new(fn, (uint32_t) sizeof...(T), argv);
	}
}

/* whether a value is a native closure (over a given body) */
static zend_always_inline bool pt_is_native_closure(zval *value)
{
	return Z_TYPE_P(value) == IS_OBJECT && Z_OBJCE_P(value) == pt_ce_native_closure;
}
bool pt_native_closure_is(zval *value, pt_native_closure_fn fn);

/* the captures of a holder (borrowed) */
zval *pt_native_closure_captures(zend_object *closure);

/* $closure(...$argv) of a holder: *retval as the engine would deliver it
 * (null when the body sets none); false = pending exception, *retval
 * released. The holder is kept alive for the duration of the call, as the
 * engine keeps a called closure. */
[[nodiscard]] bool pt_native_closure_invoke(zend_object *closure, uint32_t argc, zval *argv, zval *retval);

/* the holder's __invoke() (a \Closure over it carries a copy with the
 * engine's trampoline as its handler, so pt_direct_invoke() recognises that
 * one by scope and name) */
zif_handler pt_native_closure_invoke_handler();

/* a real \Closure over the holder's __invoke(), bound to it; UNDEF =
 * pending exception */
zv::Val pt_native_closure_to_closure(zval *closure);

/* }}} */

/* {{{ ExpressionResult creation
 *
 * The twin's `$this->expressionResultFactory->create($scope, beforeScope:
 * ..., expr: ..., ...)`. When the factory is the container-generated
 * implementation (#[GenerateFactory] — every PHPStan container), the native
 * ExpressionResult is constructed directly, with the extensions collection
 * that factory passes and the constructor's own invariants and callable
 * checks; any other factory gets create() through the engine with the
 * optional parameters the call names (`named`) as named arguments. The
 * collection is learned from the factory's first result and cached per
 * factory object (the cache holds the factory, so a new container's factory
 * can never alias a dead one). */

enum : uint32_t
{
	PT_ER_NAMED_CONTAINS_NULLSAFE = 1u << 0,
	PT_ER_NAMED_ISSETABILITY_DESCRIPTOR = 1u << 1,
	PT_ER_NAMED_TRUTHY_SCOPE_OVERRIDE_RESULT = 1u << 2,
	PT_ER_NAMED_FALSEY_SCOPE_OVERRIDE_RESULT = 1u << 3,
	PT_ER_NAMED_CREATE_TYPES_CALLBACK = 1u << 4,
	PT_ER_NAMED_TYPE = 1u << 5,
	PT_ER_NAMED_NATIVE_TYPE = 1u << 6,
	PT_ER_NAMED_ARGS_RESULT = 1u << 7,
	PT_ER_NAMED_VARIABLE_FLOW = 1u << 8,
};

/* create()'s parameters, borrowed: NULL (or an IS_NULL zval) is null, NULL
 * throwPoints / impurePoints are []. The required ones go through the
 * constructor; set an optional one with its with*() setter, which also
 * records that the twin's call passes it. */
struct pt_expression_result_args
{
	zval *scope;
	zval *beforeScope;
	zval *expr;
	bool hasYield;
	bool isAlwaysTerminating;
	zval *throwPoints;
	zval *impurePoints;
	zval *typeCallback;
	zval *specifyTypesCallback;
	bool containsNullsafe = false;
	zval *issetabilityDescriptor = NULL;
	zval *truthyScopeOverrideResult = NULL;
	zval *falseyScopeOverrideResult = NULL;
	zval *createTypesCallback = NULL;
	zval *type = NULL;
	zval *nativeType = NULL;
	zval *argsResult = NULL;
	zval *variableFlow = NULL;
	uint32_t named = 0;

	pt_expression_result_args(zval *scope, zval *beforeScope, zval *expr, bool hasYield, bool isAlwaysTerminating, zval *throwPoints, zval *impurePoints, zval *typeCallback, zval *specifyTypesCallback)
		: scope(scope), beforeScope(beforeScope), expr(expr), hasYield(hasYield), isAlwaysTerminating(isAlwaysTerminating), throwPoints(throwPoints), impurePoints(impurePoints), typeCallback(typeCallback), specifyTypesCallback(specifyTypesCallback)
	{
	}

	pt_expression_result_args &withContainsNullsafe(bool value) { containsNullsafe = value; named |= PT_ER_NAMED_CONTAINS_NULLSAFE; return *this; }
	pt_expression_result_args &withIssetabilityDescriptor(zval *value) { issetabilityDescriptor = value; named |= PT_ER_NAMED_ISSETABILITY_DESCRIPTOR; return *this; }
	pt_expression_result_args &withTruthyScopeOverrideResult(zval *value) { truthyScopeOverrideResult = value; named |= PT_ER_NAMED_TRUTHY_SCOPE_OVERRIDE_RESULT; return *this; }
	pt_expression_result_args &withFalseyScopeOverrideResult(zval *value) { falseyScopeOverrideResult = value; named |= PT_ER_NAMED_FALSEY_SCOPE_OVERRIDE_RESULT; return *this; }
	pt_expression_result_args &withCreateTypesCallback(zval *value) { createTypesCallback = value; named |= PT_ER_NAMED_CREATE_TYPES_CALLBACK; return *this; }
	pt_expression_result_args &withType(zval *value) { type = value; named |= PT_ER_NAMED_TYPE; return *this; }
	pt_expression_result_args &withNativeType(zval *value) { nativeType = value; named |= PT_ER_NAMED_NATIVE_TYPE; return *this; }
	pt_expression_result_args &withArgsResult(zval *value) { argsResult = value; named |= PT_ER_NAMED_ARGS_RESULT; return *this; }
	pt_expression_result_args &withVariableFlow(zval *value) { variableFlow = value; named |= PT_ER_NAMED_VARIABLE_FLOW; return *this; }
};

/* $factory->create(...$args); UNDEF = pending exception */
zv::Val pt_expression_result_create(zval *factory, const pt_expression_result_args &args);

/* releases the per-factory collection cache (RSHUTDOWN) */
void pt_expression_result_rshutdown();

/* $result->getType() / ->getNativeType() for native callers: the native body
 * for a native result, the method otherwise (the result borrowed); UNDEF =
 * pending exception. The getters that only read a slot — getScope(),
 * getBeforeScope(), getExpr(), getThrowPoints(), getImpurePoints(),
 * hasYield(), isAlwaysTerminating() — are the inline borrowed readers of
 * AnalyserValues.h (pt_expression_result_scope(result, hold), ...), which
 * this header includes. */
zv::Val pt_expression_result_get_type(zval *result);
zv::Val pt_expression_result_get_native_type(zval *result);

/* $result->containsNullsafe() && TypeCombinator::containsNull($type): whether
 * a link of a `?->` chain may short-circuit to null before it runs. $type NULL
 * reads $result->getType(), only once the first operand holds - the twin's
 * `&&` does not evaluate it otherwise; false = pending exception */
[[nodiscard]] inline bool pt_expression_result_may_short_circuit(zval *result, zval *type, bool &out)
{
	if (UNEXPECTED(!pt_expression_result_contains_nullsafe(result, out))) return false;
	if (!out) return true;
	if (type != NULL) return pt_type_combinator_contains_null(type, out);
	zv::Val resultType = pt_expression_result_get_type(result);
	if (UNEXPECTED(resultType.isUndef())) return false;
	return pt_type_combinator_contains_null(resultType.raw(), out);
}

/* }}} */

/* {{{ handler entries
 *
 * A native ExprHandler / StmtHandler registers the C++ body of its
 * processExpr() / processStmt() for its class entry at module startup (the
 * shadow() out-pointer: the entry resolves once activation filled it in).
 * pt_expr_handler_process() / pt_stmt_handler_process() call that body
 * directly for an instance of exactly that class; any other handler object
 * gets the method through the engine, its zend_function resolved once per
 * class entry (a table keyed by the class entry, reset per request). The
 * arguments are borrowed and must have the types the interface declares —
 * the direct path does not re-check them.
 *
 * Other public handler methods called across handlers (e.g.
 * VariableHandler::composeResult() from AssignHandler) follow rule 4 of
 * the port rules: a pt_<handler>_<method>(zval *handler, ...) export taking
 * the native body when Z_OBJCE_P(handler) is the native class (handlers are
 * final) and the method by name otherwise. */

typedef zv::Val (*pt_expr_handler_entry)(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context);
typedef zv::Val (*pt_stmt_handler_entry)(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context);

/* module startup: the body for the class entry *ce will hold */
void pt_expr_handler_entry_register(zend_class_entry **ce, pt_expr_handler_entry entry);
void pt_stmt_handler_entry_register(zend_class_entry **ce, pt_stmt_handler_entry entry);

/* $handler->processExpr($nodeScopeResolver, $stmt, $expr, $scope, $storage,
 * $nodeCallback, $context) / $handler->processStmt($nodeScopeResolver,
 * $stmt, $scope, $storage, $nodeCallback, $context); UNDEF = pending
 * exception */
zv::Val pt_expr_handler_process(zval *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context);
zv::Val pt_stmt_handler_process(zval *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context);

/* }}} */

/* {{{ cached method sites
 *
 * A call into a PHP collaborator that is not ported yet, written so the
 * later port switches it to a direct entry in one place: a static
 * pt_method_site per call site resolves the zend_function once per receiver
 * class (per request), then every call is zend_call_known_function(). */

struct pt_method_site
{
	zend_class_entry *ce;
	zend_function *fn;
	uint32_t generation;
};

/* $object->method(...$argv) / Class::method(...$argv) (a class-map class);
 * UNDEF = pending exception */
zv::Val pt_call_method_cached(pt_method_site &site, zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv);
zv::Val pt_call_static_cached(pt_method_site &site, int classIdx, const char *lcname, size_t len, uint32_t argc, zval *argv);

/* the per-request generation the sites compare against (bumped by RINIT) */
extern uint32_t pt_engine_generation;

/* A declared property of a PHP object (an AST node's $name, a value
 * object's field) read through a per-site offset resolved once per class:
 * the property slot (borrowed, not dereferenced), NULL when the class
 * declares no such property. */
struct pt_property_site
{
	zend_class_entry *ce;
	uint32_t offset;
	uint32_t generation;
};

zval *pt_property_cached_resolve(pt_property_site &site, zend_object *object, const char *name, size_t len);

static zend_always_inline zval *pt_property_cached(pt_property_site &site, zend_object *object, const char *name, size_t len)
{
	if (EXPECTED(site.ce == object->ce && site.generation == pt_engine_generation)) return OBJ_PROP(object, site.offset);
	return pt_property_cached_resolve(site, object, name, len);
}

/* request lifecycle of the handler table and the method sites */
void pt_engine_rinit();
void pt_engine_rshutdown();

/* }}} */

/* {{{ finally blocks, node callbacks and deep recursion (the
 * NodeScopeResolver port) */

/* A PHP `finally` block spelled natively: cleanup() runs with a pending
 * exception set aside (so the calls it makes into PHP execute, as they do in
 * a PHP finally block), which is rethrown after it — chained as the previous
 * exception of one the block throws itself. Like the engine, an exit()
 * unwinding the stack skips the block. */
/* the rethrow half of pt_finally() (Engine.cpp) */
void pt_finally_rethrow(zend_object *pending);

template <typename F>
inline void pt_finally(F &&cleanup)
{
	zend_object *pending = EG(exception);
	if (EXPECTED(pending == NULL)) {
		cleanup();
		return;
	}
	if (UNEXPECTED(zend_is_unwind_exit(pending))) return;
	EG(exception) = NULL;
	cleanup();
	pt_finally_rethrow(pending);
}


/* $callback($node, $scope) of a node callback or gatherer frame, its
 * return value discarded: a native closure, the native
 * ClassStatementsGatherer, the native RecordingNodeCallback and any object
 * callable (\Closure, __invoke) are
 * entered without resolving the callable by name; anything else goes
 * through the engine. false = pending exception */
[[nodiscard]] bool pt_engine_call_node_callback(zval *callback, zval *node, zval *scope);

/* ClassStatementsGatherer.cpp — $gatherer($node, $scope) of the native
 * gatherer; handled = false (nothing called) for any other object */
[[nodiscard]] bool pt_class_statements_gatherer_invoke(zend_object *gatherer, zval *node, zval *scope, bool &handled);

/* Native recursion (processExprNode -> a PHP handler -> processExprNode)
 * consumes the C stack where the PHP twin grew only the VM stack. When the C
 * stack left above PHP's own limit (EG(stack_limit)) falls below the margin,
 * the recursion continues on a fresh C stack segment (a zend_fiber_context
 * sharing the VM stack), so a nesting depth the twin analyses stays
 * analysable. */
#define PT_ENGINE_STACK_MARGIN_LIMIT ((size_t) 512 * 1024)
#define PT_ENGINE_FRESH_STACK_SIZE_LIMIT ((size_t) 16 * 1024 * 1024)

static zend_always_inline bool pt_engine_stack_low()
{
#ifdef ZEND_CHECK_STACK_LIMIT
	uintptr_t limit = (uintptr_t) EG(stack_limit);
	if (limit == 0) return false;
	uintptr_t position = (uintptr_t) zend_call_stack_position();
	return position <= limit || position - limit < PT_ENGINE_STACK_MARGIN_LIMIT;
#else
	return false;
#endif
}

/* body(data) on a fresh C stack segment (on the current stack when none can
 * be allocated); a fatal error inside is re-raised on the caller's stack */
void pt_engine_run_on_fresh_stack(void (*body)(void *), void *data);

/* fn() — on a fresh C stack segment when the current one runs low */
template <typename F>
inline void pt_engine_with_stack(F &&fn)
{
	if (EXPECTED(!pt_engine_stack_low())) {
		fn();
		return;
	}
	using Fn = std::remove_reference_t<F>;
	pt_engine_run_on_fresh_stack([](void *data) { (*static_cast<Fn *>(data))(); }, &fn);
}

/* }}} */

/* {{{ node reads the walk makes per statement
 *
 * $node->getAttribute($key) / ->setAttribute($key, $value) /
 * ->getComments() of php-parser's NodeAbstract, read from the node's
 * attributes array when its class inherits the three methods (decided once
 * per class), called through the engine otherwise. The key is a literal,
 * never numeric. UNDEF / false = pending exception. */
zv::Val pt_engine_node_get_attribute(zend_object *node, const char *key, size_t len);
[[nodiscard]] bool pt_engine_node_set_attribute(zend_object *node, const char *key, size_t len, zval *value);
zv::Val pt_engine_node_get_comments(zend_object *node);

/* }}} */

#endif /* PHPSTANTURBO_ENGINE_H */
