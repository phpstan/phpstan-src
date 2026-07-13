/*
 * phpstan_turbo — optional native acceleration for PHPStan.
 *
 * Built on PHP-CPP: the extension skeleton, lifecycle and the (cold-path)
 * Runtime class use PHP-CPP idioms. The performance-critical classes are
 * registered with the raw Zend API inside onStartup and implement their
 * methods on raw zvals: PHP-CPP's call trampolines allocate a
 * Php::Parameters vector per call, which the boundary-economics rules of
 * this extension forbid on hot paths (see turbo-ext/README.md).
 */

/* This is the only file instantiating PHP-CPP templates (Php::Class<Runtime>);
 * their instantiation re-triggers warnings inside PHP-CPP headers at the use
 * site, outside the include-time guard in support.h — so the exemption is
 * file-scoped here. Our own code in this file remains warning-clean. */
#pragma GCC diagnostic ignored "-Wpragmas"
#pragma GCC diagnostic ignored "-Wunknown-warning-option"
#pragma GCC diagnostic ignored "-Winconsistent-missing-override"

#include <phpcpp.h>
#include "support.h"

/* Baked by the Makefile from git (short SHA of the last commit touching the
 * watched set); "dev" outside a git checkout, which the enabler rejects. */
#ifndef PHPSTANTURBO_VERSION
#define PHPSTANTURBO_VERSION "dev"
#endif

/**
 * Cold-path configuration entry point, implemented in idiomatic PHP-CPP.
 * TurboExtensionEnabler passes a map of class names (::class constants, so
 * they stay correct under the scoped phar) that the native code resolves
 * lazily at run time.
 */
class Runtime : public Php::Base
{
public:
	static void configure(Php::Parameters &params)
	{
		Php::Value map = params[0];
		if (!map.isArray()) {
			throw Php::Exception("PHPStanTurbo\\Runtime::configure() expects an array");
		}
		for (auto &entry : map) {
			Php::Value key = entry.first;
			Php::Value value = entry.second;
			if (!key.isString() || !value.isString()) {
				continue;
			}
			std::string keyStr = key;
			std::string valueStr = value;
			zend_string *zkey = zend_string_init(keyStr.c_str(), keyStr.size(), 0);
			zend_string *zvalue = zend_string_init(valueStr.c_str(), valueStr.size(), 0);
			pt_class_map_configure(zkey, zvalue);
			zend_string_release(zkey);
			zend_string_release(zvalue);
		}
	}
};

extern "C" {

PHPCPP_EXPORT void *get_module()
{
	static Php::Extension extension("phpstan_turbo", PHPSTANTURBO_VERSION);
	static bool initialized = false;

	if (!initialized) {
		initialized = true;

		Php::Class<Runtime> runtime("PHPStanTurbo\\Runtime");
		runtime.method<&Runtime::configure>("configure", {
			Php::ByVal("classMap", Php::Type::Array),
		});
		extension.add(std::move(runtime));

		extension.onStartup([]() {
			pt_register_trinary_logic();
			pt_register_expression_type_holder();
			pt_register_conditional_expression_holder();
			pt_register_combinations_helper();
			pt_register_node_traverser();
			pt_register_scope_ops();
			pt_register_node_scanner();
			pt_register_parser_runner();
		});

		extension.onRequest([]() {
			pt_support_rinit();
			pt_node_traverser_rinit();
			pt_scope_ops_rinit();
		});

		extension.onIdle([]() {
			pt_scope_ops_rshutdown();
			pt_node_traverser_rshutdown();
			pt_support_rshutdown();
		});
	}

	return extension;
}

}
