<?php

namespace InheritDocFromTrait;

use function PHPStan\Testing\assertType;

class Foo implements FooInterface
{
	use FooTrait;
}

trait FooTrait
{

	/**
	 * {@inheritdoc}
	 */
	public function doFoo($string)
	{
		assertType('string', $string);
	}

}

interface FooInterface
{

	/**
	 * @param string $string
	 */
	public function doFoo($string);

}
