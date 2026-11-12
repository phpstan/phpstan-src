#!/usr/bin/env bash

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_whole_project_reports_all_unmatched_ignores() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_contains '#never in Clean# in path' "$OUTPUT"
	assert_contains '#never in srcc# in path' "$OUTPUT"
	assert_contains '#never in Plain directory# in path' "$OUTPUT"
	assert_contains '#never in srcc directory# in path' "$OUTPUT"
	assert_contains '#never in Plain glob# in path' "$OUTPUT"
	assert_contains '#never in src glob# in path' "$OUTPUT"
	assert_contains '#never in leading glob# in path' "$OUTPUT"
	assert_contains '#never in two paths# in paths:' "$OUTPUT"
	assert_contains 'Ignored error pattern #never globally# was not matched' "$OUTPUT"
	assert_contains 'Ignored error pattern #never globally either# was not matched' "$OUTPUT"
	assert_contains 'is expected to occur 2 times, but occurred only 1 time.' "$OUTPUT"
}

function test_cli_paths_covering_config_paths_are_the_whole_project() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src srcc tests 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_contains '#never in srcc directory# in path' "$OUTPUT"
	assert_contains '#never in leading glob# in path' "$OUTPUT"
	assert_contains 'Ignored error pattern #never globally# was not matched' "$OUTPUT"
}

function test_subdirectory_reports_only_ignores_under_it() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_contains '#never in Clean# in path' "$OUTPUT"
	assert_contains '#never in Plain directory# in path' "$OUTPUT"
	assert_contains '#never in Plain glob# in path' "$OUTPUT"
	assert_contains '#never in src glob# in path' "$OUTPUT"
	assert_contains 'is expected to occur 2 times, but occurred only 1 time.' "$OUTPUT"
	# only the path under the analysed paths is left in the entry
	assert_contains "#never in two paths# in path $PWD/src/Plain/Clean.php was not matched" "$OUTPUT"
	# srcc/ starts with src, but it is not under src/
	assert_not_contains '#never in srcc#' "$OUTPUT"
	assert_not_contains '#never in srcc directory#' "$OUTPUT"
	# a pattern starting with a wildcard can match anywhere
	assert_not_contains '#never in leading glob#' "$OUTPUT"
	# what an entry without a path matches can be anywhere
	assert_not_contains '#never globally#' "$OUTPUT"
	assert_not_contains '#never globally either#' "$OUTPUT"
}

function test_deeper_subdirectory() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src/Plain 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_contains '#never in Clean# in path' "$OUTPUT"
	assert_contains '#never in Plain directory# in path' "$OUTPUT"
	assert_contains '#never in Plain glob# in path' "$OUTPUT"
	# src/*/Clean.php can match files in other directories under src/
	assert_not_contains '#never in src glob#' "$OUTPUT"
	assert_not_contains '#never globally#' "$OUTPUT"
}

function test_sibling_directory() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw srcc 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_contains '#never in srcc# in path' "$OUTPUT"
	assert_contains '#never in srcc directory# in path' "$OUTPUT"
	assert_contains "#never in two paths# in path $PWD/srcc/Bar.php was not matched" "$OUTPUT"
	assert_not_contains '#never in Clean#' "$OUTPUT"
	assert_not_contains '#never in Plain directory#' "$OUTPUT"
	assert_not_contains '#never in Plain glob#' "$OUTPUT"
	assert_not_contains '#never in src glob#' "$OUTPUT"
	assert_not_contains 'is expected to occur' "$OUTPUT"
	assert_not_contains '#never globally#' "$OUTPUT"
}

function test_directory_without_ignores() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw tests 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "0" "$CODE"
}

# A rule on collected data reports that a call has no effect only when the called function is analysed too.
# Unlike a trait, what the rule reports does not show in what the analysis records about the file.
function test_rule_on_collected_data_across_directories() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src/Calls 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	if [[ "$OUTPUT" == *'CallsPureFunction.php'* ]]; then
		bashunit::todo 'the unmatched entry is reported, the rule cannot tell that srcc/pure-function.php is not analysed'
		return
	fi
	assert_same "0" "$CODE"
}
