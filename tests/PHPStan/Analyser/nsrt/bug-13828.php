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
	assertType('mixed', $foo->test());
	assertType('mixed', $bar->test());
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
	assertType('mixed', $foo->test());
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
	assertType("'foo'", $foo->test());
}

final class FinalClassWithNativeType
{
	const string FOO_BAR = 'foo';

	/** @return static::FOO_BAR */
	public function test(): string
	{
		return static::FOO_BAR;
	}
}

function testFinalClassWithNativeType(FinalClassWithNativeType $foo): void
{
	assertType("'foo'", $foo->test());
}

final class FinalClassWithPhpDocType
{
	/** @var non-empty-string */
	const FOO_BAR = 'foo';

	/** @return static::FOO_BAR */
	public function test(): string
	{
		return static::FOO_BAR;
	}
}

function testFinalClassWithPhpDocType(FinalClassWithPhpDocType $foo): void
{
	assertType("'foo'", $foo->test());
}

final class FinalClassWithBothTypes
{
	/** @var non-empty-string */
	const string FOO_BAR = 'foo';

	/** @return static::FOO_BAR */
	public function test(): string
	{
		return static::FOO_BAR;
	}
}

function testFinalClassWithBothTypes(FinalClassWithBothTypes $foo): void
{
	assertType("'foo'", $foo->test());
}

class WithFinalPhpDocConstant
{
	/** @var non-empty-string */
	final const FOO_BAR = 'foo';

	/** @return static::FOO_BAR */
	public function test(): string
	{
		return static::FOO_BAR;
	}
}

function testFinalPhpDocConstant(WithFinalPhpDocConstant $foo): void
{
	assertType("'foo'", $foo->test());
}

class WithFinalNativeConstant
{
	final const string FOO_BAR = 'foo';

	/** @return static::FOO_BAR */
	public function test(): string
	{
		return static::FOO_BAR;
	}
}

function testFinalNativeConstant(WithFinalNativeConstant $foo): void
{
	assertType("'foo'", $foo->test());
}
