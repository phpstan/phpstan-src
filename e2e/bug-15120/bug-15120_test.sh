#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_bug_15120() {
	composer install
	../../bin/phpstan analyse -vvv
	../../bin/phpstan analyse -vvv
	patch -b stubs/Foo.stub < stub-change.patch
	OUTPUT=$(../../bin/phpstan analyse --error-format=raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'should return non-empty-string but returns string' "$OUTPUT"
	mv stubs/Foo.stub.orig stubs/Foo.stub
	../../bin/phpstan analyse -vvv
	patch -b stub-files.neon < config-change.patch
	OUTPUT=$(../../bin/phpstan analyse -vvv 2>&1)
	echo "$OUTPUT"
	assert_contains 'Result cache not used because the metadata do not match' "$OUTPUT"
	assert_contains 'configStubFiles' "$OUTPUT"
	mv stub-files.neon.orig stub-files.neon
	../../bin/phpstan analyse -vvv
}
