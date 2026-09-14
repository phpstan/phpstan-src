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
	PT_CLASS_SHOULD_NOT_HAPPEN = 0,
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
	PT_CLASS_CLASS_NAME_TO_OBJECT_TYPE_RESULT,
	PT_CLASS_IDENTIFIER_TYPE_NODE,
	PT_CLASS_LOOSE_COMPARISON_HELPER,
	PT_CLASS_EXPONENTIATE_HELPER,
	PT_CLASS_COMPOUND_TYPE,
	PT_CLASS_CONSTANT_SCALAR_TYPE,
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
	PT_CLASS_NARROWED_SUBJECT_TYPE,
	PT_CLASS_GENERALIZE_PRECISION,
	PT_CLASS_CONST_EXPR_STRING_NODE,
	PT_CLASS_NETTE_STRINGS,
	PT_CLASS_NETTE_REGEXP_EXCEPTION,
	PT_CLASS_CONST_EXPR_FLOAT_NODE,
	PT_CLASS_SUBTRACTABLE_TYPE,
	PT_CLASS_DUMMY_PROPERTY_REFLECTION,
	PT_CLASS_DUMMY_METHOD_REFLECTION,
	PT_CLASS_DUMMY_CLASS_CONSTANT_REFLECTION,
	PT_CLASS_TYPE_WITH_CLASS_NAME,
	PT_CLASS_OBJECT_SHAPE_PROPERTY_REFLECTION,
	PT_CLASS_UNIVERSAL_OBJECT_CRATES_CLASS_REFLECTION_EXTENSION,
	PT_CLASS_MISSING_PROPERTY_FROM_REFLECTION_EXCEPTION,
	PT_CLASS_THIS_TYPE_NODE,
	PT_CLASS_OBJECT_SHAPE_NODE,
	PT_CLASS_OBJECT_SHAPE_ITEM_NODE,
	PT_CLASS_UNSAFE_ARRAY_STRING_KEY_CASTING_TRAVERSER,
	PT_CLASS_ALLOWED_ARRAY_KEYS_TYPES,
	PT_CLASS_CLASS_NOT_FOUND_EXCEPTION,
	PT_CLASS_UNION_TYPE_UNRESOLVED_PROPERTY_PROTOTYPE_REFLECTION,
	PT_CLASS_ENUM_UNRESOLVED_PROPERTY_PROTOTYPE_REFLECTION,
	PT_CLASS_ENUM_PROPERTY_REFLECTION,
	PT_CLASS_CONST_FETCH_NODE,
	PT_CLASS_PARAMETERS_ACCEPTOR_SELECTOR,
	PT_CLASS_CALLABLE_ASSERTIONS_HELPER,
	PT_CLASS_CALLABLE_PARAMETERS_ACCEPTOR,
	PT_CLASS_ASSERTIONS,
	PT_CLASS_SIMPLE_IMPURE_POINT,
	PT_CLASS_SIMPLE_THROW_POINT,
	PT_CLASS_DUMMY_PARAMETER,
	PT_CLASS_PASSED_BY_REFERENCE,
	PT_CLASS_EXTENDED_PARAMETER_REFLECTION,
	PT_CLASS_CLOSURE_CALL_UNRESOLVED_METHOD_PROTOTYPE_REFLECTION,
	PT_CLASS_PHPDOC_PRINTER,
	PT_CLASS_CALLABLE_TYPE_NODE,
	PT_CLASS_CALLABLE_TYPE_PARAMETER_NODE,
	PT_CLASS_TEMPLATE_TAG_VALUE_NODE,
	PT_CLASS_BLEEDING_EDGE_TOGGLE,
	PT_CLASS_CONSTANT_ARRAY_TYPE_AND_METHOD,
	PT_CLASS_ARRAY_SHAPE_NODE,
	PT_CLASS_ARRAY_SHAPE_ITEM_NODE,
	PT_CLASS_ARRAY_SHAPE_UNSEALED_TYPE_NODE,
	PT_CLASS_LATE_RESOLVABLE_TYPE,
	PT_CLASS_UNION_TYPE_UNRESOLVED_METHOD_PROTOTYPE_REFLECTION,
	PT_CLASS_MISSING_METHOD_FROM_REFLECTION_EXCEPTION,
	PT_CLASS_MISSING_CONSTANT_FROM_REFLECTION_EXCEPTION,
	PT_CLASS_INTERSECTION_TYPE_UNRESOLVED_PROPERTY_PROTOTYPE_REFLECTION,
	PT_CLASS_INTERSECTION_TYPE_UNRESOLVED_METHOD_PROTOTYPE_REFLECTION,
	PT_CLASS_ACCESSORY_TYPE,
	PT_CLASS_UNION_TYPE_NODE,
	PT_CLASS_INTERSECTION_TYPE_NODE,
	PT_CLASS_TYPE_TRAVERSER_CALLABLE,
	PT_CLASS_LATE_RESOLVABLE_TRAVERSER,
	PT_CLASS_REFLECTION_UNION_TYPE,
	PT_CLASS_REFLECTION_INTERSECTION_TYPE,
	PT_CLASS_REFLECTION_NAMED_TYPE,
	PT_CLASS_FULLY_QUALIFIED,
	PT_CLASS_PARSER_NODE_TYPE_TO_PHPSTAN_TYPE,
	PT_CLASS_TURBO_EXTENSION_ENABLER,
	PT_CLASS_PARAMETERS_ACCEPTOR,
	PT_CLASS_OFFSET_ACCESS_TYPE_NODE,
	PT_CLASS_CONDITIONAL_TYPE_NODE,
	PT_CLASS_CONDITIONAL_TYPE_FOR_PARAMETER_NODE,
	PT_CLASS_CLASS_REFLECTION,
	PT_CLASS_MUTATING_SCOPE,
	PT_CLASS_REFLECTION_ENUM,
	PT_CLASS_MEMOIZING_REFLECTION_PROVIDER,
	PT_CLASS_UNRESOLVABLE_TYPE_RESULT,
	PT_CLASS_EXTENDED_DUMMY_PARAMETER,
	PT_CLASS_EXTENDED_FUNCTION_VARIANT,
	PT_CLASS_RESOLVED_METHOD_REFLECTION,
	PT_CLASS_RESOLVED_PROPERTY_REFLECTION,
	PT_CLASS_CHANGED_TYPE_METHOD_REFLECTION,
	PT_CLASS_CHANGED_TYPE_PROPERTY_REFLECTION,
	PT_CLASS_INITIALIZER_EXPR_CONTEXT,
	PT_CLASS_NULLSAFE_METHOD_CALL,
	PT_CLASS_STATIC_PROPERTY_FETCH,
	PT_CLASS_EXTENDED_METHOD_REFLECTION,
	PT_CLASS_ARG,
	PT_CLASS_WRAPPED_EXTENDED_METHOD_REFLECTION,
	PT_CLASS_EXTENDED_PROPERTY_REFLECTION,
	PT_CLASS_WRAPPED_EXTENDED_PROPERTY_REFLECTION,
	PT_CLASS_VARIABLE_ACCESS_FLOW,
	PT_CLASS_ENUM_CASE_REFLECTION,
	PT_CLASS_REFLECTION_ENUM_BACKED_CASE,
	PT_CLASS_REAL_CLASS_CLASS_CONSTANT_REFLECTION,
	PT_CLASS_TYPE_ALIAS,
	PT_CLASS_CIRCULAR_TYPE_ALIAS_DEFINITION_EXCEPTION,
	PT_CLASS_ARGUMENTS_NORMALIZER,
	PT_CLASS_VARIABLE_SEQUENCE_FLOW,
	PT_CLASS_VARIABLE_CONTROL_FLOW,
	PT_CLASS_VARIABLE_INPUT_FLOW,
	PT_CLASS_VARIABLE_WRITE,
	PT_CLASS_VARIABLE_WRITE_OFFSET,
	PT_CLASS_LIST_EXPR,
	PT_CLASS_VARIABLE_WRITES_NODE,
	PT_CLASS_TYPE_SPECIFIER_CONTEXT,
	PT_CLASS_VOID_TO_NULL_TRAVERSER,
	PT_CLASS_ISSETABILITY_RESOLUTION,
	PT_CLASS_ISSETABILITY_LINK_INFO,
	PT_CLASS_COUNT
};

