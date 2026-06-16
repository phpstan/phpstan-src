<?php declare(strict_types = 1);

namespace Bug9746;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(): int
	{
		return 1;
	}

}

function test(?Foo $foo, Foo $bar): void
{
	// $x?->method(...) is a fatal error in PHP, but PHPStan must not crash on it.
	assertType('(Closure(): int)|null', $foo?->doFoo(...));
	assertType('Closure(): int', $bar?->doFoo(...));

	$c = $foo?->doFoo(...);
	assertType('(Closure(): int)|null', $c);
}
