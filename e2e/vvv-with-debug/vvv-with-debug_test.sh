#!/usr/bin/env bash

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_debug_runs_without_parallel_scheduler() {
	OUTPUT=$(../../bin/phpstan analyze -l 0 -vvv --debug test.php 2>&1)
	echo "$OUTPUT"
	assert_not_contains 'Parallel processing scheduler' "$OUTPUT"
}

function test_debug_runs_without_parallel_scheduler_with_parallel_config() {
	OUTPUT=$(../../bin/phpstan analyze -l 0 -vvv --debug --configuration parallel.neon test.php 2>&1)
	echo "$OUTPUT"
	assert_not_contains 'Parallel processing scheduler' "$OUTPUT"
}

function test_vvv_runs_parallel_scheduler() {
	OUTPUT=$(../../bin/phpstan analyze -l 0 -vvv test.php 2>&1)
	echo "$OUTPUT"
	assert_contains 'Parallel processing scheduler' "$OUTPUT"
}
