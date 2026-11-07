#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_collected_data_rules_tracking() {
	composer install
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Controller.php:15:View about not found.' "$OUTPUT"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	echo "$OUTPUT" | grep -E 'Result cache reused the errors of ([0-9]+) of \1 rules on collected data\.'
	# The rule tracks the views directory, so a new view runs it again.
	printf 'about\n' > views/about.txt
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
	assert_not_contains 'View about not found.' "$OUTPUT"
	rm views/about.txt
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Controller.php:15:View about not found.' "$OUTPUT"
	# The rule tracks the file of ignored views too.
	printf 'about\n' > ignored-views.txt
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
	assert_not_contains 'View about not found.' "$OUTPUT"
	# And the class of extra views, which is not analysed.
	sed -i "s/'contact'/'nothing'/" lib/ExtraViews.php
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Controller.php:20:View contact not found.' "$OUTPUT"
	# And a method that a class reflection extension describes from a file that is not
	# analysed, tracked through DeclarationDependencyTracker. No analysed file changes.
	printf '' > more-views.txt
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Controller.php:25:View faq not found.' "$OUTPUT"
}
