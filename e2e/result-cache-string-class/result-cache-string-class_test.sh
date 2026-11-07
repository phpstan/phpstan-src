#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_string_class() {
	../../bin/phpstan analyse
	# The instantiated class is named by a constant string, so it is resolved through the type
	# system rather than through a name node, and the dependency graph records no edge from
	# Thing.php to Factory.php. Deleting the class still has to be noticed.
	rm src/Thing.php
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Factory.php:12:Instantiated class ResultCacheE2EStringClass\Thing not found.' "$OUTPUT"
}
