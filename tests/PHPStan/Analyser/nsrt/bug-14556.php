<?php

namespace Bug14556;

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

final class FinalBarBaz extends FooBar
{
	const FOO_BAR = 'bar';
}

function test(FooBar $foo, BarBaz $bar, FinalBarBaz $baz): void
{
	assertType('mixed', $foo->test());
	assertType('mixed', $bar->test());
	assertType("'bar'", $baz->test());
}
