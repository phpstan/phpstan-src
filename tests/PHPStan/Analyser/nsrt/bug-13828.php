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

function test(): void
{
	assertType("'foo'", (new Foo())->test());
	assertType("'bar'", (new Bar())->test());
}
