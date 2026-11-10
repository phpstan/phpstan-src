#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_moved_tmpdir() {
	../../bin/phpstan
	# Where the cache is stored cannot change what it holds, so moving it has to keep it
	# usable: same project, same analysis, cache read from a different tmpDir.
	mkdir -p tmp-b
	cp tmp-a/resultCache.php tmp-b/resultCache.php
	INFO=$(../../bin/phpstan result-cache-info -c moved.neon --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '0' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
}
