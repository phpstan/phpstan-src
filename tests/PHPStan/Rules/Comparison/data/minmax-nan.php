<?php declare(strict_types = 1);

namespace MinMaxNanComparison;

class Foo
{

	public function max(float $f): bool
	{
		return is_nan(max(0.0, $f));
	}

	public function clamp(float $f): bool
	{
		return is_nan(\max(0.0, \min(1.0, $f)));
	}

	public function min(int|float $n): bool
	{
		return \min($n, 100) === 100.0;
	}

}
