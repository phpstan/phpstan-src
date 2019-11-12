<?php

namespace IntersectionStatic;

use function PHPStan\Analyser\assertType;

interface Foo
{

	/**
	 * @return static
	 */
	public function returnStatic(): self;

}

interface Bar
{

}

interface Baz
{

	/**
	 * @return static
	 */
	public function returnStatic(): self;

}

class Lorem
{

	/**
	 * @param Foo&Bar $intersection
	 */
	public function doFoo($intersection)
	{
		assertType('IntersectionStatic\Bar&IntersectionStatic\Foo', $intersection);
		assertType('IntersectionStatic\Bar&IntersectionStatic\Foo', $intersection->returnStatic());
	}

	/**
	 * @param Foo&Baz $intersection
	 */
	public function doBar($intersection)
	{
		assertType('IntersectionStatic\Baz&IntersectionStatic\Foo', $intersection);
		assertType('IntersectionStatic\Baz&IntersectionStatic\Foo', $intersection->returnStatic());
	}

}