/* Resolves a configured/default class; throws and returns NULL on failure. */
zend_class_entry *pt_class(int idx);
/* pt_class() without autoloading: the class when it is already declared
 * (cached like pt_class()'s), NULL with no exception when it is not — what
 * an `instanceof` against an undeclared class sees; throws only when the
 * key has neither a configured nor a default name */
zend_class_entry *pt_class_loaded(int idx);

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

/* merged from the parallel port branch */
/* the array-shape type (ConstantArrayType.cpp) */
extern zend_class_entry *pt_ce_constant_array_type;
/* ConstantArrayType after the whole array family (its bodies instantiate
 * ArrayType and every accessory) */
void pt_register_constant_array_type();
/* new ConstantArrayType($keyTypes, $valueTypes, $nextAutoIndexes = [0],
 * $optionalKeys = [], $isList = null, $unsealed = null) — an instance of the
 * shadowing class, the arguments borrowed and checked as the twin's typed
 * parameters check them; NULL stands for a parameter left at its default;
 * false = pending exception */
[[nodiscard]] bool pt_constant_array_type_new(zval *out, zval *keyTypes, zval *valueTypes, zval *nextAutoIndexes = NULL, zval *optionalKeys = NULL, zval *isList = NULL, zval *unsealed = NULL);
/* ConstantArrayType::isValidIdentifier($value); false = pending exception */
[[nodiscard]] bool pt_constant_array_type_is_valid_identifier(zend_string *value, bool &out);

/* merged from the parallel port branch */
/* the compound family (UnionType.cpp, BenevolentUnionType.cpp,
 * IntersectionType.cpp) */
extern zend_class_entry *pt_ce_union_type;
extern zend_class_entry *pt_ce_benevolent_union_type;
extern zend_class_entry *pt_ce_intersection_type;
/* the compound family after the array family (their bodies instantiate
 * its classes): UnionType first, then its child BenevolentUnionType, then
 * IntersectionType (whose bodies instantiate both) */
void pt_register_union_type();
void pt_register_benevolent_union_type();
void pt_register_intersection_type();
/* new UnionType($types, $normalized) / new BenevolentUnionType($types,
 * $normalized) / new IntersectionType($types) — instances of the shadowing
 * classes ($types borrowed, checked as the twins' `array` parameters and
 * constructors check it — fewer than two members throw); false = pending
 * exception */
[[nodiscard]] bool pt_union_type_new(zval *out, zval *types, bool normalized = false);
bool pt_benevolent_union_type_new(zval *out, zval *types, bool normalized = false);
bool pt_intersection_type_new(zval *out, zval *types);

/* merged from the parallel port branch */
/* the Type-kernel helper classes (TypeTraverser.cpp, VerbosityLevel.cpp,
 * RecursionGuard.cpp, FiniteTypeSet.cpp) */
extern zend_class_entry *pt_ce_type_traverser;
extern zend_class_entry *pt_ce_verbosity_level;
extern zend_class_entry *pt_ce_recursion_guard;
extern zend_class_entry *pt_ce_finite_type_set;
/* registered at the end of the Type block: TypeTraverser first
 * (VerbosityLevel::getRecommendedLevelByType() runs it), then
 * VerbosityLevel (RecursionGuard::run() describes with its value level),
 * RecursionGuard, FiniteTypeSet (its containedIn() names TrinaryLogic) */
void pt_register_type_traverser();
void pt_register_verbosity_level();
void pt_register_recursion_guard();
void pt_register_finite_type_set();
/* TypeTraverser::map($type, $cb) — $cb a TypeTraverserCallable or any
 * callable (borrowed); false = pending exception */
[[nodiscard]] bool pt_type_traverser_map(zval *out, zval *type, zval *cb);
/* $traverse($type) for the `callable $traverse` a TypeTraverser callback
 * receives: the native traverser's traverseInternal() directly when it is
 * its own [$traverser, 'traverseInternal'] array, any other callable
 * through the engine; false = pending exception */
