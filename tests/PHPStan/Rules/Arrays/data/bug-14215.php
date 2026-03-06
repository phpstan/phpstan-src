<?php declare(strict_types = 1);

namespace Bug14215;

class HelloWorld
{
	/**
	 * @param list<int> $array
	 * @param positive-int $index
	 */
	public function positiveIntLessThanCountMinusOne(array $array, int $index): int
	{
		if ($index < count($array) - 1) {
			return $array[$index]; // should not report
		}

		return 0;
	}

	/**
	 * @param list<int> $array
	 * @param positive-int $index
	 */
	public function positiveIntLessThanOrEqualCountMinusOne(array $array, int $index): int
	{
		if ($index <= count($array) - 1) {
			return $array[$index]; // should not report
		}

		return 0;
	}

	/**
	 * @param list<int> $array
	 */
	public function intLessThanOrEqualCountMinusOne(array $array, int $index): int
	{
		if ($index <= count($array) - 1) {
			return $array[$index]; // should error report, could be negative int
		}

		return 0;
	}

	/**
	 * @param list<int> $array
	 * @param int<0, max> $index
	 */
	public function nonNegativeIntLessThanCountMinusOne(array $array, int $index): int
	{
		if ($index < count($array) - 1) {
			return $array[$index]; // should not report
		}

		return 0;
	}

	/**
	 * @param list<int> $array
	 * @param int<0, max> $index
	 */
	public function nonNegativeIntLessThanOrEqualCountMinusOne(array $array, int $index): int
	{
		if ($index <= count($array) - 1) {
			return $array[$index]; // should not report
		}

		return 0;
	}

	/**
	 * @param list<int> $array
	 * @param positive-int $index
	 */
	public function positiveIntLessThanCountMinusTwo(array $array, int $index): int
	{
		if ($index < count($array) - 2) {
			return $array[$index]; // should not report
		}

		return 0;
	}

	/**
	 * @param list<int> $array
	 * @param positive-int $index
	 */
	public function positiveIntLessThanOrEqualCountMinusTwo(array $array, int $index): int
	{
		if ($index <= count($array) - 2) {
			return $array[$index]; // should not report
		}

		return 0;
	}

	/**
	 * @param list<int> $array
	 * @param positive-int $index
	 */
	public function positiveIntLessThanSizeofMinusOne(array $array, int $index): int
	{
		if ($index < sizeof($array) - 1) {
			return $array[$index]; // should not report
		}

		return 0;
	}

	/**
	 * @param list<int> $array
	 * @param positive-int $index
	 */
	public function positiveIntGreaterThanCountMinusOneInversed(array $array, int $index): int
	{
		if (count($array) - 1 > $index) {
			return $array[$index]; // should not report
		}

		return 0;
	}
}
