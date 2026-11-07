#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_bug_15475() {
	# https://github.com/phpstan/phpstan/issues/15475
	# A run on other paths builds the name scopes of the scanned file from its
	# simplified AST, without the anonymous class declared in a function body.
	# A later full run must not reuse them, even with the result cache cleared.
	../../bin/phpstan analyse src/usage.php
	../../bin/phpstan clear-result-cache
	../../bin/phpstan analyse && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}