[[nodiscard]] bool pt_type_traverser_traverse(zval *out, zval *traverse, zval *type);
/* the twin's private level constants */
#define PT_VERBOSITY_LEVEL_TYPE_ONLY 1
#define PT_VERBOSITY_LEVEL_VALUE 2
#define PT_VERBOSITY_LEVEL_PRECISE 3
#define PT_VERBOSITY_LEVEL_CACHE 4
/* VerbosityLevel::typeOnly() / value() / precise() / cache() for a
 * PT_VERBOSITY_LEVEL_* value — the twin's singletons, held in its static
 * properties; borrowed zval, callers copy; NULL = pending exception */
[[nodiscard]] zval *pt_verbosity_level_singleton(zend_long value);
/* $level->getLevelValue(): the slot of a native instance, the method of
 * anything else (the PHP twin declared next to the native class in the
 * differential tests); false = pending exception */
[[nodiscard]] bool pt_verbosity_level_value_of(zval *level, zend_long &out);
/* VerbosityLevel::getRecommendedLevelByType($acceptingType, $acceptedType)
 * ($acceptedType NULL for null); false = pending exception */
[[nodiscard]] bool pt_verbosity_level_recommended(zval *out, zval *acceptingType, zval *acceptedType);
/* RecursionGuard::run($type, $callback) / runOnObjectIdentity($type,
 * $callback) — $callback any callable (borrowed); false = pending exception */
[[nodiscard]] bool pt_recursion_guard_run(zval *out, zval *type, zval *callback);
bool pt_recursion_guard_run_on_object_identity(zval *out, zval *type, zval *callback);
/* whether RecursionGuard::$context is non-empty (or unreadable) — the
 * TypeCombinatorCache memo stays out while it is */
bool pt_recursion_guard_active();
/* FiniteTypeSet::create($types) (the set or null in *out) and
 * FiniteTypeSet::key($type) (a string or null in *out); false = pending
 * exception */
[[nodiscard]] bool pt_finite_type_set_create(zval *out, zval *types);
bool pt_finite_type_set_key(zval *out, zval *type);

/* merged from the parallel port branch */
/* the small Type classes (ErrorType.cpp, CircularTypeAliasErrorType.cpp,
 * AbsorbedTemplateArgumentType.cpp, NonAcceptingNeverType.cpp,
 * StringAlwaysAcceptingObjectWithToStringType.cpp,
 * StringNeverAcceptingObjectWithToStringType.cpp, ResourceType.cpp) */
extern zend_class_entry *pt_ce_error_type;
extern zend_class_entry *pt_ce_circular_type_alias_error_type;
extern zend_class_entry *pt_ce_absorbed_template_argument_type;
extern zend_class_entry *pt_ce_non_accepting_never_type;
extern zend_class_entry *pt_ce_string_always_accepting_object_with_to_string_type;
extern zend_class_entry *pt_ce_string_never_accepting_object_with_to_string_type;
extern zend_class_entry *pt_ce_resource_type;
/* registered at the end of the Type block, each child after its parent:
 * ErrorType (MixedType's child), then its children
 * CircularTypeAliasErrorType and AbsorbedTemplateArgumentType,
 * NonAcceptingNeverType (NeverType's child), the two StringType children,
 * and ResourceType (a Type of its own, whose bodies instantiate the
 * scalar classes and ConstantArrayType) */
void pt_register_error_type();
void pt_register_circular_type_alias_error_type();
void pt_register_absorbed_template_argument_type();
void pt_register_non_accepting_never_type();
void pt_register_string_always_accepting_object_with_to_string_type();
void pt_register_string_never_accepting_object_with_to_string_type();
void pt_register_resource_type();
/* new ErrorType($reason) / new CircularTypeAliasErrorType($reason) / new
 * AbsorbedTemplateArgumentType($reason) ($reason borrowed, NULL for the
 * twins' null default) / new NonAcceptingNeverType() / new
 * StringAlwaysAcceptingObjectWithToStringType() / new
 * StringNeverAcceptingObjectWithToStringType() / new ResourceType() —
 * instances of the shadowing classes; false = pending exception */
[[nodiscard]] bool pt_error_type_new(zval *out, zend_string *reason = NULL);
bool pt_circular_type_alias_error_type_new(zval *out, zend_string *reason = NULL);
bool pt_absorbed_template_argument_type_new(zval *out, zend_string *reason = NULL);
bool pt_non_accepting_never_type_new(zval *out);
bool pt_string_always_accepting_object_with_to_string_type_new(zval *out);
bool pt_string_never_accepting_object_with_to_string_type_new(zval *out);
bool pt_resource_type_new(zval *out);
/* the body of ErrorType::__construct($reason) on the object (its own
 * $reason slot, then MixedType's constructor body), for the children's
 * inherited constructor; $reason borrowed, NULL for null */
void pt_error_type_construct(zend_object *self, zend_string *reason);
/* the static type helpers (TypeUtils.cpp) — registered after the Type
 * block (its return types name ThisType) */
extern zend_class_entry *pt_ce_type_utils;
void pt_register_type_utils();
/* the native-type decision helpers (TypehintHelper.cpp) — registered after
 * the Type block */
extern zend_class_entry *pt_ce_typehint_helper;
void pt_register_typehint_helper();

/* merged from the parallel port branch */
/* the static combinator (TypeCombinator.cpp) */
extern zend_class_entry *pt_ce_type_combinator;
/* TypeCombinator after the whole Type family (its bodies instantiate the
 * compound, array and accessory classes); TypeCombinatorCache.cpp calls its
 * do*() entry points directly */
void pt_register_type_combinator();
namespace zv { class Val; }
/* TypeCombinator::<lcname>(...$args) — the public entry points answered in
 * C++ (union, intersect, remove, removeNull, addNull, containsNull,
 * removeFalsey, removeTruthy, countConstantArrayValueTypes, clearCache),
 * anything else through the class entry; the arguments borrowed; UNDEF =
 * pending exception */
zv::Val pt_type_combinator_call(const char *lcname, size_t len, uint32_t argc, zval *argv);
/* the same with the arguments spread from a PHP array
 * (`TypeCombinator::union(...$types)`) */
