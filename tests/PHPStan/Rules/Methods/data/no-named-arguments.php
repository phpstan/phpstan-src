<?php // lint >= 8.0

namespace NoNamedArgumentsMethod;

class Foo
{

	/**
	 * @no-named-arguments
	 */
	public function doFoo(int $i): void
	{

	}

}

/**
 * @no-named-arguments
 */
class Bar
{

	public function doFoo(int $i): void
	{

	}

}

function (Foo $f, Bar $b): void {
	$f->doFoo(i: 1);
	$b->doFoo(i: 1);
};
