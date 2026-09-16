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
	PT_CLASS_CONDITIONAL_TYPE_RESOLVER,
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
	PT_CLASS_CALLABLE_ASSERTIONS_HELPER,
	PT_CLASS_CALLABLE_PARAMETERS_ACCEPTOR,
	PT_CLASS_ASSERTIONS,
	PT_CLASS_SIMPLE_THROW_POINT,
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
	PT_CLASS_REFLECTION_ENUM,
	PT_CLASS_MEMOIZING_REFLECTION_PROVIDER,
	PT_CLASS_UNRESOLVABLE_TYPE_RESULT,
	PT_CLASS_EXTENDED_FUNCTION_VARIANT,
	PT_CLASS_RESOLVED_PROPERTY_REFLECTION,
	PT_CLASS_CHANGED_TYPE_PROPERTY_REFLECTION,
	PT_CLASS_UNDEFINED_VARIABLE_EXCEPTION,
	PT_CLASS_NODE_CALLBACK_SCOPE,
	PT_CLASS_PROPERTY_INITIALIZATION_EXPR,
	PT_CLASS_POSSIBLY_IMPURE_CALL_EXPR,
	PT_CLASS_CONST_FETCH,
	PT_CLASS_HALT_COMPILER,
	PT_CLASS_INITIALIZER_EXPR_CONTEXT,
	PT_CLASS_EXTENDED_PARAMETERS_ACCEPTOR,
	PT_CLASS_MATCH,
	PT_CLASS_NULLSAFE_METHOD_CALL,
	PT_CLASS_STATIC_PROPERTY_FETCH,
	PT_CLASS_CLASS_CONST_FETCH,
	PT_CLASS_SCALAR_STRING,
	PT_CLASS_SCALAR_INT,
	PT_CLASS_SCALAR_FLOAT,
	PT_CLASS_VAR_LIKE_IDENTIFIER,
	PT_CLASS_EXTENDED_METHOD_REFLECTION,
	PT_CLASS_ARG,
	PT_CLASS_FUNCTION_REFLECTION,
	PT_CLASS_PHP_VERSIONS,
	PT_CLASS_PARAM,
	PT_CLASS_TRANSFORM_STATIC_TYPE_TRAVERSER,
	PT_CLASS_PHP_METHOD_FROM_PARSER_NODE_REFLECTION,
	PT_CLASS_PHP_FUNCTION_FROM_PARSER_NODE_REFLECTION,
	PT_CLASS_PARAMETER_VARIABLE_ORIGINAL_VALUE_EXPR,
	PT_CLASS_WRAPPED_EXTENDED_METHOD_REFLECTION,
	PT_CLASS_EXTENDED_PROPERTY_REFLECTION,
	PT_CLASS_WRAPPED_EXTENDED_PROPERTY_REFLECTION,
	PT_CLASS_ENUM_CASE_REFLECTION,
	PT_CLASS_REFLECTION_ENUM_BACKED_CASE,
	PT_CLASS_REAL_CLASS_CLASS_CONSTANT_REFLECTION,
	PT_CLASS_TYPE_ALIAS,
	PT_CLASS_CIRCULAR_TYPE_ALIAS_DEFINITION_EXCEPTION,
	PT_CLASS_VARIABLE_WRITE,
	PT_CLASS_LIST_EXPR,
	PT_CLASS_VARIABLE_WRITES_NODE,
	PT_CLASS_VOID_TO_NULL_TRAVERSER,
	PT_CLASS_ISSETABILITY_RESOLUTION,
	PT_CLASS_ISSETABILITY_LINK_INFO,
	PT_CLASS_ALWAYS_REMEMBERED_EXPR,
	PT_CLASS_PHP_PROPERTY_REFLECTION,
	PT_CLASS_NATIVE_METHOD_REFLECTION,
	PT_CLASS_EXTENDED_NATIVE_PARAMETER_REFLECTION,
	PT_CLASS_ENUM_CASES_METHOD_REFLECTION,
	PT_CLASS_PRIVATE_PROPERTY_ATTRIBUTE,
	PT_CLASS_PROTECTED_PROPERTY_ATTRIBUTE,
	PT_CLASS_ADAPTER_REFLECTION_METHOD,
	PT_CLASS_EXPRESSION_STMT,
	PT_CLASS_ASSIGN_EXPR,
	PT_CLASS_NAMESPACE_STMT,
	PT_CLASS_DECLARE_STMT,
	PT_CLASS_CLASS_METHOD_STMT,
	PT_CLASS_ADAPTER_REFLECTION_CLASS,
	PT_CLASS_BETTER_REFLECTION_CLASS,
	PT_CLASS_ORIGINAL_FOREACH_VALUE_EXPR,
	PT_CLASS_ORIGINAL_FOREACH_KEY_EXPR,
	PT_CLASS_SET_EXISTING_OFFSET_VALUE_TYPE_EXPR,
	PT_CLASS_NATIVE_TYPE_EXPR,
	PT_CLASS_CLONE_REINITIALIZATION_EXPR,
	PT_CLASS_METHOD_REFLECTION,
	PT_CLASS_PRE_INC,
	PT_CLASS_PRE_DEC,
	PT_CLASS_POST_INC,
	PT_CLASS_POST_DEC,
	PT_CLASS_ISSET_EXPR,
	PT_CLASS_EMIT_COLLECTED_DATA_NODE,
	PT_CLASS_LAZY_CLASS_REFLECTION_EXTENSION_REGISTRY_PROVIDER,
	PT_CLASS_CLASS_REFLECTION_EXTENSION_REGISTRY,
	PT_CLASS_LAZY_INTERNAL_SCOPE_FACTORY,
	/* the php-parser node classes the PHPStan\Parser\*Visitor ports check */
	PT_CLASS_MAGIC_CONST,
	PT_CLASS_ASSIGN_REF_EXPR,
	PT_CLASS_ASSIGN_OP_EXPR,
	PT_CLASS_TRAIT_STMT,
	PT_CLASS_INLINE_HTML_STMT,
	PT_CLASS_INTERPOLATED_STRING,
	PT_CLASS_INSTANCEOF_EXPR,
	PT_CLASS_TRY_CATCH_STMT,
	PT_CLASS_CATCH_STMT,
	PT_CLASS_CLASS_PROPERTY_NODE,
	PT_CLASS_PROPERTY_ASSIGN_NODE,
	PT_CLASS_METHOD_RETURN_STATEMENTS_NODE,
	PT_CLASS_METHOD_CALLABLE_NODE,
	PT_CLASS_STATIC_METHOD_CALLABLE_NODE,
	PT_CLASS_FUNCTION_CALLABLE_NODE,
	PT_CLASS_INSTANTIATION_CALLABLE_NODE,
	PT_CLASS_SET_OFFSET_VALUE_TYPE_EXPR,
	PT_CLASS_GATHERED_METHOD_CALL,
	PT_CLASS_PROPERTY_READ,
	PT_CLASS_PROPERTY_WRITE,
	PT_CLASS_PROPERTY_ASSIGN,
	PT_CLASS_GATHERED_CLASS_METHOD,
	PT_CLASS_CLASS_CONSTANT_FETCH,
	PT_CLASS_COALESCE_ASSIGN_OP_EXPR,
	PT_CLASS_CLASS_CONST_STMT,
	PT_CLASS_EXPR_HANDLER,
	PT_CLASS_STMT_HANDLER,
	PT_CLASS_CONTINUE_STMT,
	PT_CLASS_BREAK_STMT,
	PT_CLASS_RESOLVED_FUNCTION_VARIANT,
	PT_CLASS_EXTENSION_CLASS_HELPER,
	PT_CLASS_LAZY_EXTENSIONS_COLLECTION,
	PT_CLASS_BOOLEAN_AND_EXPR,
	PT_CLASS_LOGICAL_AND_EXPR,
	PT_CLASS_BOOLEAN_OR_EXPR,
	PT_CLASS_LOGICAL_OR_EXPR,
	PT_CLASS_PARSER_ISSET_EXPR,
	PT_CLASS_NULLSAFE_OPERATOR_HELPER,
	PT_CLASS_COALESCE_EXPR,
	PT_CLASS_TYPE_EXPR,
	PT_CLASS_IDENTICAL_EXPR,
	/* the walk hub (NodeScopeResolver.cpp, StatementsHandler.cpp,
	 * NonNullabilityHelper.cpp) */
	PT_CLASS_STATIC_STMT,
	PT_CLASS_GLOBAL_STMT,
	PT_CLASS_PROPERTY_STMT,
	PT_CLASS_CONST_STMT,
	PT_CLASS_CLASS_LIKE_STMT,
	PT_CLASS_FUNCTION_STMT,
	PT_CLASS_ECHO_STMT,
	PT_CLASS_FOREACH_STMT,
	PT_CLASS_IF_STMT,
	PT_CLASS_RETURN_STMT,
	PT_CLASS_SWITCH_STMT,
	PT_CLASS_UNSET_STMT,
	PT_CLASS_WHILE_STMT,
	PT_CLASS_DO_STMT,
	PT_CLASS_FOR_STMT,
	PT_CLASS_LABEL_STMT,
	PT_CLASS_NOP_STMT,
	PT_CLASS_GOTO_STMT,
	PT_CLASS_EVAL_EXPR,
	PT_CLASS_INCLUDE_EXPR,
	PT_CLASS_DOC_COMMENT,
	PT_CLASS_NODE_FINDER,
	PT_CLASS_NODE_ABSTRACT,
	PT_CLASS_PHP_METHOD_REFLECTION,
	PT_CLASS_NOOP_NODE_CALLBACK,
	PT_CLASS_FUNCTION_CALL_EXPRESSION_NODE,
	PT_CLASS_METHOD_CALL_EXPRESSION_NODE,
	PT_CLASS_STATIC_METHOD_CALL_EXPRESSION_NODE,
	PT_CLASS_EXECUTION_END_NODE,
	PT_CLASS_UNREACHABLE_STATEMENT_NODE,
	PT_CLASS_VAR_TAG_CHANGED_EXPRESSION_TYPE_NODE,
	PT_CLASS_PROPERTY_HOOK_STATEMENT_NODE,
	PT_CLASS_TEMPLATE_ARGUMENT_CONSTRAINTS,
	PT_CLASS_TEMPLATE_ARGUMENT_STATS,
	PT_CLASS_ENSURED_NON_NULLABILITY_RESULT,
	PT_CLASS_ENSURED_NON_NULLABILITY_RESULT_EXPRESSION,
	PT_CLASS_RESOLVED_FUNCTION_VARIANT_WITH_ORIGINAL,
	PT_CLASS_INVALIDATE_EXPR_NODE,
	/* the assignment handlers (AssignHandler.cpp, AssignOpHandler.cpp) */
	PT_CLASS_TERNARY_EXPR,
	PT_CLASS_BINARY_OP_MINUS,
	PT_CLASS_BINARY_OP_PLUS,
	PT_CLASS_BINARY_OP_NOT_IDENTICAL,
	PT_CLASS_ASSIGN_OP_CONCAT,
	PT_CLASS_ASSIGN_OP_BITWISE_AND,
	PT_CLASS_ASSIGN_OP_BITWISE_OR,
	PT_CLASS_ASSIGN_OP_BITWISE_XOR,
	PT_CLASS_ASSIGN_OP_DIV,
	PT_CLASS_ASSIGN_OP_MOD,
	PT_CLASS_ASSIGN_OP_PLUS,
	PT_CLASS_ASSIGN_OP_MINUS,
	PT_CLASS_ASSIGN_OP_MUL,
	PT_CLASS_ASSIGN_OP_POW,
	PT_CLASS_ASSIGN_OP_SHIFT_LEFT,
	PT_CLASS_ASSIGN_OP_SHIFT_RIGHT,
	PT_CLASS_EXISTING_ARRAY_DIM_FETCH,
	PT_CLASS_VARIABLE_ASSIGN_NODE,
	PT_CLASS_VIRTUAL_ASSIGN_NODE_CALLBACK,
	PT_CLASS_COALESCE_EXPRESSION_NODE,
	PT_CLASS_THROW_EXPR,
	PT_CLASS_NOOP_EXPRESSION_NODE,
	PT_CLASS_BLOCK_STMT,
	PT_CLASS_INTERFACE_STMT,
	PT_CLASS_ENUM_STMT,
	PT_CLASS_NODE_TO_REFLECTION,
	PT_CLASS_LOCATED_SOURCE,
	PT_CLASS_BETTER_REFLECTION_ENUM,
	PT_CLASS_IN_CLASS_METHOD_NODE,
	PT_CLASS_IN_FUNCTION_NODE,
	PT_CLASS_FUNCTION_RETURN_STATEMENTS_NODE,
	PT_CLASS_RETURN_AFTER_FINALLY_NODE,
	PT_CLASS_RETURN_STATEMENT,
	PT_CLASS_IN_CLASS_NODE,
	PT_CLASS_CLASS_PROPERTIES_NODE,
	PT_CLASS_CLASS_METHODS_NODE,
	PT_CLASS_CLASS_CONSTANTS_NODE,
	PT_CLASS_FILE_READER,
	PT_CLASS_DUMMY_CONSTRUCTOR_REFLECTION,
	PT_CLASS_GENERIC_TYPE_TEMPLATE_TRAVERSER,
	PT_CLASS_CLOSURE_HANDLER,
	PT_CLASS_ALLOWED_CONSTANTS_RESULT,
	PT_CLASS_GENERIC_PARAMETERS_ACCEPTOR_RESOLVER,
	PT_CLASS_FUNCTION_VARIANT,
	PT_CLASS_EXTENDED_CALLABLE_FUNCTION_VARIANT,
	/* the function-call cluster (FuncCallHandler.cpp,
	 * FuncCallScopeEffectsHelper.cpp, FunctionReflectionAccess.cpp) */
	PT_CLASS_NATIVE_FUNCTION_REFLECTION,
	PT_CLASS_CLONE_HANDLER,
	PT_CLASS_CLONE_EXPR,
	PT_CLASS_CLOSURE_RETURN_STATEMENTS_NODE,
	PT_CLASS_BETTER_REFLECTION_PROVIDER,
	PT_CLASS_NULLSAFE_PROPERTY_FETCH_EXPRESSION_NODE,
	PT_CLASS_PHP_VERSION,
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
/* the PHPStan\Parser\*Visitor ports the native NodeTraverser dispatches
 * without an engine call (pt_native_visitor, ParserVisitors.h) */
void pt_register_array_filter_arg_visitor();
void pt_register_array_find_arg_visitor();
void pt_register_array_map_arg_visitor();
void pt_register_array_offset_normalizing_visitor();
void pt_register_array_walk_arg_visitor();
void pt_register_arrow_function_arg_visitor();
void pt_register_closure_arg_visitor();
void pt_register_closure_bind_arg_visitor();
void pt_register_closure_bind_to_var_visitor();
void pt_register_curl_set_opt_arg_visitor();
void pt_register_curl_set_opt_array_arg_visitor();
void pt_register_declare_position_visitor();
void pt_register_immediately_invoked_closure_visitor();
void pt_register_implode_arg_visitor();
void pt_register_magic_constant_param_default_visitor();
void pt_register_new_assigned_to_property_visitor();
void pt_register_parent_stmt_types_visitor();
void pt_register_trait_collecting_visitor();
void pt_register_try_catch_type_visitor();
void pt_register_type_traverser_instanceof_visitor();
void pt_register_scope_ops();
void pt_register_node_scanner();
void pt_register_expr_printer();
void pt_register_parser_runner();
void pt_register_type_combinator_cache();
void pt_register_arena_cache();
void pt_register_expression_result_storage();
void pt_register_expression_result_storage_stack();
void pt_register_class_statements_gatherer();
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
extern zend_string *pt_str_contains_call;
extern zend_string *pt_str_array_map_args;
extern zend_string *pt_str_start_file_pos;
void pt_init_strs();

zval *pt_node_attribute(zend_object *node, zend_string *name);
bool pt_node_set_attribute(zend_object *node, zend_string *name, zval *value);

