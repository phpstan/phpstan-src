#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_fix_ignored_entries() {
	composer install
	rm -rf src tmp && cp -r original src && cp baseline-original.php baseline.php && cp baseline-original.neon baseline.neon
	OUTPUT=$(../../bin/phpstan analyse --fix-ignored 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
	# the ignored errors in the first round, the ignores that matched them in the second round:
	# the PHP baseline entry, the too high count of the NEON baseline entry and the @phpstan-ignore comment
	assert_contains 'Round 1: fixed 5 errors in 3 files' "$OUTPUT"
	assert_contains 'Round 2: fixed 3 errors in 3 files' "$OUTPUT"
	assert_contains '[OK] 8 errors fixed in 5 files' "$OUTPUT"
	for file in InComment.php InNeonBaseline.php InPhpBaseline.php; do diff -u expected/$file src/$file; done
	diff -u expected/baseline.php baseline.php
	diff -u expected/baseline.neon baseline.neon
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	# the entry that was stale before, in a file --fix-ignored did not change, is left alone
	assert_contains 'Untouched.php:?:Ignored error pattern #^Stale$#' "$OUTPUT"
	assert_not_contains 'src/In' "$OUTPUT"
}

# A list of files is analysed only in part, but the entries of the analysed files are known to be unmatched.
function test_fix_ignored_entries_in_a_list_of_files() {
	composer install
	rm -rf src tmp && cp -r original src && cp baseline-original.php baseline.php && cp baseline-original.neon baseline.neon
	OUTPUT=$(../../bin/phpstan analyse --fix-ignored src/InComment.php src/InNeonBaseline.php src/InPhpBaseline.php 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
	assert_contains '[OK] 8 errors fixed in 5 files' "$OUTPUT"
	for file in InComment.php InNeonBaseline.php InPhpBaseline.php; do diff -u expected/$file src/$file; done
	diff -u expected/baseline.php baseline.php
	diff -u expected/baseline.neon baseline.neon
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Untouched.php:?:Ignored error pattern #^Stale$#' "$OUTPUT"
	assert_not_contains 'src/In' "$OUTPUT"
}
