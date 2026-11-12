#!/usr/bin/env bash

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

# An analysis of a directory that is a part of the configured paths does not report the entries
# whose errors are in the other directories as unmatched, so --fix does not remove them.
function test_directory_runs_report_no_unmatched_ignores() {
	for DIRECTORY in lib src; do
		OUTPUT=$(../../bin/phpstan analyse --error-format raw $DIRECTORY 2>&1) && CODE=0 || CODE=$?
		echo "$OUTPUT"
		assert_same "0" "$CODE"
	done
}

function test_fix_in_directory_keeps_ignores_needed_elsewhere() {
	ORIGINAL_CONFIG=$(cat phpstan.neon)
	OUTPUT=$(../../bin/phpstan analyse --fix --fix-ignored lib 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "0" "$CODE"
	assert_contains '[OK] 1 error fixed in 1 file' "$OUTPUT"
	assert_contains '#[\Override]' "$(cat lib/U.php)"
	assert_same "$ORIGINAL_CONFIG" "$(cat phpstan.neon)"
	OUTPUT=$(../../bin/phpstan analyse --fix src 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "$ORIGINAL_CONFIG" "$(cat phpstan.neon)"
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "0" "$CODE"
}
