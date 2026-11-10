#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_autoloaded_constant() {
	composer install
	# The same sequence on a project file that is neither analysed nor scanned - it is only
	# reachable through the Composer autoloader, so it is tracked through the dependency graph
	# alone.
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Foo.php:10:Dumped type: 1' "$OUTPUT"
	# The constant is renamed to one the class already declares. The file is invalid PHP, but
	# the class still reflects, so the file using the constant is re-analysed through its
	# dependency edge.
	patch -b other/Dep.php < duplicate.patch
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Foo.php:10:Access to undefined constant ResultCacheE2EAutoloadedConstant\Dep::CON.' "$OUTPUT"
	# Renamed to something else instead.
	mv other/Dep.php.orig other/Dep.php
	patch -b other/Dep.php < renamed.patch
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Foo.php:10:Access to undefined constant ResultCacheE2EAutoloadedConstant\Dep::CON.' "$OUTPUT"
	# Broken the same way again.
	mv other/Dep.php.orig other/Dep.php
	patch -b other/Dep.php < duplicate.patch
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Foo.php:10:Access to undefined constant ResultCacheE2EAutoloadedConstant\Dep::CON.' "$OUTPUT"
	# And put back the way it was.
	mv other/Dep.php.orig other/Dep.php
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Foo.php:10:Dumped type: 1' "$OUTPUT"
	# A syntax error makes the class disappear altogether: the file using it reports an unknown
	# class and records no dependency edge to the file, so nothing but the file being fixed can
	# bring the file using it back - it must be re-analysed once the class is there again.
	patch -b other/Dep.php < broken.patch
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Foo.php:10:Access to constant CON on an unknown class ResultCacheE2EAutoloadedConstant\Dep.' "$OUTPUT"
	mv other/Dep.php.orig other/Dep.php
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Foo.php:10:Dumped type: 1' "$OUTPUT"
}
