#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_constants() {
	../../bin/phpstan analyse
	patch -b src/Constants.php < changeConstantValue.patch
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'ClassUsingConstant.php:13:Method ResultCacheE2EConstants\ClassUsingConstant::getMode() should return int<1, 3> but returns 5. [identifier=return.type]' "$OUTPUT"
	mv src/Constants.php.orig src/Constants.php
	../../bin/phpstan analyse
	patch -b src/Constants.php < renameConstant.patch
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'ClassUsingConstant.php:13:Constant SOME_MODE not found. [identifier=constant.notFound]' "$OUTPUT"
}
