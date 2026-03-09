<?php declare(strict_types = 1);

namespace Bug13674;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param list<int> $listA
	 */
	public function sayHello(int $i, array $listA): void
	{
		if (isset($listA[$i])) {
			assertType('non-empty-list<int>', $listA);

			return;
		}
	}

	/**
	 * @param list<int> $listB
	 */
	public function sayHello2(int $i, array $listB): void
	{
		if (!isset($listB[$i])) {
			return;
		}
		assertType('non-empty-list<int>', $listB);
	}

	/**
	 * @param array<string, int> $arr
	 */
	public function sayHello3(string $key, array $arr): void
	{
		if (isset($arr[$key])) {
			assertType('non-empty-array<string, int>', $arr);
		}
	}
}
