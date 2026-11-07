#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_1() {
	echo -n > phpstan-baseline.neon
	../../bin/phpstan -vvv
	patch -b src/Bar.php < patch-1.patch
	cat baseline-1.neon > phpstan-baseline.neon
	../../bin/phpstan -vvv
	mv src/Bar.php.orig src/Bar.php
	echo -n > phpstan-baseline.neon
	../../bin/phpstan -vvv && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}

# @tag windows
function test_windows_plain_project() {
	echo -n > phpstan-baseline.neon
	php ../../bin/phpstan analyse --no-ansi
	php ../../bin/phpstan analyse -vv --no-ansi 2>&1 | tee output.txt
	grep -F "Result cache restored. 0 files will be reanalysed." output.txt && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}
