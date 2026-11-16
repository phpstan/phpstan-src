#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_bug_15752() {
	# https://github.com/phpstan/phpstan/issues/15752
	# The name scopes of a file name its anonymous classes after the path relative
	# to the working directory. A run from another directory must not reuse them,
	# even with the result cache cleared.
	../../bin/phpstan analyse
	../../bin/phpstan clear-result-cache
	cd src
	../../../bin/phpstan analyse -c ../phpstan.neon && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}
