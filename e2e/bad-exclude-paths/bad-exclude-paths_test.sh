#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_invalid_ignore_errors_path() {
	OUTPUT=$(../../bin/phpstan analyse -c ignore.neon 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Invalid entry in ignoreErrors' "$OUTPUT"
	assert_contains 'tests" is neither a directory, nor a file path, nor a fnmatch pattern.' "$OUTPUT"
}

function test_invalid_ignore_errors_path_in_php_config() {
	OUTPUT=$(../../bin/phpstan analyse -c phpneon.php 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Invalid entry in ignoreErrors' "$OUTPUT"
	assert_contains '"src/test.php" is neither a directory, nor a file path, nor a fnmatch pattern.' "$OUTPUT"
}

function test_invalid_exclude_path() {
	OUTPUT=$(../../bin/phpstan analyse -c excludePaths.neon 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Invalid entry in excludePaths' "$OUTPUT"
	assert_contains 'tests" is neither a directory, nor a file path, nor a fnmatch pattern.' "$OUTPUT"
}

function test_invalid_exclude_path_in_php_config() {
	OUTPUT=$(../../bin/phpstan analyse -c phpneon2.php 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Invalid entry in excludePaths' "$OUTPUT"
	assert_contains '"src/test.php" is neither a directory, nor a file path, nor a fnmatch pattern.' "$OUTPUT"
}

function test_optional_exclude_path_that_does_not_exist() {
	OUTPUT=$(../../bin/phpstan analyse -c ignoreNonexistentExcludePath.neon)
	echo "$OUTPUT" && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}

function test_optional_exclude_path_that_exists() {
	cp -r tmp-node-modules node_modules
	OUTPUT=$(../../bin/phpstan analyse -c ignoreNonexistentExcludePath.neon)
	echo "$OUTPUT" && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}

function test_unmatched_ignore_path_not_reported() {
	OUTPUT=$(../../bin/phpstan analyse -c ignoreReportUnmatchedFalse.neon)
	echo "$OUTPUT" && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}
