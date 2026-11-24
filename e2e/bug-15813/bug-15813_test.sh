#!/usr/bin/env bash

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

# Below PHP 8.0 the enum RoundingMode is not reflected as an enum, and the
# default value of round()'s $mode on phpVersion 8.4 crashed the analysis.
# @tag own-setup
function test_pure_enum_case_default_value_on_php_7_4() {
	OUTPUT=$(../../bin/phpstan analyse --no-progress --error-format raw 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "0" "$CODE"
	assert_not_contains 'Internal error' "$OUTPUT"
}
