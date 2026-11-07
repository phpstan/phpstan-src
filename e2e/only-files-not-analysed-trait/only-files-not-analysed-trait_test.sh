#!/usr/bin/env bash

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_unanalysed_trait_error_ignored() {
	cd ../..
	bin/phpstan analyse e2e/only-files-not-analysed-trait/src -c e2e/only-files-not-analysed-trait/ignore.neon && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}

function test_trait_analysed_with_its_user() {
	cd ../..
	bin/phpstan analyse e2e/only-files-not-analysed-trait/src/Foo.php e2e/only-files-not-analysed-trait/src/BarTrait.php -c e2e/only-files-not-analysed-trait/no-ignore.neon && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}
