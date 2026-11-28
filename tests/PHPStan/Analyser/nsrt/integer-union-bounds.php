<?php declare(strict_types = 1);

namespace IntegerUnionBounds;

use function PHPStan\Testing\assertType;

/**
 * @param array{1}|array{1, 2}|array{1, 2, 3}|array{1, 2, 3, 4} $arr
 * @param 1|2|3 $u
 */
function countGreaterThanUnion(array $arr, int $u): void
{
	if ($u < count($arr)) {
		assertType('list{0: 1, 1: 2, 2?: 3, 3?: 4}', $arr);
	} else {
		assertType('list{0: 1, 1?: 2, 2?: 3}', $arr);
	}
}

/**
 * @param array{1}|array{1, 2}|array{1, 2, 3}|array{1, 2, 3, 4} $arr
 * @param 1|3 $u
 */
function countGreaterThanOrEqualUnion(array $arr, int $u): void
{
	if ($u <= count($arr)) {
		assertType('list{0: 1, 1?: 2, 2?: 3, 3?: 4}', $arr);
	} else {
		assertType('array{0: 1, 1?: 2}', $arr);
	}
}

/**
 * @param list<int> $list
 * @param 1|3 $u
 * @param int<1, 3> $r
 */
function countGreaterThanList(array $list, int $u, int $r): void
{
	if ($u < count($list)) {
		assertType('non-empty-list<int>&hasOffsetValue(1, int)', $list);
	} else {
		assertType('list<int>', $list);
	}

	if ($r < count($list)) {
		assertType('non-empty-list<int>&hasOffsetValue(1, int)', $list);
	} else {
		assertType('list<int>', $list);
	}
}

/**
 * @param list<int> $list
 * @param 2|3 $u
 */
function countIdenticalUnion(array $list, int $u): void
{
	if (count($list) === $u) {
		assertType('array{0: int, 1: int, 2?: int}', $list);
	}
}

/**
 * @param list<int> $list
 * @param int<2, 3> $r
 */
function countIdenticalRange(array $list, int $r): void
{
	if (count($list) === $r) {
		assertType('array{0: int, 1: int, 2?: int}', $list);
	}
}

/**
 * @param 0|2 $u
 * @param int<0, 1> $r
 */
function stringOffset(int $u, int $r): void
{
	$s = 'abc';
	assertType('\'a\'|\'c\'', $s[$u]);
	assertType('\'a\'|\'b\'', $s[$r]);
}

/**
 * @param int<0, 2>|5 $ur
 * @param 0|1|2|5 $u
 */
function sprintfArguments(int $ur, int $u): void
{
	assertType('\'0-1\'|\'1-1\'|\'2-1\'|\'5-1\'', sprintf('%d-%d', $ur, 1));
	assertType('\'0-1\'|\'1-1\'|\'2-1\'|\'5-1\'', sprintf('%d-%d', $u, 1));
}

/**
 * @param int<1, 3> $a
 * @param int<8, 10> $b
 */
function rangeOfRanges(int $a, int $b): void
{
	assertType('non-empty-list<int<1, 10>>', range($a, $b));
	assertType('non-empty-list<int<1, 10>>', range($a, 10));
	assertType('non-empty-list<int<1, 10>>', range(10, $a));
}

/**
 * @param 1|3 $min
 * @param int<1, 3>|10 $max
 */
function filterVarRange($v, int $min, int $max): void
{
	assertType('int<1, max>|false', filter_var($v, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min]]));
	assertType('int<min, 10>|false', filter_var($v, FILTER_VALIDATE_INT, ['options' => ['max_range' => $max]]));
}
