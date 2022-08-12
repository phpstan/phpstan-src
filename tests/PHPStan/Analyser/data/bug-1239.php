<?php

namespace Bug1239;

use function PHPStan\Testing\assertType;

class Foo
{
	public function __construct()
	{
		if (get_class($this) === Bar::class) {
			assertType('$this(Bug1239\Foo)&Bug1239\Bar', $this);
			$this->bat();
		}
	}
}

class Bar extends Foo
{
	public function bat(): void
	{
	}
}
