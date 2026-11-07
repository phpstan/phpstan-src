#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_bug_15125() {
	composer install
	# scanFiles points at vendor/autoload.php through a parent-directory segment in the
	# middle of an absolute path (%rootDir%/../../../vendor/autoload.php in the issue), and
	# Composer records every install_path in vendor/composer/installed.php the same way.
	# Both paths go through the cache normalized while the freshly computed ones keep their
	# "..", so a second run without any change must still restore the whole cache without
	# reporting a metadata mismatch.
	../../bin/phpstan analyse
	OUTPUT=$(../../bin/phpstan analyse -vvv 2>&1)
	echo "$OUTPUT"
	assert_not_contains 'Result cache not used because the metadata do not match' "$OUTPUT"
	assert_not_contains 'Composer metadata changed but no package versions changed' "$OUTPUT"
	assert_contains 'Result cache restored. 0 files will be reanalysed.' "$OUTPUT"
	# --autoload-file is recorded in executedFilesHashes under the path it was given with;
	# a './' segment must not keep the cache from being reused either.
	../../bin/phpstan analyse -a ./vendor/autoload.php
	OUTPUT=$(../../bin/phpstan analyse -a ./vendor/autoload.php -vvv 2>&1)
	echo "$OUTPUT"
	assert_not_contains 'Result cache not used because the metadata do not match' "$OUTPUT"
	assert_contains 'Result cache restored. 0 files will be reanalysed.' "$OUTPUT"
}
