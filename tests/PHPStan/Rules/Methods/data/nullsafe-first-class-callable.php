<?php // lint >= 8.1

declare(strict_types = 1);

namespace MethodCallableNullsafe;

class Foo
{

	public function doFoo(): int
	{
		return 1;
	}

}

function test(?Foo $foo): void
{
	// fatal error in PHP: "Cannot combine nullsafe operator with Closure creation"
	$foo?->doFoo(...);
}

function testDynamic(?Foo $foo, string $method): void
{
	// dynamic method name - also a fatal error in PHP
	$foo?->{$method}(...);
}


class HelloWorld
{
	public function sayHello(?self $self): void
	{
		$self?->sayHello(...);
	}
}
