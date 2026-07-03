<?php declare(strict_types = 1); // lint >= 8.4

namespace PureUnlessCallableIsImpureFunctionPhp84;

/**
 * @param array<int> $arr
 * @phpstan-pure
 */
function anyWithPureCallback(array $arr): bool
{
	return array_any($arr, static fn (int $x): bool => $x > 0);
}

/**
 * @param array<int> $arr
 * @phpstan-pure
 */
function allWithPureCallback(array $arr): bool
{
	return array_all($arr, static fn (int $x): bool => $x > 0);
}

/**
 * @param array<int> $arr
 * @phpstan-pure
 */
function anyWithImpureCallback(array $arr): bool
{
	return array_any($arr, static function (int $x): bool {
		echo $x;
		return $x > 0;
	});
}

/**
 * @param array<int> $arr
 * @phpstan-pure
 */
function findWithPureCallback(array $arr): ?int
{
	return array_find($arr, static fn (int $x): bool => $x > 0);
}

/**
 * @param array<int> $arr
 * @phpstan-pure
 */
function findKeyWithPureCallback(array $arr): int|string|null
{
	return array_find_key($arr, static fn (int $x): bool => $x > 0);
}

/**
 * @param array<int> $arr
 * @phpstan-pure
 */
function findWithImpureCallback(array $arr): ?int
{
	return array_find($arr, static function (int $x): bool {
		echo $x;
		return $x > 0;
	});
}

/**
 * @param array<int> $arr
 * @phpstan-pure
 */
function findKeyWithImpureCallback(array $arr): int|string|null
{
	return array_find_key($arr, static function (int $x): bool {
		echo $x;
		return $x > 0;
	});
}
