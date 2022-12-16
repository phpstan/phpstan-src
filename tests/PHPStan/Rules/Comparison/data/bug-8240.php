<?php // lint >= 8.1

namespace Bug8240;

enum Foo
{
	case BAR;
}

function doFoo(Foo $foo): int
{
	return match ($foo) {
		Foo::BAR => 5,
		default => throw new \Exception('This will not be executed')
	};
}

enum Foo2
{
	case BAR;
	case BAZ;
}

function doFoo2(Foo2 $foo): int
{
	return match ($foo) {
		Foo2::BAR => 5,
		Foo2::BAZ => 15,
		default => throw new \Exception('This will not be executed')
	};
}
