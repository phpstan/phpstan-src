#!/usr/bin/env bash

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

# Without paths in the configuration, it is not known what the whole project is. The directories
# passed on the command line are taken as the whole project, so that the entries without a path
# are still reported for those who always pass the paths on the command line.
function test_directories_are_the_whole_project() {
	OUTPUT=$(../../bin/phpstan analyse -c phpstan-no-paths.neon --error-format raw src 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_contains '#never in Clean# in path' "$OUTPUT"
	assert_contains '#never in Plain directory# in path' "$OUTPUT"
	assert_contains 'Ignored error pattern #never globally# was not matched' "$OUTPUT"
	assert_contains 'Ignored error pattern #never globally either# was not matched' "$OUTPUT"
	# a file that is not analysed reports no errors either way
	assert_not_contains '#never in srcc# in path' "$OUTPUT"
}
