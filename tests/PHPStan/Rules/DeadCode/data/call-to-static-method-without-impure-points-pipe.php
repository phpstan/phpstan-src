<?php // lint >= 8.5

namespace CallToStaticMethodWithoutImpurePointsPipe;

class Foo
{

	public static function doFoo(int $o): int
	{
		return 1;
	}

}

function (): void {
	5 |> Foo::doFoo(...);
	5 |> (fn ($x) => Foo::doFoo($x));
};
