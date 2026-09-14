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

#ifdef _WIN32
/* the engine headers pull in windows.h, whose min() / max() macros would
 * rewrite every member and call of those names */
#undef min
#undef max
#endif

/* {{{ configurable class references */

typedef struct _pt_class_ref {
	const char *key;          /* key in the Runtime::configure() map */
	const char *default_name; /* fallback FQCN when not configured */
	zend_string *configured;  /* name set via configure(), owned */
	zend_class_entry *ce;     /* resolved entry, per-request cache */
} pt_class_ref;

enum {
	PT_CLASS_TYPE_COMBINATOR = 0,
	PT_CLASS_SHOULD_NOT_HAPPEN,
	PT_CLASS_VERBOSITY_LEVEL,
	PT_CLASS_VARIABLE,
	PT_CLASS_FUNC_CALL,
	PT_CLASS_VIRTUAL_NODE,
	PT_CLASS_NODE,
	PT_CLASS_NAME,
	PT_CLASS_EXPR,
	PT_CLASS_PROPERTY_FETCH,
	PT_CLASS_NULLSAFE_PROPERTY_FETCH,
	PT_CLASS_IDENTIFIER,
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
	PT_CLASS_UNION_TYPE,
	PT_CLASS_CONSTANT_ARRAY_TYPE,
	PT_CLASS_CLASS_NAME_TO_OBJECT_TYPE_RESULT,
	PT_CLASS_TEMPLATE_TYPE_MAP,
	PT_CLASS_IDENTIFIER_TYPE_NODE,
	PT_CLASS_STATIC_TYPE_FACTORY,
	PT_CLASS_LOOSE_COMPARISON_HELPER,
	PT_CLASS_EXPONENTIATE_HELPER,
	PT_CLASS_COMPOUND_TYPE,
	PT_CLASS_CONSTANT_SCALAR_TYPE,
	PT_CLASS_INTERSECTION_TYPE,
	PT_CLASS_INITIALIZER_EXPR_TYPE_RESOLVER,
	PT_CLASS_GENERIC_TYPE_NODE,
	PT_CLASS_CONST_TYPE_NODE,
	PT_CLASS_CONST_EXPR_INTEGER_NODE,
	PT_CLASS_REFLECTION_PROVIDER_STATIC_ACCESSOR,
	PT_CLASS_PHP_VERSION_STATIC_ACCESSOR,
	PT_CLASS_REPORT_UNSAFE_ARRAY_STRING_KEY_CASTING_TOGGLE,
	PT_CLASS_OUT_OF_CLASS_SCOPE,
	PT_CLASS_FUNCTION_CALLABLE_VARIANT,
	PT_CLASS_TRIVIAL_PARAMETERS_ACCEPTOR,
	PT_CLASS_INACCESSIBLE_METHOD,
	PT_CLASS_TEMPLATE_TYPE,
	PT_CLASS_TEMPLATE_TYPE_VARIANCE,
	PT_CLASS_GENERALIZE_PRECISION,
	PT_CLASS_CONST_EXPR_STRING_NODE,
	PT_CLASS_NETTE_STRINGS,
	PT_CLASS_NETTE_REGEXP_EXCEPTION,
	PT_CLASS_CONST_EXPR_FLOAT_NODE,
	PT_CLASS_TEMPLATE_MIXED_TYPE,
	PT_CLASS_SUBTRACTABLE_TYPE,
	PT_CLASS_DUMMY_PROPERTY_REFLECTION,
	PT_CLASS_CALLBACK_UNRESOLVED_PROPERTY_PROTOTYPE_REFLECTION,
	PT_CLASS_DUMMY_METHOD_REFLECTION,
	PT_CLASS_CALLBACK_UNRESOLVED_METHOD_PROTOTYPE_REFLECTION,
	PT_CLASS_DUMMY_CLASS_CONSTANT_REFLECTION,
	PT_CLASS_BENEVOLENT_UNION_TYPE,
	PT_CLASS_TYPE_TRAVERSER,
	PT_CLASS_TEMPLATE_TYPE_HELPER,
	PT_CLASS_TYPE_WITH_CLASS_NAME,
	PT_CLASS_OBJECT_SHAPE_PROPERTY_REFLECTION,
	PT_CLASS_UNIVERSAL_OBJECT_CRATES_CLASS_REFLECTION_EXTENSION,
	PT_CLASS_MISSING_PROPERTY_FROM_REFLECTION_EXCEPTION,
	PT_CLASS_TEMPLATE_TYPE_VARIANCE_MAP,
	PT_CLASS_THIS_TYPE_NODE,
	PT_CLASS_OBJECT_SHAPE_NODE,
	PT_CLASS_OBJECT_SHAPE_ITEM_NODE,
	PT_CLASS_TEMPLATE_STRICT_MIXED_TYPE,
	PT_CLASS_UNSAFE_ARRAY_STRING_KEY_CASTING_TRAVERSER,
	PT_CLASS_ALLOWED_ARRAY_KEYS_TYPES,
	PT_CLASS_CONSTANT_ARRAY_TYPE_BUILDER,
	PT_CLASS_LRU_CACHE,
	PT_CLASS_TYPE_UTILS,
	PT_CLASS_UNRESOLVED_TEMPLATE_ARGUMENT_TYPE,
	PT_CLASS_TYPE_PROJECTION_HELPER,
	PT_CLASS_CLASS_NOT_FOUND_EXCEPTION,
	PT_CLASS_CALLED_ON_TYPE_UNRESOLVED_METHOD_PROTOTYPE_REFLECTION,
	PT_CLASS_CALLED_ON_TYPE_UNRESOLVED_PROPERTY_PROTOTYPE_REFLECTION,
	PT_CLASS_UNION_TYPE_UNRESOLVED_PROPERTY_PROTOTYPE_REFLECTION,
	PT_CLASS_ENUM_UNRESOLVED_PROPERTY_PROTOTYPE_REFLECTION,
	PT_CLASS_ENUM_PROPERTY_REFLECTION,
	PT_CLASS_CONST_FETCH_NODE,
	PT_CLASS_PARAMETERS_ACCEPTOR_SELECTOR,
	PT_CLASS_CALLABLE_TYPE_HELPER,
	PT_CLASS_CALLABLE_ASSERTIONS_HELPER,
	PT_CLASS_CALLABLE_PARAMETERS_ACCEPTOR,
	PT_CLASS_ASSERTIONS,
	PT_CLASS_SIMPLE_IMPURE_POINT,
	PT_CLASS_SIMPLE_THROW_POINT,
	PT_CLASS_DUMMY_PARAMETER,
	PT_CLASS_NATIVE_PARAMETER_REFLECTION,
	PT_CLASS_PASSED_BY_REFERENCE,
	PT_CLASS_EXTENDED_PARAMETER_REFLECTION,
	PT_CLASS_CLOSURE_CALL_UNRESOLVED_METHOD_PROTOTYPE_REFLECTION,
	PT_CLASS_PHPDOC_PRINTER,
	PT_CLASS_CALLABLE_TYPE_NODE,
	PT_CLASS_CALLABLE_TYPE_PARAMETER_NODE,
	PT_CLASS_TEMPLATE_TAG_VALUE_NODE,
	PT_CLASS_FINITE_TYPE_SET,
	PT_CLASS_COUNT
};

