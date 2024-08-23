<?php

namespace IncompatibleSelfOutType;

/**
 * @template T
 */
interface A
{
	/** @phpstan-self-out self */
	public function one();

	/**
	 * @template NewT
	 * @param NewT $param
	 * @phpstan-self-out self<NewT>
	 */
	public function two($param);

	/**
	 * @phpstan-self-out int
	 */
	public function three();

	/**
	 * @phpstan-self-out self|null
	 */
	public function four();
}

/**
 * @template T
 */
class Foo
{

	/** @phpstan-self-out self<int> */
	public static function selfOutStatic(): void
	{

	}

	/**
	 * @phpstan-self-out int&string
	 */
	public function doFoo(): void
	{

	}

	/**
	 * @phpstan-self-out self<int&string>
	 */
	public function doBar(): void
	{

	}

}

class GenericCheck
{

	/**
	 * @phpstan-self-out self<int>
	 */
	public function doFoo(): void
	{

	}

}

/**
 * @template T of \Exception
 * @template U of int
 */
class GenericCheck2
{

	/**
	 * @phpstan-self-out self<\InvalidArgumentException>
	 */
	public function doFoo(): void
	{

	}

	/**
	 * @phpstan-self-out self<\InvalidArgumentException, positive-int, string>
	 */
	public function doFoo2(): void
	{

	}

	/**
	 * @phpstan-self-out self<\InvalidArgumentException, string>
	 */
	public function doFoo3(): void
	{

	}

}
