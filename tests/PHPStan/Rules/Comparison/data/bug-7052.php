<?php // lint >= 8.1

namespace Bug7052;

enum Foo: int
{
	case A = 1;
	case B = 2;
}

class Bar
{

	public function doFoo()
	{
		Foo::A > Foo::B;
		Foo::A < Foo::B;
		Foo::A >= Foo::B;
		Foo::A <= Foo::B;
	}

}
