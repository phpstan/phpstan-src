<?php // lint >= 8.5

namespace ArrayFirstLast;

use function PHPStan\Testing\assertType;

/**
 * @param string[] $stringArray
 * @param non-empty-array<int, string> $nonEmptyArray
 */
function doFoo(array $stringArray, array $nonEmptyArray, $mixed): void
{
	assertType("'a'", array_first([1 => 'a', 0 => 'b', 2 => 'c']));
	assertType('string|null', array_first($stringArray));
	assertType('string', array_first($nonEmptyArray));
	assertType('mixed', array_first($mixed));

	assertType("'c'", array_last([1 => 'a', 0 => 'b', 2 => 'c']));
	assertType('string|null', array_last($stringArray));
	assertType('string', array_last($nonEmptyArray));
	assertType('mixed', array_last($mixed));
}
