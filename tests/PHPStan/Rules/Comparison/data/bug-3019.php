<?php

namespace Bug3019;

trait FooTrait
{
	public function doFoo(): void
	{
		$key = __CLASS__ === 'Bug3019\Foo' ? 'display' : 'layout';
	}
}

class Foo
{
	use FooTrait;
}

class Bar
{
	use FooTrait;
}
