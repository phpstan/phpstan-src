<?php // lint >= 8.1

namespace DuplicatedEnumCase;

enum Foo
{
	case BAR;
	case FOO;
	case BAR;
}
