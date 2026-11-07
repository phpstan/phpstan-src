#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_truncated() {
	../../bin/phpstan -vvv
	# Cut inside the first section's payload: the cache is damaged, not merely stale, so
	# it has to be discarded rather than read as far as it goes.
	php truncate.php
	OUTPUT=$(../../bin/phpstan -vvv 2>&1)
	echo "$OUTPUT"
	assert_contains 'an error occurred while loading the cache file' "$OUTPUT"
	assert_contains 'Result cache is saved.' "$OUTPUT"
	# And once more inside a section that is read lazily, which is a different code path.
	php truncate-lazy.php
	OUTPUT=$(../../bin/phpstan -vvv 2>&1)
	echo "$OUTPUT"
	assert_contains 'is truncated at entry' "$OUTPUT"
	OUTPUT=$(../../bin/phpstan -vvv 2>&1)
	echo "$OUTPUT"
	assert_contains 'Result cache restored.' "$OUTPUT"
}
