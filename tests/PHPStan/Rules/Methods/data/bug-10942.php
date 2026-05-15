<?php // lint >= 8.0

namespace Bug10942;

class A
{
	/**
	 * @param string|($operator is 'in' ? int : never) $sqlRight
	 */
	protected function _renderConditionBinary(string $operator, string $sqlLeft, $sqlRight): string
	{
		return 'x';
	}
}

class B extends A
{
	protected function _renderConditionBinary(string $operator, string $sqlLeft, $sqlRight): string
	{
		return 'y';
	}
}

class C
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

class D extends C
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
