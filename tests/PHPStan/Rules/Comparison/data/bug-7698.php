<?php // lint >= 8.1

namespace Bug7698Match;

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

function matchIt()
{
	$t = new Test(new A());
	$class = $t->value::class;
	echo match ($class) {
		A::class => 'A',
		B::class => 'B'
	};
}

function matchGetClassString()
{
	$t = new Test(new A());
	echo match (get_class($t->value)) {
		A::class => 'A',
		B::class => 'B'
	};
}
