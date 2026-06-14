<?php declare(strict_types = 1);

namespace ConditionalThrowsFunction;

use Exception;

/**
 * @param int $x
 * @throws ($x is 0 ? Exception : void)
 */
function inverse(int $x): float
{
	if ($x === 0) {
		throw new Exception('Division by zero.');
	}

	return 1 / $x;
}

/** @throws void */
function callsZero(): void
{
	inverse(0);
}

/** @throws void */
function callsNonZero(): void
{
	inverse(7);
}

/** @throws void */
function callsUnknown(int $x): void
{
	inverse($x);
}

/**
 * @param int<3, 5> $x
 * @throws void
 */
function callsRange(int $x): void
{
	inverse($x);
}

/**
 * @template TKey of int|string
 * @param TKey $key
 * @throws (TKey is int ? void : Exception)
 */
function lookup($key): void
{
	if (is_string($key)) {
		throw new Exception('String keys are not supported.');
	}
}

/** @throws void */
function lookupInt(): void
{
	lookup(1);
}

/** @throws void */
function lookupString(): void
{
	lookup('foo');
}

/**
 * @param int|string $key
 * @throws void
 */
function lookupUnknown($key): void
{
	lookup($key);
}
