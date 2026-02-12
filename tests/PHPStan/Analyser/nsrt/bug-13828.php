<?php

namespace Bug13828;

use function PHPStan\Testing\assertType;

class FooBar
{
	const FOO_BAR = 'foo';

	/** @return static::FOO_BAR */
	public function test(): string
	{
		return static::FOO_BAR;
	}
}

class BarBaz extends FooBar
{
	const FOO_BAR = 'bar';
}

function test(FooBar $foo, BarBaz $bar): void
{
	assertType("'foo'", $foo->test());
	assertType("'bar'", $bar->test());
}

final class FinalFoo
{
	const FOO_BAR = 'foo';

	/** @return static::FOO_BAR */
	public function test(): string
	{
		return static::FOO_BAR;
	}
}

function testFinal(FinalFoo $foo): void
{
	assertType("'foo'", $foo->test());
}
