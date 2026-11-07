#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_composer_lock() {
	composer install
	../../bin/phpstan analyse
	# Change composer.lock without changing any installed package version/reference
	# (vendor/composer/installed.php is left untouched), like a regenerated lock in CI
	# when composer.lock is not committed. The result cache must be kept, not discarded.
	jq '.["content-hash"]="0000000000000000000000000000000000000000"' composer.lock > composer.lock.new
	mv composer.lock.new composer.lock
	OUTPUT=$(../../bin/phpstan analyse -vv 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Composer metadata changed but no package versions changed; keeping the result cache.' "$OUTPUT"
	assert_contains 'Result cache restored. 0 files will be reanalysed.' "$OUTPUT"
	# Change the content hash again AND edit a single source file: the cache is still kept
	# (no package changed), and only the one changed file is reanalysed.
	printf '\n' >> src/Foo.php
	jq '.["content-hash"]="1111111111111111111111111111111111111111"' composer.lock > composer.lock.new
	mv composer.lock.new composer.lock
	OUTPUT=$(../../bin/phpstan analyse -vv 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Composer metadata changed but no package versions changed; keeping the result cache.' "$OUTPUT"
	assert_contains 'Result cache restored. 1 file will be reanalysed.' "$OUTPUT"
}
