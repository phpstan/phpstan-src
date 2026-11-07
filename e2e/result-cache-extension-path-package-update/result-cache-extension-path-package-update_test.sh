#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_extension_path_package_update() {
	composer install
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'the rule says one thing' "$OUTPUT"
	# Only the package's version moves - the rule file stays byte-identical, so its hash says
	# nothing. The package is symlinked, so the rule's class reflects to the source directory
	# rather than to anything under vendor/; the package it belongs to has to be recognised
	# through the link, or a new version of an extension goes unnoticed.
	patch -p0 < updatePackage.patch
	composer update test/rule-lib --no-cache
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'register a class in the container; re-analysing everything.' "$OUTPUT"
}
