#!/usr/bin/env bash

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_composer_php_version_range_7_and_8() {
	# Scope::getPhpVersion() has to honour the composer.json "require.php" range:
	# https://github.com/phpstan/phpstan/issues/15270
	composer install
	OUTPUT=$(../../bin/phpstan analyse --no-progress --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains '/errors.php:11:Non-capturing catch is supported only on PHP 8.0 and later.' "$OUTPUT"
	assert_contains '/errors.php:23:Named arguments are supported only on PHP 8.0 and later.' "$OUTPUT"
	assert_contains '/errors.php:29:Function ComposerPhpVersionRange7And8Errors\phpdocBoolReturn() never returns false so the return type can be changed to true.' "$OUTPUT"
	assert_not_contains 'no-errors.php' "$OUTPUT"
	assert_not_contains 'php-version-id.php' "$OUTPUT"
}
