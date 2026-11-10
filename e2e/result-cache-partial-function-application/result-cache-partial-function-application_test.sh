#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_partial_function_application() {
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "0" "$CODE"
	# The bound arguments of a partial function application are evaluated when the
	# closure is created, so a file binding Dep::value() depends on Dep.php - through
	# a function, a method and a static method alike. Unrelated.php does not.
	patch -b src/Dep.php < dep.patch
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '4' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
}
