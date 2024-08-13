<?php declare(strict_types=1);

namespace Bug11480;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function arrayGreatherThan(): void
	{
		$x = [];
		if (rand(0, 1)) {
			$x[] = 'ab';
		}
		if (rand(0, 1)) {
			$x[] = 'xy';
		}

		if (count($x) > 0) {
			assertType("array{'xy'}|array{0: 'ab', 1?: 'xy'}", $x);
		} else {
			assertType("array{}", $x);
		}
		assertType("array{}|array{'xy'}|array{0: 'ab', 1?: 'xy'}", $x);

		if (count($x) > 1) {
			assertType("array{0: 'ab', 1?: 'xy'}", $x);
		} else {
			assertType("array{}|array{'xy'}|array{0: 'ab', 1?: 'xy'}", $x);
		}
		assertType("array{}|array{'xy'}|array{0: 'ab', 1?: 'xy'}", $x);

		if (count($x) >= 1) {
			assertType("array{'xy'}|array{0: 'ab', 1?: 'xy'}", $x);
		} else {
			assertType("array{}", $x);
		}
		assertType("array{}|array{'xy'}|array{0: 'ab', 1?: 'xy'}", $x);
	}

	public function arraySmallerThan(): void
	{
		$x = [];
		if (rand(0, 1)) {
			$x[] = 'ab';
		}
		if (rand(0, 1)) {
			$x[] = 'xy';
		}

		if (count($x) < 1) {
			assertType("array{}", $x);
		} else {
			assertType("array{'xy'}|array{0: 'ab', 1?: 'xy'}", $x);
		}
		assertType("array{}|array{'xy'}|array{0: 'ab', 1?: 'xy'}", $x);

		if (count($x) <= 1) {
			assertType("array{}|array{'xy'}|array{0: 'ab', 1?: 'xy'}", $x);
		} else {
			assertType("array{0: 'ab', 1?: 'xy'}", $x);
		}
		assertType("array{}|array{'xy'}|array{0: 'ab', 1?: 'xy'}", $x);
	}

	public function intRangeCount(): void
	{
		$count = 1;
		if (rand(0, 1)) {
			$count++;
		}

		$x = [];
		if (rand(0, 1)) {
			$x[] = 'ab';
		}
		if (rand(0, 1)) {
			$x[] = 'xy';
		}

		assertType('1|2', $count);

		assertType("array{}|array{'xy'}|array{0: 'ab', 1?: 'xy'}", $x);
		if (count($x) >= $count) {
			assertType("array{'xy'}|array{0: 'ab', 1?: 'xy'}", $x);
		} else {
			assertType("array{}|array{'xy'}|array{0: 'ab', 1?: 'xy'}", $x);
		}
		assertType("array{}|array{'xy'}|array{0: 'ab', 1?: 'xy'}", $x);
	}
}
