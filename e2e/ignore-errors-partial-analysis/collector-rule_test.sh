#!/usr/bin/env bash

function set_up() {
	e2e::set_up
	composer install
}

function tear_down() {
	e2e::tear_down
}

function test_whole_project() {
	OUTPUT=$(../../bin/phpstan analyse -c phpstan-collector.neon --error-format raw 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_not_contains 'DeadCode.php' "$OUTPUT"
	assert_not_contains 'DeadCount.php' "$OUTPUT"
	assert_contains 'UnmatchedInline.php:11:No error with identifier return.type is reported on line 11.' "$OUTPUT"
}

# A rule on CollectedDataNode that does not report its errors when only files are analysed
# leaves the ignores of them unmatched, they would match when the whole project is analysed.
function test_only_files_with_rule_skipping_its_errors() {
	OUTPUT=$(../../bin/phpstan analyse -c phpstan-collector.neon --error-format raw src/Plain/DeadCode.php src/Plain/DeadCount.php src/Plain/UnmatchedInline.php 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "0" "$CODE"
	assert_not_contains 'DeadCode.php' "$OUTPUT"
	assert_not_contains 'DeadCount.php' "$OUTPUT"
	assert_not_contains 'UnmatchedInline.php' "$OUTPUT"
}

# The rules of PHPStan itself do not ask whether only files are analysed when they have nothing
# to report, so the unmatched ignores are still reported.
function test_only_files_without_rule_skipping_its_errors() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src/Plain/UnmatchedInline.php 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_contains 'UnmatchedInline.php:11:No error with identifier return.type is reported on line 11.' "$OUTPUT"
}
