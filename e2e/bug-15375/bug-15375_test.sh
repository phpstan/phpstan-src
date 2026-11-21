#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_bug_15375() {
	# A class that was missing when the result cache was saved exists now. Restoring the
	# cache computes the class's value - its declarations and its ancestors' - in the main
	# process, before the deferred bootstrapFiles have run. It must not reach
	# StubPhpDocProvider, which runs the StubFilesExtensions.
	composer install
	OUTPUT=$(../../bin/phpstan analyse -vvv 2>&1 || true)
	echo "$OUTPUT"
	assert_contains 'Instantiated class Bug15375\Later not found.' "$OUTPUT"
	cp later/Later.php src/Later.php
	OUTPUT=$(../../bin/phpstan analyse -vvv 2>&1 || true)
	echo "$OUTPUT"
	assert_not_contains 'StubFilesExtension ran before bootstrapFiles.' "$OUTPUT"
	assert_contains 'No errors' "$OUTPUT"
	../../bin/phpstan analyse -vvv
}
