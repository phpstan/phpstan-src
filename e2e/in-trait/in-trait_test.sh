#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_in_trait() {
	OUTPUT=$(../../bin/phpstan --error-format=raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	assert_contains 'FooTrait.php:10:Strict comparison using === between int<0, max> and false will always evaluate to false.' "$OUTPUT"
	assert_contains 'FooTrait.php (in context of class E2EInTrait\Bar):18:Strict comparison using === between E2EInTrait\Bar and null will always evaluate to false.' "$OUTPUT"
	assert_contains 'FooTrait.php (in context of class E2EInTrait\Foo):18:Strict comparison using === between E2EInTrait\Foo and null will always evaluate to false.' "$OUTPUT"
}
