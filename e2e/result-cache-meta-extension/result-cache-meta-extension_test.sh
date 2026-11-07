#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_meta_extension() {
	composer install
	../../bin/phpstan -vvv
	../../bin/phpstan -vvv --fail-without-result-cache
	echo 'modified-hash' > hash.txt
	OUTPUT=$(../../bin/phpstan -vvv --fail-without-result-cache 2>&1) && CODE=0 || CODE=$?
	assert_same "2" "$CODE"
	echo "$OUTPUT"
	assert_matches "Note: Using configuration file .+phpstan.neon." "$OUTPUT"
	assert_contains 'Result cache not used because the metadata do not match: metaExtensions' "$OUTPUT"
}