zv::Val pt_type_combinator_call_spread(const char *lcname, size_t len, HashTable *args);
/* the hot entry points directly: TypeCombinator::union(...$types) /
 * intersect(...$types) (memoized when the twin's $cacheEnabled says so) /
 * remove($fromType, $typeToRemove) / removeNull($type) / addNull($type) /
 * containsNull($type) (false = pending exception), and the unmemoized
 * doUnion() / doIntersect() / doRemove() bodies TypeCombinatorCache
 * computes a miss with; the arguments borrowed; UNDEF = pending exception */
zv::Val pt_type_combinator_union(uint32_t argc, zval *argv);
zv::Val pt_type_combinator_intersect(uint32_t argc, zval *argv);
zv::Val pt_type_combinator_remove(zval *fromType, zval *typeToRemove);
zv::Val pt_type_combinator_remove_null(zval *type);
zv::Val pt_type_combinator_add_null(zval *type);
bool pt_type_combinator_contains_null(zval *type, bool &out);
zv::Val pt_type_combinator_do_union(uint32_t argc, zval *argv);
zv::Val pt_type_combinator_do_intersect(uint32_t argc, zval *argv);
zv::Val pt_type_combinator_do_remove(zval *fromType, zval *typeToRemove);
/* TypeCombinatorCache::union(...$types) / intersect(...$types) /
 * remove($fromType, $typeToRemove) / clearCache() (TypeCombinatorCache.cpp):
 * the memoized operations, the arguments borrowed; UNDEF = pending
 * exception */
zv::Val pt_type_combinator_cache_union(uint32_t argc, zval *argv);
zv::Val pt_type_combinator_cache_intersect(uint32_t argc, zval *argv);
zv::Val pt_type_combinator_cache_remove(zval *fromType, zval *typeToRemove);
void pt_type_combinator_cache_clear();

/* merged from the parallel port branch */
/* merged from the parallel port branch */
/* the template-type helper classes (TemplateTypeVariance.cpp,
 * TemplateTypeVarianceMap.cpp, TemplateTypeMap.cpp, TemplateTypeScope.cpp,
 * TemplateTypeReference.cpp, TemplateTypeHelper.cpp) */
extern zend_class_entry *pt_ce_template_type_variance;
extern zend_class_entry *pt_ce_template_type_variance_map;
extern zend_class_entry *pt_ce_template_type_map;
extern zend_class_entry *pt_ce_template_type_scope;
extern zend_class_entry *pt_ce_template_type_reference;
extern zend_class_entry *pt_ce_template_type_helper;
/* registered at the end of the Type block, after FiniteTypeSet:
 * TemplateTypeVariance first (the maps', the reference's and the helper's
 * signatures name it), then TemplateTypeVarianceMap, TemplateTypeMap,
 * TemplateTypeScope, TemplateTypeReference and TemplateTypeHelper (its
 * signatures name the maps) */
void pt_register_template_type_variance();
void pt_register_template_type_variance_map();
void pt_register_template_type_map();
void pt_register_template_type_scope();
void pt_register_template_type_reference();
void pt_register_template_type_helper();
/* the twin's private variance constants */
#define PT_TEMPLATE_TYPE_VARIANCE_INVARIANT 1
#define PT_TEMPLATE_TYPE_VARIANCE_COVARIANT 2
#define PT_TEMPLATE_TYPE_VARIANCE_CONTRAVARIANT 3
#define PT_TEMPLATE_TYPE_VARIANCE_STATIC 4
#define PT_TEMPLATE_TYPE_VARIANCE_BIVARIANT 5
/* TemplateTypeVariance::create*() for a PT_TEMPLATE_TYPE_VARIANCE_* value —
 * the twin's singletons, held in its static $registry; borrowed zval,
 * callers copy; NULL = pending exception */
[[nodiscard]] zval *pt_template_type_variance_singleton(zend_long value);
/* the $value of a variance instance: the slot of a native instance, the
 * is-queries of anything else (the PHP twin declared next to the native
 * class in the differential tests); false = pending exception */
[[nodiscard]] bool pt_template_type_variance_value_of(zval *variance, zend_long &out);
/* $self->compose($other) — natively for a native $self, through the
 * method otherwise; false = pending exception */
[[nodiscard]] bool pt_template_type_variance_compose(zval *out, zval *self, zval *other);
/* TemplateTypeVarianceMap::createEmpty() / new TemplateTypeVarianceMap($variances)
 * — the twin's singleton (held in its static $empty) and a fresh instance
 * ($variances borrowed, checked as the twin's `array` parameter); false =
 * pending exception */
bool pt_template_type_variance_map_empty(zval *out);
bool pt_template_type_variance_map_new(zval *out, zval *variances);
/* TemplateTypeMap::createEmpty() / new TemplateTypeMap($types,
 * $lowerBoundTypes) — the twin's singleton (held in its static $empty) and
 * a fresh instance (the arrays borrowed, checked as the twin's `array`
 * parameters; $lowerBoundTypes NULL for the default []); false = pending
 * exception */
[[nodiscard]] bool pt_template_type_map_empty(zval *out);
bool pt_template_type_map_new(zval *out, zval *types, zval *lowerBoundTypes = NULL);
/* new TemplateTypeScope($className, $functionName) — the twin's private
 * constructor behind its create*() factories (NULL for null, the strings
 * borrowed); false = pending exception */
[[nodiscard]] bool pt_template_type_scope_new(zval *out, zend_string *className, zend_string *functionName);
/* $self->equals($other) — natively for two native scopes, through the
 * method otherwise; false = pending exception */
[[nodiscard]] bool pt_template_type_scope_equals(zval *self, zval *other, bool &out);
/* new TemplateTypeReference($type, $positionVariance) (both borrowed,
 * checked as the twin's typed parameters check them); false = pending
 * exception */
[[nodiscard]] bool pt_template_type_reference_new(zval *out, zval *type, zval *positionVariance);
/* $scope->equals(TemplateTypeScope::createWithAnonymousFunction()) —
 * natively for a native scope, through the scope's own class otherwise;
 * false = pending exception */
[[nodiscard]] bool pt_template_type_scope_is_anonymous(zval *scope, bool &out);

