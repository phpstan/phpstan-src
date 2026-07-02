<?php

namespace Bug7280Unsealed;

use function PHPStan\Testing\assertType;

/**
 * @param list<int> $integers
 */
function listAppend(array $integers): void
{
	// the carry grows by one element per item - no array shape is a fixed point
	$result = array_reduce($integers, function ($carry, $value) {
		$carry[] = $value;

		return $carry;
	}, []);
	assertType('mixed', $result);

	$typed = array_reduce($integers, function (array $carry, int $value): array {
		assertType('array', $carry);
		$carry[] = $value;

		return $carry;
	}, []);
	assertType('array', $typed);
}

/**
 * @param list<string> $strings
 */
function doubleAppend(array $strings): void
{
	$result = array_reduce($strings, function (array $carry, string $value): array {
		$carry[] = $value;
		$carry[] = $value;

		return $carry;
	}, []);
	assertType('array', $result);
}
