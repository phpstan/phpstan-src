/*
 * phpstan_turbo — shared native support layer.
 *
 * Infrastructure used by all native classes: the configurable class map,
 * TrinaryLogic singletons, callback helpers into userland (TypeCombinator,
 * Type::equals, describe), the per-class node info cache (subnode property
 * offsets, attribute table offset), native node attributes, expression keys,
 * the recursive findFirst walker and ExpressionTypeHolder helpers.
 *
 * The function names deliberately match the proven C implementation this
 * extension was ported from (turbo-ext/native at the time of the port) so the
 * two stay diffable.
 */

#ifndef PHPSTANTURBO_SUPPORT_H
#define PHPSTANTURBO_SUPPORT_H

/* The Zend engine headers are not warning-clean under the strict flags this
 * extension is built with in CI; exempt them without relaxing the flags for
 * our own code. The -Wpragmas / -Wunknown-warning-option ignores make the
 * compiler-specific entries below portable across gcc/clang. */
#pragma GCC diagnostic push
#pragma GCC diagnostic ignored "-Wpragmas"
#pragma GCC diagnostic ignored "-Wunknown-warning-option"
#pragma GCC diagnostic ignored "-Wunused-parameter"
#pragma GCC diagnostic ignored "-Wignored-qualifiers"
#pragma GCC diagnostic ignored "-Wdeprecated-declarations"
/* zend_vm_opcodes.h uses the preserve_none calling convention, which not
 * every gcc/libc target supports — gcc then warns the attribute is ignored */
#pragma GCC diagnostic ignored "-Wattributes"

extern "C" {
#include "php.h"
#include "zend_exceptions.h"
#include "zend_interfaces.h"
#include "zend_smart_str.h"
}

#pragma GCC diagnostic pop

/* {{{ configurable class references */

typedef struct _pt_class_ref {
	const char *key;          /* key in the Runtime::configure() map */
	const char *default_name; /* fallback FQCN when not configured */
	zend_string *configured;  /* name set via configure(), owned */
	zend_class_entry *ce;     /* resolved entry, per-request cache */
} pt_class_ref;

enum {
	PT_CLASS_TYPE_COMBINATOR = 0,
	PT_CLASS_BOOLEAN_TYPE,
	PT_CLASS_CONSTANT_BOOLEAN_TYPE,
	PT_CLASS_SHOULD_NOT_HAPPEN,
	PT_CLASS_VERBOSITY_LEVEL,
	PT_CLASS_VARIABLE,
	PT_CLASS_FUNC_CALL,
	PT_CLASS_VIRTUAL_NODE,
	PT_CLASS_NODE,
	PT_CLASS_NAME,
	PT_CLASS_EXPR,
	PT_CLASS_PROPERTY_FETCH,
	PT_CLASS_INTERTWINED_VAR,
	PT_CLASS_ARRAY_DIM_FETCH,
	PT_CLASS_METHOD_CALL,
	PT_CLASS_FUNCTION_LIKE,
	PT_CLASS_CALL_LIKE,
	PT_CLASS_STATIC_CALL,
	PT_CLASS_NEW,
	PT_CLASS_CLASS_STMT,
	PT_CLASS_VARIADIC_PLACEHOLDER,
	PT_CLASS_ERROR_TYPE,
	PT_CLASS_SCALAR,
	PT_CLASS_ARRAY_EXPR,
	PT_CLASS_UNARY_MINUS,
	PT_CLASS_YIELD,
	PT_CLASS_YIELD_FROM,
	PT_CLASS_STMT,
	PT_CLASS_NODE_VISITOR_ABSTRACT,
	PT_CLASS_CLOSURE_EXPR,
	PT_CLASS_ARROW_FUNCTION,
	PT_CLASS_TYPE,
	PT_CLASS_RECURSION_GUARD,
	/* classes the extension instantiates (their PHP twins are themselves
	 * shadowed, hence no default name): configured to the stub subclasses
	 * so created objects satisfy the original PHPStan type hints */
	PT_CLASS_TRINARY,
	PT_CLASS_ETH,
	PT_CLASS_CEH,
	PT_CLASS_COUNT
};

/* Resolves a configured/default class; throws and returns NULL on failure. */
zend_class_entry *pt_class(int idx);

/* Like pt_class(), but for the *Impl entries: when unconfigured, falls back
 * to the given native class entry instead of a name lookup. */
zend_class_entry *pt_impl_class(int idx, zend_class_entry *native_fallback);

