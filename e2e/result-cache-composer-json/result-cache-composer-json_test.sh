#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_composer_json() {
	composer install
	../../bin/phpstan analyse
	# composer.json decides which PHP version the analysis targets (config.platform.php) and
	# which symbols the autoloader can reach, but its contents are hashed nowhere in the cache
	# metadata - composerLocks and composerInstalled cover composer.lock and installed.php, and
	# neither of those moves when only composer.json changes.
	patch -p0 < platform.patch
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Foo.php:10:Function str_contains not found.' "$OUTPUT"
}
