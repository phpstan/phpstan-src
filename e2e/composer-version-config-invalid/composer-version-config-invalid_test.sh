#!/usr/bin/env bash

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_composer_version_config_invalid() {
	OUTPUT=$(../../bin/phpstan 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Invalid configuration' "$OUTPUT"
	assert_contains 'Invalid PHP version range: phpVersion.max should be greater or equal to phpVersion.min.' "$OUTPUT"
}
