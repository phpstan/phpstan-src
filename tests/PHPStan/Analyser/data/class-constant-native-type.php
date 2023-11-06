<?php // lint >= 8.3

namespace ClassConstantNativeType;

use function PHPStan\Testing\assertType;

class Foo
{

	public const int FOO = 1;

	public function doFoo(): void
	{
		assertType('1', self::FOO);
		assertType('int', static::FOO);
		assertType('int', $this::FOO);
	}

}

final class FinalFoo
{

	public const int FOO = 1;

	public function doFoo(): void
	{
		assertType('1', self::FOO);
		assertType('1', static::FOO);
		assertType('1', $this::FOO);
	}

}

class FooWithPhpDoc
{

	/** @var positive-int */
	public const int FOO = 1;

	public function doFoo(): void
	{
		assertType('1', self::FOO);
		assertType('int<1, max>', static::FOO);
		assertType('int<1, max>', $this::FOO);
	}

}

final class FinalFooWithPhpDoc
{

	/** @var positive-int */
	public const int FOO = 1;

	public function doFoo(): void
	{
		assertType('1', self::FOO);
		assertType('1', static::FOO);
		assertType('1', $this::FOO);
	}

}
