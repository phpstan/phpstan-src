<?php

declare(strict_types = 1);

namespace Bug15248;

use function PHPStan\Testing\assertType;

function keylessItemAfterMaxIntKey(): void
{
	$a = [9223372036854775807 => 1, 2];
	assertType('array{9223372036854775807: 1}', $a);

	$b = [9223372036854775806 => 1, 2];
	assertType('array{9223372036854775806: 1, 9223372036854775807: 2}', $b);
}

function byRefItemAfterMaxIntKey(int $x): void
{
	$a = [9223372036854775807 => 1, &$x];
	assertType('array{9223372036854775807: 1}', $a);

	$b = [9223372036854775806 => 1, &$x];
	assertType('array{9223372036854775806: 1, 9223372036854775807: int}', $b);
	$x = 5;
	assertType('array{9223372036854775806: 1, 9223372036854775807: 5}', $b);
}

/**
 * @param list<int> $list
 */
function byRefItemAfterUnpackedList(array $list, int $x): void
{
	$a = [...$list, &$x];
	$x = 5;
	assertType('non-empty-list<int>', $a);
}

function byRefItemAfterUnpackedConstantArray(int $x): void
{
	$constant = [10, 20];
	$a = [...$constant, &$x];
	$x = 7;
	assertType('array{10, 20, 7}', $a);
}

function byRefItemsInsideAndAfterUnpackedArrayLiteral(int $x, int $y): void
{
	$a = [...[&$x], &$y];
	$x = 10;
	assertType('array{10, int}', $a);
	$y = 20;
	assertType('array{10, 20}', $a);
}

/**
 * @param array<9223372036854775806|9223372036854775807, string> $maxKeys
 */
function appendToArrayWithMaxIntKeyType(array $maxKeys): void
{
	$maxKeys[] = 'x';
	assertType('non-empty-array<9223372036854775806|9223372036854775807, string>', $maxKeys);
}

function arrayFillPastMaxInt(): void
{
	assertType("array{9223372036854775807: 'a'}", array_fill(9223372036854775807, 1, 'a'));
	assertType("array{9223372036854775806: 'a', 9223372036854775807: 'a'}", array_fill(9223372036854775806, 2, 'a'));
	assertType("non-empty-array<int, 'a'>", array_fill(9223372036854775807, 2, 'a'));
	assertType("non-empty-array<int, 'a'>", array_fill(9223372036854775806, 3, 'a'));
}
