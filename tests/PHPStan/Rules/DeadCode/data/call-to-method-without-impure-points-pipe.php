<?php // lint >= 8.5

namespace CallToMethodWithoutImpurePointsPipe;

class Foo
{

	public function maybePure(int $o): int
	{
		return 5;
	}

}

function (): void {
	$foo = new Foo();
	5 |> $foo->maybePure(...);
	5 |> (fn ($x) => $foo->maybePure($x));
};
