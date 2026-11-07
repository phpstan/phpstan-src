#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_editor_mode() {
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Bar.php:10:Parameter #1 $s of method EditorModeE2E\Bar::requireString() expects string, int given. [identifier=argument.type]' "$OUTPUT"
	assert_contains 'Foo.php:10:Method EditorModeE2E\Foo::doFoo() should return int but returns string. [identifier=return.type]' "$OUTPUT"
	assert_contains 'Result cache is saved.' "$OUTPUT"

	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw --tmp-file differentFoo.php --instead-of src/Foo.php 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Foo.php:10:Method EditorModeE2E\Foo::doFoo() should return float but returns string. [identifier=return.type]' "$OUTPUT"
	assert_not_contains 'Foo.php:10:Method EditorModeE2E\Foo::doFoo() should return int but returns string. [identifier=return.type]' "$OUTPUT"
	assert_not_contains 'differentFoo.php' "$OUTPUT"
	assert_contains 'Bar.php:10:Parameter #1 $s of method EditorModeE2E\Bar::requireString() expects string, float given. [identifier=argument.type]' "$OUTPUT"
	assert_contains 'Result cache restored. 2 files will be reanalysed.' "$OUTPUT"
	assert_contains 'Result cache was not saved because of --tmp-file and --instead-of CLI options passed (editor mode).' "$OUTPUT"
}
