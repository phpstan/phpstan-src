#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_extension_copied_package() {
	composer install
	# The rule comes from a path repository Composer copies rather than symlinks, so the
	# installed file really is inside vendor/. It is still the project's own code, edited in
	# place - unlike a downloaded package, which only ever changes through Composer.
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'the rule says one thing' "$OUTPUT"
	patch -p0 < patch.patch
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Result cache not used because extension file' "$OUTPUT"
	assert_contains 'the rule says another thing' "$OUTPUT"
	assert_not_contains 'the rule says one thing' "$OUTPUT"
}