/* Resolves a configured/default class; throws and returns NULL on failure. */
zend_class_entry *pt_class(int idx);


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
	/* TrustedTypes.cpp: the filename prefix armed by Runtime::trustTypesUnder()
	 * (empty = off) */
	size_t trusted_types_prefix_len;
	char trusted_types_prefix[MAXPATHLEN + 16];
};

extern pt_globals_t pt_globals;

#define PT_G(v) (pt_globals.v)

/* a string literal as the (chars, length) argument pair of the by-name helpers */
#define PT_LC(literal) literal, sizeof(literal) - 1

/* per-request lifecycle, wired to PHP-CPP's onRequest/onIdle */
void pt_support_rinit();
void pt_support_rshutdown();

/* }}} */

/* {{{ native class entries (registered in the per-class files) */

extern zend_class_entry *pt_ce_trinary;
extern zend_class_entry *pt_ce_expr_type_holder;
extern zend_class_entry *pt_ce_cond_expr_holder;
extern zend_class_entry *pt_ce_type_combinator_cache;
extern zend_class_entry *pt_ce_accepts_result;
extern zend_class_entry *pt_ce_is_super_type_of_result;
/* the shadowing Type classes (BooleanType.cpp, ConstantBooleanType.cpp,
 * IntegerType.cpp, ConstantIntegerType.cpp, IntegerRangeType.cpp,
 * StringType.cpp, ConstantStringType.cpp, ClassStringType.cpp,
 * GenericClassStringType.cpp) */
