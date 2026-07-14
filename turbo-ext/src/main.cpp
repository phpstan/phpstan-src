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

/* Baked from git (short SHA of the last commit touching the watched set);
 * "dev" outside a git checkout, which the enabler rejects. The Makefile
 * passes the quoted string directly; config.w32 passes the bare token as
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
 * TurboExtensionEnabler passes a map of class names (::class constants, so
 * they stay correct under the scoped phar) that the native code resolves
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

static PHP_MINIT_FUNCTION(phpstan_turbo)
{
	reg::Class runtime("PHPStanTurbo\\Runtime");
	runtime.method("configure", reg::PublicStatic, 1, { reg::arrayArg("classMap") }, runtimeConfigure);
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

	return SUCCESS;
}

static PHP_RINIT_FUNCTION(phpstan_turbo)
{
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

zend_module_entry phpstan_turbo_module_entry = {
	STANDARD_MODULE_HEADER,
	"phpstan_turbo",
	NULL, /* functions */
	PHP_MINIT(phpstan_turbo),
	NULL, /* MSHUTDOWN */
	PHP_RINIT(phpstan_turbo),
	PHP_RSHUTDOWN(phpstan_turbo),
	NULL, /* MINFO */
	PHPSTANTURBO_VERSION,
	STANDARD_MODULE_PROPERTIES,
};

ZEND_GET_MODULE(phpstan_turbo)

}
