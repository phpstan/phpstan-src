/*
 * phpstan_turbo — optional native acceleration for PHPStan.
 *
 * A plain Zend extension: the module entry below and every class
 * registration (the reg::Class builder in reg.h) hand the engine raw
 * structures and raw handler pointers. The performance-critical classes
 * implement their methods on raw zvals — no per-call boxing anywhere (see
 * the boundary-economics rules in turbo-ext/README.md).
 */

#include "support.h"
#include "reg.h"
#include "Engine.h"

#ifdef PHP_WIN32
#include <process.h>
#else
#include <unistd.h>
#endif

/* The short SHA of the last commit touching the watched set: baked from git
 * by the Makefile (quoted string passed directly), or from the VERSION.txt
 * the subsplit workflow commits into phpstan/turbo-ext; "dev" with neither,
 * which the enabler rejects. config.w32 and config.m4 pass the bare token as
 * PHPSTANTURBO_VERSION_RAW — quote characters do not survive the Windows
 * configure-to-nmake pipeline — and it is stringized here. */
#ifdef PHPSTANTURBO_VERSION_RAW
#define PT_VERSION_STR2(x) #x
#define PT_VERSION_STR(x) PT_VERSION_STR2(x)
#define PHPSTANTURBO_VERSION PT_VERSION_STR(PHPSTANTURBO_VERSION_RAW)
#endif
#ifndef PHPSTANTURBO_VERSION
#define PHPSTANTURBO_VERSION "dev"
#endif

/* PHPStanTurbo\Runtime::configure() — cold-path configuration entry point.
 * TurboExtensionEnabler passes the generated class map (derived from the
 * ReferencedByTurboExtension attributes) that the native code resolves
 * lazily at run time. ZEND_FASTCALL matches zif_handler's calling
 * convention — on MSVC x64 that is __vectorcall, and a named function
 * defaults to __cdecl (the reg.h lambdas convert implicitly). */
static void ZEND_FASTCALL runtimeConfigure(INTERNAL_FUNCTION_PARAMETERS)
{
	HashTable *map;
	if (!zp::parse<zp::Ht>(execute_data, map)) RETURN_THROWS();

	zend_string *key;
	zval *value;
	ZEND_HASH_FOREACH_STR_KEY_VAL(map, key, value) {
		ZVAL_DEREF(value);
		if (key == NULL || Z_TYPE_P(value) != IS_STRING) continue;
		pt_class_map_configure(key, Z_STR_P(value));
	} ZEND_HASH_FOREACH_END();
}

/* PHPStanTurbo\Runtime::activateShadowing() — declares the shadowing classes
 * under their PHP twins' names (Shadow.cpp). TurboExtensionEnabler calls it
 * once the version matches and the Composer autoloader is registered;
 * $twinFiles maps each class to its PHP source file, and the differential
 * tests pass a $prefix to declare the classes as PHPStanTurbo\* beside the
 * twins instead. */
static void ZEND_FASTCALL runtimeActivateShadowing(INTERNAL_FUNCTION_PARAMETERS)
{
	HashTable *twinFiles;
	zend_string *prefix = NULL;
	if (!zp::parse<zp::Ht, zp::Opt<zp::StrOrNull>>(execute_data, twinFiles, prefix)) RETURN_THROWS();

	if (!pt_shadow_activate(twinFiles, prefix)) RETURN_THROWS();
}

/* PHPStanTurbo\Runtime::isShadowing() — whether activateShadowing() ran */
static void ZEND_FASTCALL runtimeIsShadowing(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();

	RETURN_BOOL(pt_shadow_is_active());
}

/* PHPStanTurbo\Runtime::classRefs() — the native class-reference table as
 * key => default FQCN (or null), so the smoke test can hold the generated
 * class map against the real compiled table instead of parsing source. */
static void ZEND_FASTCALL runtimeClassRefs(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();

	pt_class_refs_dump(return_value);
}