extern zend_class_entry *pt_ce_boolean_type;
extern zend_class_entry *pt_ce_constant_boolean_type;
extern zend_class_entry *pt_ce_integer_type;
extern zend_class_entry *pt_ce_constant_integer_type;
extern zend_class_entry *pt_ce_integer_range_type;
extern zend_class_entry *pt_ce_string_type;
extern zend_class_entry *pt_ce_constant_string_type;
extern zend_class_entry *pt_ce_class_string_type;
extern zend_class_entry *pt_ce_generic_class_string_type;
/* FloatType.cpp, ConstantFloatType.cpp, NullType.cpp, VoidType.cpp */
extern zend_class_entry *pt_ce_float_type;
extern zend_class_entry *pt_ce_constant_float_type;
extern zend_class_entry *pt_ce_null_type;
extern zend_class_entry *pt_ce_void_type;
/* the never/mixed family (NeverType.cpp, MixedType.cpp, StrictMixedType.cpp) */
extern zend_class_entry *pt_ce_never_type;
extern zend_class_entry *pt_ce_mixed_type;
extern zend_class_entry *pt_ce_strict_mixed_type;
/* the object family (ObjectWithoutClassType.cpp, StaticType.cpp,
 * ThisType.cpp, GenericStaticType.cpp, ObjectShapeType.cpp,
 * NonexistentParentClassType.cpp) */
extern zend_class_entry *pt_ce_object_without_class_type;
extern zend_class_entry *pt_ce_static_type;
extern zend_class_entry *pt_ce_this_type;
extern zend_class_entry *pt_ce_generic_static_type;
extern zend_class_entry *pt_ce_object_shape_type;
extern zend_class_entry *pt_ce_nonexistent_parent_class_type;

/* registration hooks, called from the extension's onStartup */
/* Shadow.cpp — Runtime::activateShadowing() */
bool pt_shadow_activate(HashTable *twinFiles, zend_string *prefix);
bool pt_shadow_is_active();

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
void pt_register_scope_context();
void pt_register_is_super_type_of_result();
void pt_register_accepts_result();
/* the Type ports; registered after the result classes their return types
 * name (a plan naming a class declared later would make the linker autoload
 * the PHP twin) — BooleanType before its child ConstantBooleanType,
 * IntegerType before its children ConstantIntegerType and IntegerRangeType
 * (and after BooleanType, whose class their toBoolean() return type names) */
void pt_register_type_traits();
void pt_register_boolean_type();
void pt_register_constant_boolean_type();
void pt_register_integer_type();
void pt_register_constant_integer_type();
void pt_register_integer_range_type();
/* StringType before its children ConstantStringType and ClassStringType,
 * ClassStringType before its child GenericClassStringType; all after the
 * integer family, whose classes their bodies instantiate */
void pt_register_string_type();
void pt_register_constant_string_type();
void pt_register_class_string_type();
void pt_register_generic_class_string_type();
/* FloatType before its child ConstantFloatType, then NullType and
 * VoidType; all after the string family, whose classes their bodies
 * instantiate (FloatType::toString()) */
void pt_register_float_type();
void pt_register_constant_float_type();
void pt_register_null_type();
void pt_register_void_type();
/* the never/mixed family after the string family (their bodies instantiate
 * its classes); NeverType before MixedType (the constructor drops a NeverType
 * subtracted type), StrictMixedType last (isAcceptedBy() names MixedType) */
