<?php declare(strict_types = 1); // lint >= 8.1

namespace PureUnlessCallableIsImpureFirstClassCallable;

/**
 * @param array<int> $arr
 * @return array<int>
 * @phpstan-pure
 */
function pureCallingFirstClassCallableWithPureCallback(array $arr): array
{
	$f = array_map(...);
	// The flag travels with the callable's parameters, so a pure callback keeps
	// the indirect call as pure as the direct one.
	return $f(static fn (int $x): int => $x * 2, $arr);
}

/**
 * @param array<int> $arr
 * @return array<int>
 * @phpstan-pure
 */
function pureCallingFirstClassCallableWithImpureCallback(array $arr): array
{
	$f = array_map(...);

	return $f(static function (int $x): int {
		echo $x;

		return $x * 2;
	}, $arr);
}

/**
 * @param array<int> $arr
 * @param callable(int): int $cb
 * @return array<int>
 * @phpstan-pure
 */
function pureCallingFirstClassCallableWithOpaqueCallback(array $arr, callable $cb): array
{
	$f = array_map(...);
	// An opaque callable makes the indirect call possibly impure, same as the
	// direct one.
	return $f($cb, $arr);
}
