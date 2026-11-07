#!/usr/bin/env bash

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_composer_min_max_version() {
	composer install
	../../bin/phpstan analyze test.php --level=0 && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}
