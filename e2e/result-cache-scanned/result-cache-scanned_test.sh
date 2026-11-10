#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_scanned() {
	../../bin/phpstan
	# A file that is scanned but not analysed (excluded from the analysis, but living in an
	# analysed directory) is tracked as a file dependency: editing it re-analyses just the
	# files depending on it instead of invalidating the whole result cache.
	patch -b src/Generated/Foo.php < patch.patch
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '1' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Scanned files with changed symbols (1); re-analysing the files affected by them.' "$OUTPUT"
	assert_contains 'Instantiated class ResultCacheE2EGenerated\Foo not found.' "$OUTPUT"
}

# @tag windows
function test_windows_excluded_files_hashed_as_scannedfiles() {
	php ../../bin/phpstan analyse --no-ansi
	INFO=$(php ../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	# jq.exe ends its output with CRLF, and the command substitution would keep the CR:
	# --join-output prints no line ending at all.
	assert_equals 'true' "$(echo "$INFO" | jq --join-output '.resultCacheUsed')"
	assert_equals '0' "$(echo "$INFO" | jq --join-output '.filesToAnalyseCount')"
}
