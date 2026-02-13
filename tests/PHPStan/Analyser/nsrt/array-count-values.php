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

class HelloWorld
{
	/** @param array{0: '1.2'|'a'} $arr */
	public function sayHello(array $arr): void
	{
		assertType("non-empty-array<'1.2'|'a', int<1, max>>", array_count_values($arr));
	}

	/** @param array{0: '1'|'a'} $arr */
	public function sayHello2(array $arr): void
	{
		assertType("non-empty-array<1|'a', int<1, max>>", array_count_values($arr));
	}
}
