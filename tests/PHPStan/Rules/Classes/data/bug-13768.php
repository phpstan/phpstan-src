<?php // lint >= 8.1

namespace Bug13768;

enum Order {
	case U;
	case A = 1.5;
	case B = 2.5;
	case C = 3;
	case D = '3';
	case E = false;
	case F = Foo::A;
}

class Foo
{
	public const A = 1;
}

enum Backed: int {
	case One = Foo::A;
	case Two = Foo::A;
}
