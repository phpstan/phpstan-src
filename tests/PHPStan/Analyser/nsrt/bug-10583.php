<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug10583Types;

use function PHPStan\Testing\assertType;

function identical(int $bar, int $baz): void
{
	match (true) {
		$bar !== $baz => 2,
		default => assertType('array{true, false, true, false}', [$bar === $baz, $bar !== $baz, $baz === $bar, $baz !== $bar]),
	};
	match (true) {
		$bar === $baz => 2,
		default => assertType('array{false, true, false, true}', [$bar === $baz, $bar !== $baz, $baz === $bar, $baz !== $bar]),
	};
}

function equal(string $bar, string $baz): void
{
	match (true) {
		$bar != $baz => 2,
		default => assertType('array{true, false, true, false}', [$bar == $baz, $bar != $baz, $baz == $bar, $baz != $bar]),
	};
}

function relational(int $bar, int $baz, float $f, float $g): void
{
	match (true) {
		$bar < $baz => 2,
		default => assertType('array{false, false, true, true}', [$bar < $baz, $baz > $bar, $bar >= $baz, $baz <= $bar]),
	};
	match (true) {
		$bar <= $baz => 2,
		default => assertType('array{false, false, true, true}', [$bar <= $baz, $baz >= $bar, $bar > $baz, $baz < $bar]),
	};
	match (true) {
		$f < $g => 2,
		default => assertType('array{false, false, bool}', [$f < $g, $g > $f, $f >= $g]),
	};
}

function reassigned(int $bar, int $baz): void
{
	match (true) {
		$bar !== $baz => 2,
		default => assertType('bool', ($bar = $bar + 1) === $baz),
	};
}
