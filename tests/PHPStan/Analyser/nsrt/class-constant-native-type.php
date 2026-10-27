<?php // lint >= 8.3

namespace ClassConstantNativeType;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class Foo
{

	public const int FOO = 1;

	public function doFoo(): void
	{
		assertType('1', self::FOO);
		assertType('int', static::FOO);
		assertType('int', $this::FOO);
		assertNativeType('1', self::FOO);
		assertNativeType('int', static::FOO);
		assertNativeType('int', $this::FOO);
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
		assertNativeType('1', self::FOO);
		assertNativeType('int', static::FOO);
		assertNativeType('int', $this::FOO);
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

class FooWithOnlyPhpDoc
{

	/** @var non-empty-string */
	public const FOO = 'foo';

	/** @var non-empty-string */
	final public const FINAL_FOO = 'foo';

	public function doFoo(self $foo): void
	{
		assertType('\'foo\'', self::FOO);
		assertType('non-empty-string', static::FOO);
		assertType('non-empty-string', $foo::FOO);
		assertNativeType('\'foo\'', self::FOO);
		assertNativeType('mixed', static::FOO);
		assertNativeType('mixed', $foo::FOO);

		assertType('non-empty-string', static::FINAL_FOO);
		assertNativeType('\'foo\'', static::FINAL_FOO);
	}

}

final class FinalFooWithOnlyPhpDoc
{

	/** @var non-empty-string */
	public const FOO = 'foo';

	public function doFoo(): void
	{
		assertType('\'foo\'', static::FOO);
		assertNativeType('\'foo\'', static::FOO);
	}

}
