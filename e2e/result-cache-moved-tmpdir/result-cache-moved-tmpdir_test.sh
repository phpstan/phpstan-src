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
	OUTPUT=$(../../bin/phpstan analyse -c moved.neon -vv 2>&1)
	echo "$OUTPUT"
	assert_contains 'Result cache restored. 0 files will be reanalysed.' "$OUTPUT"
	assert_not_contains 'metadata do not match' "$OUTPUT"
}
