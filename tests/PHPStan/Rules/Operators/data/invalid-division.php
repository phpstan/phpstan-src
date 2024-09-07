<?php

namespace InvalidDivision;

class HelloWorld
{
	/**
	 * @param int<0, max> $x
	 */
	public function doDiv(int $y, $x): void
	{
		echo $y / $x;
		$y /= $x;
	}

	/**
	 * @param int<0, max> $x
	 */
	public function doMod(int $y, $x): void
	{
		echo $y % $x;
		$y %= $x;
	}

	public function doMixed(int $y, $mixed): void
	{
		echo $y % $mixed;
		$y %= $mixed;
	}

	/**
	 * @param int<min, -1> $negative
	 * @param int<1, max> $positive
	 */
	public function doRanges(int $y, $x): void
	{
		echo $y / $x;
		$y /= $x;
	}
}

function ($i, int $x): void {
	$a = range(1, 3/2);

	if ($x !== 0) {
		echo $i / $x;
	}

	if ($x != 0) {
		echo $i / $x;
	}

	if ($x > 0) {
		echo $i / $x;
	}

	if ($x >= 0) {
		echo $i / $x;
	}
};
