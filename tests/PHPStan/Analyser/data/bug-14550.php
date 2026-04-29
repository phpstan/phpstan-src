<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14550;

function testArrayKeyFirstAssign(): void
{
	$fn = array_key_first(...);
}

function testArrayKeyLastAssign(): void
{
	$fn = array_key_last(...);
}

function testArrayRandAssign(): void
{
	$fn = array_rand(...);
}

function testCountMinusOneAssign(): void
{
	$idx = count(...) - 1;
}

function testArraySearchInCondition(): void
{
	if ($key = array_search(...)) {
	}
}

function testCountInComparisons(): void
{
	if (count(...) < 1) {}
	if (0 < count(...)) {}
}

function testSizeofInComparisons(): void
{
	if (sizeof(...) < 1) {}
	if (0 < sizeof(...)) {}
}

function testCountMinusOneInComparison(): void
{
	$i = 0;
	if ($i < count(...) - 1) {}
}

function testStrlenInComparisons(): void
{
	if (strlen(...) < 1) {}
	if (0 < strlen(...)) {}
}

function testMbStrlenInComparisons(): void
{
	if (mb_strlen(...) < 1) {}
	if (0 < mb_strlen(...)) {}
}

function testPregMatchInComparisons(): void
{
	if (preg_match(...) < 1) {}
	if (0 < preg_match(...)) {}
}

function testCountIdentical(): void
{
	if (count(...) === 0) {}
}

function testStrlenIdentical(): void
{
	if (strlen(...) === 0) {}
	if (mb_strlen(...) === 0) {}
}

function testArrayKeyFirstNullComparison(): void
{
	if (array_key_first(...) !== null) {}
	if (array_key_last(...) !== null) {}
}

function testGetClassIdentical(): void
{
	if (get_class(...) === 'stdClass') {}
	if (get_debug_type(...) === 'string') {}
}

function testStringFuncIdentical(): void
{
	if (strtolower(...) === 'test') {}
}

function testGettypeEquality(): void
{
	if (gettype(...) === 'string') {}
	if (gettype(...) == 'string') {}
}

function testGetClassEquality(): void
{
	if (get_class(...) == 'stdClass') {}
	if (get_debug_type(...) == 'string') {}
}

function testGetParentClassEquality(): void
{
	if (get_parent_class(...) === 'stdClass') {}
}

function testTrimEquality(): void
{
	if (trim(...) !== '') {}
	if (ltrim(...) !== '') {}
	if (rtrim(...) !== '') {}
}

function testArrayKeysInForeach(): void
{
	foreach (array_keys(...) as $key) {}
}

function testCountInForLoop(): void
{
	for ($i = 0; $i < count(...); $i++) {}
	for ($i = 0; count(...) > $i; $i++) {}
}
