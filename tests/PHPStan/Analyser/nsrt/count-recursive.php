<?php

use function PHPStan\Testing\assertType;

class HelloWorld
{
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

	/** @param list<int> $list */
	public function countListRecursive($list): void
	{
		if (count($list, COUNT_RECURSIVE) > 2) {
			assertType('int<1, max>', count($list));
			assertType('int<1, max>', count($list, COUNT_NORMAL));
			assertType('int<3, max>', count($list, COUNT_RECURSIVE));
		}
	}
}
