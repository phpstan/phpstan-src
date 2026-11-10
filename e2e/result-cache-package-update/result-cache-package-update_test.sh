#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_package_update() {
	composer install
	../../bin/phpstan analyse
	# Update a single Composer package by bumping its version with a patch and letting Composer
	# reinstall it, which changes its recorded reference in vendor/composer/installed.php. The
	# cache must be invalidated only for files depending on that package (src/Foo.php uses
	# test/logger), not for the whole project.
	patch -p1 < updatePackage.patch
	composer update test/logger --no-cache
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Composer packages changed (test/logger); re-analysing the files depending on them.' "$OUTPUT"
}
