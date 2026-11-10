#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_vendor_class_appears() {
	composer install
	# The class does not exist yet: there is no file to point at, so the lookup is recorded as a
	# dependency on the class staying missing.
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Foo.php:10:Parameter $logger of method ResultCacheE2EVendorClassAppears\Foo::doFoo() has invalid type Psr\Log\PluginProvidedLogger.' "$OUTPUT"
	# Composer updates the package, and a plugin writes the class into it on the way - which is
	# what phpstan/phpstan#15164 is about. Nothing the cache knows about points at that file,
	# but the class is no longer missing, so the file that looked it up is re-analysed;
	# otherwise the error stands while a run from a cleared cache reports nothing.
	patch -p0 < updatePsrLog.patch
	composer update psr/log --no-cache
	cp plugin-writes/PluginProvidedLogger.php vendor/psr/log/src/PluginProvidedLogger.php
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Composer packages changed (psr/log); re-analysing the files depending on them.' "$OUTPUT"
	assert_not_contains 'has invalid type Psr\Log\PluginProvidedLogger' "$OUTPUT"
}
