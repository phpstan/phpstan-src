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
}

function testMbStrlen(): void
{
	$fn = mb_strlen(...);
	assertType('Closure(string, string|null=): int<0, max>', $fn);
}

function testCount(): void
{
	$fn = count(...);
	assertType('Closure(array|Countable, 0|1=): int<0, max>', $fn);
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
}

function testGettype(): void
{
	$fn = gettype(...);
	assertType('Closure(mixed): string', $fn);
}

function testGetClass(): void
{
	$fn = get_class(...);
	assertType('Closure(object=): class-string', $fn);
}

function testGetDebugType(): void
{
	$fn = get_debug_type(...);
	assertType('Closure(mixed): string', $fn);
}

function testGetParentClass(): void
{
	$fn = get_parent_class(...);
	assertType('Closure(object|string=): (class-string|false)', $fn);
}

function testTrim(): void
{
	$fn = trim(...);
	assertType('Closure(string, string=): string', $fn);
}

function testLtrim(): void
{
	$fn = ltrim(...);
	assertType('Closure(string, string=): string', $fn);
}

function testRtrim(): void
{
	$fn = rtrim(...);
	assertType('Closure(string, string=): string', $fn);
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
