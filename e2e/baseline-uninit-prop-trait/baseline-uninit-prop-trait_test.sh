#!/usr/bin/env bash

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_generated_baseline_with_debug() {
	../../bin/phpstan analyse --debug --configuration test-no-baseline.neon --generate-baseline test-baseline.neon
	../../bin/phpstan analyse --debug --configuration test.neon && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}

function test_generated_baseline() {
	../../bin/phpstan analyse --configuration test-no-baseline.neon --generate-baseline test-baseline.neon
	../../bin/phpstan analyse --configuration test.neon && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}