/* Called by Runtime::configure() */
void pt_class_map_configure(zend_string *key, zend_string *value);

/* Fills return_value with key => default FQCN (or null) for every
 * pt_class_refs entry — Runtime::classRefs(), backing the smoke test's
 * structural checks against the generated class map. */
void pt_class_refs_dump(zval *return_value);

/* }}} */

/* {{{ globals (NTS; the extension targets the CLI like the analysis itself) */

struct pt_globals_t {
	zval trinary_yes;
	zval trinary_maybe;
	zval trinary_no;
	bool trinary_inited;
	zval verbosity_precise;
	bool verbosity_inited;
	pt_class_ref class_refs[PT_CLASS_COUNT];
};

extern pt_globals_t pt_globals;

#define PT_G(v) (pt_globals.v)

/* per-request lifecycle, wired to PHP-CPP's onRequest/onIdle */
void pt_support_rinit();
void pt_support_rshutdown();

/* }}} */

/* {{{ native class entries (registered in the per-class files) */

extern zend_class_entry *pt_ce_trinary;
extern zend_class_entry *pt_ce_expr_type_holder;
extern zend_class_entry *pt_ce_cond_expr_holder;
extern zend_class_entry *pt_ce_type_combinator_cache;

/* registration hooks, called from the extension's onStartup */
void pt_register_trinary_logic();
void pt_register_expression_type_holder();
void pt_register_conditional_expression_holder();
void pt_register_combinations_helper();
void pt_register_node_traverser();
void pt_register_scope_ops();
void pt_register_node_scanner();
void pt_register_parser_runner();
void pt_register_type_combinator_cache();
void pt_register_arena_cache();
void pt_register_expression_result_storage();
void pt_register_php_file_cleaner();
void pt_register_symbol_finder_in_files();

/* per-request hooks of individual classes */
void pt_node_traverser_rinit();
void pt_node_traverser_rshutdown();
void pt_scope_ops_rinit();
void pt_scope_ops_rshutdown();
void pt_type_combinator_cache_rinit();
void pt_type_combinator_cache_rshutdown();

/* module-shutdown backstop: destroys the arena mapping if the run skipped
 * ArenaCache::destroy() on a graceful exit */
void pt_arena_mshutdown();

/* Runtime::enablePharForkGuard() — privatizes the phar archive's fd cursor
 * in pcntl_fork()ed children via pthread_atfork (see PharForkGuard.cpp);
 * a no-op on Windows */
void pt_phar_fork_guard_register(zend_string *path);

/* }}} */

/* {{{ TrinaryLogic values and singletons */

#define PT_TRI_YES 3
#define PT_TRI_MAYBE 1
#define PT_TRI_NO 0

#define PT_TRI_PROP_VALUE 0
#define PT_ETH_PROP_EXPR 0
#define PT_ETH_PROP_TYPE 1
#define PT_ETH_PROP_CERTAINTY 2
#define PT_CEH_PROP_CONDS 0
#define PT_CEH_PROP_TYPEHOLDER 1

/* Returns the singleton for the given value (instances of the configured
 * trinaryLogicImpl class). Borrowed zval; callers must copy. */
zval *pt_trinary_singleton(zend_long value);

static zend_always_inline zend_long pt_trinary_value(zend_object *obj)
{
	return Z_LVAL_P(OBJ_PROP_NUM(obj, PT_TRI_PROP_VALUE));
}

static zend_always_inline zend_long pt_holder_certainty_value(zend_object *holder)
{
	return pt_trinary_value(Z_OBJ_P(OBJ_PROP_NUM(holder, PT_ETH_PROP_CERTAINTY)));
}

/* }}} */

/* {{{ userland callback helpers */

zend_function *pt_find_method(zend_class_entry *ce, const char *lcname, size_t len);
bool pt_call_type_equals(zval *type_a, zval *type_b);
bool pt_types_identical_or_equal(zval *type_a, zval *type_b);
/* TypeCombinator::<lcname>($a, $b) */
bool pt_type_combinator_binary(const char *lcname, size_t len, zval *type_a, zval *type_b, zval *result);
/* $type->describe(VerbosityLevel::precise()) */
bool pt_type_describe_precise(zval *type, zval *result);
void pt_throw_should_not_happen();

/* }}} */

/* {{{ dual string/int key hashtable helpers
 *
 * Expression tables are keyed by printed expression strings, but PHP converts
 * numeric-string keys to integer keys on write, so table operations must
 * handle both key kinds like PHP array ops do.
 */

