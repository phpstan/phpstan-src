#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_debug_class_loader() {
	composer install
	../../bin/phpstan analyze
	# With --debug, the analysis runs in the main process, and PHPStan prints the
	# exception that DebugClassLoader threw for the unknown class.
	OUTPUT=$(../../bin/phpstan analyze --debug 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
	assert_contains 'by a bootstrap autoloader for class DebugClassLoaderE2e\Widget: The autoloader expected class' "$OUTPUT"
}
