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
	public function doRanges(int $y, int $negative, int $positive): void
	{
		echo $y / $negative;
		$y /= $negative;

		echo $y / $positive;
		$y /= $positive;
	}
}

function (int $i, int $x): void {
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

	try {
		$i / $x;
	} catch (\DivisionByZeroError $e) {
		echo 'Division by zero';
	}
};

/**
 * @throws \DivisionByZeroError
 */
function throws(int $i, int $x) {
	return $i / $x;
};

class Divide
{
	public function doDiv(int $y, float $x): void
	{
		echo $y / $x;
	}

	/**
	 * @param int|float $maybeFloat
	 */
	public function doDiv1(int $y, $maybeFloat): void
	{
		echo $y / $maybeFloat;
	}

	/**
	 * @param __benevolent<int|float> $benevolentMaybeFloat
	 */
	public function doDiv2(int $y, $benevolentMaybeFloat): void
	{
		echo $y / $benevolentMaybeFloat;
	}

	/**
	 * @param __benevolent<int|true> $benevolent
	 */
	public function doDiv3(int $y, $benevolent): void
	{
		echo $y / $benevolent;
	}

    /**
     * @param __benevolent<float|true> $benevolent
     */
    public function doDiv4(int $y, $benevolent): void
    {
        echo $y / $benevolent;
    }
}

/**
 * @param int<0, max>|-5 $x
 */
function divUnion(int $y, $x): void
{
	echo $y / $x;
}
