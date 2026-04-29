<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14550;

use function PHPStan\Testing\assertType;

// Standalone assignments trigger TypeSpecifier via NodeScopeResolver null-context call
function testArrayKeyFirstAssign(): void
{
	$fn = array_key_first(...);
	assertType('Closure(array): (int|string|null)', $fn);
}

function testArrayKeyLastAssign(): void
{
	$fn = array_key_last(...);
	assertType('Closure(array): (int|string|null)', $fn);
}

function testArrayRandAssign(): void
{
	$fn = array_rand(...);
	assertType('(Closure(non-empty-array): (int|string))|(Closure(non-empty-array, int<1, max>): (array<int, int|string>|int|string))', $fn);
}

function testCountMinusOneAssign(): void
{
	$idx = count(...) - 1;
	assertType('Closure(array|Countable, 0|1=): int<0, max>', count(...));
}

// array_search guard needs true context, so it must be in a condition
function testArraySearchInCondition(): void
{
	if ($key = array_search(...)) {
		assertType('Closure(mixed, array, bool=): (int|string|false)', $key);
	}
}

// Comparison guards in TypeSpecifier (Smaller/SmallerOrEqual)
function testCountInComparisons(): void
{
	if (count(...) < 1) {}
	if (0 < count(...)) {}
	assertType('Closure(array|Countable, 0|1=): int<0, max>', count(...));
}

function testSizeofInComparisons(): void
{
	if (sizeof(...) < 1) {}
	if (0 < sizeof(...)) {}
	assertType('Closure(array|Countable, int=): int', sizeof(...));
}

function testCountMinusOneInComparison(): void
{
	$i = 0;
	if ($i < count(...) - 1) {}
	assertType('Closure(array|Countable, 0|1=): int<0, max>', count(...));
}

function testStrlenInComparisons(): void
{
	if (strlen(...) < 1) {}
	if (0 < strlen(...)) {}
	assertType('Closure(string): int<0, max>', strlen(...));
}

function testMbStrlenInComparisons(): void
{
	if (mb_strlen(...) < 1) {}
	if (0 < mb_strlen(...)) {}
	assertType('Closure(string, string|null=): int<0, max>', mb_strlen(...));
}

function testPregMatchInComparisons(): void
{
	if (preg_match(...) < 1) {}
	if (0 < preg_match(...)) {}
	assertType('Closure(string, string, array<string>|null=, TFlags=, int=): (0|1|false)', preg_match(...));
}

// Identical/NotIdentical guards in resolveNormalizedIdentical
function testCountIdentical(): void
{
	if (count(...) === 0) {}
	assertType('Closure(array|Countable, 0|1=): int<0, max>', count(...));
}

function testStrlenIdentical(): void
{
	if (strlen(...) === 0) {}
	if (mb_strlen(...) === 0) {}
	assertType('Closure(string): int<0, max>', strlen(...));
}

function testArrayKeyFirstNullComparison(): void
{
	if (array_key_first(...) !== null) {}
	if (array_key_last(...) !== null) {}
	assertType('Closure(array): (int|string|null)', array_key_first(...));
}

function testGetClassIdentical(): void
{
	if (get_class(...) === 'stdClass') {}
	if (get_debug_type(...) === 'string') {}
	assertType('Closure(object=): class-string', get_class(...));
}

function testStringFuncIdentical(): void
{
	if (strtolower(...) === 'test') {}
	assertType('Closure(string): lowercase-string', strtolower(...));
}

// String equality guards in specifyTypesForConstantStringBinaryExpression
function testGettypeEquality(): void
{
	if (gettype(...) === 'string') {}
	if (gettype(...) == 'string') {}
	assertType('Closure(mixed): string', gettype(...));
}

function testGetClassEquality(): void
{
	if (get_class(...) == 'stdClass') {}
	if (get_debug_type(...) == 'string') {}
	assertType('Closure(object=): class-string', get_class(...));
}

function testGetParentClassEquality(): void
{
	if (get_parent_class(...) === 'stdClass') {}
	assertType('Closure(object|string=): (class-string|false)', get_parent_class(...));
}

function testTrimEquality(): void
{
	if (trim(...) !== '') {}
	if (ltrim(...) !== '') {}
	if (rtrim(...) !== '') {}
	assertType('Closure(string, string=): string', trim(...));
}

// NodeScopeResolver guards
function testArrayKeysInForeach(): void
{
	foreach (array_keys(...) as $key) {}
	assertType('Closure(array, mixed=, bool=): list<int|string>', array_keys(...));
}

function testCountInForLoop(): void
{
	for ($i = 0; $i < count(...); $i++) {}
	for ($i = 0; count(...) > $i; $i++) {}
	assertType('Closure(array|Countable, 0|1=): int<0, max>', count(...));
}
