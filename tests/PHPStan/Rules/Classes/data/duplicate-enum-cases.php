<?php // lint >= 8.1

namespace DuplicatedEnumCase;

enum Foo
{
	case BAR;
	case FOO;
	case bar;
	case BAR;
}

enum Boo
{
	const BAR = 0;
	const bar = 0;
	case BAR;
}

enum Hoo
{
	case BAR;
	const BAR = 0;
}
