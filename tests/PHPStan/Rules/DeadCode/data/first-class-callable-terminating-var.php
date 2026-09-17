<?php // lint >= 8.1

declare(strict_types = 1);

namespace UnreachableFirstClassCallable;

class Foo
{

	public function doFoo(): void
	{
	}

}

function methodFirstClassCallable(): void
{
	$c = (exit())->doFoo(...);
	echo 'unreachable';
}
