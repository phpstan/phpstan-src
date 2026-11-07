#!/usr/bin/env bash

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_composer_php_version_range_8_5() {
	composer install --ignore-platform-reqs
	../../bin/phpstan analyse --no-progress && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}
