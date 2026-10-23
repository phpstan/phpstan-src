<?php declare(strict_types = 1); // lint >= 8.0

namespace StaticCallStringUnions;

class Foo
{

	public static function create(): self
	{
		return new self();
	}

	public function doBar(): void
	{
	}

}

class Bar
{

	public static function create(): self
	{
		return new self();
	}

}

class Baz
{

}

class WithMagic
{

	/**
	 * @param array<mixed> $args
	 */
	public static function __callStatic(string $name, array $args): mixed
	{
		return null;
	}

}

class Runner
{

	public function singleConstantString(): void
	{
		$class = Baz::class;
		$class::create();
	}

	public function unionOfConstantStrings(bool $flag): void
	{
		$class = $flag ? Foo::class : Baz::class;
		$class::create();
	}

	public function unionOfConstantStringsAllValid(bool $flag): void
	{
		$class = $flag ? Foo::class : Bar::class;
		$class::create();
	}

	public function getClassUnion(Foo|Baz $object): void
	{
		$class = get_class($object);
		$class::create();
	}

	/**
	 * @param class-string<Foo>|class-string<Baz> $class
	 */
	public function genericClassStringUnion(string $class): void
	{
		$class::create();
	}

	/**
	 * @param class-string<Baz> $class
	 */
	public function singleGenericClassString(string $class): void
	{
		$class::create();
	}

	/**
	 * @param class-string $class
	 */
	public function plainClassString(string $class): void
	{
		$class::create();
	}

	public function instanceMethodThroughConstantString(): void
	{
		$class = Foo::class;
		$class::doBar();
	}

	public function magicCallStaticThroughConstantString(): void
	{
		$class = WithMagic::class;
		$class::whatever();
	}

	/**
	 * @param class-string<Foo>|class-string<WithMagic> $class
	 */
	public function unionWithMagicMember(string $class): void
	{
		$class::create();
	}

	/**
	 * @param class-string<Foo> $class
	 */
	public function methodExistsNarrowing(string $class): void
	{
		if (method_exists($class, 'doSomethingElse')) {
			$class::doSomethingElse();
		}
	}

	/**
	 * @param class-string<object> $class
	 */
	public function objectClassString(string $class): void
	{
		$class::create();
	}

	/**
	 * @template T of object
	 * @param class-string<T> $class
	 */
	public function templateObjectClassString(string $class): void
	{
		$class::create();
	}

	/**
	 * @param class-string<Foo|Baz> $class
	 */
	public function classStringOfUnion(string $class): void
	{
		$class::create();
	}

	public function traitThroughConstantString(): void
	{
		$trait = SomeTrait::class;
		$trait::traitMethod();
	}

	public function visibilityThroughConstantString(): void
	{
		$class = WithNonPublic::class;
		$class::privateMethod();
		$class::protectedMethod();
	}

	/**
	 * @param class-string<UnknownClass> $class
	 */
	public function unknownClassString(string $class): void
	{
		$class::create();
	}

}

trait SomeTrait
{

	public static function traitMethod(): void
	{
	}

}

class WithNonPublic
{

	private static function privateMethod(): void
	{
	}

	protected static function protectedMethod(): void
	{
	}

}
