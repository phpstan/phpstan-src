#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_inner_function() {
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Outer.php:8:Inner named functions are not supported by PHPStan.' "$OUTPUT"
	assert_contains 'Other.php:10:Method ResultCacheE2EInnerFunction\Other::doOther() should return int but returns string.' "$OUTPUT"
	# A function declared inside another function is not an exported node, so an edit of the
	# file declaring it looks like no symbol appeared or disappeared: only the file itself is
	# re-analysed, not every file with errors.
	patch -b src/Outer.php < body.patch
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
}
