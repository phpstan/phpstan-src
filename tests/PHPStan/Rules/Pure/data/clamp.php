<?php declare(strict_types = 1); // lint >= 8.6

namespace PureClamp;

final class Repro
{

	/**
	 * @phpstan-pure
	 */
	public function withMinMax(float $value): float
	{
		return max(0.0, min(1.0, $value));
	}

	/**
	 * @phpstan-pure
	 */
	public function withClamp(float $value): float
	{
		return clamp($value, 0.0, 1.0);
	}

}
