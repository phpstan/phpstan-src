#!/usr/bin/env bash
# @tags result-cache

# The files of the project's own Composer autoload entries - PSR-4, PSR-0, classmap, files, and
# those of a package from a path repository - are tracked like scanned files: a class or function
# appearing in one of them re-analyses the files that looked it up, and nothing else.

function set_up() {
	e2e::set_up
	composer install --quiet
}

function tear_down() {
	e2e::tear_down
}

# writes a file: write_php <path> <namespace> <code after the namespace>
function write_php() {
	mkdir -p "$(dirname "$1")"
	if [ -n "$2" ]; then
		printf '<?php declare(strict_types = 1);\n\nnamespace %s;\n\n%s\n' "$2" "$3" > "$1"
	else
		printf '<?php declare(strict_types = 1);\n\n%s\n' "$3" > "$1"
	fi
}

function assert_files_to_analyse() {
	INFO=$(../../bin/phpstan result-cache-info --json)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	assert_equals "$1" "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
}

function assert_same_as_cold_run() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	rm -rf tmp/resultCache.php
	COLD_OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && COLD_CODE=0 || COLD_CODE=$?
	echo "$COLD_OUTPUT"
	assert_same "$COLD_CODE" "$CODE"
	assert_same "$COLD_OUTPUT" "$OUTPUT"
}

function analyse() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
}

# src/Consumer.php uses the class, src/Unrelated.php does not: write_class_consumer <class>
function write_class_consumer() {
	write_php src/Consumer.php AutoloadEntries "function consume(): int
{
	return (new \\$1())->value();
}"
	write_php src/Unrelated.php AutoloadEntries 'function unrelated(): int
{
	return 1;
}'
	analyse
	assert_same '1' "$CODE"
	assert_contains 'Consumer.php:7:Instantiated class' "$OUTPUT"
}

# the class appears, then its method body changes, then its signature does
function assert_class_appears() {
	write_php "$1" "$2" "class $3
{

	public function value(): int
	{
		return 1;
	}

}"
	assert_files_to_analyse 1
	assert_same_as_cold_run
	assert_same '0' "$CODE"

	sed -i 's/return 1;/return 2;/' "$1"
	assert_files_to_analyse 0
	assert_same_as_cold_run

	sed -i 's/): int/): string/; s/return 2;/return "x";/' "$1"
	assert_files_to_analyse 1
	assert_same_as_cold_run
	assert_contains 'Consumer.php:7:Function AutoloadEntries\consume() should return int but returns string.' "$OUTPUT"

	rm "$1"
	assert_files_to_analyse 1
	assert_same_as_cold_run
	assert_contains 'Consumer.php:7:Instantiated class' "$OUTPUT"
}

function test_class_appears_in_a_psr4_directory() {
	write_class_consumer 'AutoloadEntries\Lib\Thing'
	assert_class_appears lib/Thing.php 'AutoloadEntries\Lib' Thing
}

function test_class_appears_in_a_psr0_directory() {
	write_class_consumer 'AutoloadEntries_Legacy_Thing'
	assert_class_appears legacy/AutoloadEntries/Legacy/Thing.php '' AutoloadEntries_Legacy_Thing
}

function test_class_appears_in_a_classmap_directory() {
	write_class_consumer 'AutoloadEntries\Mapped\Thing'
	# a classmap file is found by what it declares, not by its name
	assert_class_appears classmap/whatever.php 'AutoloadEntries\Mapped' Thing
}

function test_class_appears_in_an_autoload_dev_directory() {
	write_class_consumer 'AutoloadEntries\Tests\Thing'
	assert_class_appears tests/Thing.php 'AutoloadEntries\Tests' Thing
}

function test_class_appears_in_a_path_package() {
	write_class_consumer 'PathLib\Thing'
	assert_class_appears path-lib/src/Thing.php PathLib Thing
}

function test_class_appears_under_a_psr4_namespace_mapped_to_the_project_root() {
	write_class_consumer 'AutoloadEntries\Root\Thing'
	# the tmpDir is under the project root too, but PHPStan's own files there are not scanned files
	OUTPUT=$(../../bin/phpstan analyse -vv 2>&1) || true
	assert_contains 'Result cache was not rewritten because it is unchanged.' "$OUTPUT"
	assert_class_appears Thing.php 'AutoloadEntries\Root' Thing
}

function test_class_appears_in_an_existing_psr4_file_of_another_name() {
	write_php lib/Existing.php 'AutoloadEntries\Lib' 'class Existing
{

}'
	write_class_consumer 'AutoloadEntries\Lib\Thing'
	# the edited file is read for what it declares, and Thing is looked up again - but no PSR-4 path
	# leads to it, so a cold run does not find it either
	printf '\nclass Thing\n{\n\n\tpublic function value(): int\n\t{\n\t\treturn 1;\n\t}\n\n}\n' >> lib/Existing.php
	assert_files_to_analyse 0
	assert_same_as_cold_run
	assert_contains 'Consumer.php:7:Instantiated class' "$OUTPUT"
}

# src/Consumer.php calls the function, src/Unrelated.php does not: write_function_consumer <function>
function write_function_consumer() {
	write_php src/Consumer.php AutoloadEntries "function consume(): int
{
	return \\$1();
}"
	write_php src/Unrelated.php AutoloadEntries 'function unrelated(): int
{
	return 1;
}'
	analyse
	assert_same '1' "$CODE"
	assert_contains 'Consumer.php:7:Function' "$OUTPUT"
}

# the function appears in an existing file, then its body changes, then its signature does
function assert_function_appears() {
	printf '\nfunction %s(): int\n{\n\treturn 1;\n}\n' "$2" >> "$1"
	assert_files_to_analyse 1
	assert_same_as_cold_run
	assert_same '0' "$CODE"

	sed -i 's/return 1;/return 2;/' "$1"
	assert_files_to_analyse 0
	assert_same_as_cold_run

	sed -i 's/): int/): string/; s/return 2;/return "x";/' "$1"
	assert_files_to_analyse 1
	assert_same_as_cold_run
	assert_contains 'Consumer.php:7:Function AutoloadEntries\consume() should return int but returns string.' "$OUTPUT"
}

function test_function_appears_in_a_files_entry() {
	write_function_consumer 'AutoloadEntries\helper'
	assert_function_appears helpers.php helper
}

function test_function_appears_in_a_files_entry_of_a_path_package() {
	write_function_consumer 'PathLib\helper'
	assert_function_appears path-lib/functions.php helper
}

function test_class_appears_in_a_classmap_file_excluded_from_scanning() {
	# excludePaths keeps the file out of the scanned files, and out of what PHPStan's classmap
	# locator finds too - the class stays unknown, and nothing has to be analysed again
	printf 'parameters:\n\tlevel: 8\n\ttmpDir: tmp\n\tpaths:\n\t\t- src\n\texcludePaths:\n\t\t- classmap/excluded/*\n' > phpstan.neon
	write_class_consumer 'AutoloadEntries\Mapped\Thing'
	write_php classmap/excluded/Thing.php 'AutoloadEntries\Mapped' 'class Thing
{

	public function value(): int
	{
		return 1;
	}

}'
	assert_files_to_analyse 0
	assert_same_as_cold_run
	assert_contains 'Consumer.php:7:Instantiated class' "$OUTPUT"
}
