#!/usr/bin/env bash

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_phpstan_phpunit_190() {
	cd ../..
	bin/phpstan analyse -l 8 e2e/phpstan-phpunit-190/test.php -c e2e/phpstan-phpunit-190/test.neon && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}
