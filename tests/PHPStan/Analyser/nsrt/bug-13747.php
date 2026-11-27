<?php declare(strict_types = 1);

namespace Bug13747;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/** @param list<int> $list */
	public function count($list): void
	{
		if (count($list) === 0) {
			return;
		}

		if (count($list) > 2) {
			assertType('false', array_key_exists(-1, $list));
			assertType('true', array_key_exists(0, $list));
			assertType('true', array_key_exists(1, $list));
			assertType('true', array_key_exists(2, $list));
			assertType('bool', array_key_exists(3, $list));

			assertType('non-empty-list<int>&hasOffsetValue(1, int)&hasOffsetValue(2, int)', $list);
			assertType('int<3, max>', count($list));
		} else {
			assertType('non-empty-list<int>', $list);
		}
		assertType('non-empty-list<int>', $list);

		if (count($list, COUNT_NORMAL) > 2) {
			assertType('non-empty-list<int>&hasOffsetValue(1, int)&hasOffsetValue(2, int)', $list);
			assertType('int<3, max>', count($list, COUNT_NORMAL));
		} else {
			assertType('non-empty-list<int>', $list);
		}

		assertType('non-empty-list<int>', $list);
		if (count($list, COUNT_RECURSIVE) > 2) { // COUNT_RECURSIVE on non-recursive array
			assertType('non-empty-list<int>&hasOffsetValue(1, int)&hasOffsetValue(2, int)', $list);
			assertType('int<3, max>', count($list, COUNT_RECURSIVE));
		} else {
			assertType('non-empty-list<int>', $list);
		}
	}

	/** @param list<int> $list */
	public function doFoo($list): void
	{
		if (count($list) === 0) {
			return;
		}

		if (count($list) >= 2) {
			assertType('non-empty-list<int>&hasOffsetValue(1, int)', $list);
			assertType('int<2, max>', count($list));
		} else {
			assertType('non-empty-list<int>', $list);
		}

		if (count($list) < 5) {
			assertType('non-empty-list<int>', $list);
			assertType('int<1, 4>', count($list));
		} else {
			assertType('non-empty-list<int>&hasOffsetValue(1, int)&hasOffsetValue(2, int)&hasOffsetValue(3, int)&hasOffsetValue(4, int)', $list);
		}
	}

	/** @param list<int> $list */
	public function doBar($list): void
	{
		if (count($list) === 0) {
			return;
		}

		if (2 <= count($list)) {
			assertType('non-empty-list<int>&hasOffsetValue(1, int)', $list);
			assertType('int<2, max>', count($list));
		} else {
			assertType('non-empty-list<int>', $list);
			assertType('1', count($list));
		}
	}

	/** @param non-empty-array<int> $nonEmptyArray */
	public function doNonEmptyArray($nonEmptyArray): void
	{
		if (2 <= count($nonEmptyArray)) {
			assertType('non-empty-array<int>', $nonEmptyArray);
			assertType('int<2, max>', count($nonEmptyArray));
		} else {
			assertType('non-empty-array<int>', $nonEmptyArray);
			assertType('1', count($nonEmptyArray));
		}

		if (count($nonEmptyArray) < 5) {
			assertType('non-empty-array<int>', $nonEmptyArray);
			assertType('int<1, 4>', count($nonEmptyArray));
		} else {
			assertType('non-empty-array<int>', $nonEmptyArray);
		}
	}

	/**
	 * @param list<int> $listA
	 * @param non-empty-list<string> $listB
	 */
	public function doMaybeBar($listA, $listB): void
	{
		if (rand(0,1)) {
			$list = $listA;
		} else {
			$list = $listB;
		}

		if (2 <= count($list)) {
			assertType('(non-empty-list<int>&hasOffsetValue(1, int))|(non-empty-list<string>&hasOffsetValue(1, string))', $list);
			assertType('int<2, max>', count($list));
		} else {
			assertType('list<int>|non-empty-list<string>', $list);
			assertType('int<0, 1>', count($list));
		}
	}

	/**
	 * @param array<int> $aArray
	 * @param non-empty-list<string> $aList
	 */
	public function doMaybeArray($aArray, $aList): void
	{
		if (rand(0,1)) {
			$listOrArray = $aArray;
		} else {
			$listOrArray = $aList;
		}

		if (2 <= count($listOrArray)) {
			assertType('non-empty-array<int>|non-empty-list<string>', $listOrArray);
			assertType('int<2, max>', count($listOrArray));
		} else {
			assertType('array<int>|non-empty-list<string>', $listOrArray);
			assertType('int<0, 1>', count($listOrArray));
		}
	}

	/** @param list<int> $list */
	public function doMaybeEmpty($list): void
	{
		if (rand(0,1)) {
			$list = [1, 2, 3];
		}

		if (2 <= count($list)) {
			assertType('non-empty-list<int>&hasOffsetValue(1, int)', $list);
			assertType('int<2, max>', count($list));
		} else {
			assertType('list<int>', $list);
			assertType('int<0, 1>', count($list));
		}
	}

	/** @param list<int> $list */
	public function checkLimit($list): void
	{
		if (count($list) === 0) {
			return;
		}

		if (count($list) > 9) {
			assertType('non-empty-list<int>&hasOffsetValue(1, int)&hasOffsetValue(2, int)&hasOffsetValue(3, int)&hasOffsetValue(4, int)&hasOffsetValue(5, int)&hasOffsetValue(6, int)&hasOffsetValue(7, int)&hasOffsetValue(8, int)&hasOffsetValue(9, int)', $list);
			assertType('int<10, max>', count($list));
		} else {
			assertType('non-empty-list<int>', $list);
		}

		if (count($list) > 10) {
			assertType('non-empty-list<int>&hasOffsetValue(1, int)&hasOffsetValue(2, int)&hasOffsetValue(3, int)&hasOffsetValue(4, int)&hasOffsetValue(5, int)&hasOffsetValue(6, int)&hasOffsetValue(7, int)&hasOffsetValue(8, int)&hasOffsetValue(9, int)', $list);
			assertType('int<11, max>', count($list));
		} else {
			assertType('non-empty-list<int>', $list);
		}

	}

	/** @param array<int> $array */
	public function countArray($array): void
	{
		if (count($array) < 5) {
			assertType('array<int>', $array);
			assertType('int<0, 4>', count($array));
		} else {
			assertType('non-empty-array<int>', $array);
		}

		if (count($array) === 0) {
			return;
		}

		if (count($array) > 2) {
			assertType('bool', array_key_exists(-1, $array));
			assertType('bool', array_key_exists(0, $array));
			assertType('bool', array_key_exists(1, $array));
			assertType('bool', array_key_exists(2, $array));
			assertType('bool', array_key_exists(3, $array));

			assertType('non-empty-array<int>', $array);
			assertType('int<3, max>', count($array));
		} else {
			assertType('non-empty-array<int>', $array);
		}
		assertType('non-empty-array<int>', $array);

		if (count($array, COUNT_NORMAL) > 2) {
			assertType('non-empty-array<int>', $array);
			assertType('int<3, max>', count($array, COUNT_NORMAL));
		} else {
			assertType('non-empty-array<int>', $array);
		}

		assertType('non-empty-array<int>', $array);
		if (count($array, COUNT_RECURSIVE) > 2) { // COUNT_RECURSIVE on non-recursive array
			assertType('non-empty-array<int>', $array);
			assertType('int<3, max>', count($array, COUNT_RECURSIVE));
		} else {
			assertType('non-empty-array<int>', $array);
		}
	}
}
