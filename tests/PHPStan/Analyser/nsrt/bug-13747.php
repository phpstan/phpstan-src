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
}
