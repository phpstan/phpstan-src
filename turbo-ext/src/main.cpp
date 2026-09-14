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
	pt_register_scope_ops();
	pt_register_node_scanner();
	pt_register_parser_runner();
	pt_register_type_combinator_cache();
	pt_register_arena_cache();
	pt_register_expression_result_storage();
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
