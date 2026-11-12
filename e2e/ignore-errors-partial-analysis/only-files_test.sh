#!/usr/bin/env bash

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_only_files_reports_ignores_of_the_analysed_files() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src/Plain/Clean.php src/Plain/Foo.php 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_contains '#never in Clean# in path' "$OUTPUT"
	assert_contains 'is expected to occur 2 times, but occurred only 1 time.' "$OUTPUT"
	assert_contains "#never in two paths# in path $PWD/src/Plain/Clean.php was not matched" "$OUTPUT"
	# the files do not cover the whole directory
	assert_not_contains '#never in Plain directory#' "$OUTPUT"
	assert_not_contains '#never in Plain glob#' "$OUTPUT"
	assert_not_contains '#never in src glob#' "$OUTPUT"
	assert_not_contains '#never in srcc#' "$OUTPUT"
	assert_not_contains '#never globally#' "$OUTPUT"
}

function test_only_files_does_not_report_ignores_of_other_files() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src/Plain/Foo.php 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_contains 'is expected to occur 2 times, but occurred only 1 time.' "$OUTPUT"
	assert_not_contains '#never in Clean#' "$OUTPUT"
	assert_not_contains '#never in two paths#' "$OUTPUT"
}

# The traits of an analysed file decide what is reported in it like in an analysis of directories.
function test_only_files_with_traits() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src/Classes/UsesSharedTrait.php src/Classes/UsesSharedTraitWithOwnError.php src/Classes/UsesLibTrait.php src/Inherit/ChildOfTraitUser.php 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	# SharedTrait is not analysed
	assert_not_contains 'UsesSharedTrait.php' "$OUTPUT"
	assert_not_contains 'UsesSharedTraitWithOwnError.php' "$OUTPUT"
	# LibTrait is not analysed even in the whole project, and the errors of a parent's trait are reported in the parent
	assert_contains '#never in UsesLibTrait# in path' "$OUTPUT"
	assert_contains '#never in ChildOfTraitUser# in path' "$OUTPUT"
}

function test_only_files_with_their_traits() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw src/Classes/UsesSharedTrait.php src/Classes/UsesSharedTraitWithOwnError.php src/Traits/SharedTrait.php 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "0" "$CODE"
	# the trait is analysed only in the context of the classes using it, and not all of them are analysed
	assert_not_contains '#never in SharedTrait#' "$OUTPUT"
}

# On level 0, NotAnalysedTraitRule, which does not report its errors when only files are analysed, does not run.
function test_only_files_declaring_a_trait() {
	OUTPUT=$(../../bin/phpstan analyse -l 0 --error-format raw src/Plain/Clean.php src/Mixed/ClassAndTrait.php 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_contains '#never in Clean# in path' "$OUTPUT"
	assert_not_contains 'ClassAndTrait' "$OUTPUT"
}
