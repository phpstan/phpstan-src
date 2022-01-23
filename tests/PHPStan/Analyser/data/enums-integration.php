<?php // lint >= 8.1

namespace EnumIntegrationTest;

enum Foo
{

	case ONE;
	case TWO;

}


class FooClass
{

	public function doFoo(Foo $foo): void
	{
		$this->doBar($foo);
		$this->doBar(Foo::ONE);
		$this->doBar(Foo::TWO);
		echo Foo::TWO->value;
		echo count(Foo::cases());
	}

	public function doBar(Foo $foo): void
	{

	}

}

enum Bar : string
{

	case ONE = 'one';
	case TWO = 'two';

}

class BarClass
{

	public function doFoo(Bar $bar, string $s): void
	{
		$this->doBar($bar);
		$this->doBar(Bar::ONE);
		$this->doBar(Bar::TWO);
		$this->doBar(Bar::NONEXISTENT);
		echo Bar::TWO->value;
		echo count(Bar::cases());
		$this->doBar(Bar::from($s));
		$this->doBar(Bar::tryFrom($s));
	}

	public function doBar(?Bar $bar): void
	{

	}

}

enum Baz : int
{

	case ONE = 1;
	case TWO = 2;
	const THREE = 3;
	const FOUR = 4;

}

class Lorem
{

	public function doBaz(Foo $foo): void
	{
		if ($foo === Foo::ONE) {
			if ($foo === Foo::TWO) {

			}
		}
	}

}
