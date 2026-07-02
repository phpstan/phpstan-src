<?php declare(strict_types = 1);

namespace PureUnlessCallableIsImpureFunction;

/**
 * @param array<int> $arr
 * @return array<int>
 * @phpstan-pure
 */
function pureWithPureCallback(array $arr): array
{
	return array_map(static fn (int $x): int => $x * 2, $arr);
}

/**
 * @param array<int> $arr
 * @return array<int>
 * @phpstan-pure
 */
function pureWithImpureCallback(array $arr): array
{
	return array_map(static function (int $x): int {
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
function pureWithOpaqueCallback(array $arr, callable $cb): array
{
	return array_map($cb, $arr);
}

/**
 * @param callable(int): int $f
 * @param array<int> $arr
 * @return array<int>
 * @pure-unless-callable-is-impure $f
 */
function myMap(callable $f, array $arr): array
{
	$result = [];
	foreach ($arr as $i => $v) {
		$result[$i] = $f($v);
	}

	return $result;
}

/**
 * @param callable(int): int $f
 * @param array<int> $arr
 * @return array<int>
 * @phpstan-pure-unless-callable-is-impure $f
 */
function myMapPhpstanAlias(callable $f, array $arr): array
{
	$result = [];
	foreach ($arr as $i => $v) {
		$result[$i] = $f($v);
	}

	return $result;
}

/**
 * @param array<int> $arr
 * @return array<int>
 * @phpstan-pure
 */
function pureCallingUserlandWithPureCallback(array $arr): array
{
	return myMap(static fn (int $x): int => $x * 2, $arr);
}

/**
 * @param array<int> $arr
 * @return array<int>
 * @phpstan-pure
 */
function pureCallingUserlandAliasWithPureCallback(array $arr): array
{
	return myMapPhpstanAlias(static fn (int $x): int => $x * 2, $arr);
}
