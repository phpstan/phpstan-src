#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_internal_error() {
	composer dump-autoload
	# A rule that throws is an internal error. The result cache is not saved.
	OUTPUT=$(../../bin/phpstan analyse -vvv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Internal error: The rule crashed.' "$OUTPUT"
	assert_contains 'Result cache was not saved' "$OUTPUT"
	test ! -f tmp/resultCache.php
	# An exception about the analysed code other than a parse error, here a class that is not
	# found, also keeps the result cache from being saved.
	OUTPUT=$(../../bin/phpstan analyse -c class-not-found.neon -vvv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Result cache was not saved because of non-ignorable exception: Class ResultCacheE2EInternalError\Missing was not found' "$OUTPUT"
	test ! -f tmp/resultCache.php
}
