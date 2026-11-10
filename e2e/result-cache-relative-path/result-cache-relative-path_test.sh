#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_cache_holds_no_absolute_paths() {
	# Cold run: paths are stored relative to the phpstan install (the anchor), so the cache
	# no longer embeds the absolute checkout path anywhere - not in the error/dependency
	# sections and not inside collected data either, which is only reachable through the
	# collector that produced it (CollectorWithPaths). Assert the whole file is free of the
	# checkout prefix rather than just the analysed file's own key.
	../../bin/phpstan analyse
	if grep -q "$(cd ../.. && pwd)/" tmp/resultCache.php; then
		echo 'cache still holds absolute paths under the checkout:'
		grep -o "$(cd ../.. && pwd)/[^'\"]*" tmp/resultCache.php | sort -u | head -20
		exit 1
	fi
	# Warm run: the relative cache re-absolutizes against the current anchor and is fully reused.
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '0' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
}

function test_optional_exclude_path_is_stored_relative() {
	# An optional excludePath must reach the cache the way a required one does. It used to
	# be absolutized whatever the loader was asked to expand, so it landed in the metadata
	# as an absolute path and a checkout at another path discarded the whole cache.
	../../bin/phpstan analyse -c phpstan-optional-path.neon
	if grep -q "$(cd ../.. && pwd)/" tmp-optional-path/resultCache.php; then
		echo 'cache still holds absolute paths under the checkout:'
		grep -o "$(cd ../.. && pwd)/[^'\"]*" tmp-optional-path/resultCache.php | sort -u | head -20
		exit 1
	fi
	INFO=$(../../bin/phpstan result-cache-info -c phpstan-optional-path.neon --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '0' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -c phpstan-optional-path.neon 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
}

function test_cache_is_reused_in_a_git_worktree() {
	MAIN="$(pwd)"
	# Warm the cache in this checkout; paths are stored relative to the phpstan install, so the
	# cache is portable to another checkout with the same layout.
	../../bin/phpstan analyse
	# A git worktree is a second checkout of the same repo at a different absolute path. Give it
	# its own phpstan install (vendor) so %rootDir% points at the worktree, and carry the warm
	# cache across. The copy stands in for cache discovery, which is a follow-up and not part of
	# this PR; a real setup would CoW-clone the checkout or share the tmpDir.
	WORKTREE="$(mktemp -d)/phpstan"
	git -C ../.. worktree add --detach "$WORKTREE" HEAD
	cp -al ../../vendor "$WORKTREE/vendor"
	cp -R tmp "$WORKTREE/e2e/result-cache-relative-path/tmp"
	rm -rf "$WORKTREE/e2e/result-cache-relative-path/tmp/cache"
	# Running in the worktree (a different absolute prefix) must re-absolutize the relative cache
	# against the worktree and reuse it, with 0 files reanalysed.
	cd "$WORKTREE/e2e/result-cache-relative-path"
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '0' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	../../bin/phpstan analyse
	# Remove the worktree and confirm the original checkout still reuses its own cache, i.e.
	# running phpstan in a different worktree in between did not disturb it.
	cd "$MAIN"
	git -C ../.. worktree remove --force "$WORKTREE"
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '0' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
}

# @tag windows
function test_windows_relative_paths() {
	php ../../bin/phpstan analyse --no-ansi
	# The cache stores forward slashes, so the checkout prefix to look for is the
	# D:/a/... spelling of the workspace.
	PREFIX="$(cygpath -m "$GITHUB_WORKSPACE")/"
	if grep -qF "$PREFIX" tmp/resultCache.php; then
		echo "cache still holds absolute paths under the checkout ($PREFIX):"
		grep -nF "$PREFIX" tmp/resultCache.php | head -20
		exit 1
	fi
	# jq.exe ends its output with CRLF, and the command substitution would keep the CR:
	# --join-output prints no line ending at all.
	INFO=$(php ../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --join-output '.resultCacheUsed')"
	assert_equals '0' "$(echo "$INFO" | jq --join-output '.filesToAnalyseCount')"
}
