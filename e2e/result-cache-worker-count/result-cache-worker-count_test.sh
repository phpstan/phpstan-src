#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_worker_count() {
	OUTPUT=$(../../bin/phpstan -vvv 2>&1)
	echo "$OUTPUT"
	assert_contains 'largest of 2' "$OUTPUT"
	# Only some files are analysed again, so the result is rebuilt from the cache
	# and must still report the peak memory of the workers.
	echo '// changed' >> src/Foo.php
	echo '// changed' >> src/Bar.php
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '2' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan -vvv 2>&1)
	echo "$OUTPUT"
	assert_contains 'largest of 2' "$OUTPUT"
}
