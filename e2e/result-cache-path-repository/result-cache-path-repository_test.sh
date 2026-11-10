#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_path_repository() {
	composer install
	../../bin/phpstan analyse
	# Updating an installed package re-analyses only the files depending on it.
	patch -p0 < updatePsrLog.patch
	composer update psr/log --no-cache
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Composer packages changed (psr/log); re-analysing the files depending on them.' "$OUTPUT"
	# Editing a file of an installed package is not a valid thing to do - the next Composer
	# command overwrites it - so PHPStan does not track it and keeps the cached results, even
	# though this edit takes away a method the analysed code calls.
	php -r '$f = "vendor/psr/log/src/LoggerInterface.php"; file_put_contents($f, str_replace("public function info(", "public function renamedInfo(", file_get_contents($f)));'
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '0' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
	assert_not_contains 'Call to an undefined method' "$OUTPUT"
	# A package from a path repository is the opposite case: it is the project's own code,
	# edited in place without any Composer command noticing, so its files are tracked like the
	# project's other non-analysed files. Symlinked, the installed package is the source
	# directory - an edit inside a method body changes nothing the files using it can see.
	patch -b -p0 < symlinked-body.patch
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '0' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
	# A changed return type does reach them.
	mv symlinked-lib/src/SymlinkedDep.php.orig symlinked-lib/src/SymlinkedDep.php
	patch -b -p0 < symlinked-signature.patch
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'UsesSymlinked.php:16:Method ResultCacheE2EPathRepository\UsesSymlinked::doUsesSymlinked() should return int but returns string.' "$OUTPUT"
	# Undoing it clears the error again.
	mv symlinked-lib/src/SymlinkedDep.php.orig symlinked-lib/src/SymlinkedDep.php
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
	# Copied instead of symlinked, the analysis reads the installed copy, so editing the
	# directory Composer copied from is not something PHPStan can see at all.
	patch -b -p0 < copied-source-signature.patch
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '0' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
	mv copied-lib/src/CopiedDep.php.orig copied-lib/src/CopiedDep.php
	# The installed copy is tracked the same way the symlinked package is.
	patch -b -p0 < copied-body.patch
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '0' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
	mv vendor/test/copied-lib/src/CopiedDep.php.orig vendor/test/copied-lib/src/CopiedDep.php
	patch -b -p0 < copied-signature.patch
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'UsesCopied.php:16:Method ResultCacheE2EPathRepository\UsesCopied::doUsesCopied() should return int but returns string.' "$OUTPUT"
}
