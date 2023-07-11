<?php

namespace ConstructorReturnType;

class Foo
{

	public function __construct()
	{
	}

}

class Bar
{

	public function __construct(): void
	{
	}

}

trait FooTrait
{

	public function __construct(): void
	{
	}

}

trait BarTrait
{

	public function __construct(): void
	{
	}

}

class UsesFooTrait
{
	use FooTrait;
}

class RenamesBarTrait
{
	use BarTrait {
		__construct as baseConstructor;
	}
}
