<?php

namespace ArraySum;

use function PHPStan\Analyser\assertType;

class Foo
{
	public function doSumWithInt(
		int ...$integers
	): void
	{
		$sum = array_sum($integers);

		assertType('int', $sum);
	}

	public function doSumWithFloat(
		float ...$floats
	): void
	{
		$sum = array_sum($floats);

		assertType('float', $sum);
	}

	/**
	 * @param array<string, float> $floats
	 */
	public function doSumWithUntypedFloatParameter(
		array $floats
	): void
	{
		$sum = array_sum($floats);

		assertType('float', $sum);
	}

	/**
	 * @param array<string, int> $ints
	 */
	public function doSumWithUntypedIntParameter(
		array $ints
	): void
	{
		$sum = array_sum($ints);

		assertType('int', $sum);
	}

	/**
	 * @param array<string, int|float> $intOrFloat
	 */
	public function doSumWithUntypedIntOrFloatParameter(
		array $intOrFloat
	): void
	{
		$sum = array_sum($intOrFloat);

		assertType('float', $sum);
	}

}
