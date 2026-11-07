#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_autoloaded() {
	composer install
	../../bin/phpstan analyse
	# A project file that is not analysed and not scanned either - it is only reachable
	# through the Composer autoloader - is tracked as a file dependency too, so editing it
	# re-analyses the files depending on it instead of leaving stale results behind.
	patch -b other/Dep.php < patch.patch
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Result cache restored. 1 file will be reanalysed.' "$OUTPUT"
	assert_contains 'Foo.php:12:Method ResultCacheE2EAutoloaded\Foo::doFoo() should return int but returns string.' "$OUTPUT"
}
