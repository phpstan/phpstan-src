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
function pureCallingMethodWithPureCallback(Mapper $mapper, array $arr): array
{
	return $mapper->map(static fn (int $x): int => $x * 2, $arr);
}

/**
 * @param array<int> $arr
 * @return array<int>
 * @phpstan-pure
 */
function pureCallingMethodWithImpureCallback(Mapper $mapper, array $arr): array
{
	return $mapper->map(static function (int $x): int {
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
function pureCallingMethodWithOpaqueCallback(Mapper $mapper, array $arr, callable $cb): array
{
	return $mapper->map($cb, $arr);
}

/**
 * @param array<int> $arr
 * @param (callable(int): int)|null $cb
 * @return array<int>
 * @phpstan-pure
 */
function pureWithMaybeNullCallback(array $arr, ?callable $cb): array
{
	// $cb might be null (pure) or an unknown callable, so the call stays possibly impure.
	return array_map($cb, $arr);
}

/**
 * @param array<int> $arr
 * @param (pure-callable(int): int)|int $cb
 * @return array<int>
 * @phpstan-pure
 */
function pureWithMaybeCallablePureCallback(array $arr, $cb): array
{
	// $cb is only maybe-callable, so the call stays possibly impure.
	return array_map($cb, $arr);
}

class Baz
{

	protected $x;

	/**
	 * @pure-unless-callable-is-impure $f
	 */
	public function __construct(callable $f)
	{
		$this->x = $f(1);
	}

}

/**
 * @phpstan-pure
 */
function pureInstantiatingWithPureCallback(): int
{
	new Baz(static fn (int $x): int => $x * 2);

	return 1;
}

/**
 * @phpstan-pure
 */
function pureInstantiatingWithImpureCallback(): int
{
	new Baz(static function (int $x): int {
		echo $x;
		return $x * 2;
	});

	return 1;
}

/**
 * @phpstan-pure
 */
function pureInstantiatingWithOpaqueCallback(callable $cb): int
{
	new Baz($cb);

	return 1;
}

/**
 * @param pure-callable(int): int $pureCb
 * @phpstan-pure
 */
function pureInstantiatingWithPureCallableParam(callable $pureCb): int
{
	// $pureCb is known pure, so the instantiation is pure.
	new Baz($pureCb);

	return 1;
}

/**
 * @param pure-callable(int): int $pureCb
 * @param callable(int): int $cb
 * @phpstan-pure
 */
function pureInstantiatingWithUnionCallback(callable $pureCb, callable $cb, bool $flag): int
{
	// The union pure-callable|callable might be the impure branch, so it stays possibly impure.
	new Baz($flag ? $pureCb : $cb);

	return 1;
}
