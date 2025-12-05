<?php

namespace CountRecursive;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param array<array<mixed>> $muliDimArr
	 * @return void
	 */
	public function countMultiDim(array $muliDimArr, $mixed): void
	{
		if (count($muliDimArr, $mixed) > 2) {
			assertType('int<1, max>', count($muliDimArr));
			assertType('int<3, max>', count($muliDimArr, $mixed));
			assertType('int<1, max>', count($muliDimArr, COUNT_NORMAL));
			assertType('int<1, max>', count($muliDimArr, COUNT_RECURSIVE));
		}
	}

	public function countUnknownArray(array $arr): void
	{
		assertType('array', $arr);
		assertType('int<0, max>', count($arr));
		assertType('int<0, max>', count($arr, COUNT_NORMAL));
		assertType('int<0, max>', count($arr, COUNT_RECURSIVE));
	}

	public function countEmptyArray(array $arr): void
	{
		if (count($arr) == 0) {
			assertType('array{}', $arr);
			assertType('0', count($arr));
			assertType('0', count($arr, COUNT_NORMAL));
			assertType('0', count($arr, COUNT_RECURSIVE));
		}
	}

	public function countArray(array $arr): void
	{
		if (count($arr) > 2) {
			assertType('non-empty-array', $arr);
			assertType('int<3, max>', count($arr));
			assertType('int<1, max>', count($arr, COUNT_NORMAL)); // could be int<3, max>
			assertType('int<1, max>', count($arr, COUNT_RECURSIVE));
		}
	}

	public function countArrayNormal(array $arr): void
	{
		if (count($arr, COUNT_NORMAL) > 2) {
			assertType('non-empty-array', $arr);
			assertType('int<1, max>', count($arr)); // could be int<3, max>
			assertType('int<3, max>', count($arr, COUNT_NORMAL));
			assertType('int<1, max>', count($arr, COUNT_RECURSIVE));
		}
	}

	public function countArrayRecursive(array $arr): void
	{
		if (count($arr, COUNT_RECURSIVE) > 2) {
			assertType('non-empty-array', $arr);
			assertType('int<1, max>', count($arr));
			assertType('int<1, max>', count($arr, COUNT_NORMAL));
			assertType('int<3, max>', count($arr, COUNT_RECURSIVE));
		}
	}

	public function countArrayUnionMode(array $arr): void
	{
		$mode = rand(0,1) ? COUNT_NORMAL : COUNT_RECURSIVE;
		if (count($arr, $mode) > 2) {
			assertType('non-empty-array', $arr);
			assertType('int<3, max>', count($arr, $mode));
			assertType('int<1, max>', count($arr, COUNT_NORMAL));
			assertType('int<1, max>', count($arr, COUNT_RECURSIVE));
		}
	}

	/** @param list<int> $list */
	public function countList($list): void
	{
		if (count($list) > 2) {
			assertType('int<3, max>', count($list));
			assertType('int<1, max>', count($list, COUNT_NORMAL));
			assertType('int<1, max>', count($list, COUNT_RECURSIVE));
		}
	}

	/** @param list<int> $list */
	public function countListNormal($list): void
	{
		if (count($list, COUNT_NORMAL) > 2) {
			assertType('int<1, max>', count($list));
			assertType('int<3, max>', count($list, COUNT_NORMAL));
			assertType('int<1, max>', count($list, COUNT_RECURSIVE));
		}
	}

	public function countImplicitNormal($mode): void
	{
		$arr = [1, 2, 3];
		if (count($arr, $mode) > 2) {
			assertType('3', count($arr));
			assertType('3', count($arr, $mode));
			assertType('3', count($arr, COUNT_NORMAL));
			assertType('3', count($arr, COUNT_RECURSIVE));
		}
	}

	public function countMixed($arr, $mode): void
	{
		if (count($arr, $mode) > 2) {
			assertType('int<0, max>', count($arr));
			assertType('int<3, max>', count($arr, $mode));
			assertType('int<0, max>', count($arr, COUNT_NORMAL));
			assertType('int<0, max>', count($arr, COUNT_RECURSIVE));
		}
	}

	/** @param list<int> $list */
	public function countListRecursive($list): void
	{
		if (count($list, COUNT_RECURSIVE) > 2) {
			assertType('int<1, max>', count($list));
			assertType('int<1, max>', count($list, COUNT_NORMAL));
			assertType('int<3, max>', count($list, COUNT_RECURSIVE));
		}
	}

	/** @param arary<int> $array */
	public function countListRecursiveOnUnionOfRanges($array): void
	{
		if (!array_key_exists(5, $array)) {
			return;
		}
		assertType('non-empty-array&hasOffset(5)', $array);
		assertType('int<1, max>', count($array));

		if (
			(count($array) > 2 && count($array) < 5)
			|| (count($array) > 20 && count($array) < 50)
		) {
			assertType('int<3, 4>|int<21, 49>', count($array));
		}
	}


	public function countConstantArray(array $anotherArray): void {
		$arr = [1, 2, 3, [4, 5]];
		assertType('4', count($arr));
		assertType('4', count($arr, COUNT_NORMAL));
		assertType('int<1, max>', count($arr, COUNT_RECURSIVE));

		$arr = [1, 2, 3, $anotherArray];
		assertType('array{1, 2, 3, array}', $arr);
		assertType('4', count($arr));
		assertType('4', count($arr, COUNT_NORMAL));
		assertType('int<1, max>', count($arr, COUNT_RECURSIVE)); // could be int<4, max>

		if (rand(0,1)) {
			$arr[] = 10;
		}
		assertType('array{0: 1, 1: 2, 2: 3, 3: array, 4?: 10}', $arr);
		assertType('int<4, 5>', count($arr));
		assertType('int<4, 5>', count($arr, COUNT_NORMAL));
		assertType('int<1, max>', count($arr, COUNT_RECURSIVE)); // could be int<4, max>

		$arr = [1, 2, 3] + $anotherArray;
		assertType('non-empty-array&hasOffsetValue(0, 1)&hasOffsetValue(1, 2)&hasOffsetValue(2, 3)', $arr);
		assertType('int<3, max>', count($arr));
		assertType('int<3, max>', count($arr, COUNT_NORMAL));
		assertType('int<1, max>', count($arr, COUNT_RECURSIVE)); // could be int<3, max>
	}

	public function countAfterKeyExists(array $array, int $i): void {
		if (array_key_exists(5, $array)) {
			assertType('non-empty-array&hasOffset(5)', $array);
			assertType('int<1, max>', count($array));
		}

		if ($array !== []) {
			assertType('non-empty-array', $array);
			assertType('int<1, max>', count($array));
			if (array_key_exists(5, $array)) {
				assertType('non-empty-array&hasOffset(5)', $array);
				assertType('int<1, max>', count($array));

				if (array_key_exists(15, $array)) {
					assertType('non-empty-array&hasOffset(15)&hasOffset(5)', $array);
					assertType('int<2, max>', count($array));
				}
			}
		}
	}

	public function unionIntegerCountAfterKeyExists(array $array, int $i): void {
		if ($array === []) {
			return;
		}

		assertType('non-empty-array', $array);
		if (count($array) === 3 || count($array) === 4) {
			assertType('3|4', count($array));
			if (array_key_exists(5, $array)) {
				assertType('non-empty-array&hasOffset(5)', $array);
				assertType('3|4', count($array));
			}
		}
	}

	public function countMaybeCountable(array $arr, bool $b, int $i) {
		$c = rand(0,1) ? $arr : $b;
		assertType('array|bool', $c);
		assertType('int<0, max>', count($c, $i));

		if ($arr === []) {
			return;
		}
		assertType('int<1, max>', count($arr, $i));

		$c = rand(0,1) ? $arr : $b;
		assertType('non-empty-array|bool', $c);
		assertType('int<0, max>', count($c, $i));

	}
}
