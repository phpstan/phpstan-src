#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_bug_15150() {
	# PHPStan bundles symfony/polyfill-php85 for its own runtime, so array_first()
	# and other PHP 8.5 symbols are declared in the PHPStan process. A project
	# analysed with PHP 8.4 must not see them
	# (https://github.com/phpstan/phpstan/issues/15150).
	OUTPUT=$(../../bin/phpstan analyse --error-format=raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'test.php:11:Function array_first not found.' "$OUTPUT"
	assert_contains 'test.php:12:Function array_last not found.' "$OUTPUT"
	assert_contains 'test.php:21:Instantiated class Filter\FilterException not found.' "$OUTPUT"
	# array_find() exists in the analysed PHP version, PhpStorm stubs describe it
	assert_not_contains 'array_find' "$OUTPUT"
}
