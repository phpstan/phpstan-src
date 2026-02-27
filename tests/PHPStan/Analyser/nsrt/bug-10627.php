<?php

namespace Bug10627;

use function array_is_list;
use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function sayHello(): void
	{
		$list = ['A', 'C', 'B'];
		natcasesort($list);
		assertType("array{'A', 'C', 'B'}", $list);
		assertType('bool', array_is_list($list));
	}

	public function sayHello2(): void
	{
		$list = ['A', 'C', 'B'];
		natsort($list);
		assertType("array{'A', 'C', 'B'}", $list);
		assertType('bool', array_is_list($list));
	}

	public function sayHello3(): void
	{
		$list = ['A', 'C', 'B'];
		arsort($list);
		assertType("array{'A', 'C', 'B'}", $list);
		assertType('bool', array_is_list($list));
	}

	public function sayHello4(): void
	{
		$list = ['A', 'C', 'B'];
		asort($list);
		assertType("array{'A', 'C', 'B'}", $list);
		assertType('bool', array_is_list($list));
	}

	public function sayHello5(): void
	{
		$list = ['A', 'C', 'B'];
		ksort($list);
		assertType("array{'A', 'C', 'B'}", $list);
		assertType('true', array_is_list($list));
	}

	public function sayHello6(): void
	{
		$list = ['A', 'C', 'B'];
		uasort($list, function () {

		});
		assertType("array{'A', 'C', 'B'}", $list);
		assertType('bool', array_is_list($list));
	}

	public function sayHello7(): void
	{
		$list = ['A', 'C', 'B'];
		uksort($list, function () {

		});
		assertType("array{'A', 'C', 'B'}", $list);
		assertType('bool', array_is_list($list));
	}

	public function sayHello8(): void
	{
		$list = ['A', 'C', 'B'];
		krsort($list);
		assertType("array{2: 'B', 1: 'C', 0: 'A'}", $list);
		assertType('false', array_is_list($list));
	}

	/**
	 * @param list<string> $list
	 * @return void
	 */
	public function sayHello9(array $list): void
	{
		krsort($list);
		assertType("array<int<0, max>, string>", $list);
	}

	public function sayHello10(): void
	{
		$list = ['a' => 'A', 'c' => 'C', 'b' => 'B'];
		krsort($list);
		assertType("array{c: 'C', b: 'B', a: 'A'}", $list);
		assertType('false', array_is_list($list));
	}
}
