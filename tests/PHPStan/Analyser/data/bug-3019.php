<?php

namespace Bug3019;

use function PHPStan\Testing\assertType;

trait FooTrait
{
	public function doFoo(): void
	{
		assertType('string', __CLASS__);
		assertType('string', __NAMESPACE__);
	}
}

class Foo
{
	use FooTrait;

	public function doFooBar(): void
	{
		assertType("'Bug3019\\\Foo'", __CLASS__);
		assertType("'Bug3019'", __NAMESPACE__);
	}
}

class Bar
{
	use FooTrait;
}
