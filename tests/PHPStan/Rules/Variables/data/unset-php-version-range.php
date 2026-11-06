<?php

namespace UnsetPhpVersionRange;

class Foo
{

	public int $p = 1;

}

function doFoo(Foo $foo): void
{
	unset($foo->p);
}
