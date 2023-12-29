<?php

namespace ConsistentTemplatesStatic;

use function PHPStan\Testing\assertType;

/**
 * @template T
 * @phpstan-consistent-templates
 */
class A
{
	/** @return static<T> */
	public function doFoo()
	{
		return new static();
	}

	/** @return static<T> */
	public static function doBaz()
	{
		return new static();
	}
}

/**
 * @template T
 * @extends A<T>
 */
class B extends A
{
	public function doBar()
	{
		assertType('ConsistentTemplatesStatic\B<T (class ConsistentTemplatesStatic\B, argument)>', $this->doFoo());
		assertType('ConsistentTemplatesStatic\B<T (class ConsistentTemplatesStatic\B, argument)>', B::doBaz());
	}
}

/** @param B<int> $b */
function doFoo(B $b)
{
	assertType('ConsistentTemplatesStatic\B<int>', $b->doFoo());
	assertType('ConsistentTemplatesStatic\B<mixed>', B::doBaz());
}

