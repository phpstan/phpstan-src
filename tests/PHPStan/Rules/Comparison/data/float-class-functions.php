<?php declare(strict_types = 1);

namespace FloatClassFunctions;

class Foo
{

	/**
	 * @param finite-float $finite
	 */
	public function doFoo(float $f, int $i, float $finite): void
	{
		is_nan($f);
		is_finite($f);
		is_infinite($f);

		is_nan($i);
		is_finite($i);
		is_infinite($i);

		is_nan($finite);
		is_finite($finite);
		is_infinite($finite);

		is_nan(1.5);
		is_infinite(INF);

		if (is_nan($f)) {
			is_finite($f);
			return;
		}

		is_nan($f);
		is_finite($f);
	}

	public function coercion(string $numericString): void
	{
		// '1e999' is INF
		is_finite((float) $numericString);
	}

}
