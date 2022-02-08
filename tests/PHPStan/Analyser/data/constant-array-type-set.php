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
		assertType('array{bool, bool, bool}', $c);

		$d = [false, false, false];
		/** @var int<0, 2> $offset2 */
		$offset2 = doFoo();
		$d[$offset2] = true;
		assertType('array{bool, bool, bool}', $d);

		$e = [false, false, false];
		/** @var 0|1|2|3 $offset3 */
		$offset3 = doFoo();
		$e[$offset3] = true;
		assertType('non-empty-array<0|1|2|3, bool>', $e);

		$f = [false, false, false];
		/** @var 0|1 $offset4 */
		$offset4 = doFoo();
		$f[$offset4] = true;
		assertType('array{bool, bool, false}', $f);
	}

	/**
	 * @param int<0, 1> $offset
	 * @return void
	 */
	public function doBar(int $offset): void
	{
		$a = [false, false, false];
		$a[$offset] = true;
		assertType('array{bool, bool, false}', $a);
	}

	/**
	 * @param int<0, 1>|int<3, 4> $offset
	 * @return void
	 */
	public function doBar2(int $offset): void
	{
		$a = [false, false, false, false, false];
		$a[$offset] = true;
		assertType('array{bool, bool, false, bool, bool}', $a);
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
		assertType('non-empty-array<int<0, 4>, bool>', $a);
	}

}
