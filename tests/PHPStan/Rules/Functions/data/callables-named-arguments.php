<?php

namespace CallablesNamedArguments;

class Foo
{

	public function doFoo(): void
	{
		$f = function (int $i, int $j): void {

		};

		$f(i: 1);
	}

	/**
	 * @param callable(int, int): void $cb
	 * @param callable(int $i, int $j): void $cb2
	 */
	public function doBar(callable $cb, callable $cb2): void
	{
		$cb(i: 1);
		$cb2(i: 1);
	}

}
