#!/usr/bin/env bash
# @tags result-cache

# Every class, function and constant looked up is recorded, found or not: whatever asked is
# re-analysed when it appears or goes away, and nothing else is.

function set_up() {
	e2e::set_up
	composer install --quiet
}

function tear_down() {
	e2e::tear_down
}

# writes a file in the namespace of the project: write_php <path> <code after the namespace>
function write_php() {
	printf '<?php declare(strict_types = 1);\n\nnamespace ResultCacheE2ESymbolLookups;\n\n%s\n' "$2" > "$1"
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

# lib/ is neither analysed nor scanned, its classes come from the Composer autoloader
function write_lib_collected_replacement() {
	mkdir -p lib
	printf '<?php declare(strict_types = 1);\n\nnamespace ResultCacheE2ESymbolLookups\\Lib;\n\nclass CollectedReplacement\n{\n\n}\n' > lib/CollectedReplacement.php
}

function write_strlen_users() {
	for name in First Second; do
		write_php "src/$name.php" "function do$name(string \$value): int
{
	return strlen(\$value);
}"
	done
}

STRLEN='function strlen(string $value): string
{
	return $value;
}'

# {{{ appearing

function test_function_shadows_a_global_function() {
	# https://github.com/phpstan/phpstan/issues/15453
	write_strlen_users
	analyse
	assert_same '0' "$CODE"
	write_php src/strlen.php "$STRLEN"
	# src/strlen.php is new, the two files calling strlen() asked for the function in the namespace
	assert_files_to_analyse 3
	assert_same_as_cold_run
	assert_contains 'Second.php:7:Function ResultCacheE2ESymbolLookups\doSecond() should return int but returns string.' "$OUTPUT"
}

function test_function_appears_in_a_scanned_file() {
	write_strlen_users
	analyse
	assert_same '0' "$CODE"
	write_php scanned/strlen.php "$STRLEN"
	# scanned/ is not analysed
	assert_files_to_analyse 2
	assert_same_as_cold_run
	assert_contains 'First.php:7:Function ResultCacheE2ESymbolLookups\doFirst() should return int but returns string.' "$OUTPUT"
}

function test_constant_shadows_a_global_constant() {
	write_php src/User.php 'function doUser(): string
{
	return PHP_EOL;
}'
	analyse
	assert_same '0' "$CODE"
	write_php src/constants.php 'const PHP_EOL = 1;'
	assert_files_to_analyse 2
	assert_same_as_cold_run
	assert_contains 'User.php:7:Function ResultCacheE2ESymbolLookups\doUser() should return string but returns int.' "$OUTPUT"
}

function test_narrowed_by_function_exists() {
	write_php src/User.php 'function doUser(): mixed
{
	if (function_exists('"'"'ResultCacheE2ESymbolLookups\helper'"'"')) {
		return \ResultCacheE2ESymbolLookups\helper(1, 2);
	}

	return 0;
}'
	analyse
	assert_same '0' "$CODE"
	write_php src/helper.php 'function helper(): string
{
	return "";
}'
	assert_files_to_analyse 2
	assert_same_as_cold_run
	assert_contains 'User.php:8:Function ResultCacheE2ESymbolLookups\helper invoked with 2 parameters, 0 required.' "$OUTPUT"
}

function test_narrowed_by_defined() {
	write_php src/User.php 'function doUser(): bool
{
	if (defined('"'"'ResultCacheE2ESymbolLookups\LIMIT'"'"')) {
		return \ResultCacheE2ESymbolLookups\LIMIT === "x";
	}

	return false;
}'
	analyse
	assert_same '0' "$CODE"
	write_php src/constants.php 'const LIMIT = 1;'
	assert_files_to_analyse 2
	assert_same_as_cold_run
	assert_contains 'User.php:8:Strict comparison using === between 1 and' "$OUTPUT"
}

function test_function_named_in_a_string() {
	write_php src/User.php 'function doUser(): ?\ReflectionFunction
{
	try {
		return new \ReflectionFunction('"'"'ResultCacheE2ESymbolLookups\helper'"'"');
	} catch (\ReflectionException $e) {
		return null;
	}
}'
	analyse
	assert_same '0' "$CODE"
	write_php src/helper.php 'function helper(): void
{
}'
	assert_files_to_analyse 2
	assert_same_as_cold_run
	assert_contains 'User.php:9:Dead catch - ReflectionException is never thrown in the try block.' "$OUTPUT"
}

function test_rule_on_collected_data_asking_for_a_class() {
	write_php src/legacy.php 'function collectedLegacyCall(): void
{
}'
	write_php src/User.php 'function doUser(): void
{
	collectedLegacyCall();
}'
	analyse
	assert_same '0' "$CODE"
	write_lib_collected_replacement
	# nothing to re-analyse - the rule runs again
	assert_files_to_analyse 0
	assert_same_as_cold_run
	assert_contains 'User.php:7:Use CollectedReplacement instead of collectedLegacyCall().' "$OUTPUT"
}

function test_missing_class_returned_from_another_file() {
	write_php src/make.php '/**
 * @return Thing
 */
function makeThing(): mixed
{
	return null;
}'
	for name in First Second; do
		write_php "src/$name.php" "function do$name(): \\Countable
{
	return makeThing();
}"
	done
	analyse
	write_php src/Thing.php 'class Thing implements \Countable
{

	public function count(): int
	{
		return 0;
	}

}'
	# src/Thing.php is new, src/make.php named it, src/First.php and src/Second.php returned it
	assert_files_to_analyse 4
	assert_same_as_cold_run
}

# }}}

# {{{ going away

function test_removed_class_named_in_a_string() {
	write_php src/Thing.php 'class Thing
{

}'
	write_php src/User.php 'function doUser(Other $other): bool
{
	return get_class($other) === '"'"'ResultCacheE2ESymbolLookups\Thing'"'"';
}'
	analyse
	assert_contains 'User.php:7:Strict comparison using === between' "$OUTPUT"
	rm src/Thing.php
	assert_files_to_analyse 1
	assert_same_as_cold_run
	assert_same '0' "$CODE"
}

function test_class_removed_from_an_existing_file() {
	printf '\nclass Thing\n{\n\n}\n' >> src/Other.php
	write_php src/User.php 'function doUser(Other $other): bool
{
	return get_class($other) === '"'"'ResultCacheE2ESymbolLookups\Thing'"'"';
}'
	analyse
	assert_contains 'User.php:7:Strict comparison using === between' "$OUTPUT"
	write_php src/Other.php 'class Other
{

}'
	# src/Other.php changed, src/User.php found the class in it
	assert_files_to_analyse 2
	assert_same_as_cold_run
	assert_same '0' "$CODE"
}

function test_removed_class_named_in_a_shared_constant() {
	write_php src/Thing.php 'class Thing
{

}'
	write_php src/Names.php 'class Names
{

	const THING = '"'"'ResultCacheE2ESymbolLookups\Thing'"'"';

}'
	for name in First Second; do
		write_php "src/$name.php" "function do$name(Other \$other): bool
{
	return get_class(\$other) === Names::THING;
}"
	done
	analyse
	assert_contains 'Second.php:7:Strict comparison using === between' "$OUTPUT"
	rm src/Thing.php
	assert_files_to_analyse 2
	assert_same_as_cold_run
	assert_same '0' "$CODE"
}

function test_rule_asking_for_a_removed_class() {
	write_php src/Replacement.php 'class Replacement
{

}'
	write_php src/legacy.php 'function legacyCall(): void
{
}'
	write_php src/User.php 'function doUser(): void
{
	legacyCall();
}'
	analyse
	assert_contains 'User.php:7:Use Replacement instead of legacyCall().' "$OUTPUT"
	rm src/Replacement.php
	assert_files_to_analyse 1
	assert_same_as_cold_run
	assert_same '0' "$CODE"
}

function test_rule_on_collected_data_asking_for_a_removed_class() {
	write_lib_collected_replacement
	write_php src/legacy.php 'function collectedLegacyCall(): void
{
}'
	write_php src/User.php 'function doUser(): void
{
	collectedLegacyCall();
}'
	analyse
	assert_contains 'User.php:7:Use CollectedReplacement instead of collectedLegacyCall().' "$OUTPUT"
	rm lib/CollectedReplacement.php
	# nothing to re-analyse - the rule runs again
	assert_files_to_analyse 0
	assert_same_as_cold_run
	assert_same '0' "$CODE"
}

function test_removed_class_in_a_phpdoc_of_another_file() {
	write_php src/Scalar.php 'class Scalar
{

}'
	# "scalar" is the class while it exists
	write_php src/scalar.php '/**
 * @param scalar $value
 */
function takeScalar(mixed $value): void
{
}'
	# the second caller reuses the PHPDoc the first one resolved
	for name in First Second; do
		write_php "src/$name.php" "function do$name(): void
{
	takeScalar(1);
}"
	done
	analyse
	assert_contains 'Second.php:7:Parameter #1 $value of function ResultCacheE2ESymbolLookups\takeScalar expects ResultCacheE2ESymbolLookups\Scalar, int given.' "$OUTPUT"
	rm src/Scalar.php
	# src/scalar.php resolved its PHPDoc to the class, src/First.php and src/Second.php called the function
	assert_files_to_analyse 3
	assert_same_as_cold_run
	assert_same '0' "$CODE"
}

function test_removed_class_in_a_phpdoc_of_a_function_not_called() {
	write_php src/Scalar.php 'class Scalar
{

}'
	write_php src/functions.php '/**
 * @param scalar $value
 */
function takeScalar(mixed $value): void
{
}

function other(): void
{
}'
	write_php src/First.php 'function doFirst(): void
{
	takeScalar(1);
}'
	# src/Second.php is analysed after the PHPDoc of takeScalar() was resolved, but does not call it
	write_php src/Second.php 'function doSecond(): void
{
	other();
}'
	analyse
	assert_contains 'First.php:7:Parameter #1 $value of function ResultCacheE2ESymbolLookups\takeScalar expects ResultCacheE2ESymbolLookups\Scalar, int given.' "$OUTPUT"
	rm src/Scalar.php
	# src/functions.php resolved its PHPDoc to the class, src/First.php called the function
	assert_files_to_analyse 2
	assert_same_as_cold_run
	assert_same '0' "$CODE"
}

function test_removed_function_named_in_a_string() {
	write_php src/helper.php 'function helper(): void
{
}'
	write_php src/User.php 'function doUser(): ?\ReflectionFunction
{
	try {
		return new \ReflectionFunction('"'"'ResultCacheE2ESymbolLookups\helper'"'"');
	} catch (\ReflectionException $e) {
		return null;
	}
}'
	analyse
	assert_contains 'Dead catch' "$OUTPUT"
	rm src/helper.php
	assert_files_to_analyse 1
	assert_same_as_cold_run
	assert_same '0' "$CODE"
}

function test_removed_shadowing_function() {
	write_php src/strlen.php "$STRLEN"
	write_strlen_users
	analyse
	assert_contains 'First.php:7:Function ResultCacheE2ESymbolLookups\doFirst() should return int but returns string.' "$OUTPUT"
	rm src/strlen.php
	assert_files_to_analyse 2
	assert_same_as_cold_run
	assert_same '0' "$CODE"
}

function test_removed_shadowing_function_in_a_scanned_file() {
	write_php scanned/strlen.php "$STRLEN"
	write_strlen_users
	analyse
	assert_contains 'First.php:7:Function ResultCacheE2ESymbolLookups\doFirst() should return int but returns string.' "$OUTPUT"
	rm scanned/strlen.php
	assert_files_to_analyse 2
	assert_same_as_cold_run
	assert_same '0' "$CODE"
}

function test_removed_constant_narrowed_by_defined() {
	write_php src/constants.php 'const LIMIT = 1;'
	write_php src/User.php 'function doUser(): bool
{
	if (defined('"'"'ResultCacheE2ESymbolLookups\LIMIT'"'"')) {
		return \ResultCacheE2ESymbolLookups\LIMIT === "x";
	}

	return false;
}'
	analyse
	assert_contains 'User.php:8:Strict comparison using === between 1 and' "$OUTPUT"
	rm src/constants.php
	assert_files_to_analyse 1
	assert_same_as_cold_run
	assert_same '0' "$CODE"
}

# }}}

# {{{ suspected gaps

function test_function_called_in_another_case() {
	# function names are case-insensitive: the lookup of Helper() must be found by helper()
	write_php src/User.php 'function doUser(): int
{
	return \ResultCacheE2ESymbolLookups\Helper();
}'
	analyse
	assert_contains 'User.php:7:Function ResultCacheE2ESymbolLookups\Helper not found.' "$OUTPUT"
	write_php src/helper.php 'function helper(): string
{
	return "";
}'
	assert_files_to_analyse 2
	assert_same_as_cold_run
	assert_contains 'User.php:7:Function ResultCacheE2ESymbolLookups\doUser() should return int but returns string.' "$OUTPUT"
}

function test_constant_namespace_in_another_case() {
	# the namespace of a constant is case-insensitive, its name is not
	write_php src/User.php 'function doUser(): string
{
	return \resultcachee2esymbollookups\LIMIT;
}'
	analyse
	assert_contains 'User.php:7:Constant resultcachee2esymbollookups\LIMIT not found.' "$OUTPUT"
	write_php src/constants.php 'const LIMIT = 1;'
	assert_files_to_analyse 2
	assert_same_as_cold_run
	assert_contains 'User.php:7:Function ResultCacheE2ESymbolLookups\doUser() should return string but returns int.' "$OUTPUT"
}

function test_constant_name_in_another_case() {
	write_php src/User.php 'function doUser(): string
{
	return \ResultCacheE2ESymbolLookups\Limit;
}'
	analyse
	assert_contains 'User.php:7:Constant ResultCacheE2ESymbolLookups\Limit not found.' "$OUTPUT"
	write_php src/constants.php 'const LIMIT = 1;'
	# Limit is still missing, only the new file is analysed
	assert_files_to_analyse 1
	assert_same_as_cold_run
	assert_contains 'User.php:7:Constant ResultCacheE2ESymbolLookups\Limit not found.' "$OUTPUT"
}

function test_ignored_error_about_a_missing_function() {
	write_php src/User.php 'function doUser(): void
{
	\ResultCacheE2ESymbolLookups\helper(); // @phpstan-ignore function.notFound
}'
	analyse
	assert_same '0' "$CODE"
	write_php src/helper.php 'function helper(): void
{
}'
	assert_files_to_analyse 2
	assert_same_as_cold_run
	assert_contains 'User.php:7:No error with identifier function.notFound is reported on line 7.' "$OUTPUT"
}

function test_function_appears_in_an_existing_scanned_file() {
	# no analysed file depends on scanned/helpers.php before the function appears in it
	write_php scanned/helpers.php 'function otherHelper(): void
{
}'
	write_php src/User.php 'function doUser(): int
{
	return \ResultCacheE2ESymbolLookups\helper();
}'
	analyse
	assert_contains 'User.php:7:Function ResultCacheE2ESymbolLookups\helper not found.' "$OUTPUT"
	write_php scanned/helpers.php 'function otherHelper(): void
{
}

function helper(): string
{
	return "";
}'
	assert_files_to_analyse 1
	assert_same_as_cold_run
	assert_contains 'User.php:7:Function ResultCacheE2ESymbolLookups\doUser() should return int but returns string.' "$OUTPUT"
}

function test_removed_trait() {
	write_php src/Greets.php 'trait Greets
{

	public function greet(): string
	{
		return "hello";
	}

}'
	write_php src/Greeter.php 'class Greeter
{

	use Greets;

}'
	write_php src/User.php 'function doUser(): string
{
	return (new Greeter())->greet();
}'
	analyse
	assert_same '0' "$CODE"
	rm src/Greets.php
	# src/Greeter.php used the trait, src/User.php called its method
	assert_files_to_analyse 2
	assert_same_as_cold_run
	assert_contains 'Greeter.php:8:Class ResultCacheE2ESymbolLookups\Greeter uses unknown trait ResultCacheE2ESymbolLookups\Greets.' "$OUTPUT"
}

function test_class_moved_to_another_file() {
	THING='class Thing
{

	public function size(): int
	{
		return 1;
	}

}'
	write_php src/Thing.php "$THING"
	write_php src/User.php 'function doUser(): int
{
	return (new Thing())->size();
}'
	analyse
	assert_same '0' "$CODE"
	rm src/Thing.php
	write_php src/Things.php "$THING"
	# src/Things.php is new, src/User.php found the class in the deleted file
	assert_files_to_analyse 2
	assert_same_as_cold_run
	assert_same '0' "$CODE"
	# the class is found in its new file now
	write_php src/Things.php "${THING/int/string}"
	assert_files_to_analyse 2
	assert_same_as_cold_run
	assert_contains 'User.php:7:Function ResultCacheE2ESymbolLookups\doUser() should return int but returns string.' "$OUTPUT"
}

function test_constant_declared_by_define() {
	write_php src/User.php 'function doUser(): string
{
	return \ResultCacheE2ESymbolLookups\LIMIT;
}'
	analyse
	assert_contains 'User.php:7:Constant ResultCacheE2ESymbolLookups\LIMIT not found.' "$OUTPUT"
	write_php src/constants.php 'define('"'"'ResultCacheE2ESymbolLookups\LIMIT'"'"', 1);'
	assert_files_to_analyse 2
	assert_same_as_cold_run
	assert_contains 'User.php:7:Function ResultCacheE2ESymbolLookups\doUser() should return string but returns int.' "$OUTPUT"
	rm src/constants.php
	assert_files_to_analyse 1
	assert_same_as_cold_run
	assert_contains 'User.php:7:Constant ResultCacheE2ESymbolLookups\LIMIT not found.' "$OUTPUT"
}

function test_function_declared_conditionally() {
	write_php src/User.php 'function doUser(): int
{
	return \ResultCacheE2ESymbolLookups\helper();
}'
	analyse
	assert_contains 'User.php:7:Function ResultCacheE2ESymbolLookups\helper not found.' "$OUTPUT"
	write_php src/helper.php 'if (!function_exists('"'"'ResultCacheE2ESymbolLookups\helper'"'"')) {
	function helper(): string
	{
		return "";
	}
}'
	assert_files_to_analyse 2
	assert_same_as_cold_run
	assert_contains 'User.php:7:Function ResultCacheE2ESymbolLookups\doUser() should return int but returns string.' "$OUTPUT"
	write_php src/helper.php 'function other(): void
{
}'
	assert_files_to_analyse 2
	assert_same_as_cold_run
	assert_contains 'User.php:7:Function ResultCacheE2ESymbolLookups\helper not found.' "$OUTPUT"
}

# }}}
