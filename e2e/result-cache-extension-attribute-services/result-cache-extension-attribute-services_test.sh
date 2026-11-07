#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_extension_attribute_services() {
	composer install
	# The rule is registered through the attributeServicesDirectories section, not in services:.
	# Editing it has to invalidate the cache, editing another class in the same directory does not.
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'the rule says one thing' "$OUTPUT"
	patch -p0 < foo.patch
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Result cache restored. 1 file will be reanalysed.' "$OUTPUT"
	patch -p0 < rule.patch
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Result cache not used because extension file' "$OUTPUT"
	assert_contains 'the rule says another thing' "$OUTPUT"
	assert_not_contains 'the rule says one thing' "$OUTPUT"
}
