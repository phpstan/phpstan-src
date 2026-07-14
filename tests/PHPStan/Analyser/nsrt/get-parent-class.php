<?php

namespace ParentClass;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo()
	{
		assertType('false', get_parent_class());
		assertType('class-string<ParentClass\Foo>|false', get_parent_class($this));
		assertType('class-string<$this(ParentClass\Foo)>', get_class($this));
		assertType('\'ParentClass\\\\Foo\'', get_class());
	}

}

class Bar extends Foo
{

	use FooTrait;

	public function doBar()
	{
		assertType('\'ParentClass\\\\Foo\'', get_parent_class());
		assertType('\'ParentClass\\\\Foo\'|class-string<ParentClass\Bar>', get_parent_class($this));
	}

}

function (string $s) {
	assertType('false', get_parent_class());
	assertType('class-string|false', get_parent_class($s));
	assertType('false', get_parent_class(\ParentClass\Foo::class));
	assertType('class-string|false', get_parent_class(NonexistentClass::class));
	assertType('class-string|false', get_parent_class(1));
	assertType('\'ParentClass\\\\Foo\'', get_parent_class(\ParentClass\Bar::class));
	assertType('false', get_class());
};

trait FooTrait
{

	public function doBaz()
	{
		// Inside a trait a late-static-bound argument is not resolved to a concrete parent.
		assertType('class-string|false', get_parent_class());
		assertType('class-string|false', get_parent_class($this));
		assertType('class-string|false', get_parent_class(static::class));

		// self::class is not late static bound - it is the using class, so it is already a
		// constant class-string here, indistinguishable from writing Bar::class.
		assertType('\'ParentClass\\\\Bar\'', self::class);
		assertType('\'ParentClass\\\\Foo\'', get_parent_class(self::class));
		assertType('\'ParentClass\\\\Foo\'', get_parent_class(Bar::class));
	}

}
