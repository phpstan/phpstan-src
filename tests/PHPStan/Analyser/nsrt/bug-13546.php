<?php
namespace Bug13546;

use function PHPStan\Testing\assertType;

/** @param array<int> $array */
function first(array $array): void
{
	if (array_key_first($array) !== null) {
		assertType('non-empty-array<int>', $array);
	} else {
		assertType('array{}', $array);
	}
	assertType('array<int>', $array);
}

/** @param array<int> $array */
function firstReversed(array $array): void
{
	if (null !== array_key_first($array)) {
		assertType('non-empty-array<int>', $array);
	} else {
		assertType('array{}', $array);
	}
	assertType('array<int>', $array);
}

/** @param array<int> $array */
function last(array $array): void
{
	if (array_key_last($array) !== null) {
		assertType('non-empty-array<int>', $array);
	} else {
		assertType('array{}', $array);
	}
	assertType('array<int>', $array);
}