/* the shadowing ExprPrinter (ExprPrinter.cpp) — the miss half of
 * ExprPrinter::printExpr(): $exprPrinter->printer->prettyPrintExpr($node)
 * plus the cache write, for a caller that has already taken the Variable
 * and attribute-cache fast paths. The native body for the shadowing
 * ExprPrinter, the twin's printExpr() (which re-takes those fast paths, to
 * the same result) for anything else. Returned string owned by caller; NULL
 * on failure (exception thrown). */
extern zend_class_entry *pt_ce_expr_printer;
zend_string *pt_expr_printer_print_uncached(zval *exprPrinter, zend_object *node);

/* Expression key for the node (MutatingScope::getNodeKey semantics); the
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

/* {{{ natively dispatched node visitors */

/*
 * Node visitors the native NodeTraverser dispatches in C++ instead of
 * crossing into the engine once per node: the PHPStan\Parser\*Visitor ports
 * whose enterNode()/leaveNode() always return null and whose beforeTraverse()
 * only resets state. Each native visitor registers its entry from its MINIT
 * registrar; NodeTraverser's per-traverse visitor plan looks the entry up by
 * class entry and calls the function pointers directly, so a visitor that
 * only reacts to one node type costs a type check instead of a PHP frame.
 *
 * A NULL hook falls back to the engine call — the table is an optimisation,
 * never the definition of what the visitor does (the PHP twin is).
 */
typedef bool (*pt_visitor_node_fn)(zend_object *visitor, zend_object *node); /* false = pending exception */
typedef void (*pt_visitor_reset_fn)(zend_object *visitor);

typedef struct _pt_native_visitor {
	/* the shadowing class's entry, filled at activation (reg::Class::shadow()) */
	zend_class_entry **ce;
	pt_visitor_node_fn enter;
	pt_visitor_node_fn leave;
	pt_visitor_reset_fn before;
} pt_native_visitor;

/* MINIT only; the entry must outlive the process (a file-static) */
void pt_native_visitor_register(const pt_native_visitor *entry);
/* NULL when the class is not one of the natively dispatched visitors */
const pt_native_visitor *pt_native_visitor_for(zend_class_entry *ce);
/* drops the class-entry index; called per request and after activation
 * declared the shadowing classes */
void pt_native_visitor_index_reset();

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

/* ScopeContext.cpp — $scope->isInClass() / ->getClassReflection() for
 * native callers: when the scope is exactly a MutatingScope (or a subclass
 * inheriting both bodies) holding a native ScopeContext, the answer comes
 * out of the context's $classReflection slot, otherwise the PHP method
 * decides; the per-request slot cache is reset by the rinit */
void pt_scope_access_rinit();
/* $scope->isInClass() (coerced to bool) / ->getClassReflection(); false /
 * UNDEF = pending exception */
[[nodiscard]] bool pt_scope_is_in_class(zend_object *scope, bool &out);
zv::Val pt_scope_get_class_reflection(zend_object *scope);

/* MutatingScope.cpp — $scope->isInExpressionAssign($expr) / ->isInTrait() /
 * ->isInAnonymousFunction() / ->getFunction() for native callers: the native
 * body while the scope's method is MutatingScope's own handler, the method
 * by name otherwise; false / UNDEF = pending exception */
[[nodiscard]] bool pt_mutating_scope_is_in_expression_assign(zend_object *scope, zend_object *expr, bool &out);
bool pt_mutating_scope_is_in_trait(zend_object *scope, bool &out);
bool pt_mutating_scope_is_in_anonymous_function(zend_object *scope, bool &out);
zv::Val pt_mutating_scope_get_function(zend_object *scope);

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
/* the members of the ClassReflectionExtensionRegistry the native
 * ClassReflection asks for, each naming the registry property holding it and
 * the twin's getter */
enum pt_registry_member
{
	PT_REGISTRY_PHP_CLASS_REFLECTION_EXTENSION = 0,
	PT_REGISTRY_METHODS_EXTENSIONS,
	PT_REGISTRY_PROPERTIES_EXTENSIONS,
	PT_REGISTRY_REQUIRE_EXTENDS_METHODS_EXTENSION,
	PT_REGISTRY_REQUIRE_EXTENDS_PROPERTIES_EXTENSION,
	PT_REGISTRY_ALLOWED_SUB_TYPES_EXTENSIONS,
	PT_REGISTRY_MEMBER_COUNT
};
/* $provider->getRegistry()-><getter>() — both hops out of property slots for
 * the classes the twins declare, the methods for anything else; UNDEF =
 * pending exception */
zv::Val pt_class_reflection_extension_registry_member(zend_object *provider, pt_registry_member member);

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

/* the native MutatingScope (MutatingScope.cpp): PHPStan\Analyser\MutatingScope
 * itself once activateShadowing() ran, NULL before that */
extern zend_class_entry *pt_ce_mutating_scope;
void pt_register_mutating_scope();
/* forgets the internal scope factory's slot offsets */
void pt_mutating_scope_rinit();
/* the file / traitReflection slots of a native ScopeContext, next to
 * pt_scope_context_class_reflection() (ScopeContext.cpp) */
zval *pt_scope_context_file(zend_object *context);
zval *pt_scope_context_trait_reflection(zend_object *context);
/* ScopeOps::hasVariableType($scope, $variableName) /
 * ScopeOps::hasExpressionType($scope, $node, $exprPrinter) natively
 * (ScopeOps.cpp); the TrinaryLogic singleton, UNDEF = pending exception */
zv::Val pt_scope_ops_has_variable_type(zval *scope, zend_string *variableName);
zv::Val pt_scope_ops_has_expression_type(zval *scope, zend_object *node, zval *exprPrinter);
/* StaticTypeFactory::argc() / argv() — copies of the memoized types;
 * UNDEF = pending exception */
zv::Val pt_static_type_factory_argc();
zv::Val pt_static_type_factory_argv();
/* StaticTypeFactory::generalOffsetAccessibleType() /
 * intOffsetAccessibleType() — copies of the memoized types; UNDEF =
 * pending exception */
zv::Val pt_static_type_factory_general_offset_accessible();
zv::Val pt_static_type_factory_int_offset_accessible();
/* ScopeOps::getTypeFromCache($scope, $node, $key) /
 * ScopeOps::expressionTypeByKey($scope, $node, $exprString) natively
 * (ScopeOps.cpp), for the native MutatingScope's getType() / resolveType():
 * the memoized type (null on a miss, *keyOut the owned node key either
 * way, NULL only with an exception pending) / the tracked type of a
 * certainty-yes holder (null otherwise); UNDEF = pending exception */
zv::Val pt_scope_ops_get_type_from_cache(zval *scope, zend_object *node, zend_string **keyOut);
zv::Val pt_scope_ops_expression_type_by_key(zval *scope, zend_object *node, zend_string *exprString);
/* ScopeOps::scopeWith() / ::invalidateExpressionEntries() /
 * ::invalidateMethodsOnExpression() / ::getIntertwinedRefRootVariableName()
 * natively (ScopeOps.cpp), for the native MutatingScope's assignment and
 * invalidation family; UNDEF = pending exception */
zv::Val pt_scope_ops_scope_with(zval *scope, HashTable *expressionTypes, HashTable *nativeExpressionTypes, HashTable *conditionalExpressions, HashTable *currentlyAssignedExpressions, HashTable *currentlyAllowedUndefinedExpressions, HashTable *inFunctionCallsStack, bool inFirstLevelStatement, bool afterExtractCall);
zv::Val pt_scope_ops_invalidate_expression_entries(zval *scope, zval *exprPrinter, zend_string *exprStringToInvalidate, zval *expressionToInvalidate, bool requireMoreCharacters, zval *invalidatingClass, HashTable *expressionTypes, HashTable *nativeExpressionTypes, HashTable *conditionalExpressions, bool keepPropertyFetches);
zv::Val pt_scope_ops_invalidate_methods_on_expression(zval *exprPrinter, zend_string *exprStringToInvalidate, HashTable *expressionTypes, HashTable *nativeExpressionTypes);
zv::Val pt_scope_ops_intertwined_ref_root_variable_name(zend_object *expr);
zv::Val pt_scope_ops_match_conditional_expressions(HashTable *conditionalExpressions, HashTable *specifiedExpressions);
zv::Val pt_scope_ops_merge_variable_holders(HashTable *ourVariableTypeHolders, HashTable *theirVariableTypeHolders, HashTable *differingKeys);
zv::Val pt_scope_ops_finish_merge(HashTable *mergedExpressionTypes, HashTable *ourExpressionTypes, HashTable *theirExpressionTypes, HashTable *ourNativeExpressionTypes, HashTable *theirNativeExpressionTypes);
zv::Val pt_scope_ops_intersect_conditional_expressions(HashTable *ourConditionalExpressions, HashTable *theirConditionalExpressions);
zv::Val pt_scope_ops_create_conditional_expressions(HashTable *conditionalExpressions, HashTable *ourExpressionTypes, HashTable *theirExpressionTypes, HashTable *mergedExpressionTypes, HashTable *differingKeys);
bool pt_scope_ops_should_invalidate_expression(zval *scope, zval *exprPrinter, zend_string *exprStringToInvalidate, zval *exprToInvalidate, zend_object *expr, zend_string *exprString, bool requireMoreCharacters, zval *invalidatingClass, bool keepPropertyFetches, bool *failed);
/* the shadowing ExpressionResultStorage (ExpressionResultStorage.cpp) — new
 * ExpressionResultStorage(), $storage->findExpressionResult($expr) and
 * $storage->duplicate(): the native bodies for a native storage, the
 * methods of anything else (the PHP twin under the prefixed differential
 * activation); UNDEF = pending exception */
extern zend_class_entry *pt_ce_expression_result_storage;
/* VolatileExpressionHelper.cpp — the shadowing class entry (MutatingScope
 * calls its statics directly) */
extern zend_class_entry *pt_ce_volatile_expression_helper;
zv::Val pt_expression_result_storage_new();
zv::Val pt_expression_result_storage_find(zval *storage, zval *expr);
zv::Val pt_expression_result_storage_duplicate(zval *storage);
/* the shadowing ExpressionResultStorageStack (ExpressionResultStorageStack.cpp)
 * — $stack->getCurrent(): the native body for a native stack, the method for
 * anything else; UNDEF = pending exception */
extern zend_class_entry *pt_ce_expression_result_storage_stack;
zv::Val pt_expression_result_storage_stack_current(zval *stack);

/* merged from the parallel port branch */
/* the native ClassReflection (ClassReflection.cpp), shadowing
 * PHPStan\Reflection\ClassReflection */
extern zend_class_entry *pt_ce_class_reflection;
void pt_register_class_reflection();
/* the getters the Type kernel calls millions of times per run: a direct
 * C++ call into the native body when the object is the shadowing class
 * (the common case), the PHP method when it is a foreign object.
 * $classReflection->getName() / ->getCacheKey() / ->getNativeReflection();
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

/* {{{ PhpParser CallLike reads without a call: $call->getRawArgs(),
 * ->isFirstClassCallable() and ->getArgs() of a FuncCall, MethodCall,
 * NullsafeMethodCall, StaticCall or New_ (or a subclass keeping those three
 * methods) answered from its `args` slot, exactly what the methods return —
 * the class is checked once per class entry per request. A class overriding
 * one of the methods, or an instance whose `args` was never initialized,
 * gets the methods by name. */

/* $call->getRawArgs(): the `args` slot (borrowed, dereferenced), or the
 * method's result kept alive in hold; NULL = pending exception */
zval *pt_call_like_raw_args(zend_object *call, zv::Val &hold);
/* $call->isFirstClassCallable(): a single VariadicPlaceholder argument;
 * false = pending exception */
[[nodiscard]] bool pt_call_like_is_first_class_callable(zend_object *call, bool &out);
/* $call->getArgs(): the `args` slot of a call that is not a first-class
 * callable (borrowed, dereferenced); a first-class callable gets the method,
 * whose assert() throws under zend.assertions=1 and returns the raw
 * arguments otherwise, its result kept alive in hold; NULL = pending
 * exception */
zval *pt_call_like_args(zend_object *call, zv::Val &hold);
/* ConditionalTypeResolver::resolveForCall($declaredType, $parametersAcceptor,
 * $args, $scope): `@throws` / `@phpstan-self-out` resolved against a call
 * site; UNDEF = pending exception */
zv::Val pt_conditional_type_resolver_resolve_for_call(zval *declaredType, zval *parametersAcceptor, zval *args, zval *scope);

/* }}} */

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


/* PhpClassReflectionExtension.cpp — the shadowing member factory behind
 * ClassReflection's has*()/get*() methods; registered at the END of the
 * sequence (its signatures name ClassReflection and the Type family, and
 * its constructor instantiates the native LruCache) */
extern zend_class_entry *pt_ce_php_class_reflection_extension;
void pt_register_php_class_reflection_extension();
/* forgets the per-request class-entry/slot cache of the BetterReflection
 * adapter memo readers */
void pt_php_class_reflection_extension_rinit();

/* {{{ the narrowing value classes (TypeSpecifierContext.cpp,
 * SpecifiedTypes.cpp) — registered at the END of the sequence */

/* TypeSpecifierContext.cpp — the shadowing PHPStan\Analyser\TypeSpecifierContext */
extern zend_class_entry *pt_ce_type_specifier_context;
void pt_register_type_specifier_context();
/* TypeSpecifierContext::CONTEXT_*; PT_TSC_NULL stands for the null
 * context's $value, PT_TSC_UNINITIALIZED for a never-written one */
#define PT_TSC_CONTEXT_TRUE 0b0001
#define PT_TSC_CONTEXT_TRUTHY_BUT_NOT_TRUE 0b0010
#define PT_TSC_CONTEXT_TRUTHY (PT_TSC_CONTEXT_TRUE | PT_TSC_CONTEXT_TRUTHY_BUT_NOT_TRUE)
#define PT_TSC_CONTEXT_FALSE 0b0100
#define PT_TSC_CONTEXT_FALSEY_BUT_NOT_FALSE 0b1000
#define PT_TSC_CONTEXT_FALSEY (PT_TSC_CONTEXT_FALSE | PT_TSC_CONTEXT_FALSEY_BUT_NOT_FALSE)
#define PT_TSC_CONTEXT_BITMASK 0b1111
#define PT_TSC_NULL (-1)
#define PT_TSC_UNINITIALIZED (-2)
/* TypeSpecifierContext::createTrue() / createTruthy() / createFalse() /
 * createFalsey() / createNull(): the process-wide singleton, borrowed (the
 * class's static $registry holds it); NULL = pending exception */
[[nodiscard]] zend_object *pt_type_specifier_context_create_true();
[[nodiscard]] zend_object *pt_type_specifier_context_create_truthy();
[[nodiscard]] zend_object *pt_type_specifier_context_create_false();
[[nodiscard]] zend_object *pt_type_specifier_context_create_falsey();
[[nodiscard]] zend_object *pt_type_specifier_context_create_null();
/* $context->true() / truthy() / false() / falsey() / null() on any context
 * object — the slot of a native context, the method of anything else;
 * false = pending exception */
[[nodiscard]] bool pt_type_specifier_context_true(zend_object *context, bool &out);
[[nodiscard]] bool pt_type_specifier_context_truthy(zend_object *context, bool &out);
[[nodiscard]] bool pt_type_specifier_context_false(zend_object *context, bool &out);
[[nodiscard]] bool pt_type_specifier_context_falsey(zend_object *context, bool &out);
[[nodiscard]] bool pt_type_specifier_context_falsey_but_not_false(zend_object *context, bool &out);
[[nodiscard]] bool pt_type_specifier_context_null(zend_object *context, bool &out);
/* $context->negate(); UNDEF = pending exception */
zv::Val pt_type_specifier_context_negate(zend_object *context);

/* SpecifiedTypes.cpp — the shadowing PHPStan\Analyser\SpecifiedTypes */
extern zend_class_entry *pt_ce_specified_types;
void pt_register_specified_types();
/* new SpecifiedTypes($sureTypes, $sureNotTypes) — NULL for the [] defaults,
 * the arrays borrowed; UNDEF = pending exception */
