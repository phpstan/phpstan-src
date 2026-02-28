<?php declare(strict_types = 1);

namespace Bug13217;

use function PHPStan\Testing\assertType;

// When array_map is called with multiple arrays of different constant lengths,
// PHP pads the shorter arrays with null values.
// So callback parameters should be nullable when arrays have known different sizes.

function differentLengths(): void
{
	array_map(function ($a, $b) {
		assertType('1|2|null', $a);
		assertType('3|null', $b);
	}, [1, 2], [3]);
}

function sameLengths(): void
{
	array_map(function ($a, $b) {
		assertType('1|2', $a);
		assertType('3|4', $b);
	}, [1, 2], [3, 4]);
}

function unknownLengths(array $a, array $b): void
{
	array_map(function ($a, $b) {
		assertType('mixed', $a);
		assertType('mixed', $b);
	}, $a, $b);
}

function arrowFunctionDifferentLengths(): void
{
	$result = array_map(fn ($_, $bValue) => $bValue ?? 1, [1, 2], [3]);
	assertType('non-empty-list<1|3>', $result);
}

function sameArrayVariable(array $a): void
{
	array_map(function ($x, $y) {
		assertType('mixed', $x);
		assertType('mixed', $y);
	}, $a, $a);
}

function singleArray(): void
{
	array_map(function ($a) {
		assertType('1|2|3', $a);
	}, [1, 2, 3]);
}
