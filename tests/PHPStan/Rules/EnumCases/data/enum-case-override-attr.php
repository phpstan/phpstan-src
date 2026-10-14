<?php // lint >= 8.6

namespace EnumCaseOverrideAttr;

interface FooInterface
{
	const FOO = 'foo';
}

enum Foo: string implements FooInterface
{
	#[\Override]
	case FOO = 'foo';

	#[\Override]
	case BAR = 'bar';
}

enum Bar: string implements FooInterface
{
	case FOO = 'foo';
}

enum Baz
{
	#[\Override]
	case BAZ;
}