zv::Val pt_specified_types_new(zval *sureTypes = NULL, zval *sureNotTypes = NULL);
/* SpecifiedTypes::emptySpecifyCallback(): the process-wide Closure */
zv::Val pt_specified_types_empty_specify_callback();
/* the instance methods on any SpecifiedTypes object — the native body for a
 * native instance, the method otherwise (the PHP twin in the differential
 * tests); arguments borrowed ($rootExpr NULL for null), UNDEF = pending
 * exception. intersectWith()/unionWith() take the other operand as a zval
 * (the method's own argument check applies to a foreign one). */
zv::Val pt_specified_types_set_always_overwrite_types(zend_object *specifiedTypes);
zv::Val pt_specified_types_set_root_expr(zend_object *specifiedTypes, zval *rootExpr);
zv::Val pt_specified_types_set_new_conditional_expression_holders(zend_object *specifiedTypes, zval *holders);
zv::Val pt_specified_types_set_conditional_expression_holder_recipes(zend_object *specifiedTypes, zval *recipes);
zv::Val pt_specified_types_get_conditional_expression_holder_recipes(zend_object *specifiedTypes);
zv::Val pt_specified_types_with_deferred_augment(zend_object *specifiedTypes, zval *augment);
zv::Val pt_specified_types_get_deferred_augments(zend_object *specifiedTypes);
zv::Val pt_specified_types_get_sure_types(zend_object *specifiedTypes);
zv::Val pt_specified_types_get_sure_not_types(zend_object *specifiedTypes);
zv::Val pt_specified_types_get_alternative_types(zend_object *specifiedTypes);
zv::Val pt_specified_types_without_conditional_expression_holders(zend_object *specifiedTypes);
/* false = pending exception */
[[nodiscard]] bool pt_specified_types_should_overwrite(zend_object *specifiedTypes, bool &out);
zv::Val pt_specified_types_get_new_conditional_expression_holders(zend_object *specifiedTypes);
zv::Val pt_specified_types_get_root_expr(zend_object *specifiedTypes);
zv::Val pt_specified_types_remove_expr(zend_object *specifiedTypes, zend_string *exprString);
zv::Val pt_specified_types_intersect_with(zend_object *specifiedTypes, zval *other);
zv::Val pt_specified_types_union_with(zend_object *specifiedTypes, zval *other);
/* $specifiedTypes->setEquality(); UNDEF = pending exception */
zv::Val pt_specified_types_set_equality(zend_object *specifiedTypes);
/* $specifiedTypes->isEquality(); false = pending exception */
[[nodiscard]] bool pt_specified_types_is_equality(zval *specifiedTypes, bool &out);

/* }}} */

/* {{{ the analysis-engine value classes and handler registries
 * (ExpressionContext.cpp, StatementContext.cpp, ExprHandlerRegistry.cpp,
 * StmtHandlerRegistry.cpp) — registered at the END of the sequence (their
 * signatures name the Type interface and PHP analyser classes only) */

extern zend_class_entry *pt_ce_expression_context;
extern zend_class_entry *pt_ce_statement_context;
extern zend_class_entry *pt_ce_expr_handler_registry;
extern zend_class_entry *pt_ce_stmt_handler_registry;
void pt_register_expression_context();
void pt_register_statement_context();
void pt_register_expr_handler_registry();
void pt_register_stmt_handler_registry();

/* ExpressionContext::createTopLevel($resolveTemplateArguments) /
 * createDeep($resolveTemplateArguments); UNDEF = pending exception */
zv::Val pt_expression_context_create_top_level(bool resolveTemplateArguments = true);
zv::Val pt_expression_context_create_deep(bool resolveTemplateArguments = true);
/* $context->enterDeep() / enterDeepKeepingValueFlow() / withoutValueFlow() /
 * enterMatchArm() / withoutTemplateArgumentResolution() / enterThrow() /
 * enterArrayDimFetchRoot() / enterUnsetTarget() / enterPassedToType($type,
 * $nativeType) / enterRightSideAssign($variableName, $expr) /
 * enterAssignRightSideCallArgs($acceptor) / enterValueFlow($target, $direct)
 * — the native bodies for a native context, the methods of anything else
 * (every argument borrowed, NULL for a null); UNDEF = pending exception */
zv::Val pt_expression_context_enter_deep(zval *context);
zv::Val pt_expression_context_enter_deep_keeping_value_flow(zval *context);
zv::Val pt_expression_context_without_value_flow(zval *context);
zv::Val pt_expression_context_enter_match_arm(zval *context);
zv::Val pt_expression_context_without_template_argument_resolution(zval *context);
zv::Val pt_expression_context_enter_throw(zval *context);
zv::Val pt_expression_context_enter_array_dim_fetch_root(zval *context);
zv::Val pt_expression_context_enter_unset_target(zval *context);
zv::Val pt_expression_context_enter_passed_to_type(zval *context, zval *type, zval *nativeType);
zv::Val pt_expression_context_enter_right_side_assign(zval *context, zend_string *variableName, zval *expr);
zv::Val pt_expression_context_enter_assign_right_side_call_args(zval *context, zval *acceptor);
zv::Val pt_expression_context_enter_value_flow(zval *context, zval *target, bool direct);
/* $context->isDeep() / isValueConsumed() / shouldResolveTemplateArguments() /
 * isInThrow() / isValueFlowDirect() / isArrayDimFetchRoot() /
 * isUnsetTarget(); false = pending exception */
[[nodiscard]] bool pt_expression_context_is_deep(zval *context, bool &out);
[[nodiscard]] bool pt_expression_context_is_value_consumed(zval *context, bool &out);
[[nodiscard]] bool pt_expression_context_should_resolve_template_arguments(zval *context, bool &out);
[[nodiscard]] bool pt_expression_context_is_in_throw(zval *context, bool &out);
[[nodiscard]] bool pt_expression_context_is_value_flow_direct(zval *context, bool &out);
[[nodiscard]] bool pt_expression_context_is_array_dim_fetch_root(zval *context, bool &out);
[[nodiscard]] bool pt_expression_context_is_unset_target(zval *context, bool &out);
/* $context->getPassedToType() / getNativePassedToType() /
 * getInAssignRightSideVariableName() / getInAssignRightSideExpr() /
 * getInAssignRightSideType() / getInAssignRightSideNativeType() /
 * getValueFlowTarget(); the value or null, UNDEF = pending exception */
zv::Val pt_expression_context_get_passed_to_type(zval *context);
zv::Val pt_expression_context_get_native_passed_to_type(zval *context);
zv::Val pt_expression_context_get_in_assign_right_side_variable_name(zval *context);
zv::Val pt_expression_context_get_in_assign_right_side_expr(zval *context);
zv::Val pt_expression_context_get_in_assign_right_side_type(zval *context);
zv::Val pt_expression_context_get_in_assign_right_side_native_type(zval *context);
zv::Val pt_expression_context_get_value_flow_target(zval *context);

/* StatementContext::createTopLevel($resolveTemplateArguments) /
 * createDeep($resolveTemplateArguments), and $context->isTopLevel() /
 * shouldResolveTemplateArguments() / getForeachUnrollFactor() /
 * withoutTemplateArgumentResolution() / enterDeep() /
 * enterUnrolledForeach($totalKeys) — natively for a native context, the
 * methods otherwise; UNDEF / false = pending exception */
zv::Val pt_statement_context_create_top_level(bool resolveTemplateArguments = true);
zv::Val pt_statement_context_create_deep(bool resolveTemplateArguments = true);
[[nodiscard]] bool pt_statement_context_is_top_level(zval *context, bool &out);
[[nodiscard]] bool pt_statement_context_should_resolve_template_arguments(zval *context, bool &out);
[[nodiscard]] bool pt_statement_context_get_foreach_unroll_factor(zval *context, zend_long &out);
zv::Val pt_statement_context_without_template_argument_resolution(zval *context);
zv::Val pt_statement_context_enter_deep(zval *context);
zv::Val pt_statement_context_enter_unrolled_foreach(zval *context, zend_long totalKeys);

/* ExprHandlerRegistry::resolve($expr, $container) /
 * StmtHandlerRegistry::resolve($stmt, $container): the handler or null
 * (both arguments borrowed); UNDEF = pending exception */
zv::Val pt_expr_handler_registry_resolve(zend_object *expr, zval *container);
zv::Val pt_stmt_handler_registry_resolve(zend_object *stmt, zval *container);

/* }}} */

/* {{{ the analysis-engine foundation (Engine.h / Engine.cpp) and its first
 * handler ports (ScalarHandler.cpp, VariableHandler.cpp) */

/* MutatingScope.cpp — $scope->doNotTreatPhpDocTypesAsCertain() /
 * ->hasVariableType($name) (the TrinaryLogic singleton) /
 * ->getVariableType($name) / ->applySpecifiedTypes($specifiedTypes) for the
 * engine ports: the native body for a MutatingScope (or a subclass
 * inheriting the method), the method otherwise; UNDEF = pending exception */
zv::Val pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(zend_object *scope);
zv::Val pt_mutating_scope_has_variable_type(zend_object *scope, zend_string *variableName);
zv::Val pt_mutating_scope_get_variable_type(zend_object *scope, zend_string *variableName);
zv::Val pt_mutating_scope_apply_specified_types(zend_object *scope, zval *specifiedTypes);

/* VariableFlow.cpp — VariableFlow::mention($name) / VariableFlow::all(
 * VariableFlow::READ_ALL); UNDEF = pending exception */
zv::Val pt_variable_flow_mention(zend_string *name);
zv::Val pt_variable_flow_all_read_all();

/* the handler ports */
extern zend_class_entry *pt_ce_scalar_handler;
extern zend_class_entry *pt_ce_variable_handler;
void pt_register_scalar_handler();
void pt_register_variable_handler();
/* $variableHandler->composeResult($nodeScopeResolver, $expr, $nameResult,
 * $storage, $beforeScope, $context) ($nameResult / $context NULL for null,
 * everything borrowed); UNDEF = pending exception */
zv::Val pt_variable_handler_compose_result(zval *handler, zval *nodeScopeResolver, zval *expr, zval *nameResult, zval *storage, zval *beforeScope, zval *context);

/* }}} */

/* {{{ the analyser value classes the handlers trade results with
 * (ImpurePoint.cpp, ThrowPoint.cpp, InternalThrowPoint.cpp, ArgsResult.cpp,
 * IssetabilityDescriptor.cpp) — registered at the END of the sequence, after
 * MutatingScope and ExpressionResult, whose classes their signatures name.
 * The classes are final: an entry taking an instance answers natively for
 * the native class entry and through the method for anything else (the PHP
 * twin declared next to the native class in the differential tests). Every
 * argument is borrowed, NULL for a null where noted; UNDEF / false = pending
 * exception. The getters that only read a slot are inline in
 * AnalyserValues.h. */

extern zend_class_entry *pt_ce_impure_point;
void pt_register_impure_point();
/* new ImpurePoint($scope, $node, $identifier, $description, $certain) */
zv::Val pt_impure_point_new(zval *scope, zval *node, zend_string *identifier, zend_string *description, bool certain);

extern zend_class_entry *pt_ce_throw_point;
void pt_register_throw_point();
/* ThrowPoint::createExplicit($scope, $type, $node, $canContainAnyThrowable) /
 * ThrowPoint::createImplicit($scope, $node, $type) ($type NULL for null) */
zv::Val pt_throw_point_create_explicit(zval *scope, zval *type, zval *node, bool canContainAnyThrowable, bool fromThrowExpr);
zv::Val pt_throw_point_create_implicit(zval *scope, zval *node, zval *type = NULL);

extern zend_class_entry *pt_ce_internal_throw_point;
void pt_register_internal_throw_point();
/* InternalThrowPoint::createExplicit(...) / ::createImplicit($scope, $node,
 * $type) ($type NULL for null) / ::createFromPublic($throwPoint, $scope) */
zv::Val pt_internal_throw_point_create_explicit(zval *scope, zval *type, zval *node, bool canContainAnyThrowable, bool fromThrowExpr);
zv::Val pt_internal_throw_point_create_implicit(zval *scope, zval *node, zval *type = NULL);
zv::Val pt_internal_throw_point_create_from_public(zval *throwPoint, zval *scope);
/* $throwPoint->toPublic() / ->subtractCatchType($catchType) (the getters
 * are inline in AnalyserValues.h) */
zv::Val pt_internal_throw_point_to_public(zval *throwPoint);
zv::Val pt_internal_throw_point_subtract_catch_type(zval *throwPoint, zval *catchType);

extern zend_class_entry *pt_ce_args_result;
void pt_register_args_result();
/* new ArgsResult($expressionResult, $resolvedParametersAcceptor, $argResults,
 * $byRefArguments) ($resolvedParametersAcceptor NULL for null,
 * $byRefArguments NULL for []) */
zv::Val pt_args_result_new(zval *expressionResult, zval *resolvedParametersAcceptor, zval *argResults, zval *byRefArguments = NULL);
/* $argsResult->requireArgResult($argValue) (findArgResult(),
 * isPassedByReference() and the getters are inline in AnalyserValues.h) */
zv::Val pt_args_result_require_arg_result(zval *argsResult, zval *argValue);

extern zend_class_entry *pt_ce_issetability_descriptor;
void pt_register_issetability_descriptor();
/* IssetabilityDescriptor::variable($variableName) / ::offset($varResult,
 * $dimResult) / ::property($innerResult, $reflectionResolver, $propertyFetch)
 * ($innerResult NULL for null; the resolver any callable — the twin's
 * factory types it Closure, the slot is never re-checked) */
zv::Val pt_issetability_descriptor_variable(zend_string *variableName);
zv::Val pt_issetability_descriptor_offset(zval *varResult, zval *dimResult);
zv::Val pt_issetability_descriptor_property(zval *innerResult, zval *reflectionResolver, zval *propertyFetch);
/* $descriptor->resolve($scope, $useNativeTypes, $expr, $reprocessUntrackedLinks) */
zv::Val pt_issetability_descriptor_resolve(zval *descriptor, zval *scope, bool useNativeTypes, zval *expr, bool reprocessUntrackedLinks = false);

/* ExpressionResult.cpp — $result->getTypeOnScope($scope, $useNativeTypes) /
 * ->getIssetabilityResolution($scope, $useNativeTypes, $reprocessUntrackedLinks)
 * (the slot getters are inline in AnalyserValues.h) */
zv::Val pt_expression_result_get_type_on_scope(zval *result, zval *scope, bool useNativeTypes);
zv::Val pt_expression_result_get_issetability_resolution(zval *result, zval *scope, bool useNativeTypes, bool reprocessUntrackedLinks);

/* MutatingScope.cpp — $scope->hasExpressionType($node) / ->getType($node) /
 * ->getNativeType($expr) ($node / $expr an Expr), next to the engine
 * foundation's hasVariableType() / getVariableType() /
 * doNotTreatPhpDocTypesAsCertain() entries above: the native body for a
 * MutatingScope (or a subclass inheriting the method), the method by name
 * otherwise */
/* (hasExpressionType(): the PT_TRI_* value of the answer, -1 = pending
 * exception) */
[[nodiscard]] zend_long pt_mutating_scope_has_expression_type(zend_object *scope, zval *node);
zv::Val pt_mutating_scope_get_type(zend_object *scope, zval *node);
zv::Val pt_mutating_scope_get_native_type(zend_object *scope, zval *expr);
/* }}} */

/* {{{ the statement results (StatementExitPoint.cpp, StatementResult.cpp,
 * EndStatementResult.cpp, InternalStatementExitPoint.cpp,
 * InternalStatementResult.cpp, InternalEndStatementResult.cpp) — registered
 * after the value classes above, the public ones before the internal ones
 * whose toPublic() return them. Conventions as above; the slot getters are
 * inline in AnalyserValues.h. */

extern zend_class_entry *pt_ce_statement_exit_point;
extern zend_class_entry *pt_ce_statement_result;
extern zend_class_entry *pt_ce_end_statement_result;
extern zend_class_entry *pt_ce_internal_statement_exit_point;
extern zend_class_entry *pt_ce_internal_statement_result;
extern zend_class_entry *pt_ce_internal_end_statement_result;
void pt_register_statement_exit_point();
void pt_register_statement_result();
void pt_register_end_statement_result();
void pt_register_internal_statement_exit_point();
void pt_register_internal_statement_result();
void pt_register_internal_end_statement_result();

