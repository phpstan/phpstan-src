#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_oom_forked_worker() {
	composer install
	OUTPUT=$(../../bin/phpstan analyse --memory-limit=300M 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Child process error: PHPStan process crashed' "$OUTPUT"
	assert_contains 'Result is incomplete because of severe errors.' "$OUTPUT"
}
