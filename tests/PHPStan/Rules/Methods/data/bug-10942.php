<?php // lint >= 8.0

namespace Bug10942;

class A
{
	/**
	 * @param string|($x is int ? float : bool) $y
	 */
	public function foo(mixed $x, mixed $y): void
	{
	}

	/**
	 * @return string|($x is int ? float : bool)
	 */
	public function bar(mixed $x): mixed
	{
		return '';
	}
}

class B extends A
{
	/**
	 * @param string|($x is int ? float : bool) $y
	 */
	public function foo(mixed $x, mixed $y): void
	{
	}

	/**
	 * @return string|($x is int ? float : bool)
	 */
	public function bar(mixed $x): mixed
	{
		return '';
	}
}