/* new StatementExitPoint($statement, $scope) / new EndStatementResult($statement,
 * $result) / new StatementResult($scope, $hasYield, $isAlwaysTerminating,
 * $exitPoints, $throwPoints, $impurePoints, $endStatements) ($endStatements
 * NULL for []) */
zv::Val pt_statement_exit_point_new(zval *statement, zval *scope);
zv::Val pt_end_statement_result_new(zval *statement, zval *result);
zv::Val pt_statement_result_new(zval *scope, bool hasYield, bool isAlwaysTerminating, zval *exitPoints, zval *throwPoints, zval *impurePoints, zval *endStatements = NULL);

/* new InternalStatementExitPoint($statement, $scope) / ->toPublic() */
zv::Val pt_internal_statement_exit_point_new(zval *statement, zval *scope);
zv::Val pt_internal_statement_exit_point_to_public(zval *exitPoint);
/* new InternalEndStatementResult($statement, $result) / ->toPublic() */
zv::Val pt_internal_end_statement_result_new(zval *statement, zval *result);
zv::Val pt_internal_end_statement_result_to_public(zval *endStatement);
/* new InternalStatementResult($scope, $hasYield, $isAlwaysTerminating,
 * $exitPoints, $throwPoints, $impurePoints, $endStatements, $variableFlow,
 * $endReachable) ($endStatements NULL for [], $variableFlow NULL for null,
 * $endReachable -1 for null, else 0/1) */
zv::Val pt_internal_statement_result_new(zval *scope, bool hasYield, bool isAlwaysTerminating, zval *exitPoints, zval *throwPoints, zval *impurePoints, zval *endStatements = NULL, zval *variableFlow = NULL, int endReachable = -1);
/* $result->filterOutLoopExitPoints() / ->getExitPointsByType($stmtClass)
 * (the class entry) / ->getExitPointsForOuterLoop() /
 * ->getLoopBackEdgeScope() (the scope or null) / ->toPublic() */
zv::Val pt_internal_statement_result_filter_out_loop_exit_points(zval *result);
zv::Val pt_internal_statement_result_exit_points_by_type(zval *result, zend_class_entry *stmtClass);
zv::Val pt_internal_statement_result_exit_points_for_outer_loop(zval *result);
zv::Val pt_internal_statement_result_loop_back_edge_scope(zval *result);
zv::Val pt_internal_statement_result_to_public(zval *result);

/* MutatingScope.cpp — $scope->getTemplateArgumentConstraints() /
 * ->addTemplateArgumentConstraints($constraints) / ->mergeWith($otherScope,
 * $preserveVacuousConditionals) ($constraints / $otherScope NULL or IS_NULL
 * for null) */
zv::Val pt_mutating_scope_get_template_argument_constraints(zend_object *scope);
zv::Val pt_mutating_scope_add_template_argument_constraints(zend_object *scope, zval *constraints);
zv::Val pt_mutating_scope_merge_with(zend_object *scope, zval *otherScope, bool preserveVacuousConditionals = false);

/* }}} */

/* {{{ TemplateArgumentFrame.cpp, AssignTargetWalkMode.cpp,
 * PreparedAssignTarget.cpp — registered after the statement results.
 * Conventions as above; the slot getters are inline in AnalyserValues.h. */

extern zend_class_entry *pt_ce_template_argument_frame;
void pt_register_template_argument_frame();
/* TemplateArgumentFrame::returnTypeOfCall($acceptor, $scope, $site,
 * $allowUnresolved) ($allowUnresolved -1 for null, else 0/1) */
zv::Val pt_template_argument_frame_return_type_of_call(zval *acceptor, zval *scope, zval *site, int allowUnresolved = -1);
/* new TemplateArgumentFrame($parent, $resolutions, $siteStatementIndexes)
 * ($parent / $resolutions NULL or IS_NULL for null, $siteStatementIndexes
 * NULL for []) */
zv::Val pt_template_argument_frame_new(zval *parent, zval *resolutions = NULL, zval *siteStatementIndexes = NULL);
/* $frame->resolve($site, $templateName) (the type or null) /
 * ->resolveOrUnconstrained($site, $template) /
 * ->getResolutionCacheKeySuffix() */
zv::Val pt_template_argument_frame_resolve(zval *frame, zval *site, zend_string *templateName);
zv::Val pt_template_argument_frame_resolve_or_unconstrained(zval *frame, zval *site, zval *templateType);
zv::Val pt_template_argument_frame_resolution_cache_key_suffix(zval *frame);

/* MutatingScope.cpp — $scope->getCurrentTemplateArgumentFrame() / the
 * $scope->nativeTypesPromoted property */
zv::Val pt_mutating_scope_get_current_template_argument_frame(zend_object *scope);
[[nodiscard]] bool pt_mutating_scope_native_types_promoted(zend_object *scope, bool &out);

extern zend_class_entry *pt_ce_assign_target_walk_mode;
void pt_register_assign_target_walk_mode();
/* new AssignTargetWalkMode(...) — what assign() / virtualAssign() /
 * readModifyWrite() / coalesceReadModifyWrite() return fresh each call */
zv::Val pt_assign_target_walk_mode_new(bool enterExpressionAssign, bool producesTargetReadResult, bool issetSemanticsForRead);

extern zend_class_entry *pt_ce_prepared_assign_target;
void pt_register_prepared_assign_target();
/* new PreparedAssignTarget(...$argv): the constructor's positional
 * arguments in the twin's order (at least the 11 required ones), an
 * omitted or UNDEF optional one taking its default */
zv::Val pt_prepared_assign_target_new(uint32_t argc, zval *argv);

/* }}} */

/* {{{ RecordingNodeCallback.cpp — registered after the value classes above */

extern zend_class_entry *pt_ce_recording_node_callback;
void pt_register_recording_node_callback();
/* $callback($node, $scope) on an instance of the shadowing class: the pair
 * recorded (pt_type_call_callable() recognizes the class itself); false =
 * pending exception */
[[nodiscard]] bool pt_recording_node_callback_record(zend_object *callback, zval *node, zval *scope);
/* {{{ the analyser helper services (EarlyTerminatingCallHelper.cpp,
 * MethodCallReturnTypeHelper.cpp, MethodThrowPointHelper.cpp) — registered
 * at the END of the sequence (their signatures name MutatingScope, the Type
 * interface and PHP classes only) */

void pt_register_early_terminating_call_helper();
void pt_register_method_call_return_type_helper();
void pt_register_method_throw_point_helper();

/* MutatingScope.cpp — $scope->filterTypeWithMethod($type, $methodName) /
 * ->getMethodReflection($type, $methodName) / ->getStateType($expr) /
 * ->getConditionalExpressions() / ->getCurrentExpressionResultStorage() /
 * ->resolveTypeByName($name) / ->specifyTypesOfNewWorldHandlerNode($node,
 * $context) for native callers, next to the value classes' getType() /
 * hasExpressionType() / nativeTypesPromoted entries above: the native body
 * while the scope's method is MutatingScope's own handler, the method by name
 * otherwise (arguments borrowed); UNDEF = pending exception */
zv::Val pt_mutating_scope_filter_type_with_method(zend_object *scope, zval *typeWithMethod, zend_string *methodName);
zv::Val pt_mutating_scope_get_method_reflection(zend_object *scope, zval *typeWithMethod, zend_string *methodName);
zv::Val pt_mutating_scope_get_state_type(zend_object *scope, zend_object *expr);
zv::Val pt_mutating_scope_get_conditional_expressions(zend_object *scope);
zv::Val pt_mutating_scope_get_current_expression_result_storage(zend_object *scope);
zv::Val pt_mutating_scope_resolve_type_by_name(zend_object *scope, zend_object *name);
zv::Val pt_mutating_scope_specify_types_of_new_world_handler_node(zend_object *scope, zend_object *node, zval *context);

/* ReflectionAccess.cpp — $collection->getAll(): the memoized list out of a
 * LazyExtensionsCollection's $extensions slot, the method on the first call
 * and for any other ExtensionsCollection; UNDEF = pending exception */
zv::Val pt_extensions_collection_get_all(zend_object *collection);

/* }}} */

/* {{{ the boolean narrowing cluster (ConditionalExpressionHolderRecipe.cpp,
 * DisjunctionBranchUnionAugment.cpp, DisjunctionHolderProjectionAugment.cpp,
 * ConditionalExpressionHolderHelper.cpp, BooleanNarrowingHelper.cpp) —
 * registered at the END of the sequence, the value classes before the
 * helpers constructing them */

extern zend_class_entry *pt_ce_conditional_expression_holder_recipe;
extern zend_class_entry *pt_ce_disjunction_branch_union_augment;
extern zend_class_entry *pt_ce_disjunction_holder_projection_augment;
extern zend_class_entry *pt_ce_conditional_expression_holder_helper;
extern zend_class_entry *pt_ce_boolean_narrowing_helper;
void pt_register_conditional_expression_holder_recipe();
void pt_register_disjunction_branch_union_augment();
void pt_register_disjunction_holder_projection_augment();
void pt_register_conditional_expression_holder_helper();
void pt_register_boolean_narrowing_helper();
/* new ConditionalExpressionHolderRecipe($conditionEntries, $holderEntries,
 * $holdersFromSureTypes) / new DisjunctionBranchUnionAugment($nodeScopeResolver,
 * $defaultNarrowingHelper, $candidates) / new DisjunctionHolderProjectionAugment(
 * $nodeScopeResolver, $defaultNarrowingHelper, $leftTruthyScope, $leftFalseyScope,
 * $rightTruthyScope, $alternativeKeys) — instances of the shadowing classes
 * (arguments borrowed); UNDEF = pending exception */
zv::Val pt_conditional_expression_holder_recipe_new(zval *conditionEntries, zval *holderEntries, bool holdersFromSureTypes);
zv::Val pt_disjunction_branch_union_augment_new(zval *nodeScopeResolver, zval *defaultNarrowingHelper, zval *candidates);
zv::Val pt_disjunction_holder_projection_augment_new(zval *nodeScopeResolver, zval *defaultNarrowingHelper, zval *leftTruthyScope, zval *leftFalseyScope, zval *rightTruthyScope, zval *alternativeKeys);
/* $recipe->evaluate($scope) / $augment->evaluate($scope) — the native body
 * for an instance of the shadowing class, the method otherwise; UNDEF =
 * pending exception */
zv::Val pt_conditional_expression_holder_recipe_evaluate(zend_object *recipe, zval *scope);
zv::Val pt_disjunction_branch_union_augment_evaluate(zend_object *augment, zval *scope);
zv::Val pt_disjunction_holder_projection_augment_evaluate(zend_object *augment, zval *scope);
/* $helper->buildBranchUnionAugment(...) / ->buildConditionalHolderRecipe(...)
 * ($nonVariableTargetScope / $holderSideExpr NULL for null) /
 * BooleanNarrowingHelper's ->specifyConjunction(...) / ->specifyDisjunction(...)
 * — the native bodies for the shadowing classes, the methods otherwise
 * (arguments borrowed); UNDEF = pending exception */
zv::Val pt_conditional_expression_holder_helper_build_branch_union_augment(zend_object *helper, zval *nodeScopeResolver, zval *leftTypes, zval *rightTypes, zval *leftFilteredScope, zval *rightFilteredScope, zval *types);
zv::Val pt_conditional_expression_holder_helper_build_conditional_holder_recipe(zend_object *helper, zval *composeScope, zval *conditionSpecifiedTypes, zval *holderSpecifiedTypes, bool holdersFromSureTypes, bool holderSideIsNegated, zval *nonVariableTargetScope, zval *holderSideExpr);
zv::Val pt_boolean_narrowing_helper_specify_conjunction(zend_object *helper, zval *nodeScopeResolver, zval *s, zval *context, zval *rootExpr, zval *leftExpr, zval *leftTypesCallback, zval *leftTruthyScope, zval *leftFalseyScope, zval *rightExpr, zval *rightTypesCallback, zval *rightFalseyScope);
zv::Val pt_boolean_narrowing_helper_specify_disjunction(zend_object *helper, zval *nodeScopeResolver, zval *s, zval *context, zval *rootExpr, zval *leftExpr, zval *leftTypesCallback, zval *leftTypeCallback, zval *leftTruthyScope, zval *leftFalseyScope, zval *rightExpr, zval *rightTypesCallback, zval *rightTypeCallback, zval *rightTruthyScope);

/* }}} */

/* {{{ TypeSpecifier.cpp — the shadowing PHPStan\Analyser\TypeSpecifier,
 * registered at the END of the sequence */

extern zend_class_entry *pt_ce_type_specifier;
void pt_register_type_specifier();
/* $typeSpecifier->specifyTypesInCondition($scope, $expr, $context) — the
 * native body for the shadowing class, the method otherwise (arguments
 * borrowed); UNDEF = pending exception */
zv::Val pt_type_specifier_specify_types_in_condition(zend_object *typeSpecifier, zval *scope, zend_object *expr, zend_object *context);
/* ReflectionAccess.cpp — ExtensionClassHelper::getExtensionClassNames(
 * $reflectionProvider, $className): the static memo's list when computed,
 * the method otherwise (arguments borrowed); UNDEF = pending exception */
zv::Val pt_extension_class_helper_get_extension_class_names(zval *reflectionProvider, zval *className);

/* }}} */

/* {{{ the narrowing helpers (DefaultNarrowingHelper.cpp,
 * IdenticalNarrowingHelper.cpp) and the direct entries they read through */

/* ExpressionResult.cpp — $result->getExpr() / ->containsNullsafe() /
 * ->getTypeOnScope($scope, $useNativeTypes) / ->getCreatedTypesForScope($scope,
 * $type, $context) / ->getSpecifiedTypesForScope($scope, $context) /
 * ->getIssetabilityResolution($scope, $useNativeTypes): the native body for a
 * native result, the method otherwise (everything borrowed); UNDEF / false =
 * pending exception */
zv::Val pt_expression_result_get_created_types_for_scope(zval *result, zval *scope, zval *type, zval *context);
zv::Val pt_expression_result_get_specified_types_for_scope(zval *result, zval *scope, zval *context);

/* MutatingScope.cpp — $scope->toWalkScope() /
 * ->specifyTypesOfNewWorldHandlerNode($node, $context) /
 * ->getCurrentExpressionResultStorage() / ->getStateType($expr) /
 * ->hasExpressionType($node) / ->getMethodReflection($type, $methodName) /
 * ->resolveTypeByName($name): the native body for exactly a MutatingScope,
 * the method by name otherwise; $scope->nativeTypesPromoted of any scope
 * object. UNDEF / false = pending exception */
zv::Val pt_mutating_scope_to_walk_scope(zend_object *scope);

/* ExprPrinter.cpp — $exprPrinter->printExpr($expr): the native body for the
 * shadowing ExprPrinter, the method otherwise; owned string, NULL = pending
 * exception */
zend_string *pt_expr_printer_print(zval *exprPrinter, zend_object *expr);

/* SpecifiedTypes.cpp — (new SpecifiedTypes($sureTypes,
 * $sureNotTypes))->setRootExpr($rootExpr) in one allocation (NULL for the []
 * defaults / a null root); UNDEF = pending exception */
zv::Val pt_specified_types_new_with_root_expr(zval *sureTypes, zval *sureNotTypes, zval *rootExpr);

/* DefaultNarrowingHelper.cpp — the shadowing class and the public methods
 * for native callers: the native body for the native class, the method by
 * name otherwise (everything borrowed; a nullable argument NULL or IS_NULL
 * for null; $chainResults the by-reference array, IS_REFERENCE or not);
 * UNDEF / false = pending exception */