/* merged from the parallel port branch */
/* merged from the parallel port branch */
/* the late-resolvable family (KeyOfType.cpp, ValueOfType.cpp,
 * OffsetAccessType.cpp, ClassConstantAccessType.cpp, NewObjectType.cpp,
 * ConditionalType.cpp, ConditionalTypeForParameter.cpp,
 * LateResolvableArrayShapeType.cpp) and the observation-pass marker
 * (UnresolvedTemplateArgumentType.cpp) */
extern zend_class_entry *pt_ce_key_of_type;
extern zend_class_entry *pt_ce_value_of_type;
extern zend_class_entry *pt_ce_offset_access_type;
extern zend_class_entry *pt_ce_class_constant_access_type;
extern zend_class_entry *pt_ce_new_object_type;
extern zend_class_entry *pt_ce_conditional_type;
extern zend_class_entry *pt_ce_conditional_type_for_parameter;
extern zend_class_entry *pt_ce_late_resolvable_array_shape_type;
extern zend_class_entry *pt_ce_unresolved_template_argument_type;
/* registered at the end of the Type block (their bodies instantiate the
 * scalar, compound and array classes); ConditionalType before
 * ConditionalTypeForParameter (whose toConditional() instantiates it) */
void pt_register_key_of_type();
void pt_register_value_of_type();
void pt_register_offset_access_type();
void pt_register_class_constant_access_type();
void pt_register_new_object_type();
void pt_register_conditional_type();
void pt_register_conditional_type_for_parameter();
void pt_register_late_resolvable_array_shape_type();
void pt_register_unresolved_template_argument_type();
/* new KeyOfType($type) / new ValueOfType($type) / new OffsetAccessType($type,
 * $offset) / new ClassConstantAccessType($type, $constantName) / new
 * NewObjectType($type) / new ConditionalType($subject, $target, $if, $else,
 * $negated) / new ConditionalTypeForParameter($parameterName, $target, $if,
 * $else, $negated) / LateResolvableArrayShapeType::create($items, $unsealed,
 * $kind) ($unsealed NULL for null) / new UnresolvedTemplateArgumentType($site,
 * $templateType, $initialType) ($initialType NULL for null) — instances of
 * the shadowing classes (every argument borrowed, checked as the twins'
 * typed parameters check them); false = pending exception */
[[nodiscard]] bool pt_key_of_type_new(zval *out, zval *type);
bool pt_value_of_type_new(zval *out, zval *type);
bool pt_offset_access_type_new(zval *out, zval *type, zval *offset);
bool pt_class_constant_access_type_new(zval *out, zval *type, zend_string *constantName);
bool pt_new_object_type_new(zval *out, zval *type);
bool pt_conditional_type_new(zval *out, zval *subject, zval *target, zval *ifType, zval *elseType, bool negated);
bool pt_conditional_type_for_parameter_new(zval *out, zend_string *parameterName, zval *target, zval *ifType, zval *elseType, bool negated);
/* ConditionalTypeForParameter::resolveInType($type, fn ($name) => $passedArgs[$name] ?? null); UNDEF = pending exception */
zv::Val pt_conditional_type_for_parameter_resolve_in_type_with_args(zval *type, zval *passedArgs);
/* ConditionalTypeForParameter::resolveInType($type, $getSubjectType); UNDEF = pending exception */
zv::Val pt_conditional_type_for_parameter_resolve_in_type(zval *type, zval *getSubjectType);
/* $type->narrowTemplateType($templateType) for a ConditionalTypeForParameter; UNDEF = pending exception */
zv::Val pt_conditional_type_for_parameter_narrow_template_type(zval *type, zval *templateType);
bool pt_late_resolvable_array_shape_type_create(zval *out, zval *items, zval *unsealed, zend_string *kind);
bool pt_unresolved_template_argument_type_new(zval *out, zval *site, zval *templateType, zval *initialType);

/* merged from the parallel port branch */
/* the template family (TemplateTypeArgumentStrategy.cpp,
 * TemplateTypeParameterStrategy.cpp, the Template*Type.cpp files) */
extern zend_class_entry *pt_ce_template_type_argument_strategy;
extern zend_class_entry *pt_ce_template_type_parameter_strategy;
extern zend_class_entry *pt_ce_template_array_type;
extern zend_class_entry *pt_ce_template_benevolent_union_type;
extern zend_class_entry *pt_ce_template_boolean_type;
extern zend_class_entry *pt_ce_template_constant_array_type;
extern zend_class_entry *pt_ce_template_constant_integer_type;
extern zend_class_entry *pt_ce_template_constant_string_type;
extern zend_class_entry *pt_ce_template_float_type;
extern zend_class_entry *pt_ce_template_generic_object_type;
extern zend_class_entry *pt_ce_template_integer_type;
extern zend_class_entry *pt_ce_template_intersection_type;
extern zend_class_entry *pt_ce_template_iterable_type;
extern zend_class_entry *pt_ce_template_mixed_type;
extern zend_class_entry *pt_ce_template_null_type;
extern zend_class_entry *pt_ce_template_object_shape_type;
extern zend_class_entry *pt_ce_template_object_type;
extern zend_class_entry *pt_ce_template_object_without_class_type;
extern zend_class_entry *pt_ce_template_strict_mixed_type;
extern zend_class_entry *pt_ce_template_string_type;
extern zend_class_entry *pt_ce_template_union_type;
/* registered after the whole Type family: the two strategies (the trait's
 * getStrategy() names their interface, toArgument() instantiates one),
 * then the Template*Type classes, each after its native parent (all of
 * which precede them) */
void pt_register_template_type_argument_strategy();
void pt_register_template_type_parameter_strategy();
void pt_register_template_array_type();
void pt_register_template_benevolent_union_type();
void pt_register_template_boolean_type();
void pt_register_template_constant_array_type();
void pt_register_template_constant_integer_type();
void pt_register_template_constant_string_type();
void pt_register_template_float_type();
void pt_register_template_generic_object_type();
void pt_register_template_integer_type();
void pt_register_template_intersection_type();
void pt_register_template_iterable_type();
void pt_register_template_mixed_type();
void pt_register_template_null_type();
void pt_register_template_object_shape_type();
void pt_register_template_object_type();
void pt_register_template_object_without_class_type();
void pt_register_template_strict_mixed_type();
void pt_register_template_string_type();
void pt_register_template_union_type();
/* new TemplateTypeArgumentStrategy() / new TemplateTypeParameterStrategy()
 * / new Template<X>Type($scope, $templateTypeStrategy, $templateTypeVariance,
 * $name, $bound, $default) — instances of the shadowing classes (every
 * argument borrowed, $default NULL or IS_NULL for null, $bound checked
 * against the twin's bound class); false = pending exception */
