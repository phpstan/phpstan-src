<?php // lint >= 8.3
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

class WithNativeType
{
	const string FOO_BAR = 'foo';

	/** @return static::FOO_BAR */
	public function test(): string
	{
		return static::FOO_BAR;
	}
}

class WithNativeTypeChild extends WithNativeType
{
	const string FOO_BAR = 'bar';
}

function testNativeType(WithNativeType $foo, WithNativeTypeChild $bar): void
{
	assertType('string', $foo->test());
	assertType('string', $bar->test());
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

class WithPhpDocTypeChild extends WithPhpDocType
{
	/** @var non-empty-string */
	const FOO_BAR = 'bar';
}

function testPhpDocType(WithPhpDocType $foo, WithPhpDocTypeChild $bar): void
{
	assertType('non-empty-string', $foo->test());
	assertType('non-empty-string', $bar->test());
}

class WithBothTypes
{
	/** @var non-empty-string */
	const string FOO_BAR = 'foo';

	/** @return static::FOO_BAR */
	public function test(): string
	{
		return static::FOO_BAR;
	}
}

class WithBothTypesChild extends WithBothTypes
{
	/** @var non-empty-string */
	const string FOO_BAR = 'bar';
}

function testBothTypes(WithBothTypes $foo, WithBothTypesChild $bar): void
{
	assertType('non-empty-string', $foo->test());
	assertType('non-empty-string', $bar->test());
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

class WithFinalConstantChild extends WithFinalConstant
{
}

function testFinalConstant(WithFinalConstant $foo, WithFinalConstantChild $bar): void
{
	assertType("'foo'", $foo->test());
	assertType("'foo'", $bar->test());
}

class WithUntypedConstant
{
	const FOO_BAR = 'foo';

	/** @return static::FOO_BAR */
	public function test(): string
	{
		return static::FOO_BAR;
	}
}

function testUntypedConstant(WithUntypedConstant $foo): void
{
	assertType("'foo'", $foo->test());
}

final class FinalChild extends FooBar
{
	const FOO_BAR = 'baz';
}

function testFinalChild(FinalChild $foo): void
{
	assertType("'baz'", $foo->test());
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
	assertType('non-empty-string', $foo->test());
}
