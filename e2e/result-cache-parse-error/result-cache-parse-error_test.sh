#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_parse_error() {
	# src/Thing.php has a parse error, and src/User.php uses the class it would declare. The parse
	# error carries an exception, so src/Thing.php is left out of the result cache, but the rest
	# of it is saved.
	OUTPUT=$(../../bin/phpstan analyse -vvv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Thing.php:12:Syntax error, unexpected EOF on line 12' "$OUTPUT"
	assert_contains 'Result cache is saved.' "$OUTPUT"
	test -f tmp/resultCache.php
	# Only src/Thing.php is analysed again, and the result cache is not rewritten.
	OUTPUT=$(../../bin/phpstan analyse -vvv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Thing.php:12:Syntax error, unexpected EOF on line 12' "$OUTPUT"
	assert_contains 'Result cache restored. 1 file will be reanalysed.' "$OUTPUT"
	assert_contains 'Result cache was not rewritten because it is unchanged.' "$OUTPUT"
	# Fixing the parse error declares Thing, so the error that src/User.php had in the result
	# cache goes away.
	cp fixed/Thing.php src/Thing.php
	OUTPUT=$(../../bin/phpstan analyse -vvv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
	assert_not_contains 'not found' "$OUTPUT"
}
