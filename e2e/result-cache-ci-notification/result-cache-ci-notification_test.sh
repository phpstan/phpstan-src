#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_ci_notification() {
	# https://github.com/phpstan/phpstan/issues/14881
	# A slow full analysis in CI without a pre-existing result cache should
	# hint the user to persist the result cache directory between runs.
	# PHPSTAN_E2E_SLOW_ANALYSIS makes bootstrap.php sleep past the
	# elapsed-time limit the notification requires.
	../../bin/phpstan clear-result-cache
	OUTPUT=$(PHPSTAN_E2E_SLOW_ANALYSIS=1 ../../bin/phpstan analyse 2>&1)
	echo "$OUTPUT"
	assert_contains 'Persist PHPStan' "$OUTPUT"
	assert_contains 'to make your pipeline dramatically faster' "$OUTPUT"
	# The second run is just as slow, but finds the cache present,
	# so the hint is not shown again.
	OUTPUT=$(PHPSTAN_E2E_SLOW_ANALYSIS=1 ../../bin/phpstan analyse 2>&1)
	echo "$OUTPUT"
	assert_not_contains 'to make your pipeline dramatically faster' "$OUTPUT"
	# A fast run without any result cache does not show the hint either.
	../../bin/phpstan clear-result-cache
	OUTPUT=$(../../bin/phpstan analyse 2>&1)
	echo "$OUTPUT"
	assert_not_contains 'to make your pipeline dramatically faster' "$OUTPUT"
}
