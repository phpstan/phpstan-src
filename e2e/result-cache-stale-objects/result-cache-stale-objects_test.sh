#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_stale_objects() {
	OUTPUT=$(../../bin/phpstan -vvv 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	# Renaming a property inside the cache file is what a cache written by a PHPStan whose
	# classes have changed since looks like: the payload does not carry the property the class
	# now declares, so reconstructing the object leaves it uninitialized and reading it throws.
	php rename-property.php
	OUTPUT=$(../../bin/phpstan -vvv 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'could not be read back' "$OUTPUT"
	assert_contains 'Result cache is saved.' "$OUTPUT"
	# The cache written in its place is usable again.
	OUTPUT=$(../../bin/phpstan -vvv 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Result cache restored.' "$OUTPUT"
}
