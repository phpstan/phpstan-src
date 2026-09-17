<?php declare(strict_types = 1);

namespace Bug15242;

use function PHPStan\Testing\assertType;

/**
 * @param int<0, 30> $nonNegative
 * @param int<-30, 30> $any
 * @param int<-10, -5> $negativeRange
 * @param int<-20, 5> $mixedSignRange
 */
function foo(int $nonNegative, int $any, int $negativeRange, int $mixedSignRange): void
{
	assertType('int<0, 8>', $nonNegative % 9);
	assertType('int<0, 8>', $nonNegative % -9);
	assertType('int<-8, 8>', $any % -9);
	assertType('int<0, 9>', $nonNegative % $negativeRange);
	assertType('int<0, 19>', $nonNegative % $mixedSignRange);
}

/**
 * @param int<0, 10> $i
 */
function phpIntMinDivisor(int $i): void
{
	assertType('int<0, 10>', $i % (-9223372036854775807 - 1));
	assertType('int<0, 10>', $i % PHP_INT_MIN);
}

class Foo
{

	/**
	 * @param int<0, 30> $nonNegative
	 * @param int<min, 0> $nonPositive
	 */
	public function doFoo(int $nonNegative, int $nonPositive): void
	{
		assertType('int<-8, 0>', $nonPositive % -9);
		assertType('int<-8, 0>', $nonPositive % 9);
		assertType('0', $nonNegative % -1);
	}

	/**
	 * @param int<0, 10> $i
	 */
	public function doBar(int $i): void
	{
		assertType('int<0, 10>', $i % PHP_INT_MAX);
		assertType('int<0, 10>', $i % -9223372036854775807);
	}

	/**
	 * @param int<0, 30> $nonNegative
	 * @param int<min, 0> $nonPositive
	 * @param -9|-3 $negativeConstants
	 * @param int<min, -5> $negativeUnbounded
	 */
	public function doModVariants(int $nonNegative, int $nonPositive, int $negativeConstants, int $negativeUnbounded): void
	{
		assertType('int<0, 8>', $nonNegative % $negativeConstants);
		assertType('int<-8, 0>', $nonPositive % $negativeConstants);
		assertType('int<0, 30>', $nonNegative % $negativeUnbounded);

		$compound = $nonNegative;
		$compound %= -9;
		assertType('int<0, 8>', $compound);
	}

	/**
	 * @param int<0, 30> $nonNegative
	 * @param int<min, 5> $rMin
	 */
	public function doDiv(int $nonNegative, int $rMin, int $i): void
	{
		assertType('int<-30, 0>', $nonNegative / -1);
		assertType('int<-5, max>', $rMin / -1);
		assertType('int', $i / -1);
		assertType('9.223372036854776E+18', (-9223372036854775807 - 1) / -1);
	}

	/**
	 * @param int<1, 2> $small
	 * @param int<1, 3> $small3
	 * @param int<0, 30> $nonNegative
	 */
	public function doShiftLeft(int $small, int $small3, int $nonNegative): void
	{
		assertType('int<4, 8>', $small << 2);
		assertType('int', $small << 62);
		assertType('int', $small3 << 63);
		assertType('int', $nonNegative << 60);
	}

}
