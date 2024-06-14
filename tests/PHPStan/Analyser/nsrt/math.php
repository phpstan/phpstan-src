<?php

namespace MathTests;

use function PHPStan\Testing\assertType;

class Foo
{

	const MAX_TOTAL_PRODUCTS = 22;

	public function doFoo(array $excluded, int $i): void
	{
		assertType('int<min, 22>', self::MAX_TOTAL_PRODUCTS - count($excluded));
		assertType('int', self::MAX_TOTAL_PRODUCTS - $i);

		$maxOrPlusOne = self::MAX_TOTAL_PRODUCTS;
		if (rand(0, 1)) {
			$maxOrPlusOne++;
		}

		assertType('22|23', $maxOrPlusOne);
		assertType('int<min, 23>', $maxOrPlusOne - count($excluded));
	}

	public function doBar(int $notZero): void
	{
		if ($notZero === 0) {
			return;
		}

		assertType('int<min, 0>|int<2, max>', $notZero + 1);
	}

	/**
	 * @param int<-5, 5> $rangeFiveBoth
	 * @param int<-5, max> $rangeFiveLeft
	 * @param int<min, 5> $rangeFiveRight
	 */
	public function doBaz(int $rangeFiveBoth, int $rangeFiveLeft, int $rangeFiveRight): void
	{
		assertType('int<-4, 6>', $rangeFiveBoth + 1);
		assertType('int<-4, max>', $rangeFiveLeft + 1);
		assertType('int<-6, max>', $rangeFiveLeft - 1);
		assertType('int<min, 6>', $rangeFiveRight + 1);
		assertType('int<min, 4>', $rangeFiveRight - 1);

		assertType('int', $rangeFiveLeft + $rangeFiveRight);
		assertType('int', $rangeFiveLeft - $rangeFiveRight);

		assertType('int', $rangeFiveRight + $rangeFiveLeft);
		assertType('int', $rangeFiveRight - $rangeFiveLeft);

		assertType('int<-10, 10>', $rangeFiveBoth + $rangeFiveBoth);
		assertType('int<-10, 10>', $rangeFiveBoth - $rangeFiveBoth);

		assertType('int<-10, max>', $rangeFiveBoth + $rangeFiveLeft);
		assertType('int', $rangeFiveBoth - $rangeFiveLeft);

		assertType('int<min, 10>', $rangeFiveBoth + $rangeFiveRight);
		assertType('int<-10, max>', $rangeFiveBoth - $rangeFiveRight);

		assertType('int<-10, max>', $rangeFiveLeft + $rangeFiveBoth);
		assertType('int<-10, max>', $rangeFiveLeft - $rangeFiveBoth);

		assertType('int<min, 10>', $rangeFiveRight + $rangeFiveBoth);
		assertType('int<min, 10>', $rangeFiveRight - $rangeFiveBoth);
	}

	public function doLorem($a, $b): void
	{
		$nullsReverse = rand(0, 1) ? 1 : -1;
		$comparison = $a <=> $b;
		assertType('int<-1, 1>', $comparison);
		assertType('-1|1', $nullsReverse);
		assertType('int<-1, 1>', $comparison * $nullsReverse);
	}

	public function doIpsum(int $newLevel): void
	{
		$min = min(30, $newLevel);
		assertType('int<min, 30>', $min);
		$minDivFive = $min / 5;
		assertType('float|int<min, 6>', $minDivFive);
		$volume = 0x10000000 * $minDivFive;
		assertType('float|int<min, 1610612736>', $volume);
	}

	public function doDolor(int $i): void
	{
		$chunks = min(200, $i);
		assertType('int<min, 200>', $chunks);
		$divThirty = $chunks / 30;
		assertType('float|int<min, 6>', $divThirty);
		assertType('float|int<min, 9>', $divThirty + 3);
	}

	public function doSit(int $i, int $j): void
	{
		if ($i < 0) {
			return;
		}
		if ($j < 1) {
			return;
		}

		assertType('int<0, max>', $i);
		assertType('int<1, max>', $j);
		assertType('int', $i - $j);
	}

	/**
	 * @param int<-5, 5> $range
	 */
	public function multiplyZero(int $i, float $f, $range): void
	{
		assertType('0', $i * false);
		assertType('0.0', $f * false);
		assertType('0', $range * false);

		assertType('0', $i * '0');
		assertType('0.0', $f * '0');
		assertType('0', $range * '0');

		assertType('0', $i * 0);
		assertType('0.0', $f * 0);
		assertType('0', $range * 0);

		assertType('0', 0 * $i);
		assertType('0.0', 0 * $f);
		assertType('0', 0 * $range);

		$i *= 0;
		$f *= 0;
		$range *= 0;
		assertType('0', $i);
		assertType('0.0', $f);
		assertType('0', $range);

	}

	public function never(): void
	{
		for ($i = 1; $i < count([]); $i++) {
			assertType('*NEVER*', $i);
			assertType('*NEVER*', --$i);
			assertType('*NEVER*', $i--);
			assertType('*NEVER*', ++$i);
			assertType('*NEVER*', $i++);

			assertType('*NEVER*', $i + 2);
			assertType('*NEVER*', 2 + $i);
			assertType('*NEVER*', $i - 2);
			assertType('*NEVER*', 2 - $i);
			assertType('*NEVER*', $i * 2);
			assertType('*NEVER*', 2 * $i);
			assertType('*NEVER*', $i ** 2);
			assertType('*NEVER*', 2 ** $i);
			assertType('*NEVER*', $i / 2);
			assertType('*NEVER*', 2 / $i);
			assertType('*NEVER*', $i % 2);

			assertType('*NEVER*', $i | 2);
			assertType('*NEVER*', 2 | $i);
			assertType('*NEVER*', $i & 2);
			assertType('*NEVER*', 2 & $i);
			assertType('*NEVER*', $i ^ 2);
			assertType('*NEVER*', 2 ^ $i);
			assertType('*NEVER*', $i << 2);
			assertType('*NEVER*', 2 << $i);
			assertType('*NEVER*', $i >> 2);
			assertType('*NEVER*', 2 >> $i);
			assertType('*NEVER*', $i <=> 2);
			assertType('*NEVER*', 2 <=> $i);
		}
	}

}
