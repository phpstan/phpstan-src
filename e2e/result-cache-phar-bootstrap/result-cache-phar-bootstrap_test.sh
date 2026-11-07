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
	OUTPUT=$(../../bin/phpstan analyse -vv 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Result cache restored. 0 files will be reanalysed.' "$OUTPUT"
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
	php ../../bin/phpstan analyse -vv --no-ansi 2>&1 | tee output.txt
	grep -F "Result cache restored. 0 files will be reanalysed." output.txt && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}
