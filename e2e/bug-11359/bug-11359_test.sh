#!/usr/bin/env bash

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

# @tag own-setup
function test_mb_ucfirst_not_found_on_php_8_2() {
	OUTPUT=$(../../bin/phpstan analyse --no-progress --error-format raw --level=8 bug-11359.php 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'e2e/bug-11359/bug-11359.php:3:Function mb_ucfirst not found.' "$OUTPUT"
}

# @tag own-setup
function test_mb_ucfirst_found_on_php_8_4() {
	OUTPUT=$(../../bin/phpstan analyse --no-progress --error-format raw --level=8 bug-11359.php 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
	assert_not_contains 'Function mb_ucfirst not found.' "$OUTPUT"
}
