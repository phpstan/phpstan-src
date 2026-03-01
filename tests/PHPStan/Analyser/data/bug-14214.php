<?php declare(strict_types = 1);

namespace Bug14214;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public static function is_too_slow(): void
	{
		$x0 = $x1 = $x2 = $x3 = $x4 = $x5 = $x6 = $x7 = $x8 = $x9 = $x10 = null;
		$x11 = $x12 = $x13 = $x14 = $x15 = $x16 = null;

		if (rand(0, 1)) {
			$x0 = rand(0, 1);
		}
		if (rand(0, 1)) {
			$x1 = rand(2, 3);
		}
		if (rand(0, 1)) {
			$x2 = rand(4, 5);
		}
		if (rand(0, 1)) {
			$x3 = rand(6, 7);
		}
		if (rand(0, 1)) {
			$x4 = rand(8, 9);
		}
		if (rand(0, 1)) {
			$x5 = rand(10, 11);
		}
		if (rand(0, 1)) {
			$x6 = rand(12, 13);
		}
		if (rand(0, 1)) {
			$x7 = rand(14, 15);
		}
		if (rand(0, 1)) {
			$x8 = rand(16, 17);
		}
		if (rand(0, 1)) {
			$x9 = rand(18, 19);
		}
		if (rand(0, 1)) {
			$x10 = rand(20, 21);
		}
		if (rand(0, 1)) {
			$x11 = rand(22, 23);
		}
		if (rand(0, 1)) {
			$x12 = rand(24, 25);
		}
		if (rand(0, 1)) {
			$x13 = rand(26, 27);
		}
		if (rand(0, 1)) {
			$x14 = rand(28, 29);
		}
		if (rand(0, 1)) {
			$x15 = rand(30, 31);
		}
		if (rand(0, 1)) {
			$x16 = rand(32, 33);
		}

		$x = (
			$x0 ??
			$x1 ??
			$x2 ??
			$x3 ??
			$x4 ??
			$x5 ??
			$x6 ??
			$x7 ??
			$x8 ??
			$x9 ??
			$x10 ??
			$x11 ??
			$x12 ??
			$x13 ??
			$x14 ??
			$x15 ??
			$x16
		);

		assertType('int<0, 33>|null', $x);
	}
}
