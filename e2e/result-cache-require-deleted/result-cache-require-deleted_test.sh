#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_require_deleted() {
	../../bin/phpstan analyse
	# The file named in require() is not analysed, not scanned and not autoloadable, so no hash
	# of it exists anywhere in the cache. Deleting it has to be noticed all the same: the rule
	# that checks the path reports an error the moment it is gone.
	rm config/app.php
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Foo.php:10:Path in require() "config/app.php" is not a file or it does not exist.' "$OUTPUT"
	# And putting it back has to clear the error again.
	printf '<?php declare(strict_types = 1);\n\nreturn [];\n' > config/app.php
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
	assert_not_contains 'is not a file or it does not exist' "$OUTPUT"
}
