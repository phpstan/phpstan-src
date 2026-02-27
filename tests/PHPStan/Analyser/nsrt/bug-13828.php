<?php

declare(strict_types = 1);

namespace Bug13828;

use function PHPStan\Testing\assertType;

abstract class FooBar
{
	const FOO_BAR = 'foo';

	/** @return static::FOO_BAR */
	public function test(): string
	{
		return static::FOO_BAR;
	}
}

class Foo extends FooBar
{
	const FOO_BAR = 'foo';
}

class Bar extends FooBar
{
	const FOO_BAR = 'bar';
}

class Baz extends FooBar
{
	// Does not override FOO_BAR, inherits 'foo'
}

function () {
	assertType("'foo'", (new Foo())->test()); // Foo::FOO_BAR = 'foo'
	assertType("'bar'", (new Bar())->test()); // Bar::FOO_BAR = 'bar'
	assertType("'foo'", (new Baz())->test()); // Baz inherits FOO_BAR = 'foo'
};
