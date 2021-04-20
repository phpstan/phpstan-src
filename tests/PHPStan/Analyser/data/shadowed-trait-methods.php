<?php

namespace ShadowedTraitMethods;

use function PHPStan\Testing\assertType;

trait FooTrait
{

	public function doFoo()
	{
		$a = 1;
		assertType('foo', $a); // doesn't get evaluated
	}

}

trait BarTrait
{

	use FooTrait;

	public function doFoo()
	{
		$a = 2;
		assertType('2', $a);
	}

}

class Foo
{

	use BarTrait;

}
