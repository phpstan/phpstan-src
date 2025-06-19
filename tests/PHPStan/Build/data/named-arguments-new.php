<?php // lint >= 8.1

namespace NamedArgumentsRuleNew;

class Bar
{

}

class Foo
{

	public static function doFoo(int $a, Bar $bar = new Bar(), string $c = 'bar', string $d = 'baz'): void
	{

	}

}

function (): void {
	Foo::doFoo(1, new Bar(), 'bar');
	Foo::doFoo(1, new Bar(), 'baz');

	Foo::doFoo(1, new Bar(), 'bar', 'bar');
};
