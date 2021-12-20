<?php // lint >= 8.1

namespace MatchEnums;

enum Foo: int
{

	case ONE = 1;
	case TWO = 2;
	case THREE = 3;

}

class Bar
{

	public function doFoo(Foo $foo): int
	{
		return match ($foo) {
			Foo::ONE => 'one',
			Foo::TWO => 'two',
		};
	}

	public function doBar(Foo $foo): int
	{
		return match ($foo) {
			Foo::ONE => 'one',
			Foo::TWO => 'two',
			Foo::THREE => 'three',
		};
	}

	public function doBaz(Foo $foo, Foo $bar): int
	{
		return match ($foo) {
			Foo::ONE => 'one',
			Foo::TWO => 'two',
			Foo::THREE => 'three',
			$bar => 'four',
		};
	}

}
