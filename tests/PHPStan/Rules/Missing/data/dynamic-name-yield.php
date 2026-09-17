<?php declare(strict_types = 1);

namespace MissingReturnDynamicNameYield;

use Generator;

class Foo
{

	public function doFoo(): void
	{
	}

}

/** @return Generator<int, int, callable, void> */
function dynamicCallee(): Generator
{
	(yield 1)();
}

/** @return Generator<int, int, callable, void> */
function dynamicCalleeWithKey(): Generator
{
	(yield 5 => 1)();
}

/** @return Generator<int, int, string, void> */
function dynamicMethodName(Foo $foo): Generator
{
	$foo->{yield 1}();
}

/** @return Generator<int, int, string, void> */
function dynamicStaticMethodName(): Generator
{
	Foo::{yield 1}();
}

/** @return Generator<int, int, string, void> */
function dynamicPropertyName(Foo $foo): Generator
{
	echo $foo->{yield 1};
}
