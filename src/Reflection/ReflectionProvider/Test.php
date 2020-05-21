<?php declare(strict_types = 1);

namespace PHPStan\Reflection\ReflectionProvider;

class Test
{

	private \Foo $foo;

	public function __construct(\Foo $foo)
	{
		$this->foo = $foo;
	}

}
