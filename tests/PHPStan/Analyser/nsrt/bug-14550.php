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

function testStrlen(): void
{
	$fn = strlen(...);
	assertType('Closure(string): int<0, max>', $fn);

	if (strlen(...) < 1) {}
	if (0 < strlen(...)) {}
}

function testMbStrlen(): void
{
	$fn = mb_strlen(...);
	assertType('Closure(string, string|null=): int<0, max>', $fn);

	if (mb_strlen(...) < 1) {}
	if (0 < mb_strlen(...)) {}
}

function testCount(): void
{
	$fn = count(...);
	assertType('Closure(array|Countable, 0|1=): int<0, max>', $fn);

	if (count(...) < 1) {}
	if (0 < count(...)) {}
	if (count(...) === 0) {}
}

function testSizeof(): void
{
	$fn = sizeof(...);
	assertType('Closure(array|Countable, int=): int', $fn);
}

function testPregMatch(): void
{
	$fn = preg_match(...);
	assertType('Closure(string, string, array<string>|null=, TFlags=, int=): (0|1|false)', $fn);

	if (preg_match(...) < 1) {}
	if (1 <= preg_match(...)) {}
}

function testGettype(): void
{
	$fn = gettype(...);
	assertType('Closure(mixed): string', $fn);

	if (gettype(...) === 'string') {}
	if (gettype(...) == 'string') {}
}

function testGetClass(): void
{
	$fn = get_class(...);
	assertType('Closure(object=): class-string', $fn);

	if (get_class(...) == 'stdClass') {}
}

function testGetDebugType(): void
{
	$fn = get_debug_type(...);
	assertType('Closure(mixed): string', $fn);

	if (get_debug_type(...) == 'string') {}
}

function testGetParentClass(): void
{
	$fn = get_parent_class(...);
	assertType('Closure(object|string=): (class-string|false)', $fn);

	if (get_parent_class(...) === 'stdClass') {}
}

function testTrim(): void
{
	$fn = trim(...);
	assertType('Closure(string, string=): string', $fn);

	if (trim(...) !== '') {}
}

function testLtrim(): void
{
	$fn = ltrim(...);
	assertType('Closure(string, string=): string', $fn);

	if (ltrim(...) !== '') {}
}

function testRtrim(): void
{
	$fn = rtrim(...);
	assertType('Closure(string, string=): string', $fn);

	if (rtrim(...) !== '') {}
}

/**
 * @param list<string> $list
 */
function testArrayKeysInForeach(array $list): void
{
	foreach (array_keys(...) as $key) {
	}
	$fn = array_keys(...);
	assertType('Closure(array, mixed=, bool=): list<int|string>', $fn);
}

/**
 * @param list<string> $list
 */
function testCountInForLoop(array $list): void
{
	for ($i = 0; $i < count(...); $i++) {
	}
}
