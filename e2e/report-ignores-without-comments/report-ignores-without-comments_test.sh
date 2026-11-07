#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_report_ignores_without_comments() {
	OUTPUT=$(../../bin/phpstan --error-format=raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'test.php:4:Ignore with identifier variable.undefined has no comment.' "$OUTPUT"
	assert_contains 'test.php:4:Variable $undefined might not be defined.' "$OUTPUT"
	assert_not_contains 'test.php:7' "$OUTPUT"
	assert_contains 'test.php:10:Ignoring all errors on a line is not allowed.' "$OUTPUT"
	assert_contains 'test.php:10:Variable $yetAnotherUndefined might not be defined.' "$OUTPUT"
	assert_contains 'test.php:12:Ignoring all errors on a line is not allowed.' "$OUTPUT"
	assert_contains 'test.php:12:Variable $yetYetAnotherUndefined might not be defined.' "$OUTPUT"
}
