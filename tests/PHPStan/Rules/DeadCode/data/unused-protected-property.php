<?php // lint >= 8.4

namespace UnusedProtectedProperty;

class Foo
{

	protected $foo;

	protected string $bar;

	final protected string $baz;

	public function __construct()
	{
		$this->foo = 1;
	}

	public function getFoo()
	{
		return $this->foo;
	}

}

final class Bar
{

	protected $foo;

	protected string $bar;

	final protected string $baz;

	public function __construct()
	{
		$this->foo = 1;
	}

	public function getFoo()
	{
		return $this->foo;
	}

}
