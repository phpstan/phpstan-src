<?php // lint >= 8.1

namespace Bug7746Match;

final class A
{
}

final class B
{
}

final class Test
{
	public function __construct(public readonly A|B $value)
	{
	}
}

function matchIt():void
{
	$t = new Test(new A());
	echo match ($t->value::class) {
		A::class => 'A',
		B::class => 'B'
	};
}

