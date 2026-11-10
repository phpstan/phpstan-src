#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_stub_files_errors() {
	# The stub file is validated on the full analysis, and its error is kept in the result cache.
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'test.stub:8:Method StubFilesResultCache\Foo::doFoo() has parameter $a with no type specified.' "$OUTPUT"
	assert_not_contains 'return statement is missing' "$OUTPUT"
	# A new file is analysed while the cached results are restored. The stub file must not be
	# analysed with it, otherwise the rules for analysed files would report that doFoo() does
	# not return.
	echo '<?php function doBaz(): void {}' > src/Baz.php
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Result cache restored.' "$OUTPUT"
	assert_contains 'test.stub:8:Method StubFilesResultCache\Foo::doFoo() has parameter $a with no type specified.' "$OUTPUT"
	assert_not_contains 'return statement is missing' "$OUTPUT"
}
