<?php declare(strict_types = 1);

namespace Bug13770;

class HelloWorld
{
	/**
	 * @param list<int> $array
	 * @param positive-int $index
	 */
	public function positiveIntLessThanCount(array $array, int $index): int
	{
		if ($index < count($array)) {
			return $array[$index]; // should not report
		}

		return 0;
	}

	/**
	 * @param list<int> $array
	 * @param positive-int $index
	 */
	public function positiveIntLessThanCountInversed(array $array, int $index): int
	{
		if (count($array) > $index) {
			return $array[$index]; // should not report
		}

		return 0;
	}

	/**
	 * @param list<int> $array
	 * @param int<0, max> $index
	 */
	public function nonNegativeIntLessThanCount(array $array, int $index): int
	{
		if ($index < count($array)) {
			return $array[$index]; // should not report
		}

		return 0;
	}

	/**
	 * @param list<int> $array
	 * @param positive-int $index
	 */
	public function positiveIntLessThanOrEqualCount(array $array, int $index): int
	{
		if ($index <= count($array)) {
			return $array[$index]; // SHOULD still report - off by one
		}

		return 0;
	}

	/**
	 * @param list<int> $array
	 * @param int $index
	 */
	public function anyIntLessThanCount(array $array, int $index): int
	{
		if ($index < count($array)) {
			return $array[$index]; // SHOULD still report - could be negative
		}

		return 0;
	}

	/**
	 * @param list<int> $array
	 * @param int<0, max> $index
	 */
	public function positiveIntOnNormalCountMode(array $array, int $index): int
	{
		if ($index < count($array, COUNT_NORMAL)) {
			return $array[$index]; // should not error
		}

		return 0;
	}

	/**
	 * @param list<int> $array
	 */
	public function anyIntOnUnknownCountMode(array $array, int $index, $countMode): int
	{
		if ($index < count($array, $countMode)) {
			return $array[$index]; // SHOULD still report - could be negative
		}

		return 0;
	}

	public function anyIntOnRecursiveCount(array $array, int $index): int
	{
		if ($index < count($array, COUNT_RECURSIVE)) {
			return $array[$index]; // SHOULD still report - could be negative
		}

		return 0;
	}

	/**
	 * @param list<int> $array
	 * @param 3|6|10 $index
	 */
	public function constantPositiveIntLessThanCount(array $array, int $index): int
	{
		if ($index < count($array)) {
			return $array[$index]; // should not report
		}

		return 0;
	}

	/**
	 * @param list<int> $array
	 * @param -1|3|6|10 $index
	 */
	public function constantMaybeNegativeIntLessThanCount(array $array, int $index): int
	{
		if ($index < count($array)) {
			return $array[$index]; // SHOULD still report - could be negative
		}

		return 0;
	}

	/**
	 * @param list<int> $array
	 * @param 0|positive-int $index
	 */
	public function ZeroOrMoreIntLessThanOrEqualCount(array $array, int $index): int
	{
		if ($index <= count($array)) {
			return $array[$index]; // SHOULD still report - off by one
		}

		return 0;
	}

	/**
	 * @param list<int> $array
	 * @param 0|positive-int $index
	 */
	public function ZeroOrMoreMinusOneIntLessThanOrEqualCount(array $array, int $index): int
	{
		if ($index <= count($array) - 1) {
			return $array[$index];
		}

		return 0;
	}
}
