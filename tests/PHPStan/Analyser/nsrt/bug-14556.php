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

class WithNativeType
{
	const string FOO_BAR = 'foo';

	/** @return static::FOO_BAR */
	public function test(): string
	{
		return static::FOO_BAR;
	}
}

function testNativeType(WithNativeType $foo): void
{
	assertType('string', $foo->test());
}

class WithPhpDocType
{
	/** @var non-empty-string */
	const FOO_BAR = 'foo';

	/** @return static::FOO_BAR */
	public function test(): string
	{
		return static::FOO_BAR;
	}
}

function testPhpDocType(WithPhpDocType $foo): void
{
	assertType('non-empty-string', $foo->test());
}

class WithFinalConstant
{
	final const FOO_BAR = 'foo';

	/** @return static::FOO_BAR */
	public function test(): string
	{
		return static::FOO_BAR;
	}
}

function testFinalConstant(WithFinalConstant $foo): void
{
	assertType("'foo'", $foo->test());
}

class WithFinalTypedConstant
{
	/** @var non-empty-string */
	final const string FOO_BAR = 'foo';

	/** @return static::FOO_BAR */
	public function test(): string
	{
		return static::FOO_BAR;
	}
}

function testFinalTypedConstant(WithFinalTypedConstant $foo): void
{
	assertType("'foo'", $foo->test());
}