/* PHPStanTurbo\Runtime::enablePharForkGuard() — TurboExtensionEnabler passes
 * Phar::running(false) when PHPStan runs from a phar, arming the
 * pthread_atfork hooks that keep phar:// reads safe in pcntl_fork()ed
 * workers (see PharForkGuard.cpp). */
static void ZEND_FASTCALL runtimeEnablePharForkGuard(INTERNAL_FUNCTION_PARAMETERS)
{
	zend_string *path;
	if (!zp::parse<zp::Str>(execute_data, path)) RETURN_THROWS();

	pt_phar_fork_guard_register(path);
}

/* PHPStanTurbo\Runtime::trustTypesUnder() — TurboExtensionEnabler passes the
 * phar:// prefix of the running phar, arming the optimizer pass that drops
 * the engine's argument and return type checks from PHPStan's own code as
 * it is compiled (see TrustedTypes.cpp). Returns whether the pass is armed —
 * false without opcache. */
static void ZEND_FASTCALL runtimeTrustTypesUnder(INTERNAL_FUNCTION_PARAMETERS)
{
	zend_string *prefix;
	if (!zp::parse<zp::Str>(execute_data, prefix)) RETURN_THROWS();

	RETURN_BOOL(pt_trusted_types_set_prefix(prefix));
}

/* PHPStanTurbo\Runtime::exitImmediately() — _exit() for a pcntl_fork()ed
 * worker; ForkedChildTerminator registers it as the child's last shutdown
 * function.
 *
 * A forked child inherits the whole parent process, every loaded extension
 * with whatever background threads it started included — but fork() copies
 * only the calling thread. PHP's exit() then runs destructors and each
 * extension's module shutdown, and an extension whose shutdown waits for
 * its threads to check out (ext-grpc's grpc_shutdown() without
 * grpc.enable_fork_support, for one) waits forever for threads the child
 * never had: the worker has delivered its results, the parent keeps
 * polling waitpid(), and the run hangs at 100%. A forked child that does
 * not exec() must end with _exit() — no destructors, no module shutdown, no
 * atexit handlers; that teardown is the parent's. The shutdown functions
 * have run by then, so the crash report (ForkedChildCrashReporter) is
 * written, and everything the parent reads — the results over the socket,
 * the captured output — went through unbuffered fds.
 *
 * The status is the engine's: what exit() was given, 255 after a fatal
 * error, 0 otherwise. */
static void ZEND_FASTCALL runtimeExitImmediately(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();

	_exit(EG(exit_status));
}

