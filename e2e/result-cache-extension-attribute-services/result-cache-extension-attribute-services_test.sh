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
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'the rule says one thing' "$OUTPUT"
	patch -p0 < foo.patch
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	patch -p0 < rule.patch
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'false' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_contains 'Result cache not used because extension file' "$(echo "$INFO" | jq --raw-output '.notUsedReason')"
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'the rule says another thing' "$OUTPUT"
	assert_not_contains 'the rule says one thing' "$OUTPUT"
}
