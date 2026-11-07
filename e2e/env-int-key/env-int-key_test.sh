#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_env_int_key() {
	env 1=1 ../../bin/phpstan analyse test.php && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}