extern zend_class_entry *pt_ce_default_narrowing_helper;
void pt_register_default_narrowing_helper();
zv::Val pt_default_narrowing_helper_specify_types_for_node(zval *helper, zval *scope, zval *node, zval *context);
zv::Val pt_default_narrowing_helper_specify_default_types(zval *helper, zval *expr, zval *context);
zv::Val pt_default_narrowing_helper_specify_default_types_with_plain_twin(zval *helper, zval *expr, zval *exprResult, zval *context, zval *s);
zv::Val pt_default_narrowing_helper_to_sure_types(zval *helper, zval *types, zval *evaluationScope);
zv::Val pt_default_narrowing_helper_create_subject_types(zval *helper, zval *s, zval *subject, zval *subjectResult, zval *type, zval *context);
zv::Val pt_default_narrowing_helper_create_subject_types_from_result_state(zval *helper, zval *s, zval *subject, zval *subjectResult, zval *type, zval *context);
zv::Val pt_default_narrowing_helper_specify_default_types_with_nullsafe_fan(zval *helper, zval *expr, zval *context, zval *beforeScope, bool nativeTypesPromoted);
zv::Val pt_default_narrowing_helper_create_nullsafe_receiver_only_types(zval *helper, zval *s, zval *subject, zval *subjectResult, zval *type, zval *context);
/* $helper->callMayHaveBeenSkipped($receiverResult, $receiverType, $context);
 * $receiverResult NULL (or a null zval) for null; false = pending exception */
[[nodiscard]] bool pt_default_narrowing_helper_call_may_have_been_skipped(zval *helper, zval *receiverResult, zval *receiverType, zval *context, bool &out);
zv::Val pt_default_narrowing_helper_create_for_subject(zval *helper, zval *subject, zval *type, zval *context, zval *scope, zval *resultFor = NULL);
[[nodiscard]] bool pt_default_narrowing_helper_capture_chain_results(zval *helper, zval *node, zval *storage, zval *chainResults);
zv::Val pt_default_narrowing_helper_build_chain_type_reader(zval *helper, zval *chainResults, zval *s);
zv::Val pt_default_narrowing_helper_create_isset_truthy_chain_types(zval *helper, zval *s, zval *issetExpr, zval *readType, zval *rootExpr, zval *context);
zv::Val pt_default_narrowing_helper_create_isset_single_subject_non_true_types(zval *helper, zval *s, zval *issetExpr, zval *varResult, zval *readType, zval *context, zval *rootExpr);
zv::Val pt_default_narrowing_helper_specify_types_from_asserts(zval *helper, zval *context, zval *call, zval *assertions, zval *parametersAcceptor, zval *scope);
zv::Val pt_default_narrowing_helper_specify_types_from_conditional_return_type(zval *helper, zval *context, zval *call, zval *parametersAcceptor, zval *scope);

/* IdenticalNarrowingHelper.cpp — the shadowing class and the public methods
 * for native callers: the native body for the native class, the method by
 * name otherwise (everything borrowed; a nullable argument NULL or IS_NULL
 * for null); UNDEF = pending exception */
extern zend_class_entry *pt_ce_identical_narrowing_helper;
void pt_register_identical_narrowing_helper();
zv::Val pt_identical_narrowing_helper_specify_identical(zval *helper, zval *nodeScopeResolver, zval *left, zval *right, zval *leftResult, zval *rightResult, zval *context, zval *evaluationScope, zval *leftArgResult, zval *rightArgResult, zval *identicalTypeCallback);
zv::Val pt_identical_narrowing_helper_specify_equal(zval *helper, zval *nodeScopeResolver, zval *left, zval *right, zval *leftResult, zval *rightResult, zval *context, zval *evaluationScope, zval *leftArgResult, zval *rightArgResult);
zv::Val pt_identical_narrowing_helper_specify_identical_against_type(zval *helper, zval *subject, zval *subjectResult, zval *constantExpr, zval *constantType, zval *context, zval *evaluationScope, zval *subjectArgResult, zval *identicalTypeCallback);
zv::Val pt_identical_narrowing_helper_capture_first_arg_result(zval *helper, zval *side, zval *storage);
/* {{{ the walk hub: NodeScopeResolver.cpp, StatementsHandler.cpp,
 * StatementListWalkState.cpp, NonNullabilityHelper.cpp, and the direct
 * entries they added to their collaborators */

extern zend_class_entry *pt_ce_statement_list_walk_state;
extern zend_class_entry *pt_ce_non_nullability_helper;
extern zend_class_entry *pt_ce_statements_handler;
extern zend_class_entry *pt_ce_node_scope_resolver;
void pt_register_statement_list_walk_state();
void pt_register_non_nullability_helper();
void pt_register_statements_handler();
void pt_register_node_scope_resolver();

/* NodeScopeResolver.cpp — $nodeScopeResolver->processExprNode(...) /
 * processStmtNode(...) / processStmtNodesInternal(...) /
 * processExprOnDemand($expr, $scope, $storage) /
 * processSyntheticOnDemand($expr, $scope) / findScopeStateType($expr,
 * $scope) (a Type or null) / readScopeStateOrSyntheticType($expr, $scope) /
 * requireScopeStateType($expr, $scope) /
 * readTypeOfMaybeStored($expr, $scope) / storeExpressionResult(...) /
 * callNodeCallback(...) / callNodeCallbackWithExpression(...) /
 * suspendNodeGatherers() / restoreNodeGatherers($gatherers) /
 * observingTemplateArgumentFrame($scope) (a frame or null) /
 * replayRecordingRange(...): the native body for exactly the native class
 * (not final: a PHP subclass may override), the method otherwise; every
 * argument borrowed; UNDEF / false = pending exception */
zv::Val pt_node_scope_resolver_process_expr_node(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context);
zv::Val pt_node_scope_resolver_process_stmt_node(zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context);
zv::Val pt_node_scope_resolver_process_stmt_nodes_internal(zval *nodeScopeResolver, zval *parentNode, zval *stmts, zval *scope, zval *storage, zval *nodeCallback, zval *context);
zv::Val pt_node_scope_resolver_process_expr_on_demand(zval *nodeScopeResolver, zval *expr, zval *scope, zval *storage);
zv::Val pt_node_scope_resolver_process_synthetic_on_demand(zval *nodeScopeResolver, zval *expr, zval *scope);
zv::Val pt_node_scope_resolver_find_scope_state_type(zval *nodeScopeResolver, zval *expr, zval *scope);
zv::Val pt_node_scope_resolver_read_scope_state_or_synthetic_type(zval *nodeScopeResolver, zval *expr, zval *scope);
zv::Val pt_node_scope_resolver_require_scope_state_type(zval *nodeScopeResolver, zval *expr, zval *scope);
zv::Val pt_node_scope_resolver_read_type_of_maybe_stored(zval *nodeScopeResolver, zval *expr, zval *scope);
[[nodiscard]] bool pt_node_scope_resolver_store_expression_result(zval *nodeScopeResolver, zval *storage, zval *expr, zval *expressionResult);
[[nodiscard]] bool pt_node_scope_resolver_call_node_callback(zval *nodeScopeResolver, zval *nodeCallback, zval *node, zval *scope, zval *storage);
[[nodiscard]] bool pt_node_scope_resolver_call_node_callback_with_expression(zval *nodeScopeResolver, zval *nodeCallback, zval *expr, zval *scope, zval *storage, zval *context);
zv::Val pt_node_scope_resolver_suspend_node_gatherers(zval *nodeScopeResolver);
[[nodiscard]] bool pt_node_scope_resolver_restore_node_gatherers(zval *nodeScopeResolver, zval *gatherers);
zv::Val pt_node_scope_resolver_observing_template_argument_frame(zval *nodeScopeResolver, zval *scope);
[[nodiscard]] bool pt_node_scope_resolver_replay_recording_range(zval *nodeScopeResolver, zval *recording, zend_long from, zend_long to, zval *nodeCallback, zval *storage, zval *scope);

/* StatementsHandler.cpp — $statementsHandler->processNodesWithStorage(...) /
 * doProcessStmtNodes(...) / processStmtVarAnnotation(...) ($defaultExpr
 * NULL for null) / getOverridingThrowPoints($statement, $scope) (a list or
 * null) / emitVarTagChangedNode(...) / getVariableMentionFlow($stmt): the
 * native body for the native class, the method otherwise; UNDEF / false =
 * pending exception */
[[nodiscard]] bool pt_statements_handler_process_nodes_with_storage(zval *handler, zval *nodeScopeResolver, zval *nodes, zval *scope, zval *storage, zval *nodeCallback);
zv::Val pt_statements_handler_do_process_stmt_nodes(zval *handler, zval *nodeScopeResolver, zval *parentNode, zval *stmts, zval *scope, zval *storage, zval *nodeCallback, zval *context);
zv::Val pt_statements_handler_process_stmt_var_annotation(zval *handler, zval *nodeScopeResolver, zval *scope, zval *storage, zval *stmt, zval *defaultExpr, zval *nodeCallback);
zv::Val pt_statements_handler_get_overriding_throw_points(zval *handler, zval *statement, zval *scope);
zv::Val pt_statements_handler_emit_var_tag_changed_node(zval *handler, zval *nodeScopeResolver, zval *scope, zval *storage, zval *stmt, zval *defaultExpr, zval *nodeCallback);
zv::Val pt_statements_handler_get_variable_mention_flow(zval *handler, zval *stmt);

/* StatementListWalkState.cpp — new StatementListWalkState($scope) /
 * $state->toResult(); UNDEF = pending exception */
zv::Val pt_statement_list_walk_state_new(zval *scope);
zv::Val pt_statement_list_walk_state_to_result(zval *state);

/* NonNullabilityHelper.cpp — $helper->applyPendingEnsure($expr, $result)
 * (the result itself while no ensure is pending) and
 * $resettable->resetFileAnalysisState() of any PerFileAnalysisResettable
 * (natively for the helper); UNDEF / false = pending exception */
zv::Val pt_non_nullability_helper_apply_pending_ensure(zval *helper, zval *expr, zval *result);
[[nodiscard]] bool pt_non_nullability_helper_reset_file_analysis_state(zval *resettable);

/* MutatingScope.cpp — the walk's scope calls: the native body for exactly a
 * MutatingScope, the method through the class entry for anything else
 * (NodeCallbackScope, a third-party subclass); every argument borrowed;
 * UNDEF / false = pending exception */
zv::Val pt_mutating_scope_to_node_callback_scope(zend_object *scope);
[[nodiscard]] bool pt_mutating_scope_push_expression_result_storage(zend_object *scope, zval *storage);
[[nodiscard]] bool pt_mutating_scope_pop_expression_result_storage(zend_object *scope);
zv::Val pt_mutating_scope_exit_first_level_statements(zend_object *scope);
zv::Val pt_mutating_scope_with_template_argument_frame(zend_object *scope, zval *frame);
zv::Val pt_mutating_scope_with_template_argument_constraints(zend_object *scope, zval *constraints);
zv::Val pt_mutating_scope_get_tracked_expression_type(zend_object *scope, zend_object *expr);
[[nodiscard]] bool pt_mutating_scope_equals(zend_object *scope, zend_object *otherScope, bool &out);
zv::Val pt_mutating_scope_generalize_with(zend_object *scope, zend_object *otherScope);
zv::Val pt_mutating_scope_get_differing_variable_roots(zend_object *scope, zend_object *other);
zv::Val pt_mutating_scope_with_recorded_statement_delta(zend_object *scope, zend_object *recordedEntry, zend_object *recordedExit);
zv::Val pt_mutating_scope_set_allowed_undefined_expression(zend_object *scope, zend_object *expr);
zv::Val pt_mutating_scope_unset_allowed_undefined_expression(zend_object *scope, zend_object *expr);
zv::Val pt_mutating_scope_get_anonymous_function_return_type(zend_object *scope);
[[nodiscard]] bool pt_mutating_scope_is_in_class(zend_object *scope, bool &out);
zv::Val pt_mutating_scope_get_class_reflection(zend_object *scope);
zv::Val pt_mutating_scope_get_trait_reflection(zend_object *scope);
zv::Val pt_mutating_scope_get_file(zend_object *scope);
[[nodiscard]] bool pt_mutating_scope_can_any_variable_exist(zend_object *scope, bool &out);
zv::Val pt_mutating_scope_assign_variable(zend_object *scope, zend_string *variableName, zval *type, zval *nativeType, zval *certainty);
zv::Val pt_mutating_scope_assign_expression(zend_object *scope, zend_object *expr, zval *type, zval *nativeType);
zv::Val pt_mutating_scope_specify_expression_type(zend_object *scope, zend_object *expr, zval *type, zval *nativeType, zval *certainty);
zv::Val pt_mutating_scope_invalidate_expression(zend_object *scope, zval *expressionToInvalidate, bool requireMoreCharacters = false, zval *invalidatingClass = NULL, bool keepPropertyFetches = false);

/* ExpressionResult.cpp — $result->withScope($scope) / getArgsResult() /
 * getTypeOnScope($scope, $useNativeTypes) /
 * askScopeVariableStateMatches($scope, $useNativeTypes) / atAskPosition($scope)
 * / onNonNullabilityDevicedScopes($beforeScope, $scope); UNDEF / false =
 * pending exception */
zv::Val pt_expression_result_with_scope(zval *result, zval *scope);
zv::Val pt_expression_result_get_args_result(zval *result);
[[nodiscard]] bool pt_expression_result_ask_scope_variable_state_matches(zval *result, zval *scope, bool useNativeTypes, bool &out);
zv::Val pt_expression_result_at_ask_position(zval *result, zval *scope);
zv::Val pt_expression_result_on_non_nullability_deviced_scopes(zval *result, zval *beforeScope, zval *scope);

/* ExpressionResultStorage.cpp / ExpressionResultStorageStack.cpp —
 * $storage->storeExpressionResult($expr, $result) (an exception of the
 * method left pending) and $stack->push($storage) / pop(); false = pending
 * exception */
void pt_expression_result_storage_store(zval *storage, zval *expr, zval *expressionResult);
[[nodiscard]] bool pt_expression_result_storage_stack_push(zval *stack, zval *storage);
[[nodiscard]] bool pt_expression_result_storage_stack_pop(zval *stack);

/* ExprPrinter.cpp — $exprPrinter->printExpr($expr); owned string, NULL =
 * pending exception */

/* VariableFlow.cpp — VariableFlow::all(VariableFlow::MENTION_ALL) */
zv::Val pt_variable_flow_all_mention_all();

/* }}} */

/* {{{ ChangedTypeMethodReflection.cpp, ResolvedMethodReflection.cpp — the
 * method reflections the member prototypes create (TypeTraits.cpp), which
 * every method call's reflection goes through; registered after the Type
 * family and ClassReflection, whose classes their signatures name */

extern zend_class_entry *pt_ce_changed_type_method_reflection;
extern zend_class_entry *pt_ce_resolved_method_reflection;
void pt_register_changed_type_method_reflection();
void pt_register_resolved_method_reflection();
/* new ChangedTypeMethodReflection(...) / new ResolvedMethodReflection(...)
 * (borrowed, already of the constructor's parameter types; NULL or IS_NULL
 * for a nullable null); UNDEF = pending exception */
zv::Val pt_changed_type_method_reflection_new(zval *declaringClass, zval *reflection, zval *variants, zval *namedArgumentsVariants, zval *selfOutType, zval *throwType, zval *assertions);
zv::Val pt_resolved_method_reflection_new(zval *reflection, zval *resolvedTemplateTypeMap, zval *callSiteVarianceMap);
/* the ExtendedMethodReflection interface's methods */
enum pt_method_reflection_member
{
	PT_MR_GET_NAME = 0,
	PT_MR_GET_PROTOTYPE,
	PT_MR_GET_VARIANTS,
	PT_MR_GET_ONLY_VARIANT,
	PT_MR_GET_NAMED_ARGUMENTS_VARIANTS,
	PT_MR_GET_DECLARING_CLASS,
	PT_MR_IS_STATIC,
	PT_MR_IS_PRIVATE,
	PT_MR_IS_PUBLIC,
	PT_MR_GET_DOC_COMMENT,
	PT_MR_IS_DEPRECATED,
	PT_MR_GET_DEPRECATED_DESCRIPTION,
	PT_MR_IS_FINAL,
	PT_MR_IS_FINAL_BY_KEYWORD,
	PT_MR_IS_INTERNAL,
	PT_MR_IS_BUILTIN,
	PT_MR_GET_THROW_TYPE,
	PT_MR_HAS_SIDE_EFFECTS,
	PT_MR_IS_PURE,
	PT_MR_GET_PURE_UNLESS_CALLABLE_IS_IMPURE_PARAMETERS,
	PT_MR_GET_ASSERTS,
	PT_MR_ACCEPTS_NAMED_ARGUMENTS,
	PT_MR_GET_SELF_OUT_TYPE,
	PT_MR_RETURNS_BY_REFERENCE,
	PT_MR_IS_ABSTRACT,
	PT_MR_GET_ATTRIBUTES,
	PT_MR_MUST_USE_RETURN_VALUE,
	PT_MR_GET_RESOLVED_PHP_DOC,
	PT_MR_MEMBER_COUNT
};
/* $method-><member>() of any method reflection (borrowed): the native body
 * of a ResolvedMethodReflection / ChangedTypeMethodReflection, the method
 * through one cached site per member otherwise; UNDEF = pending exception.
 * The per-class _call entries take that native class's body unconditionally. */
