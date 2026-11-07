#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_scanned_new_file() {
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'UsesNew.php:10:Instantiated class ResultCacheE2EScannedNewFile\NewlyScanned not found.' "$OUTPUT"
	# A file that newly appears in a scanDirectories directory may define a symbol that was
	# reported as unknown somewhere, so the files with errors are re-analysed too - the same
	# way a new analysed file makes them re-analysed. src/Foo.php is still left alone.
	mv new-file/NewlyScanned.php scanned/NewlyScanned.php
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Scanned files with changed symbols (1); re-analysing the files affected by them.' "$OUTPUT"
	assert_contains 'Result cache restored. 1 file will be reanalysed.' "$OUTPUT"
	assert_not_contains 'Instantiated class ResultCacheE2EScannedNewFile\NewlyScanned not found.' "$OUTPUT"
	assert_contains 'UsesNew.php:10:Method ResultCacheE2EScannedNewFile\UsesNew::doUsesNew() should return int but returns string.' "$OUTPUT"
}
