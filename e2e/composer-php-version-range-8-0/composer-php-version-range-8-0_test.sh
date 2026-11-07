#!/usr/bin/env bash

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_composer_php_version_range_8_0() {
	composer install
	OUTPUT=$(../../bin/phpstan analyse --no-progress --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains '/errors.php:16:Missing parameter $j (int) in call to function ComposerPhpVersionRange80Errors\acceptsThreeInts.' "$OUTPUT"
	assert_contains '/errors.php:16:Unpacked argument (...) cannot be followed by a non-unpacked argument.' "$OUTPUT"
	assert_not_contains 'no-errors.php' "$OUTPUT"
}