static zend_always_inline zval *pt_ht_find(HashTable *ht, zend_string *skey, zend_ulong idx)
{
	return skey != NULL ? zend_hash_find(ht, skey) : zend_hash_index_find(ht, idx);
}

static zend_always_inline bool pt_ht_exists(HashTable *ht, zend_string *skey, zend_ulong idx)
{
	return skey != NULL ? zend_hash_exists(ht, skey) : zend_hash_index_exists(ht, idx);
}

static zend_always_inline void pt_ht_add_new(HashTable *ht, zend_string *skey, zend_ulong idx, zval *val)
{
	if (skey != NULL) {
		zend_hash_add_new(ht, skey, val);
	} else {
		zend_hash_index_add_new(ht, idx, val);
	}
}

static zend_always_inline void pt_ht_update(HashTable *ht, zend_string *skey, zend_ulong idx, zval *val)
{
	if (skey != NULL) {
		zend_hash_update(ht, skey, val);
	} else {
		zend_hash_index_update(ht, idx, val);
	}
}

static zend_always_inline void pt_ht_del(HashTable *ht, zend_string *skey, zend_ulong idx)
{
	if (skey != NULL) {
		zend_hash_del(ht, skey);
	} else {
		zend_hash_index_del(ht, idx);
	}
}

/* }}} */

/* {{{ node class info, attributes, keys, findFirst */

typedef struct _pt_node_class_info {
	uint32_t *subnode_offsets;
	uint32_t subnode_count; /* UINT32_MAX: not resolvable */
	int32_t attributes_offset;
	int32_t name_offset;
	bool is_variable;
} pt_node_class_info;

#define PT_HAS_SUBNODES(info) ((info)->subnode_offsets != NULL && (info)->subnode_count != UINT32_MAX)

/* cheap info (offsets/flags); does not resolve subnodes */
pt_node_class_info *pt_get_node_class_info(zend_class_entry *ce);
/* resolves subnode offsets on first sight of an instance */
pt_node_class_info *pt_node_class_info_for_object(zend_object *obj);

int32_t pt_instance_prop_offset(zend_class_entry *ce, const char *name, size_t len);

/* cached attribute-name strings, created lazily per request */
extern zend_string *pt_str_cache_printer;
extern zend_string *pt_str_contains_super_global;
extern zend_string *pt_str_array_map_args;
extern zend_string *pt_str_start_file_pos;
void pt_init_strs();

zval *pt_node_attribute(zend_object *node, zend_string *name);
bool pt_node_set_attribute(zend_object *node, zend_string *name, zval *value);

/* Expression key for the node (MutatingScope::getNodeKey semantics); the PHP
 * ExprPrinter is called on cache misses. Returned string owned by caller;
 * NULL on failure (exception thrown). */
zend_string *pt_node_key(zend_object *node, zval *expr_printer);

typedef struct _pt_find_ctx {
	zend_class_entry *target_ce;
	zend_string *invalidate_str;
	zval *expr_printer;
	bool is_this;
	zval *scope;
	zval class_reflection;
	bool class_reflection_fetched;
	bool failed;
} pt_find_ctx;

typedef bool (*pt_node_matcher)(zend_object *node, void *ctx);

zend_object *pt_find_first_recursive(zend_object *node, pt_node_matcher matcher, void *ctx);

bool pt_is_superglobal_name(zend_string *name);
/* CONTAINS_SUPER_GLOBAL_ATTRIBUTE_NAME-cached superglobal scan */
bool pt_expr_contains_superglobal(zend_object *expr);

/* }}} */

/* {{{ ExpressionTypeHolder helpers (no zpp; used by holders and ScopeOps) */

bool pt_check_holder(zval *zv);
/* creates an instance of the configured expressionTypeHolderImpl class */
void pt_holder_create(zval *result, zval *expr, zval *type, zend_long certainty);
bool pt_holder_and(zval *a, zval *b, zval *result);
bool pt_holder_equals(zval *a, zval *b, bool *out);
bool pt_holder_equal_types(zval *a, zval *b, bool *out);

/* ConditionalExpressionHolder::getKey() builder, shared with ScopeOps */
zend_string *pt_ceh_key_build(HashTable *conds, zval *type_holder);

/* calls a (possibly private) method on the scope, coercing result to bool */
bool pt_call_scope_bool(zval *scope, const char *lcname, size_t len, uint32_t argc, zval *argv, bool *out);

/* }}} */

#endif /* PHPSTANTURBO_SUPPORT_H */
