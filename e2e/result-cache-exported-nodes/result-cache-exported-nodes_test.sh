#!/usr/bin/env bash
# @tags result-cache

# Every scenario in signatures/ and symbols/ edits one detail of what a file declares - Foo.php or
# alike - and its User.php reports something else after the edit. The exported nodes of the edited
# file have to change for the result cache to re-analyse User.php; when they do not, the stale
# errors of User.php are reported from the cache.
#
# User.php of a scenario depends on nothing else that is edited, so the scenarios do not cover for
# each other and can share one analysis. expected.txt is what a run without the result cache
# reports after the edits.
#
# The scenarios in symbols/ change the symbols a file declares, which re-analyses every file with
# errors - the scenarios in signatures/ would no longer be on their own then.

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function analyse_edit() {
	cp -R "$1/before" src
	../../bin/phpstan analyse --error-format raw || true
	cp -R "$1/after/." src
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>/dev/null | sed "s|$(pwd)/||" | sort || true)
	echo "$OUTPUT"
	assert_same "$(cat "$1/expected.txt")" "$OUTPUT"
}

function test_signature_changes() {
	analyse_edit signatures
}

function test_symbol_changes() {
	analyse_edit symbols
}
