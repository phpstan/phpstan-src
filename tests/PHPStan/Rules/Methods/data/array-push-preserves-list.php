<?php

namespace ArrayPushPreservesList;

use function array_unshift;

class Foo
{

	/**
	 * @param list<int> $a
	 * @return list<int>
	 */
	public function doFoo(array $a): array
	{
		array_push($a, ...$a);

		return $a;
	}

	/**
	 * @param list<int> $a
	 * @return list<int>
	 */
	public function doFoo2(array $a): array
	{
		array_push($a, ...[1, 2, 3]);

		return $a;
	}

	/**
	 * @param list<int> $a
	 * @return list<int>
	 */
	public function doFoo3(array $a): array
	{
		$b = [1, 2, 3];
		array_push($b, ...$a);

		return $b;
	}

}

class Bar
{

	/**
	 * @param list<int> $a
	 * @return list<int>
	 */
	public function doFoo(array $a): array
	{
		array_unshift($a, ...$a);

		return $a;
	}

	/**
	 * @param list<int> $a
	 * @return list<int>
	 */
	public function doFoo2(array $a): array
	{
		array_unshift($a, ...[1, 2, 3]);

		return $a;
	}

	/**
	 * @param list<int> $a
	 * @return list<int>
	 */
	public function doFoo3(array $a): array
	{
		$b = [1, 2, 3];
		array_unshift($b, ...$a);

		return $b;
	}

}