void pt_register_never_type();
void pt_register_mixed_type();
void pt_register_strict_mixed_type();
/* the object family after the never/mixed family (their bodies instantiate
 * its classes): ObjectWithoutClassType first (the others' isSuperTypeOf()
 * and toObjectTypeForIsACheck() name it), StaticType before its children
 * ThisType and GenericStaticType, then ObjectShapeType and
 * NonexistentParentClassType */
void pt_register_object_without_class_type();
void pt_register_static_type();
void pt_register_this_type();
void pt_register_generic_static_type();
void pt_register_object_shape_type();
void pt_register_nonexistent_parent_class_type();
void pt_integer_range_type_rinit();
void pt_constant_string_type_rinit();
void pt_is_super_type_of_result_rinit();
void pt_is_super_type_of_result_rshutdown();
void pt_accepts_result_rinit();
void pt_accepts_result_rshutdown();

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

/* Runtime::trustTypesUnder() — arms the optimizer pass that drops the
 * engine's argument and return type checks from scripts under the prefix
 * (see TrustedTypes.cpp); false when opcache's pass registration is not
 * available */
bool pt_trusted_types_set_prefix(zend_string *prefix);

/* }}} */

/* {{{ TrinaryLogic values and singletons */

#define PT_TRI_YES 3
#define PT_TRI_MAYBE 1
#define PT_TRI_NO 0

/* TrinaryLogic::and() / or() of two PT_TRI_* values (yes > maybe > no) */
static constexpr zend_long pt_trinary_and(zend_long a, zend_long b) { return a < b ? a : b; }
static constexpr zend_long pt_trinary_or(zend_long a, zend_long b) { return a > b ? a : b; }

#define PT_TRI_PROP_VALUE 0
#define PT_ETH_PROP_EXPR 0
#define PT_ETH_PROP_TYPE 1
#define PT_ETH_PROP_CERTAINTY 2
#define PT_CEH_PROP_CONDS 0
#define PT_CEH_PROP_TYPEHOLDER 1

/* Returns the singleton for the given value (instances of the shadowing
 * TrinaryLogic class). Borrowed zval; callers must copy. */
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

/* the per-request AcceptsResult / IsSuperTypeOfResult singletons for a
 * PT_TRI_* value (createYes()/createMaybe()/createNo() with no reasons —
 * createFromBoolean() maps to the yes/no ones); owned copy in *out, false =
 * pending exception (AcceptsResult.cpp / IsSuperTypeOfResult.cpp) */
bool pt_accepts_result_singleton(zval *out, zend_long value);
bool pt_is_super_type_of_result_singleton(zval *out, zend_long value);
/* $self->and($other) on two AcceptsResult instances; false = pending
 * exception (AcceptsResult.cpp) */
[[nodiscard]] bool pt_accepts_result_and(zval *out, zval *self, zval *other);
/* new AcceptsResult($trinary, $reasons); $reasons is owned and consumed;
 * false = pending exception (AcceptsResult.cpp) */
[[nodiscard]] bool pt_accepts_result_create(zval *out, zval *trinary, zval *reasons);
/* ->result's trinary value of a native result object; -1 with an Error
 * pending for an object that skipped its constructor (AcceptsResult.cpp) */
[[nodiscard]] zend_long pt_result_value(zend_object *object);

/* new BooleanType() / new ConstantBooleanType($value) — instances of the
 * shadowing classes (BooleanType.cpp / ConstantBooleanType.cpp); false =
 * pending exception */
bool pt_boolean_type_new(zval *out);
bool pt_constant_boolean_type_new(zval *out, bool value);
/* the $value of an instance of the shadowing ConstantBooleanType; false
 * with an Error pending when uninitialized */
bool pt_constant_boolean_type_value(zend_object *object, bool &out);

/* new IntegerType() / new ConstantIntegerType($value) — instances of the
 * shadowing classes (IntegerType.cpp / ConstantIntegerType.cpp); false =
 * pending exception */
