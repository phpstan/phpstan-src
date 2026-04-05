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

class Bar2 extends Foo
{
	public function __construct(
		string $prop,
	){
		parent::__construct($prop);
	}
}

class Baz2 extends Bar2 {
	public string $prop;
}

class Baz extends Foo {
	public string $prop;

	public function __construct()
	{
		// Does not call parent::__construct, so $prop is uninitialized
	}
}

class Foo3
{
	public function __construct(
		protected string $prop,
	){
	}
}

class Bar3 extends Foo3
{
	public function __construct()
	{
	}
}

class Baz3 extends Bar3 {
	public string $prop;
}
