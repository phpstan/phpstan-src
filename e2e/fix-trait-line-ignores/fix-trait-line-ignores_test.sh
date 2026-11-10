#!/usr/bin/env bash

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_fix_trait_line_ignores() {
	rm -rf src && cp -r original src

	# an ignore in a trait is reported once, not again in the context of each class using it - with the result cache too
	for RUN in cold warm; do
		OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
		assert_same "1" "$CODE"
		echo "$OUTPUT"
		assert_same "5" "$(grep -c 'Ignoring all errors on a line is not allowed.' <<< "$OUTPUT")"
	done

	OUTPUT=$(../../bin/phpstan analyse --fix 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
	assert_contains '[OK] 5 errors fixed in 5 files' "$OUTPUT"
	diff -u expected src

	# the ignores with identifiers match every error the original ignores matched, in each class using the trait
	for RUN in cold warm; do
		OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
		echo "$OUTPUT"
		assert_same "0" "$CODE"
	done
}
