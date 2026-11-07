#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_atomic_save() {
	../../bin/phpstan
	INODE=$(ls -i tmp/resultCache.php | awk '{print $1}')
	OUTPUT=$(../../bin/phpstan -vvv 2>&1)
	echo "$OUTPUT"
	UNCHANGED=$([ "$INODE" = "$(ls -i tmp/resultCache.php | awk '{print $1}')" ] && echo unchanged || echo replaced)
	assert_equals 'unchanged' "$UNCHANGED"
	assert_contains 'Result cache was not rewritten because it is unchanged.' "$OUTPUT"
	echo >> src/Foo.php
	../../bin/phpstan
	# The cache is written next to its final path and renamed into place, so saving it
	# after a change replaces the file rather than truncating and rewriting the one the
	# next run reads. That is what keeps a run killed mid-save from leaving a partial
	# cache behind.
	REPLACED=$([ "$INODE" != "$(ls -i tmp/resultCache.php | awk '{print $1}')" ] && echo replaced || echo 'written in place')
	assert_equals 'replaced' "$REPLACED"
	# A completed save leaves nothing next to the cache file.
	LEFTOVERS=$(ls tmp/ | grep -c '\.tmp$' || true)
	assert_equals '0' "$LEFTOVERS"
	OUTPUT=$(../../bin/phpstan -vvv 2>&1)
	echo "$OUTPUT"
	assert_contains 'Result cache restored. 0 files will be reanalysed.' "$OUTPUT"
}
