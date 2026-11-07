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
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Scanned files with changed symbols (1); re-analysing the files affected by them.' "$OUTPUT"
	assert_contains 'Result cache restored. 1 file will be reanalysed.' "$OUTPUT"
	assert_contains 'Instantiated class ResultCacheE2EGenerated\Foo not found.' "$OUTPUT"
}

# @tag windows
function test_windows_excluded_files_hashed_as_scannedfiles() {
	php ../../bin/phpstan analyse --no-ansi
	php ../../bin/phpstan analyse -vv --no-ansi 2>&1 | tee output.txt
	grep -F "Result cache restored. 0 files will be reanalysed." output.txt && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}
