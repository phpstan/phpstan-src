<?php

namespace Bug14336;

use function PHPStan\Testing\assertType;

/**
 * Assigning with arbitrary int key in a loop should degrade list to array.
 *
 * @param list<array{abc: string}> $list
 * @param array<int, int> $intMap
 */
function testAssignAnyIntInLoop(array $list, array $intMap): void
{
	foreach ($intMap as $intKey => $intValue) {
		$list[$intKey] = ['abc' => 'def'];
	}
	assertType("array<int, array{abc: string}>", $list);
}

/**
 * @param list<string> $list
 * @param int $intKey
 */
function testAssignAnyIntOutsideLoop(array $list, int $intKey): void
{
	$list[$intKey] = 'foo';
	assertType("non-empty-array<int, string>", $list);
}

/**
 * Safe patterns should still preserve list.
 *
 * @param list<string> $list
 */
function testKeepListWithAppend(array $list): void
{
	$list[] = 'foo';
	assertType("non-empty-list<string>", $list);
}

/**
 * @param list<string> $list
 */
function testKeepListWithConstantZero(array $list): void
{
	$list[0] = 'foo';
	assertType("non-empty-list<string>&hasOffsetValue(0, 'foo')", $list);
}

/**
 * Nested array assignment in loop should keep outer list when key comes from iteration.
 *
 * @param list<array<string, string>> $list
 */
function testNestedAssignKeepsList(array $list): void
{
	foreach ($list as $k => $v) {
		$list[$k]['abc'] = 'world';
	}
	assertType("list<non-empty-array<string, string>&hasOffsetValue('abc', 'world')>", $list);
}

/**
 * @param list<list<string>> $list
 * @param int $intKey
 */
function testNestedListAssignWithAnyInt(array $list, int $intKey): void
{
	$list[$intKey] = ['foo'];
	assertType("non-empty-array<int, list<string>>", $list);
}

/**
 * Assigning with negative int key should also degrade list.
 *
 * @param list<string> $list
 * @param int<min, -1> $negativeKey
 */
function testAssignNegativeInt(array $list, int $negativeKey): void
{
	$list[$negativeKey] = 'foo';
	assertType("non-empty-array<int, string>", $list);
}

/**
 * Assigning with int<0, max> should still keep list (valid range).
 *
 * @param list<array<string>> $list
 * @param int<0, max> $nonNegativeKey
 */
function testAssignNonNegativeIntWithArrayValue(array $list, int $nonNegativeKey): void
{
	$list[$nonNegativeKey] = ['foo'];
	assertType("non-empty-list<array<string>>", $list);
}

/**
 * Direct scalar assignment with int<0, max> key.
 *
 * @param list<string> $list
 * @param int<0, max> $nonNegativeKey
 */
function testAssignNonNegativeIntWithScalarValue(array $list, int $nonNegativeKey): void
{
	$list[$nonNegativeKey] = 'foo';
	assertType("non-empty-array<int<0, max>, string>", $list);
}
