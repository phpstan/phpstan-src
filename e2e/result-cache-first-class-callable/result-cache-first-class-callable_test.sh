#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_first_class_callable() {
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "0" "$CODE"
	# The signature of a first-class callable is the signature of its target, so a
	# file creating one from a function, a method or a static method of Dep.php
	# depends on Dep.php. Unrelated.php does not.
	patch -b src/Dep.php < dep.patch
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '4' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same "1" "$CODE"
	assert_contains 'FunctionFcc.php:9:Function ResultCacheE2EFirstClassCallable\functionFcc() should return string but returns int.' "$OUTPUT"
	assert_contains 'MethodFcc.php:9:Function ResultCacheE2EFirstClassCallable\methodFcc() should return string but returns int.' "$OUTPUT"
	assert_contains 'StaticMethodFcc.php:9:Function ResultCacheE2EFirstClassCallable\staticMethodFcc() should return string but returns int.' "$OUTPUT"
}
