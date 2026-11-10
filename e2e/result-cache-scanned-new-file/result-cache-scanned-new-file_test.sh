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
	# A file that newly appears in a scanDirectories directory defines a class src/UsesNew.php
	# recorded as missing, so that file is re-analysed. src/Foo.php is left alone.
	mv new-file/NewlyScanned.php scanned/NewlyScanned.php
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_not_contains 'Instantiated class ResultCacheE2EScannedNewFile\NewlyScanned not found.' "$OUTPUT"
	assert_contains 'UsesNew.php:10:Method ResultCacheE2EScannedNewFile\UsesNew::doUsesNew() should return int but returns string.' "$OUTPUT"
}
