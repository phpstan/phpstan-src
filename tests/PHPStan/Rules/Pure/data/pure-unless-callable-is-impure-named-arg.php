<?php // lint >= 8.0

declare(strict_types = 1);

namespace PureUnlessCallableIsImpureNamedArg;

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

class Mapper
{

	/**
	 * @param callable(int): int $f
	 * @param array<int> $arr
	 * @return array<int>
	 * @pure-unless-callable-is-impure $f
	 */
	public function map(callable $f, array $arr): array
	{
		$result = [];
		foreach ($arr as $i => $v) {
			$result[$i] = $f($v);
		}

		return $result;
	}

}

/**
 * @param array<int> $arr
 * @return array<int>
 * @phpstan-pure
 */
function pureCallingUserlandWithPureCallbackByName(array $arr): array
{
	return myMap(f: static fn (int $x): int => $x * 2, arr: $arr);
}

/**
 * @param array<int> $arr
 * @return array<int>
 * @phpstan-pure
 */
function pureCallingUserlandWithImpureCallbackByName(array $arr): array
{
	return myMap(f: static function (int $x): int {
		echo $x;
		return $x * 2;
	}, arr: $arr);
}

/**
 * @param array<int> $arr
 * @param callable(int): int $cb
 * @return array<int>
 * @phpstan-pure
 */
function pureCallingUserlandWithOpaqueCallbackByName(array $arr, callable $cb): array
{
	return myMap(f: $cb, arr: $arr);
}

/**
 * @param array<int> $arr
 * @return array<int>
 * @phpstan-pure
 */
function pureWithImpureCallbackByName(array $arr): array
{
	return array_map(callback: static function (int $x): int {
		echo $x;
		return $x * 2;
	}, array: $arr);
}

/**
 * @param array<int> $arr
 * @param callable(int): int $cb
 * @return array<int>
 * @phpstan-pure
 */
function pureWithOpaqueCallbackByName(array $arr, callable $cb): array
{
	return array_map(callback: $cb, array: $arr);
}

/**
 * @param array<int> $arr
 * @return array<int>
 * @phpstan-pure
 */
function pureCallingUserlandWithPureCallbackShuffled(array $arr): array
{
	// Arguments are passed in shuffled order using names, so name-based matching
	// (not positional matching) determines which argument is judged for purity.
	return myMap(arr: $arr, f: static fn (int $x): int => $x * 2);
}

/**
 * @param array<int> $arr
 * @return array<int>
 * @phpstan-pure
 */
function pureCallingUserlandWithImpureCallbackShuffled(array $arr): array
{
	return myMap(arr: $arr, f: static function (int $x): int {
		echo $x;
		return $x * 2;
	});
}

/**
 * @param array<int> $arr
 * @return array<int>
 * @phpstan-pure
 */
function pureCallingMethodWithImpureCallbackByName(Mapper $mapper, array $arr): array
{
	return $mapper->map(f: static function (int $x): int {
		echo $x;
		return $x * 2;
	}, arr: $arr);
}
