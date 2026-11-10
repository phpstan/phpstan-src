#!/usr/bin/env bash
# @tags result-cache

# A class looked up and not found is a dependency of whatever asked for it. These files have no
# error while the class is missing, so declaring it re-analyses them only because they asked.

function set_up() {
	e2e::set_up
	composer install --quiet
}

function tear_down() {
	e2e::tear_down
}

# writes a file in the namespace of the project: write_php <path> <code after the namespace>
function write_php() {
	printf '<?php declare(strict_types = 1);\n\nnamespace ResultCacheE2EMissingClasses;\n\n%s\n' "$2" > "$1"
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

function analyse_without_errors() {
	OUTPUT=$(../../bin/phpstan analyse --error-format raw 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	assert_same '0' "$CODE"
}

function test_narrowed_by_class_exists() {
	write_php src/User.php 'function doUser(): int
{
	if (class_exists(Thing::class)) {
		return (new Thing())->count();
	}

	return 0;
}'
	analyse_without_errors
	write_php src/Thing.php 'class Thing
{

}'
	# src/Thing.php is new, src/User.php asked for the class
	assert_files_to_analyse 2
	assert_same_as_cold_run
	assert_contains 'User.php:8:Call to an undefined method ResultCacheE2EMissingClasses\Thing::count().' "$OUTPUT"
}

function test_class_name_in_a_string() {
	write_php src/User.php 'function doUser(Other $other): bool
{
	return get_class($other) === '"'"'ResultCacheE2EMissingClasses\Thing'"'"';
}'
	analyse_without_errors
	write_php src/Thing.php 'class Thing
{

}'
	assert_files_to_analyse 2
	assert_same_as_cold_run
	assert_contains 'User.php:7:Strict comparison using === between' "$OUTPUT"
}

function test_rule_asking_for_a_class() {
	write_php src/legacy.php 'function legacyCall(): void
{
}'
	write_php src/User.php 'function doUser(): void
{
	legacyCall();
}'
	analyse_without_errors
	write_php src/Replacement.php 'class Replacement
{

}'
	# src/legacy.php declares nothing the rule asked about
	assert_files_to_analyse 2
	assert_same_as_cold_run
	assert_contains 'User.php:7:Use Replacement instead of legacyCall().' "$OUTPUT"
}

function test_missing_parent_of_a_used_class() {
	# src/Child.php reports its missing parent, src/User.php only uses Child
	write_php src/Child.php 'class Child extends Base
{

}'
	write_php src/User.php 'function doUser(): Child
{
	return new Child();
}'
	../../bin/phpstan analyse > /dev/null 2>&1 || true
	write_php src/Base.php 'class Base
{

	public function __construct(int $value)
	{
	}

}'
	# src/Base.php is new, src/Child.php named it, src/User.php used what Child declares
	assert_files_to_analyse 3
	assert_same_as_cold_run
	assert_contains 'User.php:7:Class ResultCacheE2EMissingClasses\Child constructor invoked with 0 parameters, 1 required.' "$OUTPUT"
}

function test_missing_trait_of_a_used_class() {
	write_php src/Child.php 'class Child
{

	use Base;

}'
	write_php src/User.php 'function doUser(): Child
{
	return new Child();
}'
	../../bin/phpstan analyse > /dev/null 2>&1 || true
	write_php src/Base.php 'trait Base
{

	public function __construct(int $value)
	{
	}

}'
	assert_files_to_analyse 3
	assert_same_as_cold_run
	assert_contains 'User.php:7:Class ResultCacheE2EMissingClasses\Child constructor invoked with 0 parameters, 1 required.' "$OUTPUT"
}

function test_phpdoc_type_becomes_a_class() {
	# "scalar" is a PHPDoc type until a class of the name exists in the namespace
	write_php src/scalar.php '/**
 * @param scalar $value
 */
function takeScalar(mixed $value): void
{
}'
	write_php src/User.php 'function doUser(): void
{
	takeScalar(1);
}'
	analyse_without_errors
	write_php src/Scalar.php 'class Scalar
{

}'
	# src/scalar.php resolved its PHPDoc, src/User.php called the function
	assert_files_to_analyse 3
	assert_same_as_cold_run
	assert_contains 'User.php:7:Parameter #1 $value of function ResultCacheE2EMissingClasses\takeScalar expects ResultCacheE2EMissingClasses\Scalar, int given.' "$OUTPUT"
}

function test_class_appears_in_an_autoloaded_directory() {
	# lib/ is neither analysed nor scanned, its classes come from the Composer autoloader
	write_php src/User.php 'function doUser(): object
{
	return new Lib\Thing();
}'
	../../bin/phpstan analyse > /dev/null 2>&1 || true
	printf '<?php declare(strict_types = 1);\n\nnamespace ResultCacheE2EMissingClasses\\Lib;\n\nclass Thing\n{\n\n}\n' > lib/Thing.php
	assert_files_to_analyse 1
	assert_same_as_cold_run
	assert_not_contains 'not found' "$OUTPUT"
}

function test_unrelated_error_is_not_analysed_again() {
	# src/Broken.php has an error, but it never asked for the class
	write_php src/Broken.php 'function broken(): int
{
	return "x";
}'
	write_php src/User.php 'function doUser(): object
{
	return new Thing();
}'
	../../bin/phpstan analyse > /dev/null 2>&1 || true
	write_php src/Thing.php 'class Thing
{

}'
	# src/Thing.php is new, src/User.php asked for the class
	assert_files_to_analyse 2
	assert_same_as_cold_run
	assert_contains 'Broken.php:7:Function ResultCacheE2EMissingClasses\broken() should return int but returns string.' "$OUTPUT"
}

function test_same_lookup_in_several_files() {
	# only the first file to ask finds out, the others get the remembered answer
	for name in First Second Third; do
		write_php "src/$name.php" "function do$name(Other \$other): bool
{
	return get_class(\$other) === 'ResultCacheE2EMissingClasses\\Thing';
}"
	done
	analyse_without_errors
	write_php src/Thing.php 'class Thing
{

}'
	assert_files_to_analyse 4
	assert_same_as_cold_run
	assert_contains 'First.php:7:Strict comparison using === between' "$OUTPUT"
	assert_contains 'Third.php:7:Strict comparison using === between' "$OUTPUT"
}

function test_missing_mixin() {
	write_php src/Child.php '/**
 * @mixin Helper
 */
class Child
{

}'
	write_php src/User.php 'function doUser(Child $child): void
{
	$child->help();
}'
	../../bin/phpstan analyse > /dev/null 2>&1 || true
	write_php src/Helper.php 'class Helper
{

	public function help(int $value): void
	{
	}

}'
	# src/Helper.php is new, src/Child.php named it, src/User.php called a method it adds
	assert_files_to_analyse 3
	assert_same_as_cold_run
	assert_contains 'User.php:7:Method ResultCacheE2EMissingClasses\Helper::help() invoked with 0 parameters, 1 required.' "$OUTPUT"
}

function test_class_constant_initializer() {
	write_php src/Config.php 'class Config
{

	const LIMIT = Limits::MAX;

}'
	write_php src/User.php 'function doUser(): int
{
	return Config::LIMIT;
}'
	# the type of the constant is remembered, only the first of the two files resolves it
	write_php src/UserToo.php 'function doUserToo(): int
{
	return Config::LIMIT;
}'
	../../bin/phpstan analyse > /dev/null 2>&1 || true
	write_php src/Limits.php 'class Limits
{

	const MAX = "many";

}'
	# src/Limits.php is new, src/Config.php named it, src/User.php and src/UserToo.php read the constant
	assert_files_to_analyse 4
	assert_same_as_cold_run
	assert_contains "User.php:7:Function ResultCacheE2EMissingClasses\\doUser() should return int but returns string." "$OUTPUT"
	assert_contains "UserToo.php:7:Function ResultCacheE2EMissingClasses\\doUserToo() should return int but returns string." "$OUTPUT"
}

function test_class_name_in_a_shared_constant() {
	# the type of the constant is resolved once, both files compare against it
	write_php src/Names.php 'class Names
{

	const THING = '"'"'ResultCacheE2EMissingClasses\Thing'"'"';

}'
	for name in First Second; do
		write_php "src/$name.php" "function do$name(Other \$other): bool
{
	return get_class(\$other) === Names::THING;
}"
	done
	analyse_without_errors
	write_php src/Thing.php 'class Thing
{

}'
	# src/Thing.php is new, src/First.php and src/Second.php asked for the class
	assert_files_to_analyse 3
	assert_same_as_cold_run
	assert_contains 'Second.php:7:Strict comparison using === between' "$OUTPUT"
}

function test_enum_case_value() {
	write_php src/Suit.php 'enum Suit: string
{

	case Hearts = Values::HEARTS;

}'
	for name in First Second; do
		write_php "src/$name.php" "function do$name(): int
{
	return strlen(Suit::Hearts->value);
}"
	done
	../../bin/phpstan analyse > /dev/null 2>&1 || true
	write_php src/Values.php 'class Values
{

	const HEARTS = "h";

}'
	# src/Values.php is new, src/Suit.php named it, src/First.php and src/Second.php read the case
	assert_files_to_analyse 4
	assert_same_as_cold_run
}

function test_parameter_default_value() {
	write_php src/pick.php '/**
 * @return ($flag is true ? int : string)
 */
function pick(bool $flag = Flags::ON): int|string
{
	return $flag ? 1 : "a";
}'
	for name in First Second; do
		write_php "src/$name.php" "function do$name(): int
{
	return pick();
}"
	done
	../../bin/phpstan analyse > /dev/null 2>&1 || true
	write_php src/Flags.php 'class Flags
{

	const ON = false;

}'
	# src/Flags.php is new, src/pick.php named it, src/First.php and src/Second.php called pick()
	assert_files_to_analyse 4
	assert_same_as_cold_run
	assert_contains 'Second.php:7:Function ResultCacheE2EMissingClasses\doSecond() should return int but returns string.' "$OUTPUT"
}

function test_global_type_alias_to_a_missing_class() {
	# the global type alias is resolved once for the whole analysis
	for name in First Second; do
		write_php "src/$name.php" "/**
 * @return Amount
 */
function make$name(): mixed
{
	return 1;
}"
		write_php "src/Use$name.php" "function use$name(): int
{
	return make$name()->value;
}"
	done
	../../bin/phpstan analyse -c type-aliases.neon > /dev/null 2>&1 || true
	write_php src/Money.php 'class Money
{

	public string $value = "";

}'
	INFO=$(../../bin/phpstan result-cache-info --json -c type-aliases.neon)
	echo "$INFO"
	assert_equals 'true' "$(echo "$INFO" | jq --raw-output '.resultCacheUsed')"
	# src/Money.php is new, the two files with the PHPDoc and the two using the functions
	assert_equals '5' "$(echo "$INFO" | jq --raw-output '.filesToAnalyseCount')"
	OUTPUT=$(../../bin/phpstan analyse -c type-aliases.neon --error-format raw 2>&1) && CODE=0 || CODE=$?
	echo "$OUTPUT"
	rm -rf tmp/resultCache.php
	COLD_OUTPUT=$(../../bin/phpstan analyse -c type-aliases.neon --error-format raw 2>&1) && COLD_CODE=0 || COLD_CODE=$?
	echo "$COLD_OUTPUT"
	assert_same "$COLD_CODE" "$CODE"
	assert_same "$COLD_OUTPUT" "$OUTPUT"
	assert_contains 'UseSecond.php:7:Function ResultCacheE2EMissingClasses\useSecond() should return int but returns string.' "$OUTPUT"
}
