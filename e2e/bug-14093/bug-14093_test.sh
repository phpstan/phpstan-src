#!/usr/bin/env bash

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_bug_14093() {
	# Use a relative sys_temp_dir so the value starts with a letter (not /), just like Windows
	# paths starting with a drive letter. If the sys_temp_dir value isn't correctly quoted,
	# PHP evaluates ~ as a bitwise NOT rather than a literal character, truncating the path.
	mkdir tmp~1
	php -d "sys_temp_dir='tmp~1'" ../../bin/phpstan analyze
	# A worker that evaluated sys_temp_dir='tmp~1' to 'tmp' would have created tmp
	assert_directory_not_exists tmp
}
