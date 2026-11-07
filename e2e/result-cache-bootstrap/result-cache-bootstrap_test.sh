#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_bootstrap() {
	../../bin/phpstan analyse
	# A bootstrapFile is executed, not just read, so it can change anything about the
	# analysis. It stays part of the executedFilesHashes metadata and keeps invalidating the
	# whole result cache - the scanned file tracking does not apply to it.
	patch -b bootstrap.php < patch.patch
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Result cache not used because the metadata do not match: executedFilesHashes' "$OUTPUT"
	assert_contains 'Foo.php:10:Method ResultCacheE2EBootstrap\Foo::doFoo() should return int but returns string.' "$OUTPUT"
}
