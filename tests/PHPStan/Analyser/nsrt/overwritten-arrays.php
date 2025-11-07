<?php

namespace OverwrittenArrays;

use function PHPStan\Testing\assertType;
use function rad2deg;

class Foo
{

	/**
	 * @param array<int, string> $a
	 */
	public function doFoo(array $a): void
	{
		foreach ($a as $k => $v) {
			$a[$k] = 1;
		}

		assertType('array<int, 1>', $a);
	}

	/**
	 * @param array<int, string> $a
	 */
	public function doFoo2(array $a): void
	{
		foreach ($a as $k => $v) {
			if (rand(0, 1)) {
				$a[$k] = 2;
				continue;
			}
			$a[$k] = 1;
		}

		assertType('array<int, 1|2>', $a);
	}

	/**
	 * @param array<int, string> $a
	 */
	public function doFoo3(array $a): void
	{
		foreach ($a as $k => $v) {
			if (rand(0, 1)) {
				break;
			}
			if (rand(0, 1)) {
				$a[$k] = 2;
				continue;
			}
			$a[$k] = 1;
		}

		assertType('array<int, 1|2|string>', $a);
	}

	/**
	 * @param array<int, string> $a
	 */
	public function doFoo4(array $a): void
	{
		foreach ($a as $k => $v) {
			$k++;
			$a[$k] = 1;
		}

		assertType('array<int, 1|string>', $a);
	}

	/**
	 * @param array<int, string> $a
	 */
	public function doFoo5(array $a): void
	{
		foreach ($a as $k => $v) {
			if (rand(0, 1)) {
				$k++;
				$a[$k] = 2;
				continue;
			}
			$a[$k] = 1;
		}

		assertType('array<int, 1|2|string>', $a);
	}

	/**
	 * @param array<int, string> $a
	 */
	public function doFoo6(array $a): void
	{
		foreach ($a as $k => $v) {
			if (rand(0, 1)) {
				$a[$k] = 2;
				continue;
			}
			$k++;
			$a[$k] = 1;
		}

		assertType('array<int, 1|2|string>', $a);
	}

	/**
	 * @param array<int, string> $a
	 */
	public function doFoo7(array $a): void
	{
		foreach ($a as &$v) {
			$v = 1;
		}

		assertType('array<int, 1>', $a);
	}

	/**
	 * @param array<int, string> $a
	 */
	public function doFoo8(array $a): void
	{
		foreach ($a as &$v) {
			if (rand(0, 1)) {
				$v = 1;
			}
		}

		assertType('array<int, 1|string>', $a);
	}

}
