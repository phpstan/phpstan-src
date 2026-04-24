<?php // lint >= 8.4

namespace Bug12871;

abstract readonly class A
{

	protected string $foo;

	public function __construct()
	{
		$this->foo = '';
	}

}

readonly class B extends A
{

	public function __construct()
	{
		$this->foo = 'foo';
	}

}

readonly class PrivateSetParent
{

	public private(set) string $bar;

	public function __construct()
	{
		$this->bar = '';
	}

}

readonly class PrivateSetChild extends PrivateSetParent
{

	public function __construct()
	{
		$this->bar = 'nope'; // report - private(set)
	}

}

readonly class NonConstructorChild extends A
{

	public function init(): void
	{
		$this->foo = 'nope'; // report - outside constructor
	}

}
