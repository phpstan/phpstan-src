#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_restore_without_reflection() {
	composer install
	../../bin/phpstan -vvv
	../../bin/phpstan -vvv && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}
