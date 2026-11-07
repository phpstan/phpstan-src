#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_statement_parameter() {
	PARAMETERS=$(../../bin/phpstan dump-parameters -c phpstan.neon --json -l 9)
	assert_equals 'PHPSTAN' "$(echo "$PARAMETERS" | jq --raw-output '.statementParameter')"
	assert_equals 'PHPSTAN/suffix' "$(echo "$PARAMETERS" | jq --raw-output '.concatenatedStatementParameter')"
}