[[nodiscard]] bool pt_template_type_argument_strategy_new(zval *out);
bool pt_template_type_parameter_strategy_new(zval *out);
bool pt_template_array_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType);
bool pt_template_benevolent_union_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType);
bool pt_template_boolean_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType);
bool pt_template_constant_array_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType);
bool pt_template_constant_integer_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType);
bool pt_template_constant_string_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType);
bool pt_template_float_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType);
bool pt_template_generic_object_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType);
bool pt_template_integer_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType);
bool pt_template_intersection_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType);
bool pt_template_iterable_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType);
bool pt_template_mixed_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType);
bool pt_template_null_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType);
bool pt_template_object_shape_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType);
bool pt_template_object_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType);
bool pt_template_object_without_class_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType);
bool pt_template_strict_mixed_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType);
bool pt_template_string_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType);
bool pt_template_union_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType);
/* the static template helpers (TemplateTypeFactory.cpp,
 * TypeProjectionHelper.cpp), registered after the Template*Type classes
 * the factory instantiates */
extern zend_class_entry *pt_ce_template_type_factory;
extern zend_class_entry *pt_ce_type_projection_helper;
void pt_register_template_type_factory();
void pt_register_type_projection_helper();
namespace zv { class Val; }
/* TypeProjectionHelper::describe($type, $variance, $level) ($variance NULL
 * or IS_NULL for null); an owned string, UNDEF = pending exception */
zv::Val pt_type_projection_helper_describe(zval *type, zval *variance, zval *level);

/* merged from the parallel port branch */
/* merged from the parallel port branch */
/* the Type-namespace helper classes (ConstantArrayTypeBuilder.cpp,
 * UnionTypeHelper.cpp, ConstantTypeHelper.cpp, StaticTypeFactory.cpp,
 * TypeResult.cpp, CallableTypeHelper.cpp) and the late-resolvable
 * GetTemplateTypeType (GetTemplateTypeType.cpp) */
extern zend_class_entry *pt_ce_constant_array_type_builder;
extern zend_class_entry *pt_ce_union_type_helper;
extern zend_class_entry *pt_ce_constant_type_helper;
extern zend_class_entry *pt_ce_static_type_factory;
extern zend_class_entry *pt_ce_type_result;
extern zend_class_entry *pt_ce_callable_type_helper;
extern zend_class_entry *pt_ce_get_template_type_type;
/* registered at the end of the Type block (their signatures name
 * ConstantArrayType, IsSuperTypeOfResult and the Type classes their
 * bodies instantiate): the builder first (ConstantTypeHelper's body uses
 * it), then the helpers, TypeResult, and GetTemplateTypeType last (its
 * trait methods' return types name BooleanType, EnumCaseObjectType and
 * TemplateTypeMap) */
void pt_register_constant_array_type_builder();
void pt_register_union_type_helper();
void pt_register_constant_type_helper();
void pt_register_static_type_factory();
void pt_register_type_result();
void pt_register_callable_type_helper();
void pt_register_get_template_type_type();
/* the twin's `static $falsey` & co. — the memoized types StaticTypeFactory
 * hands out, held per request */
void pt_static_type_factory_rinit();
void pt_static_type_factory_rshutdown();
/* the twin's public const ARRAY_COUNT_LIMIT — the one place the native
 * code reads it from (the class constant is declared with the same value) */
#define PT_CONSTANT_ARRAY_TYPE_BUILDER_ARRAY_COUNT_LIMIT 256
/* ConstantArrayTypeBuilder::createEmpty() / createFromConstantArray($array)
 * ($array borrowed, checked as the twin's typed parameter); UNDEF = pending
 * exception */
zv::Val pt_constant_array_type_builder_create_empty();
zv::Val pt_constant_array_type_builder_create_from_constant_array(zval *array);
/* $builder->setOffsetValueType($offsetType, $valueType, $optional) /
 * ->makeUnsealed($keyType, $valueType) / ->degradeToGeneralArray($oversized)
 * / ->getArray() — natively for a native builder, through the method
 * otherwise ($offsetType NULL or IS_NULL for null, the Types borrowed);
 * false / UNDEF = pending exception */
[[nodiscard]] bool pt_constant_array_type_builder_set_offset_value_type(zval *builder, zval *offsetType, zval *valueType, bool optional = false);
bool pt_constant_array_type_builder_make_unsealed(zval *builder, zval *keyType, zval *valueType);
bool pt_constant_array_type_builder_degrade_to_general_array(zval *builder, bool oversized = false);
zv::Val pt_constant_array_type_builder_get_array(zval *builder);
/* UnionTypeHelper::sortTypes($types) ($types a borrowed list of Types);
 * UNDEF = pending exception */
zv::Val pt_union_type_helper_sort_types(zval *types);
/* ConstantTypeHelper::getTypeFromValue($value) ($value borrowed); UNDEF =
 * pending exception */
zv::Val pt_constant_type_helper_get_type_from_value(zval *value);
/* StaticTypeFactory::falsey() / truthy() — copies of the memoized types;
 * UNDEF = pending exception */
zv::Val pt_static_type_factory_falsey();
zv::Val pt_static_type_factory_truthy();
/* new TypeResult($type, $reasons) (both borrowed, checked as the twin's
 * typed parameters); UNDEF = pending exception */
zv::Val pt_type_result_new(zval *type, zval *reasons);
/* CallableTypeHelper::isParametersAcceptorSuperTypeOf($ours, $theirs,
 * $treatMixedAsAny, $strictTypes) (the acceptors borrowed); UNDEF = pending
 * exception */
