<?php declare(strict_types = 1);

namespace Bug14213;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public static function coalesce_int_range(): void
	{
		$x0 = $x1 = $x2 = null;

		if (rand(0, 1)) {
			$x0 = rand(0, 1);
			$x1 = rand(2, 3);
			$x2 = rand(4, 5);
		}

		$x = (
			$x0 ??
			$x1 ??
			$x2
		);

		assertType('int<0, 5>|null', $x);
	}

	public static function coalesce_int_range_after_maybe_defined(): void
	{
		$x0 = $x1 = $x2 = null;

		if (rand(0, 1)) {
			$maybeDefined = 10;
			$x0 = rand(0, 1);
			$x1 = rand(2, 3);
			$x2 = rand(4, 5);
		}

		$x = (
			$maybeDefined ??
			$x0 ??
			$x1 ??
			$x2
		);

		assertType('int<0, 5>|10|null', $x);
	}
}
