#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

# paths: [src, lib] - analysing only one of them must not report or fix away
# the ignoreErrors entries that the other one still needs
function test_fix_partial_paths() {
	rm -rf tmp && cp phpstan.neon phpstan-original.neon

	OUTPUT=$(AI_AGENT=1 ../../bin/phpstan analyse lib --error-format raw 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "0" "$CODE"
	assert_not_contains 'Ignored error' "$OUTPUT"

	OUTPUT=$(AI_AGENT=1 ../../bin/phpstan analyse src --error-format raw 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "0" "$CODE"
	assert_not_contains 'Ignored error' "$OUTPUT"

	for paths in lib src; do
		OUTPUT=$(../../bin/phpstan analyse $paths --fix 2>&1) && CODE=0 || CODE=$?
		echo "$OUTPUT"
		assert_contains 'No fixable errors found' "$OUTPUT"
		diff -u phpstan-original.neon phpstan.neon
	done

	OUTPUT=$(cd lib && ../../../bin/phpstan analyse . -c ../phpstan.neon --fix 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_contains 'No fixable errors found' "$OUTPUT"
	diff -u phpstan-original.neon phpstan.neon

	# the ignored method.missingOverride is fixed in lib/, the entry stays for src/
	OUTPUT=$(../../bin/phpstan analyse lib --fix --fix-ignored 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "0" "$CODE"
	assert_contains '[OK] 1 error fixed in 1 file' "$OUTPUT"
	assert_contains '#[\Override]' "$(cat lib/U.php)"
	diff -u phpstan-original.neon phpstan.neon

	# a full analysis still removes the entry no path needs
	OUTPUT=$(../../bin/phpstan analyse --fix 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "0" "$CODE"
	assert_contains '[OK] 1 error fixed in 1 file' "$OUTPUT"
	assert_not_contains 'neverReported' "$(cat phpstan.neon)"
	OUTPUT=$(AI_AGENT=1 ../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "0" "$CODE"
}
