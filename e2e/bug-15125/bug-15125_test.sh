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
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '0' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	# The Composer metadata must not be reported as changed either, which -vvv shows.
	OUTPUT=$(../../bin/phpstan analyse -vvv 2>&1)
	echo "$OUTPUT"
	assert_not_contains 'Composer metadata changed but no package versions changed' "$OUTPUT"
	# --autoload-file is recorded in executedFilesHashes under the path it was given with;
	# a './' segment must not keep the cache from being reused either.
	../../bin/phpstan analyse -a ./vendor/autoload.php
	INFO=$(../../bin/phpstan result-cache-info -a ./vendor/autoload.php --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '0' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
}
