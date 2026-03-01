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

		assertType('10|int<0, 5>|null', $x);
	}

	public static function coalesce_int_range_with_last_non_nullable(): void
	{
		$x0 = $x1 = null;
		$x2 = 20;

		if (rand(0, 1)) {
			$x0 = rand(0, 1);
			$x1 = rand(2, 3);
			$x2 = rand(4, 5);
		}

		$x = (
			$x0 ??
			$x1 ??
			$x2 // cannot be null
		);

		assertType('20|int<0, 5>', $x);
	}
}
