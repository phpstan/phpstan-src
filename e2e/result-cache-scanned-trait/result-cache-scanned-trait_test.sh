#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_scanned_trait() {
	../../bin/phpstan analyse
	# A trait living in a scanned file is tracked the same way, including through the
	# used-trait dependencies.
	patch -b scanned/DepTrait.php < patch.patch
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'UsesTrait.php:12:Method ResultCacheE2EScannedTrait\UsesTrait::doIt() should return int but returns string.' "$OUTPUT"
}
