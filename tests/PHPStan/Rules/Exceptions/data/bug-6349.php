<?php declare(strict_types = 1);

namespace Bug6349;

class TestBinaryOp
{
	/**
	 * @param int $value
	 */
	public function integer(int $value): void
	{
		try {
			99 / $value;
		} catch (\DivisionByZeroError $e) {
		}
		try {
			99 % $value;
		} catch (\DivisionByZeroError $e) {
		}
	}

	/**
	 * @param int<min, -1>|int<1, max> $value
	 */
	public function nonZeroIntegerRange1(int $value): void
	{
		try {
			99 / $value;
		} catch (\DivisionByZeroError $e) {
		}
		try {
			99 % $value;
		} catch (\DivisionByZeroError $e) {
		}
	}

	/**
	 * @param int<min, -1> $value
	 */
	public function nonZeroIntegerRange2(int $value): void
	{
		try {
			99 / $value;
		} catch (\DivisionByZeroError $e) {
		}
		try {
			99 % $value;
		} catch (\DivisionByZeroError $e) {
		}
	}

	/**
	 * @param int<min, 1> $value
	 */
	public function zeroIncludedIntegerRange(int $value): void
	{
		try {
			99 / $value;
		} catch (\DivisionByZeroError $e) {
		}
		try {
			99 % $value;
		} catch (\DivisionByZeroError $e) {
		}
	}

	/**
	 * @param array<string, int> $values
	 */
	public function sayHello(array $values): float
	{
		try {
			return 99 / $values['a'];
		} catch (\DivisionByZeroError $e) {
			return 0.0;
		}
		try {
			return 99 % $values['a'];
		} catch (\DivisionByZeroError $e) {
			return 0.0;
		}
	}

	/**
	 * @param '0' $value
	 */
	public function numericZeroString(string $value): void
	{
		try {
			99 / $value;
		} catch (\DivisionByZeroError $e) {
		}
		try {
			99 % $value;
		} catch (\DivisionByZeroError $e) {
		}
	}

	/**
	 * @param '1' $value
	 */
	public function numericNonZeroString(string $value): void
	{
		try {
			99 / $value;
		} catch (\DivisionByZeroError $e) {
		}
		try {
			99 % $value;
		} catch (\DivisionByZeroError $e) {
		}
	}

	/**
	 * @param float $value
	 */
	public function floatValue(float $value): void
	{
		try {
			99 / $value;
		} catch (\DivisionByZeroError $e) {
		}
		try {
			99 % $value;
		} catch (\DivisionByZeroError $e) {
		}
	}

	/**
	 * @param float $value
	 */
	public function floatNonZeroValue(float $value): void
	{
		if ($value === 0.0) {
			return;
		}
		try {
			99 / $value;
		} catch (\DivisionByZeroError $e) {
		}
		try {
			99 % $value;
		} catch (\DivisionByZeroError $e) {
		}
	}
}

class TestAssignOp
{
	/**
	 * @param int $value
	 */
	public function integer($val, int $value): void
	{
		try {
			$val /= $value;
		} catch (\DivisionByZeroError $e) {
		}
		try {
			$val %= $value;
		} catch (\DivisionByZeroError $e) {
		}
	}

	/**
	 * @param int<min, -1>|int<1, max> $value
	 */
	public function nonZeroIntegerRange1($val, int $value): void
	{
		try {
			$val /= $value;
		} catch (\DivisionByZeroError $e) {
		}
		try {
			$val %= $value;
		} catch (\DivisionByZeroError $e) {
		}
	}

	/**
	 * @param int<min, -1> $value
	 */
	public function nonZeroIntegerRange2($val, int $value): void
	{
		try {
			$val /= $value;
		} catch (\DivisionByZeroError $e) {
		}
		try {
			$val %= $value;
		} catch (\DivisionByZeroError $e) {
		}
	}

	/**
	 * @param int<min, 1> $value
	 */
	public function zeroIncludedIntegerRange($val, int $value): void
	{
		try {
			$val /= $value;
		} catch (\DivisionByZeroError $e) {
		}
		try {
			$val %= $value;
		} catch (\DivisionByZeroError $e) {
		}
	}

	/**
	 * @param array<string, int> $values
	 */
	public function sayHello($val, array $values): float
	{
		try {
			return $val /= $values['a'];
		} catch (\DivisionByZeroError $e) {
			return 0.0;
		}
		try {
			return $val %= $values['a'];
		} catch (\DivisionByZeroError $e) {
			return 0.0;
		}
	}

	/**
	 * @param '0' $value
	 */
	public function numericZeroString($val, string $value): void
	{
		try {
			$val /= $value;
		} catch (\DivisionByZeroError $e) {
		}
		try {
			$val %= $value;
		} catch (\DivisionByZeroError $e) {
		}
	}

	/**
	 * @param '1' $value
	 */
	public function numericNonZeroString(string $value): void
	{
		try {
			$val /= $value;
		} catch (\DivisionByZeroError $e) {
		}
		try {
			$val %= $value;
		} catch (\DivisionByZeroError $e) {
		}
	}

	/**
	 * @param float $value
	 */
	public function floatValue(float $value): void
	{
		try {
			$val /= $value;
		} catch (\DivisionByZeroError $e) {
		}
		try {
			$val %= $value;
		} catch (\DivisionByZeroError $e) {
		}
	}

	/**
	 * @param float $value
	 */
	public function floatNonZeroValue(float $value): void
	{
		if ($value === 0.0) {
			return;
		}
		try {
			$val /= $value;
		} catch (\DivisionByZeroError $e) {
		}
		try {
			$val %= $value;
		} catch (\DivisionByZeroError $e) {
		}
	}
}