static PHP_MINIT_FUNCTION(phpstan_turbo)
{
#ifdef ZTS
	ZEND_TSRMLS_CACHE_UPDATE();
#endif

	static const reg::Arg returnsBool = reg::boolArg("");
	reg::Class runtime("PHPStanTurbo\\Runtime");
	runtime.method("configure", reg::PublicStatic, 1, { reg::arrayArg("classMap") }, runtimeConfigure);
	runtime.method("classRefs", reg::PublicStatic, 0, {}, runtimeClassRefs);
	runtime.method("activateShadowing", reg::PublicStatic, 1, { reg::arrayArg("twinFiles"), reg::withDefault(reg::stringArg("prefix", true), "null") }, runtimeActivateShadowing);
	runtime.method("isShadowing", reg::PublicStatic, 0, {}, runtimeIsShadowing, &returnsBool);
	runtime.method("enablePharForkGuard", reg::PublicStatic, 1, { reg::stringArg("pharPath") }, runtimeEnablePharForkGuard);
	runtime.method("trustTypesUnder", reg::PublicStatic, 1, { reg::stringArg("prefix") }, runtimeTrustTypesUnder, &returnsBool);
	runtime.method("exitImmediately", reg::PublicStatic, 0, {}, runtimeExitImmediately);
	runtime.register_();

	pt_register_trinary_logic();
	pt_register_expression_type_holder();
	pt_register_conditional_expression_holder();
	pt_register_combinations_helper();
	pt_register_node_traverser();
	pt_register_array_filter_arg_visitor();
	pt_register_array_find_arg_visitor();
	pt_register_array_map_arg_visitor();
	pt_register_array_offset_normalizing_visitor();
	pt_register_array_walk_arg_visitor();
	pt_register_arrow_function_arg_visitor();
	pt_register_closure_arg_visitor();
	pt_register_closure_bind_arg_visitor();
	pt_register_closure_bind_to_var_visitor();
	pt_register_curl_set_opt_arg_visitor();
	pt_register_curl_set_opt_array_arg_visitor();
	pt_register_declare_position_visitor();
	pt_register_immediately_invoked_closure_visitor();
	pt_register_implode_arg_visitor();
	pt_register_magic_constant_param_default_visitor();
	pt_register_new_assigned_to_property_visitor();
	pt_register_parent_stmt_types_visitor();
	pt_register_trait_collecting_visitor();
	pt_register_try_catch_type_visitor();
	pt_register_type_traverser_instanceof_visitor();
	pt_register_scope_ops();
	pt_register_node_scanner();
	pt_register_expr_printer();
	pt_register_parser_runner();
	pt_register_type_combinator_cache();
	pt_register_arena_cache();
	pt_register_expression_result_storage();
	pt_register_expression_result_storage_stack();
	pt_register_class_statements_gatherer();
	pt_register_php_file_cleaner();
	pt_register_symbol_finder_in_files();
	pt_register_scope_context();
	pt_register_is_super_type_of_result();
	pt_register_accepts_result();
	/* the Type ports go after the result classes their return types name
	 * (a plan naming a class declared later would make the linker autoload
	 * the PHP twin); a parent before its child */
	pt_register_type_traits();
	pt_register_boolean_type();
	pt_register_constant_boolean_type();
	pt_register_integer_type();
	pt_register_constant_integer_type();
	pt_register_integer_range_type();
	pt_register_string_type();
	pt_register_constant_string_type();
	pt_register_class_string_type();
	pt_register_generic_class_string_type();
	pt_register_float_type();
	pt_register_constant_float_type();
	pt_register_null_type();
	pt_register_void_type();
	pt_register_never_type();
	pt_register_mixed_type();
	pt_register_strict_mixed_type();
	pt_register_object_type();
	pt_register_generic_object_type();
	pt_register_enum_case_object_type();
	pt_register_object_without_class_type();
	pt_register_static_type();
	pt_register_this_type();
	pt_register_generic_static_type();
	pt_register_object_shape_type();
	pt_register_nonexistent_parent_class_type();
	pt_register_array_type();
	pt_register_non_empty_array_type();
	pt_register_accessory_array_list_type();
	pt_register_oversized_array_type();
	pt_register_has_offset_type();
	pt_register_has_offset_value_type();
	pt_register_accessory_numeric_string_type();
	pt_register_accessory_non_empty_string_type();
	pt_register_accessory_non_falsy_string_type();
	pt_register_accessory_literal_string_type();
	pt_register_accessory_lowercase_string_type();
	pt_register_accessory_uppercase_string_type();
	pt_register_accessory_decimal_integer_string_type();
	pt_register_has_method_type();
	pt_register_has_property_type();
	pt_register_iterable_type();
	pt_register_callable_type();
	pt_register_closure_type();
	pt_register_constant_array_type();
	pt_register_union_type();
	pt_register_benevolent_union_type();
	pt_register_intersection_type();
	pt_register_type_traverser();
	pt_register_verbosity_level();
	pt_register_recursion_guard();
	pt_register_finite_type_set();
	pt_register_error_type();
	pt_register_circular_type_alias_error_type();
	pt_register_absorbed_template_argument_type();
	pt_register_non_accepting_never_type();
	pt_register_string_always_accepting_object_with_to_string_type();
	pt_register_string_never_accepting_object_with_to_string_type();
	pt_register_resource_type();
	pt_register_type_utils();
	pt_register_typehint_helper();
	pt_register_type_combinator();
	pt_register_template_type_variance();
	pt_register_template_type_variance_map();
	pt_register_template_type_map();
	pt_register_template_type_scope();
	pt_register_template_type_reference();
	pt_register_template_type_helper();
	pt_register_key_of_type();
	pt_register_value_of_type();
	pt_register_offset_access_type();
	pt_register_class_constant_access_type();
	pt_register_new_object_type();
	pt_register_conditional_type();
	pt_register_conditional_type_for_parameter();
	pt_register_late_resolvable_array_shape_type();
	pt_register_unresolved_template_argument_type();
	pt_register_template_type_argument_strategy();
	pt_register_template_type_parameter_strategy();
	pt_register_template_array_type();
	pt_register_template_benevolent_union_type();
	pt_register_template_boolean_type();
	pt_register_template_constant_array_type();
	pt_register_template_constant_integer_type();
	pt_register_template_constant_string_type();
	pt_register_template_float_type();
	pt_register_template_generic_object_type();
	pt_register_template_integer_type();
	pt_register_template_intersection_type();
	pt_register_template_iterable_type();
	pt_register_template_mixed_type();
	pt_register_template_null_type();
	pt_register_template_object_shape_type();
	pt_register_template_object_type();
	pt_register_template_object_without_class_type();
	pt_register_template_strict_mixed_type();
	pt_register_template_string_type();
	pt_register_template_union_type();
	pt_register_template_type_factory();
	pt_register_type_projection_helper();
	pt_register_template_key_of_type();
	pt_register_constant_array_type_builder();
	pt_register_union_type_helper();
	pt_register_constant_type_helper();
	pt_register_static_type_factory();
	pt_register_type_result();
	pt_register_callable_type_helper();
	pt_register_get_template_type_type();
	pt_register_lru_cache();
	pt_register_unresolvable_type_helper();
	pt_register_native_parameter_reflection();
	pt_register_called_on_type_unresolved_method_prototype_reflection();
	pt_register_called_on_type_unresolved_property_prototype_reflection();
	pt_register_callback_unresolved_method_prototype_reflection();
	pt_register_callback_unresolved_property_prototype_reflection();
	pt_register_mutating_scope();
	pt_register_class_reflection();
	pt_register_volatile_expression_helper();
	pt_register_variable_flow();
	pt_register_variable_flow_builder();
	pt_register_variable_liveness_resolver();
	pt_register_expression_result();
	/* the DI service behind ClassReflection's member lookups — after the
	 * Type family and LruCache, whose classes its signatures name */
	pt_register_php_class_reflection_extension();
	/* the narrowing value classes — their signatures name PhpParser's Expr and
	 * the augment interface only */
	pt_register_type_specifier_context();
	pt_register_specified_types();
	/* the analysis-engine value classes and handler registries */
	pt_register_expression_context();
	pt_register_statement_context();
	pt_register_expr_handler_registry();
	pt_register_stmt_handler_registry();
	/* the engine-port foundation (Engine.h) and the handler ports; the
	 * handlers after the ExpressionResult and context classes their
	 * signatures name */
	pt_register_native_closure();
	pt_register_scalar_handler();
	pt_register_variable_handler();
	/* the analyser value classes — after MutatingScope and ExpressionResult,
	 * whose classes their signatures name; ThrowPoint before
	 * InternalThrowPoint, whose toPublic() returns it */
	pt_register_impure_point();
	pt_register_throw_point();
	pt_register_internal_throw_point();
	pt_register_args_result();
	pt_register_issetability_descriptor();
	/* the statement results — the public ones before the internal ones
	 * whose toPublic() return them */
	pt_register_statement_exit_point();
	pt_register_statement_result();
	pt_register_end_statement_result();
	pt_register_internal_statement_exit_point();
	pt_register_internal_statement_result();
	pt_register_internal_end_statement_result();
	pt_register_template_argument_frame();
	pt_register_assign_target_walk_mode();
	pt_register_prepared_assign_target();
	pt_register_recording_node_callback();
	/* the analyser helper services — their signatures name MutatingScope,
	 * SpecifiedTypes and PHP classes only */
	pt_register_early_terminating_call_helper();
	pt_register_method_call_return_type_helper();
	pt_register_method_throw_point_helper();
	/* the boolean narrowing cluster: the value classes before the helpers
	 * whose signatures and bodies name them */
	pt_register_conditional_expression_holder_recipe();
	pt_register_disjunction_branch_union_augment();
	pt_register_disjunction_holder_projection_augment();
	pt_register_conditional_expression_holder_helper();
	pt_register_boolean_narrowing_helper();
	/* the narrowing service handed to the type-specifying extensions — its
	 * signatures name SpecifiedTypes and TypeSpecifierContext */
	pt_register_type_specifier();
	/* the narrowing helpers, after the SpecifiedTypes, ExpressionResult and
	 * context classes their signatures name */
	pt_register_default_narrowing_helper();
	pt_register_identical_narrowing_helper();
	/* the walk hub, after the scope, result, storage, context and handler
	 * classes its signatures name */
	pt_register_statement_list_walk_state();
	pt_register_non_nullability_helper();
	pt_register_statements_handler();
	pt_register_node_scope_resolver();
	/* the method reflections of the member prototypes — ChangedTypeMethodReflection
	 * first, which ResolvedMethodReflection wraps */
	pt_register_changed_type_method_reflection();
	pt_register_resolved_method_reflection();
	pt_register_simple_impure_point();
	pt_register_dynamic_return_type_storage_primer();
	pt_register_method_call_handler();
	/* the assignment handlers, after the walk hub and the value classes their
	 * signatures name */
	pt_register_assign_handler();
	pt_register_assign_op_handler();
	/* the statement handlers, after the MutatingScope, context and
	 * statement-result classes their signatures name */
	pt_register_expression_handler();
	pt_register_return_handler();
	pt_register_echo_handler();
	pt_register_block_handler();
	pt_register_nop_handler();
	pt_register_class_method_handler();
	pt_register_function_handler();
	pt_register_class_like_handler();
	pt_register_if_handler();
	pt_register_static_call_handler();
	pt_register_new_handler();
	/* the VariableFlow subclasses (after VariableFlow, their parent) */
	pt_register_variable_access_flow();
	pt_register_variable_sequence_flow();
	pt_register_variable_input_flow();
	pt_register_variable_control_flow();
	pt_register_var_annotation_processor();
	/* the argument walk of the call handlers — its signatures name the
	 * analyser value classes and PHP classes only */
	pt_register_arguments_handler();
	/* the parameter value classes — their signatures name the Type interface,
	 * TrinaryLogic and PHP reflection classes only; the parent before the child */
	pt_register_passed_by_reference();
	pt_register_dummy_parameter();
	pt_register_extended_dummy_parameter();
	/* the argument reordering of the call handlers — its signatures name
	 * php-parser classes, ParametersAcceptor and Scope only */
	pt_register_arguments_normalizer();
	/* the variant selection — its signatures name Scope and the reflection
	 * interfaces only */
	pt_register_parameters_acceptor_selector();
	/* the function-call cluster, after the walk hub, scope and result
	 * classes its signatures name */
	pt_register_output_buffer_helper();
	pt_register_func_call_scope_effects_helper();
	pt_register_func_call_handler();
	/* the property fetch handlers (after the hook throw points resolver
	 * their direct entries call) */
	pt_register_property_hook_throw_points_resolver();
	pt_register_property_fetch_handler();
	pt_register_static_property_fetch_handler();
	pt_register_nullsafe_property_fetch_handler();
	pt_register_variable_write_offset();
	pt_register_array_dim_fetch_handler();
	pt_register_const_fetch_handler();
	pt_register_class_const_fetch_handler();
	/* the attribute and parameter walks of the declarations and closures —
	 * their signatures name the walk hub, ArgumentsHandler and the reflection
	 * provider; the processor after the handler its constructor names */
	pt_register_attributes_handler();
	pt_register_parameters_processor();
	/* the closure resolvers — their signatures name the walk hub, MutatingScope,
	 * the storage and the Type interface; each after the ones its constructor
	 * names */
	pt_register_contextual_closure_parameter_resolver();
	pt_register_closure_type_resolver();
	pt_register_closure_parameter_resolver();
	/* the closure walk — the result classes before the processor whose
	 * signatures name them, the handlers after the processor their
	 * constructors name */
	pt_register_process_closure_result();
	pt_register_process_arrow_function_result();
	pt_register_closure_processor();
	pt_register_closure_handler();
	pt_register_arrow_function_handler();
	pt_register_boolean_and_handler();
	pt_register_boolean_or_handler();
	pt_register_boolean_not_handler();
	pt_register_ternary_handler();
	pt_register_binary_op_handler();
	pt_register_coalesce_composition_helper();
	pt_register_coalesce_handler();

	return SUCCESS;
}

