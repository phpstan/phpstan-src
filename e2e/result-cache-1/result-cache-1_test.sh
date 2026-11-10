#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_1() {
	echo -n > phpstan-baseline.neon
	../../bin/phpstan -vvv
	patch -b src/Bar.php < patch-1.patch
	cat baseline-1.neon > phpstan-baseline.neon
	../../bin/phpstan -vvv
	mv src/Bar.php.orig src/Bar.php
	echo -n > phpstan-baseline.neon
	../../bin/phpstan -vvv && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}

# @tag windows
function test_windows_plain_project() {
	echo -n > phpstan-baseline.neon
	php ../../bin/phpstan analyse --no-ansi
	INFO=$(php ../../bin/phpstan result-cache-info --json --no-ansi)
	echo "$INFO"
	# jq.exe ends its output with CRLF, and the command substitution would keep the CR:
	# --join-output prints no line ending at all.
	assert_equals 'true' "$(echo "$INFO" | jq --join-output '.resultCacheUsed')"
	assert_equals '0' "$(echo "$INFO" | jq --join-output '.filesToAnalyseCount')"
}
