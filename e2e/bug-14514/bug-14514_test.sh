#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_bug_14514() {
	composer install
	../../bin/phpstan analyze bug-14515.php && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}
