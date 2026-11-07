#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_fix() {
	composer install
	rm -rf src cycle tmp && cp -r original src && cp -r cycle-original cycle && cp ignore-original.neon ignore.neon
	OUTPUT=$(AI_AGENT=1 ../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains '7 of the errors can be fixed automatically. Run PHPStan again with --fix' "$OUTPUT"
	OUTPUT=$(../../bin/phpstan analyse --fix 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Analysing fixed code (round 4)...' "$OUTPUT"
	assert_not_contains 'round 5' "$OUTPUT"
	assert_contains 'Round 2: fixed 3 errors in 1 file' "$OUTPUT"
	assert_contains '[OK] 10 errors fixed in 2 files' "$OUTPUT"
	diff -u expected/Foo.php src/Foo.php
	diff -u expected/Ignored-fix.php src/Ignored.php
	OUTPUT=$(../../bin/phpstan analyse --fix-ignored 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
	# the last round removes the ignoreErrors entry, it does not match anything after the fixes
	assert_contains '[OK] 3 errors fixed in 2 files' "$OUTPUT"
	diff -u expected/Foo.php src/Foo.php
	diff -u expected/Ignored-fix-ignored.php src/Ignored.php
	diff -u ignore-expected.neon ignore.neon
	OUTPUT=$(../../bin/phpstan analyse --fix --fix-ignored 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'No fixable errors found' "$OUTPUT"
	OUTPUT=$(../../bin/phpstan analyse -c cycle.neon --fix 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Fixing stopped after 10 rounds' "$OUTPUT"
}
