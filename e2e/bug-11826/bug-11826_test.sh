#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_bug_11826() {
	composer install
	OUTPUT=$(../../bin/phpstan 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Child process error (exit code 255): PHP Fatal error' "$OUTPUT"
	assert_contains 'Result is incomplete because of severe errors.' "$OUTPUT"
}
