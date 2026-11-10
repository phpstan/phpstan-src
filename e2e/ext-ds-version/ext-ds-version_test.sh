#!/usr/bin/env bash

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

# @tag result-cache
function test_changed_ext_ds_version_reanalyses_dependent_files() {
	cp composer-json/v1-platform.json composer.json
	composer install --ignore-platform-reqs
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Foo.php:12:Instantiated class Ds\Seq not found.' "$OUTPUT"
	# Only composer.json changes - composer.lock and installed.php stay as they are - and it now
	# selects the ext-ds 2 stubs. src/Bar.php has no error with ext-ds 1, so only its dependency on
	# ext-ds re-analyses it; src/Baz.php does not use ext-ds and stays cached.
	cp composer-json/v2-platform.json composer.json
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '2' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'PHP extension versions changed (ext-ds); re-analysing the files depending on them and the files with errors.' "$OUTPUT"
	assert_contains 'Foo.php:11:Instantiated class Ds\Vector not found.' "$OUTPUT"
	assert_contains 'Bar.php:15:Call to an undefined method Ds\Pair<string, int>::copy().' "$OUTPUT"
	assert_not_contains 'Ds\Seq' "$OUTPUT"
	cp composer-json/v1-platform.json composer.json
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals '2' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'PHP extension versions changed (ext-ds); re-analysing the files depending on them and the files with errors.' "$OUTPUT"
	assert_contains 'Foo.php:12:Instantiated class Ds\Seq not found.' "$OUTPUT"
	assert_not_contains 'Ds\Vector' "$OUTPUT"
	assert_not_contains 'Bar.php' "$OUTPUT"
}

function test_platform_selects_ext_ds_1() {
	# config.platform selects the ext-ds 1 stubs: Ds\Vector exists, Ds\Seq does not.
	cp composer-json/v1-platform.json composer.json
	composer install --ignore-platform-reqs
	OUTPUT=$(../../bin/phpstan analyse --no-progress --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Foo.php:12:Instantiated class Ds\Seq not found.' "$OUTPUT"
	assert_not_contains 'Ds\Vector' "$OUTPUT"
	assert_not_contains 'Bar.php' "$OUTPUT"
}

function test_platform_selects_ext_ds_2() {
	# config.platform selects the ext-ds 2 stubs: Ds\Seq exists, Ds\Vector and Ds\Pair::copy() do not.
	cp composer-json/v2-platform.json composer.json
	composer install --ignore-platform-reqs
	OUTPUT=$(../../bin/phpstan analyse --no-progress --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Foo.php:11:Instantiated class Ds\Vector not found.' "$OUTPUT"
	assert_contains 'Bar.php:15:Call to an undefined method Ds\Pair<string, int>::copy().' "$OUTPUT"
	assert_not_contains 'Ds\Seq' "$OUTPUT"
}

function test_required_major_version_is_selected() {
	# Without a config.platform entry, the major version of the required ext-ds is selected.
	cp composer-json/v2-require.json composer.json
	composer install --ignore-platform-reqs
	OUTPUT=$(../../bin/phpstan analyse --no-progress --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Foo.php:11:Instantiated class Ds\Vector not found.' "$OUTPUT"
	assert_contains 'Bar.php:15:Call to an undefined method Ds\Pair<string, int>::copy().' "$OUTPUT"
	assert_not_contains 'Ds\Seq' "$OUTPUT"
}

function test_alternatives_within_one_major_version_select_it() {
	# A constraint whose alternatives all stay within one major version (^2.0 || ^2.1) selects that version.
	cp composer-json/v2-same-major-or-require.json composer.json
	composer install --ignore-platform-reqs
	OUTPUT=$(../../bin/phpstan analyse --no-progress --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Foo.php:11:Instantiated class Ds\Vector not found.' "$OUTPUT"
	assert_contains 'Bar.php:15:Call to an undefined method Ds\Pair<string, int>::copy().' "$OUTPUT"
	assert_not_contains 'Ds\Seq' "$OUTPUT"
}

function test_unknown_platform_version_falls_back_to_required() {
	# config.platform names ext-ds 3, which has no stubs, so the required ^2.0 decides instead.
	cp composer-json/v2-unknown-platform.json composer.json
	composer install --ignore-platform-reqs
	OUTPUT=$(../../bin/phpstan analyse --no-progress --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'Foo.php:11:Instantiated class Ds\Vector not found.' "$OUTPUT"
	assert_contains 'Bar.php:15:Call to an undefined method Ds\Pair<string, int>::copy().' "$OUTPUT"
	assert_not_contains 'Ds\Seq' "$OUTPUT"
}
