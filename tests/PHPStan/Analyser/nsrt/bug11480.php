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
			assertType("array{'ab', 'xy'}|array{'ab'|'xy'}", $x);
		} else {
			assertType("array{}", $x);
		}
		assertType("array{}|array{'ab', 'xy'}|array{'ab'|'xy'}", $x);

		if (count($x) > 1) {
			assertType("array{'ab', 'xy'}", $x);
		} else {
			assertType("array{}|array{'ab'|'xy'}", $x);
		}
		assertType("array{}|array{'ab', 'xy'}|array{'ab'|'xy'}", $x);

		if (count($x) >= 1) {
			assertType("array{'ab', 'xy'}|array{'ab'|'xy'}", $x);
		} else {
			assertType("array{}", $x);
		}
		assertType("array{}|array{'ab', 'xy'}|array{'ab'|'xy'}", $x);
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
			assertType("array{'ab', 'xy'}|array{'ab'|'xy'}", $x);
		}
		assertType("array{}|array{'ab', 'xy'}|array{'ab'|'xy'}", $x);

		if (count($x) <= 1) {
			assertType("array{}|array{'ab'|'xy'}", $x);
		} else {
			assertType("array{'ab', 'xy'}", $x);
		}
		assertType("array{}|array{'ab', 'xy'}|array{'ab'|'xy'}", $x);
	}

	public function intUnionCount(): void
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

		assertType("array{}|array{'ab', 'xy'}|array{'ab'|'xy'}", $x);
		if (count($x) >= $count) {
			assertType("array{'ab', 'xy'}|array{'ab'|'xy'}", $x);
		} else {
			assertType("array{}", $x);
		}
		assertType("array{}|array{'ab', 'xy'}|array{'ab'|'xy'}", $x);
	}

	/**
	 * @param int<1,2> $count
	 */
	public function intRangeCount($count): void
	{
		$x = [];
		if (rand(0, 1)) {
			$x[] = 'ab';
		}
		if (rand(0, 1)) {
			$x[] = 'xy';
		}

		assertType("array{}|array{'ab', 'xy'}|array{'ab'|'xy'}", $x);
		if (count($x) >= $count) {
			assertType("array{'ab', 'xy'}|array{'ab'|'xy'}", $x);
		} else {
			assertType("array{}|array{'ab'|'xy'}", $x);
		}
		assertType("array{}|array{'ab', 'xy'}|array{'ab'|'xy'}", $x);
	}
}
