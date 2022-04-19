<?php // lint >= 8.1

namespace Bug7012;

enum Foo
{
	case BAR;
}

function test(Foo $f = Foo::BAR): void
{
}

function test2(): void
{
	test();
}
