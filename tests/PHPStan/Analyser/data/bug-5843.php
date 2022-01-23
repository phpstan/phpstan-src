<?php // lint >= 8.0

namespace Bug5843;

use function PHPStan\Testing\assertType;

class Foo
{

	function doFoo(object $object): void
	{
		assertType('class-string', $object::class);
		switch ($object::class) {
			case \DateTime::class:
				assertType(\DateTime::class, $object);
				break;
			case \Throwable::class:
				assertType(\Throwable::class, $object);
				break;
		}
	}

}

class Bar
{

	function doFoo(object $object): void
	{
		match ($object::class) {
			\DateTime::class => assertType(\DateTime::class, $object),
			\Throwable::class => assertType(\Throwable::class, $object),
		};
	}

}
