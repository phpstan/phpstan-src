#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_import_type_deleted() {
	../../bin/phpstan analyse
	# Deleting the class an imported type alias comes from makes the PHPDoc of Base.php
	# unresolvable, and a file whose PHPDoc cannot be resolved records no dependencies at all -
	# the edge to User.php is dropped. The next edit of Base.php must still reach User.php.
	rm src/Aliases.php
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	patch -b -p0 < signature.patch
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'User.php:10:Method ResultCacheE2EImportType\User::doUser() should return int but returns string.' "$OUTPUT"
}
