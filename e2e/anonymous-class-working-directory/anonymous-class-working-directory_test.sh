#!/usr/bin/env bash

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

# The name of an anonymous class contains the hash of its file path relative to the working
# directory. The PHPDoc name scopes of a file are cached by its absolute path, so a run from
# another working directory must not look up its anonymous classes in the name scopes cached
# by the previous run, or their PHPDocs (here @return never) get lost.
function test_anonymous_class_phpdoc_survives_working_directory_change() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "0" "$CODE"

	cd ..
	../bin/phpstan clear-result-cache -c anonymous-class-working-directory/phpstan.neon
	OUTPUT=$(../bin/phpstan analyse --error-format raw -c anonymous-class-working-directory/phpstan.neon 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "0" "$CODE"
}
