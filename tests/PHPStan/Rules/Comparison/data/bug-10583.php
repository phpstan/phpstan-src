<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug10583;

function foo(int $bar, int $baz): int
{
	return match (true) {
		$bar === $baz => 1,
		$bar !== $baz => 2,
	};
}

function foo2(int $bar, int $baz): int
{
	return match (true) {
		$bar !== $baz => 2,
		$bar === $baz => 1,
	};
}

function swapped(int $bar, int $baz): int
{
	return match (true) {
		$bar !== $baz => 2,
		$baz === $bar => 1,
	};
}

function looseEqual(string $bar, string $baz): int
{
	return match (true) {
		$bar != $baz => 2,
		$bar == $baz => 1,
	};
}

function looseEqualSwapped(string $bar, string $baz): int
{
	return match (true) {
		$bar == $baz => 2,
		$baz != $bar => 1,
	};
}

function smaller(int $bar, int $baz): int
{
	return match (true) {
		$bar < $baz => 2,
		$baz <= $bar => 1,
	};
}

function greater(int $bar, int $baz): int
{
	return match (true) {
		$bar > $baz => 2,
		$bar <= $baz => 1,
	};
}

function floats(float $bar, float $baz): int
{
	return match (true) {
		$bar < $baz => 2,
		$bar >= $baz => 1,
	};
}

function matchFalse(int $bar, int $baz): int
{
	return match (false) {
		$bar === $baz => 1,
		$bar !== $baz => 2,
	};
}