zv::Val pt_callable_type_helper_is_parameters_acceptor_super_type_of(zval *ours, zval *theirs, bool treatMixedAsAny, bool strictTypes = true);
/* new GetTemplateTypeType($type, $ancestorClassName, $templateTypeName)
 * (borrowed); UNDEF = pending exception */
zv::Val pt_get_template_type_type_new(zval *type, zend_string *ancestorClassName, zend_string *templateTypeName);

/* TemplateKeyOfType.cpp — registered after KeyOfType and the rest of the
 * template family (its parent, and the factory that instantiates it) */
extern zend_class_entry *pt_ce_template_key_of_type;
void pt_register_template_key_of_type();
/* new TemplateKeyOfType($scope, $templateTypeStrategy, $templateTypeVariance,
 * $name, $bound, $default) — an instance of the shadowing class (every
 * argument borrowed, $default NULL or IS_NULL for null, $bound checked
 * against KeyOfType); false = pending exception */
[[nodiscard]] bool pt_template_key_of_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType);

/* ClassReflectionAccess.cpp — native readers of the memo slots of
 * PHPStan\Reflection\ClassReflection (a userland final class) and of a
 * MutatingScope's ScopeContext: the Type kernel's hottest native->PHP calls
 * (getName()/isGeneric()/hasMethod()/getCacheKey() on class reflections,
 * isInClass()/getClassReflection() on scopes), answered from the twin's own
 * property slot when it holds the memoized answer and through the PHP
 * method otherwise, so the observable behaviour (lazy computation, the
 * Error on an uninitialized slot, a subclass's override) stays the twin's */
void pt_class_reflection_access_rinit();
/* $classReflection->getName() / ->getCacheKey() / ->getNativeReflection();
 * UNDEF = pending exception */
zv::Val pt_class_reflection_get_name(zend_object *classReflection);
zv::Val pt_class_reflection_get_cache_key(zend_object *classReflection);
zv::Val pt_class_reflection_get_native_reflection(zend_object *classReflection);
/* $classReflection->isGeneric() / ->hasMethod($methodName) /
 * ->hasFinalByKeywordOverride() / ->isEnum(), coerced to bool as the call
 * sites always did; false = pending exception */
[[nodiscard]] bool pt_class_reflection_is_generic(zend_object *classReflection, bool &out);
bool pt_class_reflection_has_method(zend_object *classReflection, zval *methodName, bool &out);
bool pt_class_reflection_has_final_by_keyword_override(zend_object *classReflection, bool &out);
bool pt_class_reflection_is_enum(zend_object *classReflection, bool &out);
/* $scope->isInClass() (coerced to bool) / ->getClassReflection(); false /
 * UNDEF = pending exception */
[[nodiscard]] bool pt_scope_is_in_class(zend_object *scope, bool &out);
zv::Val pt_scope_get_class_reflection(zend_object *scope);

/* ScopeContext.cpp: the shadowing class entry, and the $classReflection
 * slot of one of its instances (borrowed; IS_NULL outside a class) */
extern zend_class_entry *pt_ce_scope_context;
zval *pt_scope_context_class_reflection(zend_object *context);

/* LruCache.cpp — registered at the END of the sequence (it names no
 * shadowed class; ObjectType's description-key LRU instantiates it at run
 * time) */
extern zend_class_entry *pt_ce_lru_cache;
void pt_register_lru_cache();
/* new LruCache($maxCount, $maxWeight, $weightEvictionFloorCount) — an
 * instance of the shadowing class; false = pending exception */
[[nodiscard]] bool pt_lru_cache_new(zval *out, zend_long maxCount = 0, zend_long maxWeight = 0, zend_long weightEvictionFloorCount = 0);
/* $cache->get($key) / ->set($key, $value, $weight) / ->replace($key,
 * $value) / ->count() / ->all() — natively for a native cache, through the
 * method otherwise ($key and $value borrowed); UNDEF / false / -1 = pending
 * exception */
zv::Val pt_lru_cache_get(zval *cache, zend_string *key);
zv::Val pt_lru_cache_set(zval *cache, zend_string *key, zval *value, zend_long weight);
bool pt_lru_cache_replace(zval *cache, zend_string *key, zval *value);
zend_long pt_lru_cache_count(zval *cache);
zv::Val pt_lru_cache_all(zval *cache);

/* {{{ ReflectionAccess.cpp: slot readers of the PHP reflection provider */

void pt_reflection_access_rinit();
/* ReflectionProviderStaticAccessor::getInstance() — the registered provider
 * out of the static slot; the method (which throws) while none is
 * registered; UNDEF = pending exception */
zv::Val pt_reflection_provider_instance();
/* $provider->hasClass($className) / ->getClass($className) — the memoized
 * answer of a MemoizingReflectionProvider, the method on a miss or on any
 * other provider; false / UNDEF = pending exception */
[[nodiscard]] bool pt_reflection_provider_has_class(zend_object *provider, zval *className, bool &out);
zv::Val pt_reflection_provider_has_class_zv(zend_object *provider, zval *className);
zv::Val pt_reflection_provider_get_class(zend_object *provider, zval *className);

/* }}} */

/* {{{ the shadowing reflection value classes (UnresolvableTypeHelper.cpp,
 * NativeParameterReflection.cpp, CalledOnTypeUnresolved{Method,Property}PrototypeReflection.cpp,
 * CallbackUnresolved{Method,Property}PrototypeReflection.cpp) */

extern zend_class_entry *pt_ce_unresolvable_type_helper;
extern zend_class_entry *pt_ce_native_parameter_reflection;
extern zend_class_entry *pt_ce_called_on_type_unresolved_method_prototype_reflection;
extern zend_class_entry *pt_ce_called_on_type_unresolved_property_prototype_reflection;
extern zend_class_entry *pt_ce_callback_unresolved_method_prototype_reflection;
extern zend_class_entry *pt_ce_callback_unresolved_property_prototype_reflection;

/* registered after the Type family: their signatures name TemplateTypeMap
 * and the Type interface */
