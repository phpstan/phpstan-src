#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_scanned_2() {
	../../bin/phpstan analyse
	# Editing a file from scanDirectories re-analyses only the files depending on it -
	# src/Unrelated.php and src/UsesExtra.php are left alone.
	patch -b scanned/Dep.php < patch.patch
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Result cache restored. 1 file will be reanalysed.' "$OUTPUT"
	assert_contains 'Foo.php:10:Method ResultCacheE2EScanned2\Foo::doFoo() should return int but returns string.' "$OUTPUT"
	# Undoing the edit makes the error go away again.
	mv scanned/Dep.php.orig scanned/Dep.php
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Result cache restored. 1 file will be reanalysed.' "$OUTPUT"
	# The same for a file listed in scanFiles.
	patch -b scan-file/Extra.php < patch-2.patch
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Result cache restored. 1 file will be reanalysed.' "$OUTPUT"
	assert_contains 'UsesExtra.php:10:Method ResultCacheE2EScanned2\UsesExtra::doUsesExtra() should return int but returns string.' "$OUTPUT"
	# Deleting a scanned file re-analyses the files depending on it.
	rm scanned/Dep.php
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Result cache restored. 1 file will be reanalysed.' "$OUTPUT"
	assert_contains 'Foo.php:10:Call to method doDep() on an unknown class ResultCacheE2EScanned2\Dep.' "$OUTPUT"
}
