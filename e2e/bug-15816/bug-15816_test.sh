#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

# Restoring the result cache must not run the StubFilesExtensions - they can rely on
# bootstrapFiles, which run only after the restore.

function test_class_appearing_after_it_was_missing() {
	composer install
	OUTPUT=$(../../bin/phpstan analyse --error-format=raw 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_contains 'Instantiated class App\Later not found.' "$OUTPUT"

	cp Later.php.txt src/Later.php
	OUTPUT=$(../../bin/phpstan analyse -vvv --error-format=raw 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "0" "$CODE"
	assert_contains 'Result cache restored' "$OUTPUT"
	assert_not_contains 'not found' "$OUTPUT"
}

function test_tracked_class_dependency() {
	composer install
	../../bin/phpstan analyse -c phpstan-track.neon --error-format=raw || true

	echo '// edited' >> src/Uses.php
	OUTPUT=$(../../bin/phpstan analyse -c phpstan-track.neon -vvv --error-format=raw 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_contains 'Result cache restored' "$OUTPUT"
	assert_contains 'Instantiated class App\Later not found.' "$OUTPUT"
}
