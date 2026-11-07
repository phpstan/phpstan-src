#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_collector_sweep() {
	# Two errors: one from an ordinary rule, one from a rule built on collected data.
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'PlainCaller.php:8:Method ResultCacheE2ECollectorSweep\PlainCaller::doPlainCaller() has invalid return type ResultCacheE2ECollectorSweep\Missing.' "$OUTPUT"
	assert_contains 'Caller.php:7:Call to function resultCacheE2ECollectorHelper() on a separate line has no effect.' "$OUTPUT"
	# One new file defines both missing symbols. The files with errors are re-analysed - including
	# the file holding the error of the collector-backed rule, which is not in the cached error map.
	cp appears/nsfns.php src/nsfns.php
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
	assert_not_contains 'has invalid return type' "$OUTPUT"
	assert_not_contains 'on a separate line has no effect' "$OUTPUT"
}
