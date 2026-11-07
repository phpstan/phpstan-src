#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_define() {
	../../bin/phpstan analyse
	patch -b src/define.php < changeDefineValue.patch
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'ClassUsingDefine.php:13:Method ResultCacheE2EDefine\ClassUsingDefine::getMode() should return int<1, 3> but returns 5. [identifier=return.type]' "$OUTPUT"
}