bool pt_integer_type_new(zval *out);
bool pt_constant_integer_type_new(zval *out, zend_long value);
/* the $value of an instance of the shadowing ConstantIntegerType; false
 * with an Error pending when uninitialized */
bool pt_constant_integer_type_value(zend_object *object, zend_long &out);

/* new StringType() / new ClassStringType() / new ConstantStringType($value,
 * $isClassString) — instances of the shadowing classes (StringType.cpp /
 * ClassStringType.cpp / ConstantStringType.cpp; $value borrowed); false =
 * pending exception */
bool pt_string_type_new(zval *out);
bool pt_class_string_type_new(zval *out);
bool pt_constant_string_type_new(zval *out, zend_string *value, bool isClassString = false);

/* new FloatType() / new ConstantFloatType($value) / new NullType() /
 * new VoidType() — instances of the shadowing classes (FloatType.cpp /
 * ConstantFloatType.cpp / NullType.cpp / VoidType.cpp); false = pending
 * exception */
[[nodiscard]] bool pt_float_type_new(zval *out);
bool pt_constant_float_type_new(zval *out, double value);
bool pt_null_type_new(zval *out);
bool pt_void_type_new(zval *out);
/* new NeverType($isExplicit) / new MixedType($isExplicitMixed, $subtractedType)
 * — instances of the shadowing classes (NeverType.cpp / MixedType.cpp;
 * $subtractedType borrowed, NULL for null); false = pending exception */
[[nodiscard]] bool pt_never_type_new(zval *out, bool isExplicit = false);
bool pt_mixed_type_new(zval *out, bool isExplicitMixed = false, zval *subtractedType = NULL);
/* new ObjectWithoutClassType($subtractedType) / new StaticType($classReflection,
 * $subtractedType) / new ThisType($classReflection, $subtractedType) /
 * new GenericStaticType($classReflection, $types, $subtractedType, $variances)
 * / new ObjectShapeType($properties, $optionalProperties) /
 * new NonexistentParentClassType() — instances of the shadowing classes
 * (arguments borrowed, NULL for null); false = pending exception */
[[nodiscard]] bool pt_object_without_class_type_new(zval *out, zval *subtractedType = NULL);
bool pt_static_type_new(zval *out, zval *classReflection, zval *subtractedType = NULL);
bool pt_this_type_new(zval *out, zval *classReflection, zval *subtractedType = NULL);
bool pt_generic_static_type_new(zval *out, zval *classReflection, zval *types, zval *subtractedType, zval *variances);
bool pt_object_shape_type_new(zval *out, zval *properties, zval *optionalProperties);
bool pt_nonexistent_parent_class_type_new(zval *out);

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
/* creates an instance of the shadowing ExpressionTypeHolder class */
void pt_holder_create(zval *result, zval *expr, zval *type, zend_long certainty);
bool pt_holder_and(zval *a, zval *b, zval *result);
bool pt_holder_equals(zval *a, zval *b, bool *out);
bool pt_holder_equal_types(zval *a, zval *b, bool *out);

/* ConditionalExpressionHolder::getKey() builder, shared with ScopeOps */
zend_string *pt_ceh_key_build(HashTable *conds, zval *type_holder);

/* calls a (possibly private) method on the scope, coercing result to bool */
bool pt_call_scope_bool(zval *scope, const char *lcname, size_t len, uint32_t argc, zval *argv, bool *out);

/* }}} */

/* merged from the parallel port branch */
/* the array family (ArrayType.cpp, NonEmptyArrayType.cpp,
 * AccessoryArrayListType.cpp, OversizedArrayType.cpp, HasOffsetType.cpp,
 * HasOffsetValueType.cpp) */
extern zend_class_entry *pt_ce_array_type;
extern zend_class_entry *pt_ce_non_empty_array_type;
extern zend_class_entry *pt_ce_accessory_array_list_type;
extern zend_class_entry *pt_ce_oversized_array_type;
extern zend_class_entry *pt_ce_has_offset_type;
extern zend_class_entry *pt_ce_has_offset_value_type;
/* the array family after the never/mixed family (ArrayType's constructor
 * probes MixedType and StrictMixedType); ArrayType first, then the five
 * accessories (their getDefaultBaseType() instantiates ArrayType) */
