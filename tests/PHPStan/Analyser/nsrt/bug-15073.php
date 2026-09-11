<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15073;

use function PHPStan\Testing\assertType;

/**
 * @param array-key $array_key
 * @param numeric-string $numeric_string
 * @param decimal-int-string $decimal_string
 * @param non-decimal-int-string $non_decimal_string
 */
function foreachKey(int|string $int_or_str, int|string $array_key, string $string, string $numeric_string, string $decimal_string, string $non_decimal_string): void
{
	// `foreach` hands the key back unwidened - see the class-level doc of
	// UnsafeArrayStringKeyCastingTraverser, and reportUnsafeArrayStringKeyCasting
	// for the accurate variant.
	foreach ([$int_or_str => $int_or_str] as $key => $value) {
		assertType('array{int|string, int|string}', [$key, $value]);
	}
	foreach ([$array_key => $array_key] as $key => $value) {
		assertType('array{(int|string), (int|string)}', [$key, $value]);
	}
	foreach ([$string => $string] as $key => $value) {
		assertType('array{string, string}', [$key, $value]);
	}
	foreach ([$numeric_string => $numeric_string] as $key => $value) {
		assertType('array{int|numeric-string, numeric-string}', [$key, $value]);
	}
	foreach ([$decimal_string => $decimal_string] as $key => $value) {
		assertType('array{int, decimal-int-string}', [$key, $value]);
	}
	foreach ([$non_decimal_string => $non_decimal_string] as $key => $value) {
		assertType('array{non-decimal-int-string, non-decimal-int-string}', [$key, $value]);
	}
}

/**
 * @param array-key $array_key
 * @param numeric-string $numeric_string
 * @param decimal-int-string $decimal_string
 * @param non-decimal-int-string $non_decimal_string
 */
function readKeys(int|string $int_or_str, int|string $array_key, string $string, string $numeric_string, string $decimal_string, string $non_decimal_string): void
{
	assertType('int|string', array_key_first([$int_or_str => null]));
	assertType('(int|string)', array_key_first([$array_key => null]));
	assertType('(int|string)', array_key_first([$string => null]));
	assertType('int|numeric-string', array_key_first([$numeric_string => null]));
	assertType('int', array_key_first([$decimal_string => null]));
	assertType('non-decimal-int-string', array_key_first([$non_decimal_string => null]));

	assertType('(int|string)', array_key_last([$string => null]));
	assertType('non-empty-list<(int|string)>', array_keys([$string => null]));
	assertType('(int|string)', array_keys([$string => null])[0]);
}

/**
 * @param array-key $array_key
 * @param numeric-string $numeric_string
 * @param decimal-int-string $decimal_string
 * @param non-decimal-int-string $non_decimal_string
 */
function valuesBecomeKeys(int|string $int_or_str, int|string $array_key, string $string, string $numeric_string, string $decimal_string, string $non_decimal_string): void
{
	assertType('non-empty-array<int|string, 0|1>', array_flip([$int_or_str, $int_or_str]));
	assertType('non-empty-array<0|1>', array_flip([$array_key, $array_key]));
	assertType('non-empty-array<string, 0|1>', array_flip([$string, $string]));
	assertType('non-empty-array<int|numeric-string, 0|1>', array_flip([$numeric_string, $numeric_string]));
	assertType('non-empty-array<int, 0|1>', array_flip([$decimal_string, $decimal_string]));
	assertType('non-empty-array<non-decimal-int-string, 0|1>', array_flip([$non_decimal_string, $non_decimal_string]));

	assertType('non-empty-array<int|string, null>', array_fill_keys([$int_or_str, $int_or_str], null));
	assertType('non-empty-array<null>', array_fill_keys([$array_key, $array_key], null));
	assertType('non-empty-array<string, null>', array_fill_keys([$string, $string], null));
	assertType('non-empty-array<int|numeric-string, null>', array_fill_keys([$numeric_string, $numeric_string], null));
	assertType('non-empty-array<int, null>', array_fill_keys([$decimal_string, $decimal_string], null));
	assertType('non-empty-array<non-decimal-int-string, null>', array_fill_keys([$non_decimal_string, $non_decimal_string], null));
}

/**
 * @param list<int|string> $int_or_strs
 * @param list<array-key> $array_keys
 * @param list<string> $strings
 * @param list<numeric-string> $numeric_strings
 * @param list<decimal-int-string> $decimal_strings
 * @param list<non-decimal-int-string> $non_decimal_strings
 */
function valuesBecomeKeysOfGeneralArray(array $int_or_strs, array $array_keys, array $strings, array $numeric_strings, array $decimal_strings, array $non_decimal_strings): void
{
	assertType('array<int|string, int<0, max>>', array_flip($int_or_strs));
	assertType('array<int<0, max>>', array_flip($array_keys));
	assertType('array<string, int<0, max>>', array_flip($strings));
	assertType('array<int|numeric-string, int<0, max>>', array_flip($numeric_strings));
	assertType('array<int, int<0, max>>', array_flip($decimal_strings));
	assertType('array<non-decimal-int-string, int<0, max>>', array_flip($non_decimal_strings));

	// array_fill_keys() goes through the same key cast as array_flip() above
	assertType('array<int|string, null>', array_fill_keys($int_or_strs, null));
	assertType('array<null>', array_fill_keys($array_keys, null));
	assertType('array<string, null>', array_fill_keys($strings, null));
	assertType('array<int|numeric-string, null>', array_fill_keys($numeric_strings, null));
	assertType('array<int, null>', array_fill_keys($decimal_strings, null));
	assertType('array<non-decimal-int-string, null>', array_fill_keys($non_decimal_strings, null));
}

/**
 * @param array<string, \stdClass> $a
 * @param array<non-decimal-int-string, \stdClass> $b
 */
function iterateGeneralArray(array $a, array $b): void
{
	foreach ($a as $key => $_) {
		assertType('string', $key);
	}
	foreach ($b as $key => $_) {
		assertType('non-decimal-int-string', $key);
	}

	// The widened key stays benevolent through the `null` that array_key_first()
	// adds for a possibly-empty array, so neither branch reports anything.
	assertType('(int|string|null)', array_key_first($a));
	assertType('non-decimal-int-string|null', array_key_first($b));

	$key = array_key_first($a);
	if ($key !== null) {
		assertType('(int|string)', $key);
	}
}

/**
 * @template T of array-key
 * @param array<T, mixed> $templateKeys
 * @param array<int<0, max>|string, int> $partlyInt
 */
function keyTypesThatStopShortOfTheWidening(array $templateKeys, array $partlyInt): void
{
	// a template key is not certainly a string, so it stays as it is
	assertType('list<T of (int|string) (function Bug15073\\keyTypesThatStopShortOfTheWidening(), argument)>', array_keys($templateKeys));

	// int<0, max>|string covers only part of int, so the string half still widens
	assertType('list<(int|string)>', array_keys($partlyInt));
}

function isDecimalIntString(mixed $val): bool
{
	if (!is_string($val)) {
		return false;
	}

	assertType('(int|string)', array_key_first([$val => null]));

	return is_int(array_key_first([$val => null]));
}