static PHP_MSHUTDOWN_FUNCTION(phpstan_turbo)
{
	pt_arena_mshutdown();

	return SUCCESS;
}

static PHP_RINIT_FUNCTION(phpstan_turbo)
{
#ifdef ZTS
	ZEND_TSRMLS_CACHE_UPDATE();
#endif

	pt_support_rinit();
	pt_node_traverser_rinit();
	pt_scope_ops_rinit();
	pt_type_combinator_cache_rinit();
	pt_is_super_type_of_result_rinit();
	pt_accepts_result_rinit();
	pt_integer_range_type_rinit();
	pt_object_type_rinit();
	pt_static_type_factory_rinit();
	pt_scope_access_rinit();
	pt_reflection_access_rinit();
	pt_mutating_scope_rinit();
	pt_variable_flow_rinit();
	pt_php_class_reflection_extension_rinit();
	pt_engine_rinit();

	return SUCCESS;
}

static PHP_RSHUTDOWN_FUNCTION(phpstan_turbo)
{
	pt_scope_ops_rshutdown();
	pt_node_traverser_rshutdown();
	pt_type_combinator_cache_rshutdown();
	pt_is_super_type_of_result_rshutdown();
	pt_accepts_result_rshutdown();
	pt_support_rshutdown();
	pt_object_type_rshutdown();
	pt_static_type_factory_rshutdown();
	pt_engine_rshutdown();

	return SUCCESS;
}

extern "C" {

/* ZTS builds resolve EG()/CG() through this per-thread cache (the build
 * defines ZEND_ENABLE_STATIC_TSRMLS_CACHE; php.h declares the extern in
 * every translation unit). The extension's own state stays in plain statics
 * regardless: PHPStan's CLI processes are single-threaded — parallelism is
 * worker processes, not threads — so a ZTS build (for hosts like PMMP's
 * bundled PHP) never runs our code from two threads at once. */
#ifdef ZTS
ZEND_TSRMLS_CACHE_DEFINE()
#endif

zend_module_entry phpstan_turbo_module_entry = {
	STANDARD_MODULE_HEADER,
	"phpstan_turbo",
	NULL, /* functions */
	PHP_MINIT(phpstan_turbo),
	PHP_MSHUTDOWN(phpstan_turbo),
	PHP_RINIT(phpstan_turbo),
	PHP_RSHUTDOWN(phpstan_turbo),
	NULL, /* MINFO */
	PHPSTANTURBO_VERSION,
	STANDARD_MODULE_PROPERTIES,
};

ZEND_GET_MODULE(phpstan_turbo)

}
