<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14557;

enum MyEnum: string
{
	case Foo = 'foo';
	case Bar = 'bar';
}

class SomeClass
{

	/** @phpstan-pure */
	public static function pureStaticMethod(): int
	{
		return 1;
	}

	/** @phpstan-impure */
	public static function impureStaticMethod(): int
	{
		echo 'hello';
		return 1;
	}

}

class Foo
{

	/**
	 * @param enum-string<MyEnum> $enum
	 * @phpstan-pure
	 */
	public function doFoo(string $enum): MyEnum
	{
		return $enum::from('foo');
	}

	/**
	 * @param enum-string<MyEnum> $enum
	 * @phpstan-pure
	 */
	public function doBar(string $enum): ?MyEnum
	{
		return $enum::tryFrom('foo');
	}

	/**
	 * @param class-string<MyEnum> $enum
	 * @phpstan-pure
	 */
	public function doBaz(string $enum): MyEnum
	{
		return $enum::from('foo');
	}

	/**
	 * @param class-string<MyEnum> $enum
	 * @phpstan-pure
	 */
	public function doLorem(string $enum): ?MyEnum
	{
		return $enum::tryFrom('foo');
	}

	/**
	 * @phpstan-pure
	 */
	public function fromEnum(MyEnum $enum): MyEnum
	{
		return $enum::from('foo');
	}

	/**
	 * @param class-string<SomeClass> $class
	 * @phpstan-pure
	 */
	public function pureViaClassString(string $class): int
	{
		return $class::pureStaticMethod();
	}

	/**
	 * @param class-string<SomeClass> $class
	 * @phpstan-pure
	 */
	public function impureViaClassString(string $class): int
	{
		return $class::impureStaticMethod(); // error
	}

}
