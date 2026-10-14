<?php declare(strict_types = 1); // lint >= 8.0

namespace StaticPropertyStringUnions;

class Foo
{

	public static $create;

	public $doBar;

}

class Bar
{

	public static $create;

}

class Baz
{

}

class Runner
{

	public function singleConstantString(): void
	{
		$class = Baz::class;
		echo $class::$create;
	}

	public function unionOfConstantStrings(bool $flag): void
	{
		$class = $flag ? Foo::class : Baz::class;
		echo $class::$create;
	}

	public function unionOfConstantStringsAllValid(bool $flag): void
	{
		$class = $flag ? Foo::class : Bar::class;
		echo $class::$create;
	}

	public function getClassUnion(Foo|Baz $object): void
	{
		$class = get_class($object);
		echo $class::$create;
	}

	/**
	 * @param class-string<Foo>|class-string<Baz> $class
	 */
	public function genericClassStringUnion(string $class): void
	{
		echo $class::$create;
	}

	/**
	 * @param class-string<Baz> $class
	 */
	public function singleGenericClassString(string $class): void
	{
		echo $class::$create;
	}

	/**
	 * @param class-string $class
	 */
	public function plainClassString(string $class): void
	{
		echo $class::$create;
	}

	public function instancePropertyThroughConstantString(): void
	{
		$class = Foo::class;
		echo $class::$doBar;
	}

	/**
	 * @param class-string<Foo> $class
	 */
	public function propertyExistsNarrowing(string $class): void
	{
		if (property_exists($class, 'doesNotExistStatically')) {
			echo $class::$doesNotExistStatically;
		}
	}

	/**
	 * @param class-string<Foo>|class-string<Bar> $class
	 */
	public function genericClassStringUnionAllValid(string $class): void
	{
		echo $class::$create;
	}

	/**
	 * @param class-string<Foo> $class
	 */
	public function singleGenericClassStringValid(string $class): void
	{
		echo $class::$create;
	}

	public function traitThroughConstantString(): void
	{
		$trait = SomeTrait::class;
		echo $trait::$traitProperty;
	}

	public function issetOnMissingProperty(): void
	{
		$class = Baz::class;
		echo isset($class::$missing) ? 1 : 0;
		echo $class::$missing ?? 'default';
		echo empty($class::$missing) ? 1 : 0;
	}

	public function visibilityThroughConstantString(): void
	{
		$class = WithNonPublic::class;
		echo $class::$privateProperty;
		echo $class::$protectedProperty;
	}

}

trait SomeTrait
{

	public static $traitProperty;

}

class WithNonPublic
{

	private static $privateProperty;

	protected static $protectedProperty;

}
