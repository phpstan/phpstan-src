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
assertType('non-empty-array<string, int<1, max>>', array_count_values(returnsStringOrObjectArray()));

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
