#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_extension_update() {
	composer install
	# cold run populates the cache; the shipped rule reports an error, so ignore the exit code
	../../bin/phpstan analyse || true
	# Update the package that registers the rule: bump its version and change the rule, then let
	# Composer reinstall it. Because the package registers a class in the container, the whole
	# cache must be invalidated, not just the files depending on the package.
	patch -p1 < updatePackage.patch
	composer update test/rule-ext --no-cache
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'and register a class in the container; re-analysing everything.' "$OUTPUT"
	assert_contains 'Subject.php:5:extension rule version 2' "$OUTPUT"
}