zv::Val pt_extended_method_reflection_call(zval *method, pt_method_reflection_member member);
zv::Val pt_changed_type_method_reflection_call(zend_object *method, pt_method_reflection_member member);
zv::Val pt_resolved_method_reflection_call(zend_object *method, pt_method_reflection_member member);
/* the TrinaryLogic value (PT_TRI_*) of a trinary member's answer; -1 =
 * pending exception */
zend_long pt_extended_method_reflection_trinary(zval *method, pt_method_reflection_member member);

/* ClassReflection.cpp — $classReflection->getDisplayName($withTemplateTypes)
 * / ->isBuiltin() for native callers (the native body for the shadowing
 * class, the method otherwise); UNDEF / false = pending exception */
zv::Val pt_class_reflection_get_display_name(zend_object *classReflection, bool withTemplateTypes = true);
[[nodiscard]] bool pt_class_reflection_is_builtin(zend_object *classReflection, bool &out);

/* }}} */

/* {{{ SimpleImpurePoint.cpp — registered after the method reflections */

extern zend_class_entry *pt_ce_simple_impure_point;
void pt_register_simple_impure_point();
/* new SimpleImpurePoint($identifier, $description, $certain); UNDEF =
 * pending exception */
zv::Val pt_simple_impure_point_new(zend_string *identifier, zend_string *description, bool certain);
/* SimpleImpurePoint::createFromVariant($function, $variant, $scope, $args)
 * without the object: exists false for the twin's null, the three
 * constructor arguments otherwise (identifier a permanent interned string,
 * description owned by the caller, who releases it); $variant / $scope NULL
 * or IS_NULL for null, $args an array; false = pending exception */
struct pt_simple_impure_point_data
{
	bool exists = false;
	zend_string *identifier = nullptr;
	zend_string *description = nullptr;
	bool certain = false;
};
[[nodiscard]] bool pt_simple_impure_point_resolve(zval *function, zval *variant, zval *scope, zval *args, pt_simple_impure_point_data &out);

/* }}} */

/* {{{ MethodCallHandler.cpp, DynamicReturnTypeStoragePrimer.cpp — registered
 * after the method reflections and SimpleImpurePoint */

extern zend_class_entry *pt_ce_method_call_handler;
extern zend_class_entry *pt_ce_dynamic_return_type_storage_primer;
void pt_register_dynamic_return_type_storage_primer();
void pt_register_method_call_handler();
/* $popPrimedStorage = $primer->pushPrimedStorage($scope, $argsResult) and,
 * in the caller's finally, $popPrimedStorage(): the native primer pushes
 * and records whether it did (no closure), any other primer's method runs
 * and its closure is kept in pop (owned, UNDEF otherwise). push false /
 * pop false = pending exception; pop releases the closure either way. */
struct pt_primed_storage
{
	zend_object *scope = nullptr;
	bool pushed = false;
	zval pop;
};
[[nodiscard]] bool pt_dynamic_return_type_storage_primer_push(zval *primer, zval *scope, zval *argsResult, pt_primed_storage &out);
[[nodiscard]] bool pt_dynamic_return_type_storage_primer_pop(pt_primed_storage &primed);

/* MutatingScope.cpp — the method call handler's scope calls, next to the
 * walk's above ($scope->invalidateExpression() with its optional parameters
 * is declared there): the native body for exactly a MutatingScope, the
 * method through the class entry otherwise; UNDEF = pending exception */
zv::Val pt_mutating_scope_get_naked_method(zend_object *scope, zval *typeWithMethod, zend_string *methodName);
zv::Val pt_mutating_scope_invalidate_volatile_expressions(zend_object *scope);
zv::Val pt_mutating_scope_enter_closure_call(zend_object *scope, zval *thisType, zval *nativeThisType);
zv::Val pt_mutating_scope_restore_original_scope_after_closure_bind(zend_object *scope, zval *originalScope);
zv::Val pt_mutating_scope_merge_initialized_properties(zend_object *scope, zval *calledMethodScope);
zv::Val pt_mutating_scope_get_function_name(zend_object *scope);

/* ExpressionResult.cpp — $result->finalize(...) /
 * ->getKeepVoidType($nativeTypesPromoted) ($variableFlow NULL or IS_NULL
 * for null); UNDEF = pending exception */
zv::Val pt_expression_result_finalize(zval *result, zval *scope, bool hasYield, bool isAlwaysTerminating, zval *throwPoints, zval *impurePoints, zval *variableFlow);
zv::Val pt_expression_result_get_keep_void_type(zval *result, bool nativeTypesPromoted);

/* VariableFlow.cpp — VariableFlow::exit(VariableFlow::STOP);
 * VariableFlowBuilder.cpp — VariableFlowBuilder::throws($expr, $throwPoints)
 * / ::arguments($call, $argsResult, $storage); UNDEF = pending exception */
zv::Val pt_variable_flow_exit_stop();
zv::Val pt_variable_flow_builder_throws(zval *expr, HashTable *throwPoints);
zv::Val pt_variable_flow_builder_arguments(zval *call, zval *argsResult, zval *storage);

/* TypeUtils.cpp — TypeUtils::findThisType($type) (the ThisType or null);
 * UNDEF = pending exception */
zv::Val pt_type_utils_find_this_type(zval *type);

/* the method call handler's collaborators: EarlyTerminatingCallHelper.cpp —
 * $helper->isEarlyTerminatingMethodCall($methodName, $calledOnType);
 * MethodCallReturnTypeHelper.cpp — $helper->methodCallReturnType($scope,
 * $typeWithMethod, $methodName, $methodCall, $preResolvedAcceptor,
 * $argsResult) (the type or null; the last two NULL or IS_NULL for null);
 * MethodThrowPointHelper.cpp — $helper->getThrowPoint($methodReflection,
 * $parametersAcceptor, $normalizedMethodCall, $scope, $context,
 * $methodCallReturnType) (the throw point or null); TypeSpecifier.cpp —
 * $typeSpecifier->getMethodTypeSpecifyingExtensionsForClass($className):
 * the native body for the shadowing class, the method otherwise (everything
 * borrowed); UNDEF / false = pending exception */
[[nodiscard]] bool pt_early_terminating_call_helper_is_early_terminating_method_call(zval *helper, zval *methodName, zval *calledOnType, bool &out);
zv::Val pt_method_call_return_type_helper_method_call_return_type(zval *helper, zval *scope, zval *typeWithMethod, zval *methodName, zval *methodCall, zval *preResolvedAcceptor, zval *argsResult);
zv::Val pt_method_throw_point_helper_get_throw_point(zval *helper, zval *methodReflection, zval *parametersAcceptor, zval *normalizedMethodCall, zval *scope, zval *context, zval *methodCallReturnType);
zv::Val pt_type_specifier_get_method_type_specifying_extensions_for_class(zend_object *typeSpecifier, zval *className);

/* }}} */

/* {{{ the assignment handlers (AssignHandler.cpp, AssignOpHandler.cpp) and
 * the direct entries they added to the native classes they call */

/* MutatingScope.cpp — $scope->enterExpressionAssign($expr, $isPlainWrite) /
 * exitExpressionAssign($expr) / assignInitializedProperty($fetchedOnType,
 * $propertyName) / addConditionalExpressions($exprString, $holders) /
 * getStaticPropertyReflection($type, $propertyName) / getDefinedVariables() /
 * getMaybeDefinedVariables() / isDeclareStrictTypes(): the native body for
 * exactly a MutatingScope, the method by name otherwise (every argument
 * borrowed); UNDEF / false = pending exception */
zv::Val pt_mutating_scope_enter_expression_assign(zend_object *scope, zend_object *expr, bool isPlainWrite);
zv::Val pt_mutating_scope_exit_expression_assign(zend_object *scope, zend_object *expr);
zv::Val pt_mutating_scope_assign_initialized_property(zend_object *scope, zval *fetchedOnType, zend_string *propertyName);
zv::Val pt_mutating_scope_add_conditional_expressions(zend_object *scope, zend_string *exprString, HashTable *conditionalExpressionHolders);
zv::Val pt_mutating_scope_get_static_property_reflection(zend_object *scope, zval *typeWithProperty, zend_string *propertyName);
zv::Val pt_mutating_scope_get_defined_variables(zend_object *scope);
zv::Val pt_mutating_scope_get_maybe_defined_variables(zend_object *scope);
[[nodiscard]] bool pt_mutating_scope_is_declare_strict_types(zend_object *scope, bool &out);

/* NodeScopeResolver.cpp — $nodeScopeResolver->getAssignedVariables($expr) /
 * ->readStoredResult($expr, $storage) /
 * ->lookForSetAllowedUndefinedExpressions($scope, $expr); NonNullabilityHelper.cpp
 * — $helper->ensureNonNullability($scope, $expr): the native body for the
 * native class, the method otherwise (everything borrowed); UNDEF = pending
 * exception */
zv::Val pt_node_scope_resolver_get_assigned_variables(zval *nodeScopeResolver, zval *expr);
zv::Val pt_node_scope_resolver_read_stored_result(zval *nodeScopeResolver, zval *expr, zval *storage);
zv::Val pt_node_scope_resolver_look_for_set_allowed_undefined_expressions(zval *nodeScopeResolver, zval *scope, zval *expr);
zv::Val pt_non_nullability_helper_ensure_non_nullability(zval *helper, zval *scope, zval *expr);
/* MethodThrowPointHelper.cpp — $helper->getThrowPointsForCallOnType($scope,
 * $context, $calledOnType, $methodCall); UNDEF = pending exception */
zv::Val pt_method_throw_point_helper_get_throw_points_for_call_on_type(zval *helper, zval *scope, zval *context, zval *calledOnType, zval *methodCall);

/* VariableFlowBuilder.cpp — VariableFlowBuilder::targetRead($target, $storage,
 * $read, $targetId) / targetWrite($target, $kind, $scope, $storage,
 * $redundant) / writeSite($target, $kind, $scope, $storage) /
 * escapeRoot($expr) (NULL or an IS_NULL zval for null, everything borrowed);
 * a flow / VariableWrite or null, UNDEF = pending exception */
zv::Val pt_variable_flow_builder_target_read(zval *target, zval *storage, bool read, zval *targetId);
zv::Val pt_variable_flow_builder_target_write(zval *target, zend_long kind, zval *scope, zval *storage, zval *redundant);
zv::Val pt_variable_flow_builder_write_site(zval *target, zend_long kind, zval *scope, zval *storage);
zv::Val pt_variable_flow_builder_escape_root(zval *expr);
/* VariableFlow.cpp — VariableFlow::inputs($writeId, $targetId) /
 * choice(...$branches); UNDEF = pending exception */
zv::Val pt_variable_flow_inputs(zend_long writeId, zval *targetId);
zv::Val pt_variable_flow_choice(uint32_t argc, zval *argv);

/* AssignHandler.cpp — the shadowing class entry, its registrar and
 * $assignHandler->prepareTarget(...) / ->applyWrite(...) /
 * ->processVirtualAssign(...) for native callers: the native body for the
 * shadowing class, the method otherwise (everything borrowed,
 * $assignedValueResult / $assignedExprResult NULL for null); UNDEF = pending
 * exception */
extern zend_class_entry *pt_ce_assign_handler;
void pt_register_assign_handler();
zv::Val pt_assign_handler_prepare_target(zval *handler, zval *nodeScopeResolver, zval *scope, zval *storage, zval *stmt, zval *var, zval *assignedExpr, zval *nodeCallback, zval *context, zval *mode);
zv::Val pt_assign_handler_apply_write(zval *handler, zval *nodeScopeResolver, zval *target, zval *valueResult, zval *assignedValueResult, zval *stmt, zval *storage, zval *nodeCallback, zval *context);
zv::Val pt_assign_handler_process_virtual_assign(zval *handler, zval *nodeScopeResolver, zval *scope, zval *storage, zval *stmt, zval *var, zval *assignedExpr, zval *nodeCallback, zval *assignedExprResult);
/* AssignOpHandler.cpp — registered after AssignHandler */
extern zend_class_entry *pt_ce_assign_op_handler;
void pt_register_assign_op_handler();

/* }}} */

/* {{{ the statement handlers (ExpressionHandler.cpp, ReturnHandler.cpp,
 * EchoHandler.cpp, BlockHandler.cpp, NopHandler.cpp) and the direct entries
 * they read through — registered at the END of the sequence (their
 * signatures name MutatingScope, the contexts and the statement results) */

extern zend_class_entry *pt_ce_expression_handler;
extern zend_class_entry *pt_ce_return_handler;
extern zend_class_entry *pt_ce_echo_handler;
extern zend_class_entry *pt_ce_block_handler;
extern zend_class_entry *pt_ce_nop_handler;
void pt_register_expression_handler();
void pt_register_return_handler();
void pt_register_echo_handler();
void pt_register_block_handler();
void pt_register_nop_handler();

/* ExpressionResult.cpp — $result->getTruthyScope() / ->getFalseyScope()
 * (withScope() is declared with the walk hub's entries): the native body for
 * a native result, the method otherwise (everything borrowed); UNDEF =
 * pending exception */
zv::Val pt_expression_result_get_truthy_scope(zval *result);
zv::Val pt_expression_result_get_falsey_scope(zval *result);

/* VariableFlow.cpp — VariableFlow::exit($kind, $level, $name) for the kind
 * constants RETURN / BREAK / CONTINUE / STOP ($name NULL for null) and
 * VariableFlow::conditional($condition, $if, $else, $truthy) (the flows NULL
 * or IS_NULL for null, $truthy -1 for null, else 0/1); UNDEF = pending
 * exception */
enum pt_variable_flow_exit_kind
{
	PT_VARIABLE_FLOW_EXIT_RETURN,
	PT_VARIABLE_FLOW_EXIT_BREAK,
	PT_VARIABLE_FLOW_EXIT_CONTINUE,
	PT_VARIABLE_FLOW_EXIT_STOP,
};
zv::Val pt_variable_flow_exit(pt_variable_flow_exit_kind kind, zend_long level = 1, zend_string *name = NULL);
zv::Val pt_variable_flow_conditional(zval *condition, zval *ifFlow, zval *elseFlow, int truthy);

/* MutatingScope.cpp — $scope->getAnonymousFunctionReflection(): the native
 * body for a MutatingScope (or a subclass inheriting the method), the
 * method by name otherwise; UNDEF = pending exception */
zv::Val pt_mutating_scope_get_anonymous_function_reflection(zend_object *scope);

/* NodeScopeResolver.cpp — $nodeScopeResolver->pushNodeGatherer($gatherer) /
 * ->popNodeGatherer() / ->collectReturnSend($scope, $returnedResult): the
 * native body for exactly the native class, the method otherwise (everything
 * borrowed); false / UNDEF = pending exception */
[[nodiscard]] bool pt_node_scope_resolver_push_node_gatherer(zval *nodeScopeResolver, zval *gatherer);
[[nodiscard]] bool pt_node_scope_resolver_pop_node_gatherer(zval *nodeScopeResolver);
zv::Val pt_node_scope_resolver_collect_return_send(zval *nodeScopeResolver, zval *scope, zval *returnedResult);

/* }}} */

