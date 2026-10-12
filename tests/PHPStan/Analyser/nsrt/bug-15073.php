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
function readKeys(int|string $int_or_str, int|string $array_key, string $string, string $numeric_string, string $decimal_string, string $non_decimal_string): void
{
	assertType('int|string', array_key_first([$int_or_str => null]));
	assertType('(int|string)', array_key_first([$array_key => null]));
	assertType('(int|string)', array_key_first([$string => null]));
	assertType('int|numeric-string', array_key_first([$numeric_string => null]));
	assertType('int', array_key_first([$decimal_string => null]));
	assertType('non-decimal-int-string', array_key_first([$non_decimal_string => null]));

	assertType('(int|string)', array_key_last([$string => null]));
}

/**
 * @param non-empty-array<int|string, int> $intOrString
 * @param non-empty-array<int<0, max>|string, int> $partlyInt
 */
function keyTypesThatStopShortOfTheWidening(array $intOrString, array $partlyInt): void
{
	// int|string already covers int, so it is left alone and stays strict
	assertType('int|string', array_key_first($intOrString));

	// int<0, max>|string covers only part of int, so the string half still widens
	assertType('(int|string)', array_key_first($partlyInt));
}

function isDecimalIntString(mixed $val): bool
{
	if (!is_string($val)) {
		return false;
	}

	assertType('(int|string)', array_key_first([$val => null]));

	return is_int(array_key_first([$val => null]));
}
