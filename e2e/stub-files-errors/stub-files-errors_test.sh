#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_stub_files_errors() {
	OUTPUT=$(../../bin/phpstan 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Method StubFiles\Foo::doFoo() has no return type specified.' "$OUTPUT"
	OUTPUT=$(../../bin/phpstan 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Method StubFiles\Foo::doFoo() has no return type specified.' "$OUTPUT"
}
