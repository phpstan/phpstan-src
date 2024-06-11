<?php

namespace ConstantArrayTypeSet;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(int $i)
	{
		$a = [1, 2, 3];
		$a[$i] = 4;
		assertType('non-empty-array<int, 1|2|3|4>', $a);

		$b = [1, 2, 3];
		$b[3] = 4;
		assertType('array{1, 2, 3, 4}', $b);

		$c = [false, false, false];
		/** @var 0|1|2 $offset */
		$offset = doFoo();
		$c[$offset] = true;
		assertType('array{false, false, true}|array{false, true, false}|array{true, false, false}', $c);

		$d = [false, false, false];
		/** @var int<0, 2> $offset2 */
		$offset2 = doFoo();
		$d[$offset2] = true;
		assertType('array{false, false, true}|array{false, true, false}|array{true, false, false}', $d);

		$e = [false, false, false];
		/** @var 0|1|2|3 $offset3 */
		$offset3 = doFoo();
		$e[$offset3] = true;
		assertType('array{false, false, false, true}|array{false, false, true}|array{false, true, false}|array{true, false, false}', $e);

		$f = [false, false, false];
		/** @var 0|1 $offset4 */
		$offset4 = doFoo();
		$f[$offset4] = true;
		assertType('array{false, true, false}|array{true, false, false}', $f);
	}

	/**
	 * @param int<0, 1> $offset
	 * @return void
	 */
	public function doBar(int $offset): void
	{
		$a = [false, false, false];
		$a[$offset] = true;
		assertType('array{false, true, false}|array{true, false, false}', $a);
	}

	/**
	 * @param int<0, 1>|int<3, 4> $offset
	 * @return void
	 */
	public function doBar2(int $offset): void
	{
		$a = [false, false, false, false, false];
		$a[$offset] = true;
		assertType('array{false, false, false, false, true}|array{false, false, false, true, false}|array{false, true, false, false, false}|array{true, false, false, false, false}', $a);
	}

	/**
	 * @param int<0, max> $offset
	 * @return void
	 */
	public function doBar3(int $offset): void
	{
		$a = [false, false, false, false, false];
		$a[$offset] = true;
		assertType('non-empty-array<int<0, max>, bool>', $a);
	}

	/**
	 * @param int<min, 0> $offset
	 * @return void
	 */
	public function doBar4(int $offset): void
	{
		$a = [false, false, false, false, false];
		$a[$offset] = true;
		assertType('non-empty-array<int<min, 4>, bool>', $a);
	}

	/**
	 * @param int<0, 4> $offset
	 * @return void
	 */
	public function doBar5(int $offset): void
	{
		$a = [false, false, false];
		$a[$offset] = true;
		assertType('array{0: false, 1: false, 2: false, 4: true}|array{false, false, false, true}|array{false, false, true}|array{false, true, false}|array{true, false, false}', $a);
	}

	public function doBar6(bool $offset): void
	{
		$a = [false, false, false];
		$a[$offset] = true;
		assertType('array{false, true, false}|array{true, false, false}', $a);
	}

	/**
	 * @param true $offset
	 */
	public function doBar7(bool $offset): void
	{
		$a = [false, false, false];
		$a[$offset] = true;
		assertType('array{false, true, false}', $a);
	}

}
