#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

# @tag windows
function test_windows_paths_through_the_currentworkingdirectory_placeholder() {
	# paths, scanFiles and bootstrapFiles written with %currentWorkingDirectory% - the
	# three meta keys the issue reports as differing (analysedPaths, scannedFiles,
	# executedFilesHashes). Values carrying a placeholder skip the path normalisation
	# the Neon loader applies to plain relative paths.
	php ../../bin/phpstan analyse --no-ansi
	php ../../bin/phpstan analyse -vv --no-ansi 2>&1 | tee output.txt
	grep -F "Result cache restored. 0 files will be reanalysed." output.txt && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
}
