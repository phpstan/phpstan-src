#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_trait_ignore() {
	rm -rf tmp/cache tmp/resultCache.php
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Result cache is saved.' "$OUTPUT"
	assert_contains 'MyTrait.php (in context of class Repro\A):10:Ignore with identifier method.notFound has no comment.' "$OUTPUT"
	assert_contains 'MyTrait.php (in context of class Repro\B):10:Ignore with identifier method.notFound has no comment.' "$OUTPUT"
	assert_contains 'MyTrait.php (in context of class Repro\A):12:Ignoring all errors on a line is not allowed.' "$OUTPUT"
	assert_contains 'MyTrait.php (in context of class Repro\B):12:Ignoring all errors on a line is not allowed.' "$OUTPUT"

	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Result cache restored. 0 files will be reanalysed.' "$OUTPUT"
	assert_contains 'MyTrait.php (in context of class Repro\A):10:Ignore with identifier method.notFound has no comment.' "$OUTPUT"
	assert_contains 'MyTrait.php (in context of class Repro\B):10:Ignore with identifier method.notFound has no comment.' "$OUTPUT"
	assert_contains 'MyTrait.php (in context of class Repro\A):12:Ignoring all errors on a line is not allowed.' "$OUTPUT"
	assert_contains 'MyTrait.php (in context of class Repro\B):12:Ignoring all errors on a line is not allowed.' "$OUTPUT"

	cp src/A.php tmp/A.php
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw --tmp-file tmp/A.php --instead-of src/A.php 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'MyTrait.php (in context of class Repro\A):10:Ignore with identifier method.notFound has no comment.' "$OUTPUT"
	assert_contains 'MyTrait.php (in context of class Repro\A):12:Ignoring all errors on a line is not allowed.' "$OUTPUT"
	rm -f tmp/A.php

	patch -b src/A.php < removeTrait.patch
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Result cache restored. 1 file will be reanalysed.' "$OUTPUT"
	assert_not_contains 'in context of class Repro\A' "$OUTPUT"
	assert_contains 'MyTrait.php (in context of class Repro\B):10:Ignore with identifier method.notFound has no comment.' "$OUTPUT"
	assert_contains 'MyTrait.php (in context of class Repro\B):12:Ignoring all errors on a line is not allowed.' "$OUTPUT"
	mv src/A.php.orig src/A.php
}
