#!/usr/bin/env bash
# Loaded by bashunit before the E2E tests, see BASHUNIT_BOOTSTRAP in .bashunitrc.
# Run the tests from the repository root: e2e/bashunit test e2e

# A test runs in the directory of its test file, which it is free to change: the
# directory is put back the way it was once the test ends, so the next test using
# it - or the next run - starts from what is in the repository. The test also gets
# a TMPDIR of its own, as PHPStan keeps its default tmpDir - and with it the result
# cache - in sys_get_temp_dir().
function e2e::set_up() {
	E2E_TEST_DIR=$(cd "$(dirname "${BASH_SOURCE[1]}")" && pwd)
	E2E_TMPDIR=$(mktemp -d)
	cp -a "$E2E_TEST_DIR/." "$E2E_TMPDIR/snapshot"
	mkdir "$E2E_TMPDIR/tmp"
	export TMPDIR="$E2E_TMPDIR/tmp"
	cd "$E2E_TEST_DIR"
}

function e2e::tear_down() {
	find "${E2E_TEST_DIR:?}" -mindepth 1 -maxdepth 1 -exec rm -rf {} +
	cp -a "${E2E_TMPDIR:?}/snapshot/." "$E2E_TEST_DIR"
	rm -rf "$E2E_TMPDIR"
}
