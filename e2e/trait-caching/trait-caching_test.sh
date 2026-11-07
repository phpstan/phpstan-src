#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_unchanged_traits() {
	../../bin/phpstan analyze --no-progress --level 8 --error-format raw data/
	../../bin/phpstan analyze --no-progress --level 8 --error-format raw data/ && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}

function test_changed_trait_used_in_class() {
	../../bin/phpstan analyze --no-progress --level 8 --error-format raw data/
	patch -b data/TraitOne.php < TraitOne.patch
	OUTPUT=$(../../bin/phpstan analyze --no-progress --level 8 --error-format raw data/ 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_line_count 2 "$OUTPUT"
	assert_matches "Note: Using configuration file .+phpstan.neon." "$OUTPUT"
	assert_contains 'Method TraitsCachingIssue\TestClassUsingTrait::doBar() should return stdClass but returns Exception.' "$OUTPUT"
}

function test_changed_trait_used_in_anonymous_class() {
	../../bin/phpstan analyze --no-progress --level 8 --error-format raw data/
	patch -b data/TraitTwo.php < TraitTwo.patch
	OUTPUT=$(../../bin/phpstan analyze --no-progress --level 8 --error-format raw data/ 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_line_count 2 "$OUTPUT"
	assert_matches "Note: Using configuration file .+phpstan.neon." "$OUTPUT"
	assert_contains 'Method class@anonymous/TestClassUsingTrait.php:20::doBar() should return stdClass but returns Exception.' "$OUTPUT"
}

function test_both_traits_changed() {
	../../bin/phpstan analyze --no-progress --level 8 --error-format raw data/
	patch -b data/TraitOne.php < TraitOne.patch
	patch -b data/TraitTwo.php < TraitTwo.patch
	OUTPUT=$(../../bin/phpstan analyze --no-progress --level 8 --error-format raw data/ 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_line_count 3 "$OUTPUT"
	assert_matches "Note: Using configuration file .+phpstan.neon." "$OUTPUT"
	assert_contains 'Method TraitsCachingIssue\TestClassUsingTrait::doBar() should return stdClass but returns Exception.' "$OUTPUT"
	assert_contains 'Method class@anonymous/TestClassUsingTrait.php:20::doBar() should return stdClass but returns Exception.' "$OUTPUT"
}