void pt_register_array_type();
void pt_register_non_empty_array_type();
void pt_register_accessory_array_list_type();
void pt_register_oversized_array_type();
void pt_register_has_offset_type();
void pt_register_has_offset_value_type();
/* new ArrayType($keyType, $itemType) / new NonEmptyArrayType() /
 * new AccessoryArrayListType() / new OversizedArrayType() /
 * new HasOffsetType($offsetType) / new HasOffsetValueType($offsetType, $valueType)
 * — instances of the shadowing classes (ArrayType.cpp and the accessory
 * files; the Type arguments borrowed, checked as the twins' typed
 * parameters check them); false = pending exception */
[[nodiscard]] bool pt_array_type_new(zval *out, zval *keyType, zval *itemType);
bool pt_non_empty_array_type_new(zval *out);
bool pt_accessory_array_list_type_new(zval *out);
bool pt_oversized_array_type_new(zval *out);
bool pt_has_offset_type_new(zval *out, zval *offsetType);
bool pt_has_offset_value_type_new(zval *out, zval *offsetType, zval *valueType);

/* the string accessory family (AccessoryNumericStringType.cpp,
 * AccessoryNonEmptyStringType.cpp, AccessoryNonFalsyStringType.cpp,
 * AccessoryLiteralStringType.cpp, AccessoryLowercaseStringType.cpp,
 * AccessoryUppercaseStringType.cpp, AccessoryDecimalIntegerStringType.cpp)
 * and the member accessories (HasMethodType.cpp, HasPropertyType.cpp) */
extern zend_class_entry *pt_ce_accessory_numeric_string_type;
extern zend_class_entry *pt_ce_accessory_non_empty_string_type;
extern zend_class_entry *pt_ce_accessory_non_falsy_string_type;
extern zend_class_entry *pt_ce_accessory_literal_string_type;
extern zend_class_entry *pt_ce_accessory_lowercase_string_type;
extern zend_class_entry *pt_ce_accessory_uppercase_string_type;
extern zend_class_entry *pt_ce_accessory_decimal_integer_string_type;
extern zend_class_entry *pt_ce_has_method_type;
extern zend_class_entry *pt_ce_has_property_type;
/* the string accessories after the array family (their bodies instantiate
 * nothing of each other at registration; the order among them only follows
 * the twins' cross-references: NumericString before DecimalIntegerString,
 * NonEmptyString before NonFalsyString), then the member accessories */
void pt_register_accessory_numeric_string_type();
void pt_register_accessory_non_empty_string_type();
void pt_register_accessory_non_falsy_string_type();
void pt_register_accessory_literal_string_type();
void pt_register_accessory_lowercase_string_type();
void pt_register_accessory_uppercase_string_type();
void pt_register_accessory_decimal_integer_string_type();
void pt_register_has_method_type();
void pt_register_has_property_type();
/* new AccessoryNumericStringType() / new AccessoryNonEmptyStringType() /
 * new AccessoryNonFalsyStringType() / new AccessoryLiteralStringType() /
 * new AccessoryLowercaseStringType() / new AccessoryUppercaseStringType() /
 * new AccessoryDecimalIntegerStringType($inverse) /
 * new HasMethodType($methodName) / new HasPropertyType($propertyName) —
 * instances of the shadowing classes (the string arguments borrowed); false
 * = pending exception */
[[nodiscard]] bool pt_accessory_numeric_string_type_new(zval *out);
bool pt_accessory_non_empty_string_type_new(zval *out);
bool pt_accessory_non_falsy_string_type_new(zval *out);
bool pt_accessory_literal_string_type_new(zval *out);
bool pt_accessory_lowercase_string_type_new(zval *out);
bool pt_accessory_uppercase_string_type_new(zval *out);
bool pt_accessory_decimal_integer_string_type_new(zval *out, bool inverse = false);
bool pt_has_method_type_new(zval *out, zend_string *methodName);
bool pt_has_property_type_new(zval *out, zend_string *propertyName);

