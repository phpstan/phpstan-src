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
	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_ARRAY_HT(map)
	ZEND_PARSE_PARAMETERS_END();

	zend_string *key;
	zval *value;
	ZEND_HASH_FOREACH_STR_KEY_VAL(map, key, value) {
		ZVAL_DEREF(value);
		if (key == NULL || Z_TYPE_P(value) != IS_STRING) {
			continue;
		}
		pt_class_map_configure(key, Z_STR_P(value));
	} ZEND_HASH_FOREACH_END();
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
	ZEND_PARSE_PARAMETERS_START(1, 1)
		Z_PARAM_STR(path)
	ZEND_PARSE_PARAMETERS_END();

	pt_phar_fork_guard_register(path);
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

	reg::Class runtime("PHPStanTurbo\\Runtime");
	runtime.method("configure", reg::PublicStatic, 1, { reg::arrayArg("classMap") }, runtimeConfigure);
	runtime.method("classRefs", reg::PublicStatic, 0, {}, runtimeClassRefs);
	runtime.method("enablePharForkGuard", reg::PublicStatic, 1, { reg::stringArg("pharPath") }, runtimeEnablePharForkGuard);
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

	return SUCCESS;
}

static PHP_RSHUTDOWN_FUNCTION(phpstan_turbo)
{
	pt_scope_ops_rshutdown();
	pt_node_traverser_rshutdown();
	pt_type_combinator_cache_rshutdown();
	pt_support_rshutdown();

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
