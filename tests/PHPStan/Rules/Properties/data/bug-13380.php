<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug13380;

class Foo
{
	public function __construct(
		protected string $prop,
	){
	}
}

class Bar extends Foo {
	public string $prop;
}

class Baz extends Foo {
	public string $prop;

	public function __construct()
	{
		// Does not call parent::__construct, so $prop is uninitialized
	}
}
