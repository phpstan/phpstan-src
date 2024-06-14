<?php

namespace Bug3019;

use function PHPStan\Testing\assertType;

trait FooTrait
{
	public function doFoo(): void
	{
		assertType('class-string&literal-string', __CLASS__);
		assertType('literal-string', __NAMESPACE__);
	}

	public function doFooBaz(): void
	{
		$key = __CLASS__ === 'Bug3019\Foo' ? 'display' : 'layout';
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
