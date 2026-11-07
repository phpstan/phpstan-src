#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_fix_baseline() {
	composer install
	mkdir -p vendor/acme/rules && cp vendor-baseline.php vendor/acme/rules/baseline.php
	rm -rf tmp && cp baseline-original.php baseline.php && cp baseline-original.neon baseline.neon
	OUTPUT=$(AI_AGENT=1 ../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	# two unmatched messages, an unmatched entry, and a count higher than the occurrences in the PHP baseline,
	# an unmatched message, an unmatched entry, and a count higher than the occurrences in the NEON baseline;
	# and two entries without a path in the NEON baseline, reported in it;
	# not the entries in vendor/, and not a count lower than the occurrences (--generate-baseline does that)
	assert_contains '9 of the errors can be fixed automatically. Run PHPStan again with --fix' "$OUTPUT"
	assert_contains 'baseline.neon:21:Ignored error pattern #^Unmatched without a path$# was not matched' "$OUTPUT"
	OUTPUT=$(../../bin/phpstan analyse --fix --generate-baseline 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Errors cannot be fixed when generating the baseline.' "$OUTPUT"
	OUTPUT=$(../../bin/phpstan analyse --fix 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
	# removing the last two messages of the PHP list conflicts, the second one goes in the next round;
	# a NEON entry after the first one is removed with only its own lines, it does not conflict with its neighbours
	assert_contains 'Round 2: fixed 1 error in 1 file' "$OUTPUT"
	assert_contains '[OK] 9 errors fixed in 2 files' "$OUTPUT"
	diff -u baseline-expected.php baseline.php
	diff -u baseline-expected.neon baseline.neon
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Ignored error pattern #^Unmatched in vendor$#' "$OUTPUT"
	assert_contains 'Ignored error pattern #^Unmatched in vendor without a path$# was not matched' "$OUTPUT"
	assert_contains 'is expected to occur 1 time, but occurred 2 times.' "$OUTPUT"
	assert_not_contains 'Unmatched one' "$OUTPUT"
	assert_not_contains 'NEON' "$OUTPUT"
	assert_not_contains 'occurred only' "$OUTPUT"
}
