<?php

namespace MethodConditionalReturnType;

class Foo
{

	/**
	 * @return ($i is positive-int ? non-empty-array : array)
	 */
	public function fill(int $i): array
	{

	}

	/**
	 * @template T
	 * @param T $p
	 * @return (T is positive-int ? non-empty-array : array)
	 */
	public function fill2(int $i): array
	{

	}

	/**
	 * @template T of int
	 * @param T $p
	 * @return (T is positive-int ? non-empty-array : array)
	 */
	public function fill3(int $i): array
	{

	}

}

/**
 * @template TAboveClass
 */
class Bar
{

	/**
	 * @param int $i
	 * @return (\stdClass is object ? Foo : Bar)
	 */
	public function doFoo(int $i)
	{

	}

	/**
	 * @param int $i
	 * @return (TAboveClass is object ? Foo : Bar)
	 */
	public function doFoo2(int $i)
	{

	}

	/**
	 * @return ($j is object ? Foo : Bar)
	 */
	public function doFoo3(int $i)
	{

	}

	/**
	 * @return ($i is int ? non-empty-array : array)
	 */
	public function fill(int $i): array
	{

	}

	/**
	 * @template T of int
	 * @param T $p
	 * @return (T is int ? non-empty-array : array)
	 */
	public function fill2(int $i): array
	{

	}

	/**
	 * @template T of int
	 * @param T $p
	 * @return (T is int ? non-empty-array : array)
	 */
	public function fill3($i): array
	{

	}

	/**
	 * @return ($i is not int ? non-empty-array : array)
	 */
	public function fill4(int $i): array
	{

	}

}

class Baz
{

	/**
	 * @return ($i is string ? non-empty-array : array)
	 */
	public function fill(int $i): array
	{

	}

	/**
	 * @template T of int
	 * @param T $p
	 * @return (T is string ? non-empty-array : array)
	 */
	public function fill2(int $i): array
	{

	}

	/**
	 * @template T of int
	 * @param T $p
	 * @return (T is string ? non-empty-array : array)
	 */
	public function fill3($i): array
	{

	}

	/**
	 * @return ($i is not string ? non-empty-array : array)
	 */
	public function fill4(int $i): array
	{

	}

}

class MoreVerboseDescription
{

	/**
	 * @param array{foo: string} $i
	 * @return ($i is array{foo: int} ? non-empty-array : array)
	 */
	public function fill(array $i): array
	{

	}

}

/**
 * @template T
 */
class ConditionalThis
{

	/**
	 * @return ($this is self<T> ? true : false)
	 */
	public function foo(): bool
	{

	}

}

class ParamOut
{

	/**
	 * @param-out ($i is int ? 1 : 2) $out
	 */
	public function doFoo(int $i, &$out) {

	}

}