void pt_register_unresolvable_type_helper();
void pt_register_native_parameter_reflection();
void pt_register_called_on_type_unresolved_method_prototype_reflection();
void pt_register_called_on_type_unresolved_property_prototype_reflection();
void pt_register_callback_unresolved_method_prototype_reflection();
void pt_register_callback_unresolved_property_prototype_reflection();

/* new <Class>(...$argv) — instances of the shadowing classes over values
 * as PHP code hands them (borrowed): directly when the arguments already
 * have the constructor's parameter types, through its parameter parsing
 * otherwise; UNDEF = pending exception */
zv::Val pt_native_parameter_reflection_new(uint32_t argc, zval *argv);
zv::Val pt_called_on_type_unresolved_method_prototype_reflection_new(uint32_t argc, zval *argv);
zv::Val pt_called_on_type_unresolved_property_prototype_reflection_new(uint32_t argc, zval *argv);
zv::Val pt_callback_unresolved_method_prototype_reflection_new(uint32_t argc, zval *argv);
zv::Val pt_callback_unresolved_property_prototype_reflection_new(uint32_t argc, zval *argv);

/* }}} */

/* the shadowing ExpressionResultStorage (ExpressionResultStorage.cpp) —
 * $storage->findExpressionResult($expr): the native body for a native
 * storage, the method of anything else (the PHP twin under the prefixed
 * differential activation); UNDEF = pending exception */
extern zend_class_entry *pt_ce_expression_result_storage;
/* VolatileExpressionHelper.cpp — the shadowing class entry (MutatingScope
 * calls its statics directly) */
extern zend_class_entry *pt_ce_volatile_expression_helper;
zv::Val pt_expression_result_storage_find(zval *storage, zval *expr);

/* merged from the parallel port branch */
/* the native ClassReflection (ClassReflection.cpp); until the flip the
 * plan is declared by the prefixed activation of the differential tests
 * only (reg::Class::shadowDifferentialOnly()), so pt_ce_class_reflection
 * stays NULL in a production run and the slot readers of
 * ClassReflectionAccess.cpp keep serving the Type kernel */
extern zend_class_entry *pt_ce_class_reflection;
void pt_register_class_reflection();
/* TypehintHelper::decideTypeFromReflection() for native callers (every
 * argument borrowed, NULL for a null / the default); UNDEF = pending
 * exception */
zv::Val pt_typehint_helper_decide_type_from_reflection(zval *reflectionType, zval *phpDocType = NULL, zval *selfClass = NULL, bool isVariadic = false);

/* merged from the parallel port branch */
extern zend_string *pt_str_end_file_pos;

/* the same for a bare byte range */
bool pt_is_superglobal_cstr(const char *name, size_t len);
/* the superglobal names (Scope::SUPERGLOBAL_VARIABLES), in the twin's order */
typedef struct _pt_superglobal_name { const char *name; size_t len; } pt_superglobal_name;
const pt_superglobal_name *pt_superglobal_names(size_t *count);

/* $call->isFirstClassCallable() of a PhpParser CallLike node, read from its
 * args: a single VariadicPlaceholder argument; false = pending exception */
[[nodiscard]] bool pt_call_like_is_first_class_callable(zend_object *call, bool &out);

/* {{{ the NodeScopeResolver-adjacent helper services (VolatileExpressionHelper.cpp,
 * VariableFlow.cpp, VariableFlowBuilder.cpp) — registered at the END of the
 * sequence (their signatures name the Type interface and their own classes;
 * VariableFlow before VariableFlowBuilder, whose return types name it) */

extern zend_class_entry *pt_ce_variable_flow;
void pt_register_volatile_expression_helper();
void pt_register_variable_flow();
void pt_register_variable_flow_builder();
void pt_register_variable_liveness_resolver();
/* ExpressionResult.cpp — registered after VariableFlow (its signatures name
 * it) */
extern zend_class_entry *pt_ce_expression_result;
void pt_register_expression_result();
/* $result->getVariableFlow() — the slot of a native result, the method
 * otherwise; UNDEF = pending exception */
zv::Val pt_expression_result_variable_flow(zval *result);
/* the per-request slot cache of VariableFlow.cpp (the class entry of the
 * PHP VariableWrite class) */
void pt_variable_flow_rinit();
/* the property slots (OBJ_PROP byte offsets) of PHPStan\Node\Variable\VariableWrite,
 * a final PHP class whose getters return its promoted properties: for an
 * object that is exactly that class with every slot initialized the slots
 * answer the getters; NULL otherwise (the caller calls the getters, which
 * answer — or throw — the way the twin's calls did), with `error` set when
 * the class map cannot resolve the class at all (exception pending) */
typedef struct _pt_variable_write_slots {
	zend_class_entry *ce;
	uint32_t variableName;
	uint32_t node;
	uint32_t id;
	uint32_t kind;
	uint32_t offsetWrite;
	uint32_t offset;
	uint32_t parentId;
	uint32_t replacesOffset;
} pt_variable_write_slots;
const pt_variable_write_slots *pt_variable_write_slots_of(zend_object *write, bool &error);
/* $storage->findExpressionResult($expr) — natively for a native storage,
 * through the method otherwise ($expr borrowed); the result or null, UNDEF
 * = pending exception */
/* VariableFlow::sequence(...$flows) / read($name, $targetId, $container,
 * $offset) / write($write, $redundantType) / escape($name) / dead($flow) /
 * throwing($type, $canContinue, $canContainAnyThrowable) — the twin's
 * factories (every argument borrowed, NULL for a null); a flow, PHP null
 * where the twin returns null, UNDEF = pending exception */
zv::Val pt_variable_flow_sequence(uint32_t argc, zval *argv);
/* VariableFlow::sequence(...$flows) spread from a PHP list */
zv::Val pt_variable_flow_sequence_list(HashTable *flows);
zv::Val pt_variable_flow_read(zend_string *name, zval *targetId, bool container, zval *offset);
zv::Val pt_variable_flow_write(zval *write, zval *redundantType);
zv::Val pt_variable_flow_escape(zend_string *name);
zv::Val pt_variable_flow_dead(zval *flow);
zv::Val pt_variable_flow_throwing(zval *type, bool canContinue, bool canContainAnyThrowable);

/* }}} */

#endif /* PHPSTANTURBO_SUPPORT_H */