/* merged from the parallel port branch */
/* the object family (ObjectType.cpp, GenericObjectType.cpp, EnumCaseObjectType.cpp) */
extern zend_class_entry *pt_ce_object_type;
extern zend_class_entry *pt_ce_generic_object_type;
extern zend_class_entry *pt_ce_enum_case_object_type;
/* registered after the never/mixed family (their bodies instantiate
 * its classes); ObjectType before its children GenericObjectType and
 * EnumCaseObjectType */
void pt_register_object_type();
void pt_register_generic_object_type();
void pt_register_enum_case_object_type();
void pt_object_type_rinit();
void pt_object_type_rshutdown();
/* new ObjectType($className, $subtractedType, $classReflection) / new
 * GenericObjectType($mainType, $types, $subtractedType, $classReflection,
 * $variances) / new EnumCaseObjectType($className, $enumCaseName,
 * $classReflection) — instances of the shadowing classes (ObjectType.cpp /
 * GenericObjectType.cpp / EnumCaseObjectType.cpp; every argument borrowed,
 * NULL for a null or a default); false = pending exception */
[[nodiscard]] bool pt_object_type_new(zval *out, zend_string *className, zval *subtractedType = NULL, zval *classReflection = NULL);
bool pt_generic_object_type_new(zval *out, zend_string *mainType, zval *types, zval *subtractedType = NULL, zval *classReflection = NULL, zval *variances = NULL);
bool pt_enum_case_object_type_new(zval *out, zend_string *className, zend_string *enumCaseName, zval *classReflection = NULL);

/* merged from the parallel port branch */
/* the callable family (IterableType.cpp, CallableType.cpp, ClosureType.cpp) */
extern zend_class_entry *pt_ce_iterable_type;
extern zend_class_entry *pt_ce_callable_type;
extern zend_class_entry *pt_ce_closure_type;
/* registered at the end of the Type block: IterableType, then CallableType
 * (MixedType's isIterable()/isCallable() probes instantiate them), then
 * ClosureType (its toCoercedArgumentType() instantiates CallableType, its
 * constructor ObjectType) */
void pt_register_iterable_type();
void pt_register_callable_type();
void pt_register_closure_type();
/* new IterableType($keyType, $itemType) / new CallableType($parameters,
 * $returnType, $variadic, $templateTypeMap, $resolvedTemplateTypeMap,
 * $templateTags, $isPure, $assertions) / new ClosureType($parameters,
 * $returnType, $variadic, $templateTypeMap, $resolvedTemplateTypeMap,
 * $callSiteVarianceMap, $templateTags, $throwPoints, $impurePoints,
 * $invalidateExpressions, $usedVariables, $acceptsNamedArguments,
 * $mustUseReturnValue, $assertions, $isStatic) — instances of the shadowing
 * classes (IterableType.cpp / CallableType.cpp / ClosureType.cpp; every
 * argument borrowed, NULL for a null or a default — the twins' `?array`
 * and `?Type` parameters at null decide isCommonCallable); false = pending
 * exception */
[[nodiscard]] bool pt_iterable_type_new(zval *out, zval *keyType, zval *itemType);
bool pt_callable_type_new(zval *out, zval *parameters = NULL, zval *returnType = NULL, bool variadic = true, zval *templateTypeMap = NULL, zval *resolvedTemplateTypeMap = NULL, zval *templateTags = NULL, zval *isPure = NULL, zval *assertions = NULL);
bool pt_closure_type_new(zval *out, zval *parameters = NULL, zval *returnType = NULL, bool variadic = true, zval *templateTypeMap = NULL, zval *resolvedTemplateTypeMap = NULL, zval *callSiteVarianceMap = NULL, zval *templateTags = NULL, zval *throwPoints = NULL, zval *impurePoints = NULL, zval *invalidateExpressions = NULL, zval *usedVariables = NULL, zval *acceptsNamedArguments = NULL, zval *mustUseReturnValue = NULL, zval *assertions = NULL, zval *isStatic = NULL);

#endif /* PHPSTANTURBO_SUPPORT_H */
