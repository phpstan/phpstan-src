<?php declare(strict_types = 1);

namespace FunctionCallCallbackAfterArrays;

/**
 * @param array<int> $a
 * @param array<int> $b
 * @param array<int> $c
 */
function doFoo(array $a, array $b, array $c): void
{
	// the arrays before the callback go to parameters that take a callable too
	array_udiff($a, $b, $c, static fn (int $x, int $y): int => $x <=> $y);
	array_udiff($a, $b, $c, static function (int $x, int $y): int {
		echo $x;
		return $x <=> $y;
	});
}
