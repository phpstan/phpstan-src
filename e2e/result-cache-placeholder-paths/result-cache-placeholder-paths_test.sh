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
	# jq.exe ends its output with CRLF, and the command substitution would keep the CR:
	# --join-output prints no line ending at all.
	INFO=$(php ../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --join-output '.resultCacheUsed')"
	assert_equals '0' "$(echo "$INFO" | jq --join-output '.filesToAnalyseCount')"
}
