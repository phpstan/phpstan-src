<?php

namespace Bug15244;

class Foo
{

	/**
	 * @return array<int, int>
	 */
	public function doFoo(): array
	{
		return [9223372036854775807 => 1, 2];
	}

	/**
	 * @return array<int, int>
	 */
	public function doBar(): array
	{
		return ['9223372036854775807' => 1, 2];
	}

	/**
	 * @return array<int, int>
	 */
	public function doBaz(): array
	{
		return [9223372036854775806 => 1, 2, 3];
	}

	/**
	 * @return array<int, int>
	 */
	public function doLorem(): array
	{
		// the implicit key of `2` is PHP_INT_MAX
		return [9223372036854775806 => 1, 2, 9223372036854775807 => 3];
	}

	/**
	 * @param array<int, string> $b
	 */
	public function doUnpack(array $b): void
	{
		$a = ['a', ...$b, 1 => 'x'];
		$c = ['a', ...[], 1 => 'x'];
		$d = ['a', ...['k' => 1], 1 => 'x'];
		$e = ['a', ...$b, 0 => 'x'];
	}

}
