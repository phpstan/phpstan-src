<?php // lint >= 8.1

namespace EnumInstantiation;

enum Foo
{
	public function createSelf()
	{
		return new self();
	}

	public function createStatic()
	{
		return new static();
	}
}

class Boo
{
	public static function createFoo() {
		return new Foo();
	}
}
