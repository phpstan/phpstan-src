<?php // lint >= 8.0

declare(strict_types = 1);

namespace ClosureArgumentInTernaryMatchCoalesce;

use function PHPStan\Testing\assertType;

/** @param callable(int): mixed $cb */
function takesCb(callable $cb): void {}

/** @param \Closure(int): mixed $cb */
function takesClosure(\Closure $cb): void {}

/** @param list<callable(string): mixed> $cbs */
function takesCbs(array $cbs): void {}

/**
 * @template T
 * @param list<T> $xs
 * @param callable(T): mixed $cb
 */
function each(array $xs, callable $cb): void {}

/**
 * @param (\Closure(int): mixed)|null $maybe
 * @param (callable(int): mixed)|false $cbOrFalse
 */
function operands(bool $c, int $n, ?\Closure $maybe, $cbOrFalse): void
{
	takesCb($c ? fn ($x) => assertType('int', $x) : fn ($y) => assertType('int', $y));
	takesCb($c ? function ($x) { assertType('int', $x); } : function ($y) { assertType('int', $y); });
	takesCb($cbOrFalse ?: fn ($x) => assertType('int', $x));
	takesCb(match ($n) {
		1 => fn ($x) => assertType('int', $x),
		default => fn ($y) => assertType('int', $y),
	});
	takesClosure($maybe ?? fn ($x) => assertType('int', $x));
}

function nested(bool $c, bool $d, int $n): void
{
	takesCb($c ? ($d ? fn ($x) => assertType('int', $x) : fn ($y) => assertType('int', $y)) : fn ($z) => assertType('int', $z));
	takesCb(match ($n) {
		1 => $c ? fn ($x) => assertType('int', $x) : fn ($y) => assertType('int', $y),
		default => fn ($z) => assertType('int', $z),
	});
	takesCbs($c ? [fn ($x) => assertType('string', $x)] : []);
}

function generic(bool $c): void
{
	each([1, 2], $c ? fn ($x) => assertType('1|2', $x) : fn ($y) => assertType('1|2', $y));
}

function noContext(bool $c): void
{
	$f = $c ? fn ($x) => assertType('mixed', $x) : fn ($y) => assertType('mixed', $y);
}
