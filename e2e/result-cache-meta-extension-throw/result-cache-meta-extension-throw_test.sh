#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_meta_extension_throw() {
	composer install
	# https://github.com/phpstan/phpstan/issues/14805
	# An exception thrown from ResultCacheMetaExtension::getHash() during result cache
	# restore must fail the run with a non-zero exit code - even when a bootstrap file
	# installs a global exception handler that would otherwise swallow it into exit 0.
	OUTPUT=$(../../bin/phpstan --error-format=raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'boom from getHash' "$OUTPUT"
	assert_not_contains 'Swallowed by global exception handler' "$OUTPUT"
}
