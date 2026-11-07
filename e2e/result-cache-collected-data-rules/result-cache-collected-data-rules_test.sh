#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_collected_data_rules() {
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Caller.php:10:Call to function ResultCacheCollectedDataRules\pure() on a separate line has no effect.' "$OUTPUT"
	# Nothing changed, so every rule on CollectedDataNode reuses its errors from the result cache.
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Caller.php:10:Call to function ResultCacheCollectedDataRules\pure() on a separate line has no effect.' "$OUTPUT"
	echo "$OUTPUT" | grep -E 'Result cache reused the errors of ([0-9]+) of \1 rules on collected data\.'
	assert_contains 'Result cache was not rewritten because it is unchanged.' "$OUTPUT"
	# With -vvv the diagnose extensions print what the rules filled while they ran, so every rule runs.
	OUTPUT=$(../../bin/phpstan analyse -vvv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Caller.php:10:Call to function ResultCacheCollectedDataRules\pure() on a separate line has no effect.' "$OUTPUT"
	assert_not_contains 'Result cache reused the errors' "$OUTPUT"
	# src/Other.php is analysed again, but what it collects stays the same.
	echo '// changed' >> src/Other.php
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Result cache restored. 1 file will be reanalysed.' "$OUTPUT"
	assert_contains 'Caller.php:10:Call to function ResultCacheCollectedDataRules\pure() on a separate line has no effect.' "$OUTPUT"
	echo "$OUTPUT" | grep -E 'Result cache reused the errors of ([0-9]+) of \1 rules on collected data\.'
	# Removing the call changes what src/Caller.php collects, so the rule runs again and the error goes away.
	sed -i 's/^\t\tpure();$//' src/Caller.php
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "0" "$CODE"
	echo "$OUTPUT"
	assert_not_contains 'on a separate line has no effect' "$OUTPUT"
}