/* {{{ the declaration statement handlers (ClassMethodHandler.cpp,
 * FunctionHandler.cpp, ClassLikeHandler.cpp) and the direct entries they
 * call — registered at the END of the sequence */

extern zend_class_entry *pt_ce_class_method_handler;
extern zend_class_entry *pt_ce_function_handler;
extern zend_class_entry *pt_ce_class_like_handler;
void pt_register_class_method_handler();
void pt_register_function_handler();
void pt_register_class_like_handler();

/* MutatingScope.cpp — $scope->enterClassMethod(...$argv) (the 20 positional
 * arguments) / ->enterFunction(...$argv) (the 16 positional arguments) /
 * ->enterClass($classReflection) / ->rememberConstructorScope() /
 * ->invalidateExistenceCheckExpressions($functionNames, $declaredSymbolName)
 * (IS_NULL for null) / ->getNamespace(), next to the walk hub's
 * push/popExpressionResultStorage(), assignExpression() and getFile(): the
 * native body for exactly a MutatingScope (or one inheriting a named
 * handler) with arguments passing the glue's checks, the method by name
 * otherwise (everything borrowed); UNDEF = pending exception */
zv::Val pt_mutating_scope_enter_class_method(zend_object *scope, zval *argv);
zv::Val pt_mutating_scope_enter_function(zend_object *scope, zval *argv);
zv::Val pt_mutating_scope_enter_class(zend_object *scope, zval *classReflection);
zv::Val pt_mutating_scope_remember_constructor_scope(zend_object *scope);
zv::Val pt_mutating_scope_invalidate_existence_check_expressions(zend_object *scope, zval *functionNames, zval *declaredSymbolName);
zv::Val pt_mutating_scope_get_namespace(zend_object *scope);

/* ClassReflection.cpp — $classReflection->hasConstructor() /
 * ->getConstructor() / ->isReadOnly() / ->getFileName() /
 * ->evictPrivateSymbols(): the native body for the shadowing class, the
 * method otherwise; false / UNDEF = pending exception */
[[nodiscard]] bool pt_class_reflection_has_constructor(zend_object *classReflection, bool &out);
zv::Val pt_class_reflection_get_constructor(zend_object *classReflection);
[[nodiscard]] bool pt_class_reflection_is_read_only(zend_object *classReflection, bool &out);
zv::Val pt_class_reflection_get_file_name(zend_object *classReflection);
[[nodiscard]] bool pt_class_reflection_evict_private_symbols(zend_object *classReflection);

/* VariableLivenessResolver.cpp — VariableLivenessResolver::resolve($function,
 * $flow) ($flow NULL or IS_NULL for null); UNDEF = pending exception */
zv::Val pt_variable_liveness_resolver_resolve(zval *function, zval *flow);

/* ClassStatementsGatherer.cpp — new ClassStatementsGatherer($classReflection,
 * $nodeCallback) / its collected lists (getProperties() ... getPropertyAssigns()
 * of an instance of the shadowing class); UNDEF = pending exception */
enum pt_class_statements_gatherer_list
{
	PT_CSG_LIST_PROPERTIES,
	PT_CSG_LIST_METHODS,
	PT_CSG_LIST_METHOD_CALLS,
	PT_CSG_LIST_PROPERTY_USAGES,
	PT_CSG_LIST_CONSTANTS,
	PT_CSG_LIST_CONSTANT_FETCHES,
	PT_CSG_LIST_RETURN_STATEMENT_NODES,
	PT_CSG_LIST_PROPERTY_ASSIGNS,
};
zv::Val pt_class_statements_gatherer_new(zval *classReflection, zval *nodeCallback);
zv::Val pt_class_statements_gatherer_get(zval *gatherer, pt_class_statements_gatherer_list list);

/* }}} */

/* {{{ IfHandler.cpp — registered at the END of the sequence */

extern zend_class_entry *pt_ce_if_handler;
void pt_register_if_handler();

/* }}} */

/* {{{ StaticCallHandler.cpp — registered after MethodCallHandler; the call
 * handlers share CallHandlerSupport.h */

extern zend_class_entry *pt_ce_static_call_handler;
void pt_register_static_call_handler();

/* MutatingScope.cpp — $scope->resolveName($name) /
 * ->enterClosureBind($thisType, $nativeThisType, $scopeClasses) ($thisType /
 * $nativeThisType NULL or IS_NULL for null): the native body for exactly a
 * MutatingScope (resolveName() also for a subclass inheriting it), the
 * method otherwise; UNDEF = pending exception
 * (assignInitializedProperty() is declared with the assignment handlers') */
zv::Val pt_mutating_scope_resolve_name(zend_object *scope, zend_object *name);
zv::Val pt_mutating_scope_enter_closure_bind(zend_object *scope, zval *thisType, zval *nativeThisType, zval *scopeClasses);

/* ClassReflection.cpp — $classReflection->is($className) /
 * ->isSubclassOfClass($class) for native callers (the native body for the
 * shadowing class, the method otherwise); false = pending exception */
[[nodiscard]] bool pt_class_reflection_is(zend_object *classReflection, zval *className, bool &out);
[[nodiscard]] bool pt_class_reflection_is_subclass_of_class(zend_object *classReflection, zval *otherClassReflection, bool &out);

/* TypeSpecifier.cpp — $typeSpecifier->getStaticMethodTypeSpecifyingExtensionsForClass($className):
 * the native body for the shadowing class, the method otherwise; UNDEF =
 * pending exception */
zv::Val pt_type_specifier_get_static_method_type_specifying_extensions_for_class(zend_object *typeSpecifier, zval *className);

/* }}} */

/* {{{ NewHandler.cpp — registered after StaticCallHandler */

extern zend_class_entry *pt_ce_new_handler;
void pt_register_new_handler();

/* ClassReflection.cpp — $classReflection->isFinal() (false = pending
 * exception) / ->asFinal() / ->getTemplateTypeMap() /
 * ->getActiveTemplateTypeMap() / ->typeMapToList($typeMap) /
 * ->withTypes($types) (UNDEF = pending exception): the native body for the
 * shadowing class, the method otherwise (hasConstructor() /
 * getConstructor() are declared with the declaration statement handlers') */
[[nodiscard]] bool pt_class_reflection_is_final(zend_object *classReflection, bool &out);
zv::Val pt_class_reflection_as_final(zend_object *classReflection);
zv::Val pt_class_reflection_get_template_type_map(zend_object *classReflection);
zv::Val pt_class_reflection_get_active_template_type_map(zend_object *classReflection);
zv::Val pt_class_reflection_type_map_to_list(zend_object *classReflection, zval *typeMap);
zv::Val pt_class_reflection_with_types(zend_object *classReflection, zval *types);

/* TemplateTypeMap.cpp — $map->getTypes() / ->getType($name) (the type or
 * null) / ->resolveToBounds() / ->map($cb): the native body for the
 * shadowing class, the method otherwise; UNDEF = pending exception */
zv::Val pt_template_type_map_get_types(zval *map);
zv::Val pt_template_type_map_get_type(zval *map, zend_string *name);
zv::Val pt_template_type_map_resolve_to_bounds(zval *map);
zv::Val pt_template_type_map_map(zval *map, zval *cb);

/* SimpleImpurePoint.cpp —
 * SimpleImpurePoint::resolvePureUnlessCallableIsImpureVerdict($variant,
 * $scope, $args): hasVerdict false for the twin's null, verdict the PT_TRI_*
 * value otherwise; false = pending exception */
[[nodiscard]] bool pt_simple_impure_point_resolve_verdict(zval *variant, zval *scope, zval *args, bool &hasVerdict, zend_long &verdict);

/* }}} */

/* {{{ the VariableFlow subclasses (VariableAccessFlow.cpp,
 * VariableSequenceFlow.cpp, VariableInputFlow.cpp, VariableControlFlow.cpp):
 * final native subclasses of the native VariableFlow, registered after it.
 * The factories construct a fresh instance with every promoted slot written
 * (borrowed arguments; NULL for null, or [] for an array parameter); readers
 * compare the class entry and read ptdecl::Variable*Flow::slot in place.
 * UNDEF = pending exception */

extern zend_class_entry *pt_ce_variable_access_flow;
extern zend_class_entry *pt_ce_variable_sequence_flow;
extern zend_class_entry *pt_ce_variable_input_flow;
extern zend_class_entry *pt_ce_variable_control_flow;
void pt_register_variable_access_flow();
void pt_register_variable_sequence_flow();
void pt_register_variable_input_flow();
void pt_register_variable_control_flow();

/* a constructor's assignment of a promoted readonly slot declared by
 * `declaring`: the engine's "Cannot modify readonly property" Error when the
 * slot is already initialized (false), the write otherwise */
[[nodiscard]] bool pt_variable_flow_init_readonly(zend_object *self, uint32_t index, zval *value, zend_class_entry *declaring, const char *name);

/* new VariableAccessFlow($kind, $name, $write, $type, $targetId, $container, $offset) */
zv::Val pt_variable_access_flow_new(zend_string *kind, zval *name, zval *write, zval *type, zval *targetId, bool container, zval *offset);
/* new VariableSequenceFlow($kind, $children) */
zv::Val pt_variable_sequence_flow_new(zend_string *kind, zval *children);
/* new VariableInputFlow($writeId, $targetId) */
zv::Val pt_variable_input_flow_new(zend_long writeId, zval *targetId);

/* the parameters of new VariableControlFlow($kind, ...) after $kind, at the
 * twin's defaults */
struct pt_variable_control_flow_args
{
	zval *children = NULL;
	zend_string *name = NULL;
	zval *type = NULL;
	zend_long level = 1;
	bool atLeastOnce = false;
	bool canExit = true;
	zval *catches = NULL;
	zval *arrow = NULL;
	zval *cases = NULL;
	bool canRepeat = true;
	bool canContainAnyThrowable = false;
	zval *stmt = NULL;
	zval *bindings = NULL;
	zval *ownWrites = NULL;
};
zv::Val pt_variable_control_flow_new(zend_string *kind, const pt_variable_control_flow_args &args);

/* }}} */

/* {{{ VarAnnotationProcessor.cpp — the shadowing DI service and
 * $processor->processVarAnnotation($scope, $variableNames, $node, $changed)
 * for native callers: the native body for the native class, the method by
 * name otherwise (everything borrowed; $variableNames an array zval;
 * *changed set to true when a tag assigns, NULL when the caller does not
 * pass $changed). The resulting scope, UNDEF = pending exception */

extern zend_class_entry *pt_ce_var_annotation_processor;
void pt_register_var_annotation_processor();
zv::Val pt_var_annotation_processor_process_var_annotation(zval *processor, zval *scope, zval *variableNames, zval *node, bool *changed);
/* {{{ the function-call cluster (FunctionReflectionAccess.cpp,
 * OutputBufferHelper.cpp, FuncCallScopeEffectsHelper.cpp) */

/* FunctionReflectionAccess.cpp — the getters of the PHP function reflection
 * classes the call handlers read on every call: the constructor-written slot
 * of exactly NativeFunctionReflection / ExtendedFunctionVariant /
 * ExtendedNativeParameterReflection / Assertions, the method (its result
 * kept in hold) for anything else; NULL / false = pending exception */
zval *pt_function_reflection_name(zval *reflection, zv::Val &hold);
zval *pt_function_reflection_variants(zval *reflection, zv::Val &hold);
zval *pt_function_reflection_named_arguments_variants(zval *reflection, zv::Val &hold);
zval *pt_function_reflection_throw_type(zval *reflection, zv::Val &hold);
zval *pt_function_reflection_asserts(zval *reflection, zv::Val &hold);
[[nodiscard]] bool pt_function_reflection_is_builtin(zval *reflection, bool &out);
/* $reflection->hasSideEffects(): the TrinaryLogic (borrowed or in hold) */
zval *pt_function_reflection_has_side_effects(zval *reflection, zv::Val &hold);
zval *pt_parameters_acceptor_return_type(zval *acceptor, zv::Val &hold);
zval *pt_parameters_acceptor_parameters(zval *acceptor, zv::Val &hold);
[[nodiscard]] bool pt_parameter_reflection_is_optional(zval *parameter, bool &out);
zval *pt_assertions_all(zval *assertions, zv::Val &hold);

/* MutatingScope.cpp — $scope->afterExtractCall() /
 * afterClearstatcacheCall() / afterOpenSslCall($name); UNDEF = pending
 * exception */
zv::Val pt_mutating_scope_after_extract_call(zend_object *scope);
zv::Val pt_mutating_scope_after_clearstatcache_call(zend_object *scope);
zv::Val pt_mutating_scope_after_open_ssl_call(zend_object *scope, zend_string *openSslFunctionName);

/* OutputBufferHelper.cpp — the shadowing class and its public methods for
 * native callers (the native body for the native class, the method by name
 * otherwise); ok = false / UNDEF = pending exception */
extern zend_class_entry *pt_ce_output_buffer_helper;
void pt_register_output_buffer_helper();
zend_long pt_output_buffer_helper_get_level_delta(zval *helper, zend_string *functionName, bool &ok);
zv::Val pt_output_buffer_helper_apply_level_delta(zval *helper, zval *nodeScopeResolver, zval *scope, zend_long delta);

/* FuncCallScopeEffectsHelper.cpp — the same; $functionReflection /
 * $parametersAcceptor NULL (or IS_NULL) for null */
extern zend_class_entry *pt_ce_func_call_scope_effects_helper;
void pt_register_func_call_scope_effects_helper();
zv::Val pt_func_call_scope_effects_helper_apply_array_walk_result(zval *helper, zval *nodeScopeResolver, zval *stmt, zval *arrayWalkArrayArg, zval *arrayWalkValueTypes, zval *argsResult, zval *scope, zval *storage, zval *nodeCallback);
zv::Val pt_func_call_scope_effects_helper_apply_call_scope_effects(zval *helper, zval *nodeScopeResolver, zval *stmt, zval *normalizedExpr, zval *functionReflection, zval *parametersAcceptor, zval *argsResult, zval *scope, zval *scopeBeforeArgs, zval *storage, zval *nodeCallback);

/* FuncCallHandler.cpp — the shadowing class (processExpr() is its handler
 * entry) */
extern zend_class_entry *pt_ce_func_call_handler;
void pt_register_func_call_handler();

/* TypeSpecifier.cpp — $typeSpecifier->getFunctionTypeSpecifyingExtensions();
 * EarlyTerminatingCallHelper.cpp —
 * $helper->isEarlyTerminatingFunctionCall($name); ArgsResult.cpp —
 * $argsResult->withResolvedParametersAcceptor($acceptor (NULL or IS_NULL =
 * null)); UNDEF / false = pending exception */
zv::Val pt_type_specifier_get_function_type_specifying_extensions(zval *typeSpecifier);
[[nodiscard]] bool pt_early_terminating_call_helper_is_early_terminating_function_call(zval *helper, zend_string *functionName, bool &out);
zv::Val pt_args_result_with_resolved_parameters_acceptor(zval *argsResult, zval *resolvedParametersAcceptor);

/* ArgumentsNormalizer.cpp — ArgumentsNormalizer::reorderCallUserFuncArguments($call,
 * $scope) / ::reorderCallUserFuncArrayArguments($call, $scope) ([$callbackArg,
 * $innerFuncCall] or null; a FuncCall and a scope, borrowed); UNDEF =
 * pending exception */
zv::Val pt_arguments_normalizer_reorder_call_user_func_arguments(zval *callUserFuncCall, zval *scope);
zv::Val pt_arguments_normalizer_reorder_call_user_func_array_arguments(zval *callUserFuncArrayCall, zval *scope);

/* FunctionReflectionAccess.cpp — $reflectionProvider->hasFunction($nameNode,
 * $namespaceAnswerer) / ->getFunction(...): the memoized answer of a
 * MemoizingReflectionProvider over a BetterReflectionProvider
 * (resolvedFunctionNames / functionReflections), the method on a miss or for
 * any other provider, name or answerer; false / UNDEF = pending exception */
[[nodiscard]] bool pt_reflection_provider_has_function(zval *provider, zval *nameNode, zval *namespaceAnswerer, bool &out);
zv::Val pt_reflection_provider_get_function(zval *provider, zval *nameNode, zval *namespaceAnswerer);

