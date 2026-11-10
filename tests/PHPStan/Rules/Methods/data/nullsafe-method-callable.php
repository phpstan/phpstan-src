<?php // lint >= 8.1

namespace NullsafeMethodCallable;

class Foo
{

	public function doFoo(int $i): string
	{
		return '';
	}

}

function (?Foo $foo, Foo $bar): void {
	$foo?->doFoo(...);
	$bar->doFoo(...);
};
