#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_phar_bootstrap() {
	# A bootstrapFile living inside a phar is hashed into executedFilesHashes under its
	# phar:// URL, and PHPStan registers its own runtime stubs exactly like that inside
	# phpstan.phar. Only the path after the scheme may be relativized: dropping the scheme
	# leaves absolutizePath() unable to reconstruct the URL, so the restored key never
	# matches and the cache is discarded on every run.
	php -d phar.readonly=0 build-boot-phar.php
	../../bin/phpstan analyse
	# Every stored reference to the bootstrap file must keep its scheme. Grepping for
	# 'phar://' alone would also match the Neon-encoded projectConfig, so assert the
	# negative: no scheme-less key pointing at boot.phar/boot.php.
	if grep -oE "'[^']*boot\.phar/boot\.php'" tmp/resultCache.php | grep -v 'phar://'; then
		echo 'bootstrap path stored without its phar:// scheme'; exit 1
	fi
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '0' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	../../bin/phpstan analyse && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}

# @tag windows
function test_windows_phar_bootstrap_file() {
	# PHPStan registers its own runtime stubs as bootstrapFiles under their phar:// URL
	# in a phar install; this reproduces that without a compiled phpstan.phar.
	php -d phar.readonly=0 build-boot-phar.php
	php ../../bin/phpstan analyse --no-ansi
	if grep -oE "'[^']*boot\.phar/boot\.php'" tmp/resultCache.php | grep -v 'phar://'; then
		echo 'bootstrap path stored without its phar:// scheme'; exit 1
	fi
	INFO=$(php ../../bin/phpstan result-cache-info --json --no-ansi)
	echo "$INFO"
	# jq.exe ends its output with CRLF, and the command substitution would keep the CR:
	# --join-output prints no line ending at all.
	assert_equals 'true' "$(echo "$INFO" | jq --join-output '.resultCacheUsed')"
	assert_equals '0' "$(echo "$INFO" | jq --join-output '.filesToAnalyseCount')"
}
