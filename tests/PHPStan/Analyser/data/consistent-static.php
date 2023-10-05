<?php

namespace ConsistentStaticGenerics;

use function PHPStan\Testing\assertType;

/**
 * @template T
 * @template U
 */
class Foo
{

	/**
	 * @return static
	 */
	public function doFoo(): self
	{

	}

	/**
	 * @return static<T, U>
	 */
	public function doBar(): self
	{

	}

	/**
	 * @return static<T>
	 */
	public function doBaz(): self
	{

	}

	/**
	 * @return static<U, T>
	 */
	public function doLorem(): self
	{

	}

}

class Bar extends Foo
{

}

function (Bar $bar): void {
	assertType(Bar::class, $bar->doFoo());
	assertType(Bar::class, $bar->doBar());
	assertType('ConsistentStaticGenerics\Foo<mixed>', $bar->doBaz()); // the definition should error
	assertType('ConsistentStaticGenerics\Foo<mixed, mixed>', $bar->doLorem());
};
