<?php

namespace ArrayCountValues;

use function PHPStan\Testing\assertType;

$ints = array_count_values([1, 2, 2, 3]);

assertType('non-empty-array<1|2|3, int<1, max>>', $ints);

$strings = array_count_values(['one', 'two', 'two', 'three']);

assertType('non-empty-array<\'one\'|\'three\'|\'two\', int<1, max>>', $strings);

$objects = array_count_values([new \stdClass()]);

assertType('array{}', $objects);

/**
 * @return array<int, string|object>
 */
function returnsStringOrObjectArray(): array
{

}

// Objects are ignored by array_count_values, with a warning emitted.
assertType('non-empty-array<int|string, int<1, max>>', array_count_values(returnsStringOrObjectArray()));

class StringableObject
{

	public function __toString(): string
	{
		return 'string';
	}

}

// Stringable objects are ignored by array_count_values, with a warning emitted.
$stringable = array_count_values([new StringableObject(), 'string', 1]);

assertType('non-empty-array<1|\'string\', int<1, max>>', $stringable);

// Booleans, floats and null are ignored by array_count_values even if they can be cast to array key.
$scalar = array_count_values([true, 1.0, false, 0.0, null]);

assertType('array{}', $scalar);

$intAsString = array_count_values(['1', '2', '2', '3']);

assertType("non-empty-array<1|2|3, int<1, max>>", $intAsString);

/**
 * @param array<string> $strings
 * @param array<int> $ints
 * @param array<positive-int> $positives
 * @param array<int<10, 30>> $ranges
 * @param array<string|int> $stringOrInts
 * @param array<string|bool> $stringsOrBool
 */
function strings(array $strings, array $ints, array $positives, array $ranges, array $stringOrInts, array $stringsOrBool): void
{
	// numeric-strings in array-keys are auto-casted to int, see https://3v4l.org/VQoSJQ#vnull
	assertType("non-empty-array<int|string, int<1, max>>", array_count_values($strings));

	assertType("non-empty-array<int, int<1, max>>", array_count_values($ints));
	assertType("non-empty-array<int<1, max>, int<1, max>>", array_count_values($positives));
	assertType("non-empty-array<int<10, 30>, int<1, max>>", array_count_values($ranges));

	assertType("non-empty-array<int|string, int<1, max>>", array_count_values($stringOrInts));
	assertType("non-empty-array<int|string, int<1, max>>", array_count_values($stringsOrBool));
}
