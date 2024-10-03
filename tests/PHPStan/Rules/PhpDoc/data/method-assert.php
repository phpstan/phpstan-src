<?php

namespace MethodAssert;

class Foo
{
	/**
	 * @phpstan-assert int $i
	 */
	public function fill(int $i): void
	{
	}

	/**
	 * @template T
	 * @param T $p
	 * @phpstan-assert int $i
	 */
	public function fill2(int $i): array
	{
	}

	/**
	 * @template T of int
	 * @param T $p
	 * @phpstan-assert int $i
	 */
	public function fill3(int $i): array
	{
	}

	/**
	 * @template T of int
	 * @param T $p
	 * @phpstan-assert positive-int $i
	 */
	public function fill4(int $i): array
	{
	}

	/**
	 * @phpstan-assert int|string $i
	 */
	public function fill5(int $i): void
	{
	}

	/**
	 * @phpstan-assert string $i
	 */
	public function fill6(int $i): void
	{
	}

	/**
	 * @phpstan-assert positive-int $j
	 */
	public function doFoo(int $i)
	{
	}

	/**
	 * @phpstan-assert !int $i
	 */
	public function negate(int $i): void
	{
	}

	/**
	 * @phpstan-assert !string $i
	 */
	public function negate1(int $i): void
	{
	}

	/**
	 * @phpstan-assert !positive-int $i
	 */
	public function negate2(int $i): void
	{
	}

	/**
	 * @phpstan-assert empty-str $str
	 */
	public function unresolvableAssert(string $str): void
	{
	}

	/**
	 * @phpstan-assert-if-true empty-str $str
	 */
	public function unresolvableAssertIfTrue(string $str): void
	{
	}

	/**
	 * @phpstan-assert-if-false empty-str $str
	 */
	public function unresolvableAssertIfFalse(string $str): void
	{
	}

}