/* }}} */

/* {{{ ArgumentsHandler.cpp — the shadowing PHPStan\Analyser\ArgumentsHandler,
 * registered at the END of the sequence */

extern zend_class_entry *pt_ce_arguments_handler;
void pt_register_arguments_handler();
/* $argumentsHandler->processArgs($nodeScopeResolver, $stmt, $calleeReflection,
 * $nakedMethodReflection, $parametersAcceptors, $namedArgumentsVariants,
 * $callLike, $scope, $storage, $nodeCallback, $context,
 * $closureBindScopeFactory) — the native body for the shadowing class, the
 * method otherwise ($calleeReflection / $nakedMethodReflection /
 * $namedArgumentsVariants / $closureBindScopeFactory NULL or IS_NULL for
 * null, everything borrowed); UNDEF = pending exception */
zv::Val pt_arguments_handler_process_args(zval *handler, zval *nodeScopeResolver, zval *stmt, zval *calleeReflection, zval *nakedMethodReflection, zval *parametersAcceptors, zval *namedArgumentsVariants, zval *callLike, zval *scope, zval *storage, zval *nodeCallback, zval *context, zval *closureBindScopeFactory = NULL);
/* $argumentsHandler->processDroppedArgs($nodeScopeResolver, $stmt,
 * $originalCall, $normalizedCall, $scope, $storage, $context); false =
 * pending exception */
[[nodiscard]] bool pt_arguments_handler_process_dropped_args(zval *handler, zval *nodeScopeResolver, zval *stmt, zval *originalCall, zval *normalizedCall, zval *scope, zval *storage, zval *context);
/* NodeScopeResolver.cpp — $nodeScopeResolver->lookForUnsetAllowedUndefinedExpressions($scope,
 * $expr) / ->isReturningStoredExpressionResults() /
 * ->isConsumingStoredExpressionResults(): the native body for the native
 * class, the method otherwise; UNDEF / false = pending exception */
zv::Val pt_node_scope_resolver_look_for_unset_allowed_undefined_expressions(zval *nodeScopeResolver, zval *scope, zval *expr);
[[nodiscard]] bool pt_node_scope_resolver_is_returning_stored_expression_results(zval *nodeScopeResolver, bool &out);
[[nodiscard]] bool pt_node_scope_resolver_is_consuming_stored_expression_results(zval *nodeScopeResolver, bool &out);
/* MutatingScope.cpp — $scope->pushInFunctionCall($reflection, $parameter,
 * $rememberTypes) ($reflection / $parameter IS_NULL for null) /
 * ->popInFunctionCall() / ->withClosureBindScopeClasses($classes) /
 * ->restoreThis($scope) / ->getIterableValueType($type) /
 * ->getIterableKeyType($type): the native body
 * for a MutatingScope (or a subclass inheriting the method), the method by
 * name otherwise (arguments borrowed); UNDEF = pending exception */
zv::Val pt_mutating_scope_push_in_function_call(zend_object *scope, zval *reflection, zval *parameter, bool rememberTypes);
zv::Val pt_mutating_scope_pop_in_function_call(zend_object *scope);
zv::Val pt_mutating_scope_with_closure_bind_scope_classes(zend_object *scope, zval *scopeClasses);
zv::Val pt_mutating_scope_restore_this(zend_object *scope, zval *restoreThisScope);
zv::Val pt_mutating_scope_get_iterable_value_type(zend_object *scope, zval *type);
zv::Val pt_mutating_scope_get_iterable_key_type(zend_object *scope, zval *type);
/* TypeUtils.cpp — TypeUtils::findCallableType($type) (the type or null);
 * UNDEF = pending exception */
zv::Val pt_type_utils_find_callable_type(zval *type);

/* }}} */

/* {{{ the parameter value classes (PassedByReference.cpp, DummyParameter.cpp,
 * ExtendedDummyParameter.cpp) — registered at the END of the sequence, the
 * parent before the child; the slot readers are inline in ParameterValues.h */

extern zend_class_entry *pt_ce_passed_by_reference;
extern zend_class_entry *pt_ce_dummy_parameter;
extern zend_class_entry *pt_ce_extended_dummy_parameter;
void pt_register_passed_by_reference();
void pt_register_dummy_parameter();
void pt_register_extended_dummy_parameter();
/* the twin's private mode constants */
#define PT_PASSED_BY_REFERENCE_NO 1
#define PT_PASSED_BY_REFERENCE_READS_ARGUMENT 2
#define PT_PASSED_BY_REFERENCE_CREATES_NEW_VARIABLE 3
/* PassedByReference::createNo() / createReadsArgument() /
 * createCreatesNewVariable(): the process-wide singleton, borrowed (the
 * class's static $registry holds it); NULL = pending exception */
[[nodiscard]] zend_object *pt_passed_by_reference_create_no();
[[nodiscard]] zend_object *pt_passed_by_reference_create_reads_argument();
[[nodiscard]] zend_object *pt_passed_by_reference_create_creates_new_variable();
/* the PT_PASSED_BY_REFERENCE_* mode of a PassedByReference — the slot of the
 * native class, no() / createsNewVariable() of anything else; -1 = pending
 * exception */
[[nodiscard]] zend_long pt_passed_by_reference_mode(zval *passedByReference);
/* $passedByReference->combine($other) — natively for two native instances,
 * the method otherwise (borrowed); UNDEF = pending exception */
zv::Val pt_passed_by_reference_combine(zval *passedByReference, zval *other);
/* new DummyParameter($name, $type, $optional, $passedByReference, $variadic,
 * $defaultValue) ($passedByReference / $defaultValue NULL for null, the rest
 * borrowed) / DummyParameter's constructor body on an object of it or of a
 * subclass (ExtendedDummyParameter's parent::__construct()); UNDEF / false =
 * pending exception */
zv::Val pt_dummy_parameter_new(zend_string *name, zval *type, bool optional, zval *passedByReference, bool variadic, zval *defaultValue);
[[nodiscard]] bool pt_dummy_parameter_construct(zend_object *object, zend_string *name, zval *type, bool optional, zval *passedByReference, bool variadic, zval *defaultValue);
/* new ExtendedDummyParameter(...$argv) over values as PHP code hands them
 * (borrowed): directly when they already have the parameter types, through
 * the constructor's parameter parsing otherwise; UNDEF = pending exception */
zv::Val pt_extended_dummy_parameter_new(uint32_t argc, zval *argv);

/* }}} */

/* {{{ ArgumentsNormalizer.cpp — the shadowing PHPStan\Analyser\ArgumentsNormalizer,
 * registered at the END of the sequence */

extern zend_class_entry *pt_ce_arguments_normalizer;
void pt_register_arguments_normalizer();
/* ArgumentsNormalizer::reorderArgs($parametersAcceptor, $callArgs) (the list
 * or null) / ::reorderFuncArguments($acceptor, $functionCall) /
 * ::reorderMethodArguments($acceptor, $methodCall) /
 * ::reorderStaticCallArguments($acceptor, $staticCall) /
 * ::reorderNewArguments($acceptor, $new) (the call itself when nothing was
 * reordered, a rebuilt call, or null) — arguments borrowed, of the twin's
 * parameter types; UNDEF = pending exception */
zv::Val pt_arguments_normalizer_reorder_args(zval *parametersAcceptor, zval *callArgs);
zv::Val pt_arguments_normalizer_reorder_func_arguments(zval *parametersAcceptor, zval *functionCall);
zv::Val pt_arguments_normalizer_reorder_method_arguments(zval *parametersAcceptor, zval *methodCall);
zv::Val pt_arguments_normalizer_reorder_static_call_arguments(zval *parametersAcceptor, zval *staticCall);
zv::Val pt_arguments_normalizer_reorder_new_arguments(zval *parametersAcceptor, zval *newExpr);

/* }}} */

/* {{{ ParametersAcceptorSelector.cpp — the shadowing
 * PHPStan\Reflection\ParametersAcceptorSelector, registered at the END of the
 * sequence */

extern zend_class_entry *pt_ce_parameters_acceptor_selector;
void pt_register_parameters_acceptor_selector();
/* ParametersAcceptorSelector::selectFromArgs($scope, $args, $acceptors,
 * $namedArgumentsVariants) / ::selectFromTypes($types, $acceptors, $unpack) /
 * ::combineVariantsForNormalization($args, $variants, $namedArgumentsVariants)
 * / ::combineAcceptors($acceptors) — the native bodies for arguments of the
 * twin's parameter types, the method otherwise ($namedArgumentsVariants NULL
 * or IS_NULL for null, everything borrowed); UNDEF = pending exception */
zv::Val pt_parameters_acceptor_selector_select_from_args(zval *scope, zval *args, zval *parametersAcceptors, zval *namedArgumentsVariants);
zv::Val pt_parameters_acceptor_selector_select_from_types(zval *types, zval *parametersAcceptors, bool unpack);
zv::Val pt_parameters_acceptor_selector_combine_variants_for_normalization(zval *args, zval *variants, zval *namedArgumentsVariants);
zv::Val pt_parameters_acceptor_selector_combine_acceptors(zval *acceptors);
/* ::applyIntrinsicArgOverrides(...) over arrays, with the four getters any
 * callables (a native closure holder included — the public method's \Closure
 * types are not re-checked) */
zv::Val pt_parameters_acceptor_selector_apply_intrinsic_arg_overrides(zval *args, zval *parametersAcceptors, zval *namedArgumentsVariants, zval *scope, zval *typeGetter, zval *nativeTypeGetter, zval *iterableValueTypeGetter, zval *iterableKeyTypeGetter);
/* ::hasAcceptorTemplateOrLateResolvableType($acceptor) /
 * ::hasAcceptorTemplateOrLateResolvableParameterType($acceptor); false =
 * pending exception */
[[nodiscard]] bool pt_parameters_acceptor_selector_has_acceptor_template_or_late_resolvable_type(zval *acceptor, bool &out);
[[nodiscard]] bool pt_parameters_acceptor_selector_has_acceptor_template_or_late_resolvable_parameter_type(zval *acceptor, bool &out);

/* }}} */

/* {{{ the property fetch handlers (PropertyHookThrowPointsResolver.cpp,
 * PropertyFetchHandler.cpp, StaticPropertyFetchHandler.cpp,
 * NullsafePropertyFetchHandler.cpp) — registered after FuncCallHandler */

extern zend_class_entry *pt_ce_property_hook_throw_points_resolver;
extern zend_class_entry *pt_ce_property_fetch_handler;
extern zend_class_entry *pt_ce_static_property_fetch_handler;
extern zend_class_entry *pt_ce_nullsafe_property_fetch_handler;
void pt_register_property_hook_throw_points_resolver();
void pt_register_property_fetch_handler();
void pt_register_static_property_fetch_handler();
void pt_register_nullsafe_property_fetch_handler();

/* $resolver->getThrowPointsFromPropertyHook($scope, $propertyFetch,
 * $propertyReflection, $hookName) — the native body for the shadowing class
 * and a PhpPropertyReflection, the method otherwise; UNDEF = pending
 * exception */
zv::Val pt_property_hook_throw_points_resolver_get_throw_points_from_property_hook(zval *resolver, zval *scope, zval *propertyFetch, zval *propertyReflection, zend_string *hookName);

/* $propertyFetchHandler->composeResult($nodeScopeResolver, $expr, $varResult,
 * $nameResult, $scopeBeforeVar, $beforeScope) /
 * $staticPropertyFetchHandler->composeResult($expr, $classResult,
 * $nameResult, $beforeScope) — the native body for the shadowing class, the
 * method otherwise ($classResult / $nameResult NULL or IS_NULL for null);
 * UNDEF = pending exception */
zv::Val pt_property_fetch_handler_compose_result(zval *handler, zval *nodeScopeResolver, zval *expr, zval *varResult, zval *nameResult, zval *scopeBeforeVar, zval *beforeScope);
zv::Val pt_static_property_fetch_handler_compose_result(zval *handler, zval *expr, zval *classResult, zval *nameResult, zval *beforeScope);

/* PropertyHookThrowPointsResolver.cpp — an ExtendedPropertyReflection's
 * ->getDeclaringClass() / ->hasNativeType() / ->getNativeType() /
 * ->getReadableType() / ->getWritableType(): the declared slots of the final
 * ResolvedPropertyReflection / ChangedTypePropertyReflection /
 * PhpPropertyReflection read in place where the getter only returns (or
 * forwards to) one, a filled memo included; the method through a cached site
 * otherwise. $phpVersion->supportsPropertyHooks(): the versionId slot of
 * PhpVersion, the method otherwise. UNDEF / false = pending exception */
zv::Val pt_property_reflection_get_declaring_class(zval *reflection);
[[nodiscard]] bool pt_property_reflection_has_native_type(zval *reflection, bool &out);
zv::Val pt_property_reflection_get_native_type(zval *reflection);
zv::Val pt_property_reflection_get_readable_type(zval *reflection);
zv::Val pt_property_reflection_get_writable_type(zval *reflection);
[[nodiscard]] bool pt_php_version_supports_property_hooks(zval *phpVersion, bool &out);

/* MutatingScope.cpp — $scope->getInstancePropertyReflection($type, $name) /
 * ->isInWriteExpressionAssign($expr): the native body for exactly a
 * MutatingScope, the method otherwise; UNDEF / false = pending exception */
zv::Val pt_mutating_scope_get_instance_property_reflection(zend_object *scope, zval *typeWithProperty, zend_string *propertyName);
[[nodiscard]] bool pt_mutating_scope_is_in_write_expression_assign(zend_object *scope, zend_object *expr, bool &out);

/* ClassReflection.cpp — $classReflection->hasNativeProperty($name) /
 * ->getNativeProperty($name): the native body for the shadowing class, the
 * method otherwise; false / UNDEF = pending exception */
[[nodiscard]] bool pt_class_reflection_has_native_property(zend_object *classReflection, zend_string *propertyName, bool &out);
zv::Val pt_class_reflection_get_native_property(zend_object *classReflection, zend_string *propertyName);

/* NonNullabilityHelper.cpp — $helper->getActiveEnsuredOriginalType($expr,
 * $native) (a type or null) / ->ensureShallowNonNullability($scope,
 * $originalScope, $exprToSpecify) / ->revertNonNullability($scope,
 * $specifiedExpressions): the native body for the shadowing class, the method
 * otherwise; UNDEF = pending exception */
zv::Val pt_non_nullability_helper_get_active_ensured_original_type(zval *helper, zval *expr, bool native);
zv::Val pt_non_nullability_helper_ensure_shallow_non_nullability(zval *helper, zval *scope, zval *originalScope, zval *exprToSpecify);
zv::Val pt_non_nullability_helper_revert_non_nullability(zval *helper, zval *scope, zval *specifiedExpressions);

/* NodeScopeResolver.cpp — $nodeScopeResolver->processExprNodeConsumingStored(...):
 * the native body for exactly the native class, the method otherwise; UNDEF =
 * pending exception */
zv::Val pt_node_scope_resolver_process_expr_node_consuming_stored(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context);

/* }}} */

/* {{{ ArrayDimFetchHandler.cpp, VariableWriteOffset.cpp — registered after
 * the property fetch handlers */

extern zend_class_entry *pt_ce_variable_write_offset;
extern zend_class_entry *pt_ce_array_dim_fetch_handler;
void pt_register_variable_write_offset();
void pt_register_array_dim_fetch_handler();

/* VariableWriteOffset::fromType($dimType): the int|string offset or PHP
 * null; UNDEF = pending exception */
zv::Val pt_variable_write_offset_from_type(zval *dimType);

/* $arrayDimFetchHandler->composeResult($nodeScopeResolver, $stmt, $expr,
 * $dimResult, $varResult, $storage, $context, $beforeScope) — the native body
 * for the shadowing class, the method otherwise ($dimResult NULL or IS_NULL
 * for null); UNDEF = pending exception */
zv::Val pt_array_dim_fetch_handler_compose_result(zval *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *dimResult, zval *varResult, zval *storage, zval *context, zval *beforeScope);

/* }}} */

#endif /* PHPSTANTURBO_SUPPORT_H */
