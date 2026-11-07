#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_9() {
	echo -n > phpstan-baseline.neon
	../../bin/phpstan -vvv
	patch -b src/Foo.php < patch-1.patch
	cat baseline-1.neon > phpstan-baseline.neon
	../../bin/phpstan -vvv
	mv src/Foo.php.orig src/Foo.php
	echo -n > phpstan-baseline.neon
	../../bin/phpstan -vvv && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}
