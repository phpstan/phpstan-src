#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_bug_14718() {
	# https://github.com/phpstan/phpstan/issues/14718
	# An inline @phpstan-ignore on an error deduplicated directly into the trait must
	# suppress it, consistently with both an empty and a primed result cache.
	../../bin/phpstan clear-result-cache
	../../bin/phpstan --error-format=raw 2>&1 && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	../../bin/phpstan --error-format=raw 2>&1 && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	# With the ignore removed, the error is reported directly in the trait file.
	sed -i 's# // @phpstan-ignore identical.alwaysFalse##' src/FooTrait.php
	../../bin/phpstan clear-result-cache
	OUTPUT=$(../../bin/phpstan --error-format=raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	assert_contains 'FooTrait.php:10:Strict comparison using === between int<0, max> and false will always evaluate to false.' "$OUTPUT"
	# An ignoreErrors path may target the error by the trait file (hides it for
	# all using classes) or by a using-class file - a class-pathed entry accounts
	# only for its own class's context, so the other using class's context is
	# still reported (see also the e2e/bug-14932 script).
	../../bin/phpstan clear-result-cache
	../../bin/phpstan analyse --error-format=raw -c ignore-trait-path.neon 2>&1 && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	../../bin/phpstan clear-result-cache
	OUTPUT=$(../../bin/phpstan analyse --error-format=raw -c ignore-class-path.neon 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	assert_contains 'FooTrait.php (in context of class Bug14718\Bar):10:Strict comparison using === between int<0, max> and false will always evaluate to false.' "$OUTPUT"
	assert_not_contains 'was not matched' "$OUTPUT"
	# A generated baseline suppresses the error on re-run, with both an empty and a
	# primed result cache (the issue reported baseline generation as impossible).
	../../bin/phpstan --generate-baseline=baseline.neon
	../../bin/phpstan clear-result-cache
	../../bin/phpstan analyse --error-format=raw -c with-baseline.neon 2>&1 && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	../../bin/phpstan analyse --error-format=raw -c with-baseline.neon 2>&1 && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}
