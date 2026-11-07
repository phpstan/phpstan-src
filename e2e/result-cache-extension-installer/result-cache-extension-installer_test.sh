#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_extension_installer() {
	composer install
	# cold run populates the cache; the shipped rule reports an error, so ignore the exit code
	../../bin/phpstan analyse || true
	# Same as result-cache-extension-update, but the rule package registers its extension through
	# phpstan/extension-installer (no includes: in the project config), which is the common case.
	patch -p1 < updatePackage.patch
	composer update test/installer-rule --no-cache
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'and register a class in the container; re-analysing everything.' "$OUTPUT"
	assert_contains 'Subject.php:5:installer rule version 2' "$OUTPUT"
}
