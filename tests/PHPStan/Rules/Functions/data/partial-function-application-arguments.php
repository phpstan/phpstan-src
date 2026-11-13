<?php // lint >= 8.6

declare(strict_types = 1);

namespace PartialFunctionApplicationArguments;

function doFoo(int $a, string $b, float $c, bool ...$d): string
{
	return '';
}

function opt(int $a, string $b = 'B', float $c = 1.0): int
{
	return 1;
}

function none(): void
{
}

function byRef(int &$a, string $b): void
{
}

function test(int $i, array $arr): void
{
	doFoo(1, ?, 3.0);
	doFoo(1, ?, ...);
	doFoo(1, 'b', 3.0, x: true, ...);
	none(...);
	byRef($i, ?);
	strtok(?);
	opt(?, c: 2.0);

	doFoo(1, ?);
	none(?);
	none(1, ...);
	opt(1, 'b', 2.0, ?);
	opt(x: ?, a: 1);
	doFoo(1, 'b', 3.0, d: ?);
	opt(?, a: 1);
	opt(a: ?, a: ?);
	opt('x', ?);
	byRef(1, ?);
	opt(...$arr, ?);
	strtok('a', 'b', ?);
	doFoo(a: 1, ?);
	doFoo(..., ?);
}

function closures(): void
{
	$c = fn (int $a, string $b): int => 1;
	$c(1, ?);
	$c('x', ?);
	$c(1, 'b', ?);
}

/**
 * @template T of int
 * @param T $a
 * @return T
 */
function intOnly($a)
{
	return $a;
}

function templates(): void
{
	// templates are resolved from the bound arguments
	usort(?, fn (int $a, int $b): int => $a <=> $b);
	array_map(fn (string $s): int => 1, ?);
	intOnly(1, ...);
	intOnly('x', ...);
}

/** @param array<int> $ints */
function placementsWithUnknownCallees(mixed $cb, array $ints): void
{
	// compile errors whatever the callee is
	undefinedFunction(..., ?);
	undefinedFunction(?, ...$ints);
	$cb(..., ?);
}

function variadicPlaceholderPlacements(): void
{
	doFoo(?, ..., ...);
	doFoo(1, ..., ..., ?);
	doFoo(1, ..., c: 5.0);
}

function multiVariant(): void
{
	// no variant of mt_rand() takes 1 or 3 arguments
	mt_rand(?);
	mt_rand(?, ?, ?, ...);
	mt_rand(1, ?);
	mt_rand(...);
}
