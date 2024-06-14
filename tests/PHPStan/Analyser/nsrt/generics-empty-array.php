<?php

namespace GenericsEmptyArray;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @template TKey of array-key
	 * @template T
	 * @param array<T, TKey> $a
	 * @return array{TKey, T}
	 */
	public function doFoo(array $a = []): array
	{

	}

	public function doBar()
	{
		assertType('array{*NEVER*, *NEVER*}', $this->doFoo());
		assertType('array{*NEVER*, *NEVER*}', $this->doFoo([]));
	}

}

/**
 * @template TKey of array-key
 * @template T
 */
class ArrayCollection
{

	/**
	 * @param array<TKey, T> $items
	 */
	public function __construct(array $items = [])
	{

	}

}

class Bar
{

	public function doFoo()
	{
		assertType('GenericsEmptyArray\\ArrayCollection<*NEVER*, *NEVER*>', new ArrayCollection());
		assertType('GenericsEmptyArray\\ArrayCollection<*NEVER*, *NEVER*>', new ArrayCollection([]));
	}

}

/**
 * @template TKey of array-key
 * @template T
 */
class ArrayCollection2
{

	public function __construct(array $items = [])
	{

	}

}

class Baz
{

	public function doFoo()
	{
		assertType('GenericsEmptyArray\\ArrayCollection2<(int|string), mixed>', new ArrayCollection2());
		assertType('GenericsEmptyArray\\ArrayCollection2<(int|string), mixed>', new ArrayCollection2([]));
	}

}
