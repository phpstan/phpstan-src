<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14550;

use function PHPStan\Testing\assertType;

/**
 * @param list<string> $list
 */
function crashArrayKeyFirst(array $list): void
{
	$fn = array_key_first(...);
	assertType('Closure(array): (int|string|null)', $fn);
}

/**
 * @param list<string> $list
 */
function crashArrayKeyLast(array $list): void
{
	$fn = array_key_last(...);
	assertType('Closure(array): (int|string|null)', $fn);
}

/**
 * @param list<string> $list
 */
function crashArrayRand(array $list): void
{
	$fn = array_rand(...);
	assertType('(Closure(non-empty-array): (int|string))|(Closure(non-empty-array, int<1, max>): (array<int, int|string>|int|string))', $fn);
}

/**
 * @param list<string> $list
 */
function crashArraySearch(array $list, string $s): void
{
	$fn = array_search(...);
	assertType('Closure(mixed, array, bool=): (int|string|false)', $fn);
}
