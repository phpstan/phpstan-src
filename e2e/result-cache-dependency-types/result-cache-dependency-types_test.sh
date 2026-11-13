#!/usr/bin/env bash
# @tags result-cache

# Every scenario in calls/ and declarations/ edits Foo.php, which its User.php depends on only
# through the type of something it calls or declares - it does not name the edited class
# anywhere else. Without the dependency, the stale errors of User.php are reported from the cache.
#
# calls/: Foo implements Bar before the edit, so an instanceof Bar in User.php is always true.
# declarations/: the edit renames Foo, so User.php naming it in a signature becomes an error.
#
# User.php of a scenario has no errors before the edit and depends on nothing else that is edited,
# so the scenarios do not cover for each other and can share one analysis. expected.txt is what a
# run without the result cache reports after the edits.

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

function test_calls() {
	analyse_edit calls
}

function test_declarations() {
	analyse_edit declarations
}
