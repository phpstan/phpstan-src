<?php

namespace OffsetAccessValueAssignment;

class Foo
{

	/**
	 * @param \ArrayAccess<int,int> $arrayAccess
	 */
	public function doFoo(\ArrayAccess $arrayAccess): void
	{
		function () use ($arrayAccess) {
			$arrayAccess[] = 'foo';
		};

		function () use ($arrayAccess) {
			$arrayAccess[] = 1;
		};

		function () use ($arrayAccess) {
			$arrayAccess[2] = 'bar';
		};

		function () use ($arrayAccess) {
			$i = 1;
			$arrayAccess[] = $i;
		};

		function () use ($arrayAccess) {
			$arrayAccess[] = 'baz';
		};

		function () use ($arrayAccess) {
			$arrayAccess[] = ['foo'];
		};
	}

	public function doBar(int $test): void
	{
		$test[2] = 'foo';
	}

	/**
	 * @param \ArrayAccess<int,int> $arrayAccess
	 */
	public function doBaz(\ArrayAccess $arrayAccess): void
	{
		function () use ($arrayAccess) {
			$arrayAccess[1] += 1;
		};

		function () use ($arrayAccess) {
			$arrayAccess[1] += 2.5;
		};
	}

	public function doLorem(string $str): void
	{
		$str[3] = 'bar';
	}

}
