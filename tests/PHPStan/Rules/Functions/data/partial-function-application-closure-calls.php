<?php // lint >= 8.6

declare(strict_types = 1);

namespace PartialFunctionApplicationClosureCalls;

function doFoo(int $a, string $b, float $c, bool ...$d): string
{
	return '';
}

/**
 * @param int ...$rest
 */
function opt(int $a, string $b = 'B', int ...$rest): void
{
}

function test(): void
{
	// a ? landing in the variadic is named after it: $d0, $d1, ...
	$f = doFoo(1, 'b', 3.0, ?, ?);
	$f(d0: true, d1: false);
	$f(d: true);

	$g = opt(1, 'b', ?, ...);
	$g(rest0: 1);
	$g(1, 2, 3);
	$g(rest0: 'x');

	// a ? is required even for an optional parameter, and keeps its name
	$h = opt(?, ?);
	$h(1);
	$h(a: 1, b: 'x');

	// named placeholders come first after the positional ones
	$i = doFoo(c: ?, a: ?, b: ?);
	$i(1.0, 1, 'b');
	$i(1, 1.0, 'b');
}
