<?php

namespace OverwrittenArrays;

use function PHPStan\Testing\assertType;

class Foo
{

	/** @var array<string> */
	private array $a;

	public function doFooProp1(): void
	{
		foreach ($this->a as $k => $v) {
			if (rand(0, 1)) {
				$this->a[$k] = 2;
				continue;
			}

			$this->a[$k] = 1;
		}

		assertType('array<1|2>', $this->a);
	}

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

		assertType('array<int, 1|string>', $a); // could be array<int, 1>
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

	/**
	 * @param array<int, string> $a
	 */
	public function doFoo9(array $a): void
	{
		foreach ($a as $k => &$v) {
			$v = 1;
			assertType('non-empty-array<int, 1|string>', $a);
			assertType('1', $a[$k]);
		}

		assertType('array<int, 1>', $a);
	}

	/**
	 * @param array<int, string> $a
	 */
	public function doFoo10(array $a): void
	{
		foreach ($a as $k => &$v) {
			$k++;
			$v = 1;
			assertType('non-empty-array<int, 1|string>', $a);
			assertType('1|string', $a[$k]);
		}

		assertType('array<int, 1|string>', $a);
	}

}
