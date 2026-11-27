<?php declare(strict_types = 1);

namespace ClosureArrayArgumentBeforeTemplateSibling;

use function PHPStan\Testing\assertType;

/**
 * @template T
 * @param list<callable(T): mixed> $cbs
 * @param T $value
 */
function each(array $cbs, $value): void {}

/**
 * @template T
 * @param T $value
 * @param list<callable(T): mixed> $cbs
 */
function eachSwapped($value, array $cbs): void {}

/**
 * @template T
 * @param list<array<string, callable(T): mixed>> $cbs
 * @param T $value
 */
function eachNested(array $cbs, $value): void {}

function test(): void
{
	each([fn ($x) => assertType('1', $x)], 1);
	each([function ($x) {
		assertType('1', $x);
	}, fn ($y) => assertType('1', $y)], 1);
	eachSwapped(1, [fn ($x) => assertType('1', $x)]);
	eachNested([['a' => fn ($x) => assertType('1', $x)], ['b' => fn ($y) => assertType('1', $y)]], 1);
}
