#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_empty_file_gains_symbol() {
	# src/script.php declares no symbol, so the cache holds no exported nodes entry for it at
	# all, and src/user.php uses a class that does not exist yet.
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'user.php:7:Instantiated class ResultCacheE2EEmptyFileGainsSymbol\Thing not found.' "$OUTPUT"
	# That file now declares a class. src/user.php recorded it as missing, so it is re-analysed
	# no matter whether the file declaring it was known to the cache before.
	mv gains/script.php src/script.php
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
	assert_not_contains 'Instantiated class ResultCacheE2EEmptyFileGainsSymbol\Thing not found.' "$OUTPUT"
}
