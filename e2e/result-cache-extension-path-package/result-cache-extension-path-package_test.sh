#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_extension_path_package() {
	composer install
	# The rule comes from a package Composer symlinked out of the project, so the file the
	# class reflects to is the source directory, outside vendor/ and outside the analysed
	# paths. Editing it has to invalidate the cache: the rule decides what every file reports.
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'the rule says one thing' "$OUTPUT"
	patch -p0 < patch.patch
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Result cache not used because extension file' "$OUTPUT"
	assert_contains 'the rule says another thing' "$OUTPUT"
	assert_not_contains 'the rule says one thing' "$OUTPUT"
}
