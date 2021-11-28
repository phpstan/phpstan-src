<?php // lint >= 8.1

namespace EnumTypeAssertions;

use function PHPStan\Testing\assertType;

enum Foo
{

	case ONE;
	case TWO;

}


class FooClass
{

	public function doFoo(): void
	{
		assertType(Foo::class, Foo::ONE);
		assertType(Foo::class, Foo::TWO);
		assertType('*ERROR*', Foo::TWO->value);
		assertType('array<' . Foo::class . '>', Foo::cases());
	}

}

enum Bar : string
{

	case ONE = 'one';
	case TWO = 'two';

}

class BarClass
{

	public function doFoo(string $s): void
	{
		assertType(Bar::class, Bar::ONE);
		assertType(Bar::class, Bar::TWO);
		assertType('string', Bar::TWO->value);
		assertType('array<' . Bar::class . '>', Bar::cases());

		assertType(Bar::class, Bar::from($s));
		assertType(Bar::class . '|null', Bar::tryFrom($s));
	}

}

enum Baz : int
{

	case ONE = 1;
	case TWO = 2;

}

class BazClass
{

	public function doFoo(int $i): void
	{
		assertType(Baz::class, Baz::ONE);
		assertType(Baz::class, Baz::TWO);
		assertType('int', Baz::TWO->value);
		assertType('array<' . Baz::class . '>', Baz::cases());

		assertType(Baz::class, Baz::from($i));
		assertType(Baz::class . '|null', Baz::tryFrom($i));
	}

}
