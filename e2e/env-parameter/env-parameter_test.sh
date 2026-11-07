#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_env_parameter() {
	export PHPSTAN_RESULT_CACHE_PATH=/some/path
	ACTUAL=$(../../bin/phpstan dump-parameters -c phpstan.neon --json -l 9 | jq --raw-output '.resultCachePath')
	assert_equals "/some/path" "$ACTUAL"
}
