#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_define_in_function() {
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Other.php:10:Method ResultCacheE2EDefineInFunction\Other::doOther() should return int but returns string.' "$OUTPUT"
	# A define() inside a function or method body is not an exported node, so an edit of the
	# file calling it looks like no symbol appeared or disappeared: only the file itself is
	# re-analysed, not every file with errors.
	patch -b src/functions.php < body.patch
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
}
