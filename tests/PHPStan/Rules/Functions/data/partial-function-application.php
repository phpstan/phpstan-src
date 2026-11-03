<?php // lint >= 8.6

namespace PartialFunctionApplication;

use Closure;

class Foo
{

	public function __construct(
		public Closure $callback = strlen(?),
	)
	{
	}

	public function doFoo(int $a, string $b): string
	{
		return $b . $a;
	}

	public static function doBar(int $a, int ...$rest): int
	{
		return $a;
	}

}

function doFoo(Foo $foo, ?Foo $maybe): void
{
	$a = str_replace(' ', '-', ?);
	$b = $foo->doFoo(1, ?);
	$c = Foo::doBar(?, ...);
	$d = $foo->doFoo(b: ?, a: 1);
	$e = array_map(strtoupper(?), ['a']);
	$f = strlen(...);
	$g = $maybe?->doFoo(?, 'x');
	$h = $maybe?->doFoo(...);
	$i = new Foo(?);
}
