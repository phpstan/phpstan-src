#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_function_shares_method_name() {
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Caller.php:10:Parameter #1 $string of function strlen expects string, int given.' "$OUTPUT"
	# with-method.php has a method named like the function and is scanned after functions.php,
	# so the cached reflection of the function must still be tied to functions.php
	patch -b src/functions.php < changeReturnType.patch
	../../bin/phpstan analyse -vv --error-format raw
}
