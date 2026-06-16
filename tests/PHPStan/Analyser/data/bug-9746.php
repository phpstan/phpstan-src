<?php declare(strict_types = 1);

namespace Bug9746Integration;

class Foo
{

	public function doFoo(): int
	{
		return 1;
	}

}

function test(?Foo $foo): void
{
	// $x?->method(...) is a fatal error in PHP, but PHPStan must not crash on it.
	$foo?->doFoo(...);
}
