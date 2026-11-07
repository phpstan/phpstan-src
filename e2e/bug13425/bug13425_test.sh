#!/usr/bin/env bash

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_bug13425() {
	timeout 15 ../../bin/phpstan analyze src/ plugins/ 2>&1 && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
}
