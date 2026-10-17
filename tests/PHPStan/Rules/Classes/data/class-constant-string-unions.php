<?php declare(strict_types = 1); // lint >= 8.2

namespace ClassConstantStringUnions;

class Foo
{

	const BAR = 1;

}

class Bar
{

	const BAR = 2;

}

class Baz
{

}

enum SomeEnum: string
{

	case A = 'a';

	const K = 1;

}

class Runner
{

	public function singleConstantString(): void
	{
		$class = Baz::class;
		echo $class::BAR;
	}

	public function unionOfConstantStrings(bool $flag): void
	{
		$class = $flag ? Foo::class : Baz::class;
		echo $class::BAR;
	}

	public function unionOfConstantStringsAllValid(bool $flag): void
	{
		$class = $flag ? Foo::class : Bar::class;
		echo $class::BAR;
	}

	public function getClassUnion(Foo|Baz $object): void
	{
		$class = get_class($object);
		echo $class::BAR;
	}

	/**
	 * @param class-string<Foo>|class-string<Baz> $class
	 */
	public function genericClassStringUnion(string $class): void
	{
		echo $class::BAR;
	}

	/**
	 * @param class-string<Baz> $class
	 */
	public function singleGenericClassString(string $class): void
	{
		echo $class::BAR;
	}

	/**
	 * @param class-string $class
	 */
	public function plainClassString(string $class): void
	{
		echo $class::BAR;
	}

	public function classPseudoConstantOnConstantStringUnion(bool $flag): void
	{
		$class = $flag ? Foo::class : Baz::class;
		echo $class::class;
	}

	/**
	 * @param class-string<Foo> $class
	 */
	public function classPseudoConstantOnGenericClassString(string $class): void
	{
		echo $class::class;
	}

	public function enumCaseAccessThroughConstantString(): void
	{
		$class = SomeEnum::class;
		echo $class::A->value;
	}

	/**
	 * @param class-string<SomeEnum> $class
	 */
	public function enumCaseAccessThroughGenericClassString(string $class): void
	{
		echo $class::A->value;
	}

	public function visibilityThroughConstantString(): void
	{
		$class = WithNonPublic::class;
		echo $class::PRIVATE_CONST;
		echo $class::PROTECTED_CONST;
	}

	public function traitThroughConstantString(): void
	{
		$trait = SomeTrait::class;
		echo $trait::TRAIT_CONST;
		echo $trait::MISSING;
	}

	/**
	 * @param class-string<Baz> $class
	 */
	public function definedGuards(string $class): void
	{
		if (defined($class . '::BAR')) {
			echo $class::BAR;
		}
		if (defined("$class::BAR")) {
			echo $class::BAR;
		}
		if (defined("{$class}::BAR")) {
			echo $class::BAR;
		}
		echo $class::BAR;
	}

	/**
	 * @param list<class-string<SomeInterface>> $classNames
	 * @param array<string, string> $map
	 */
	public function definedGuardOnInterface(array $classNames, array $map): void
	{
		foreach ($classNames as $className) {
			if (defined($className . '::ENTITY_CLASSNAME') && isset($map[$className::ENTITY_CLASSNAME])) {
				echo $className::ENTITY_CLASSNAME;
			}
		}
	}

}

class WithNonPublic
{

	private const PRIVATE_CONST = 1;

	protected const PROTECTED_CONST = 2;

}

trait SomeTrait
{

	public const TRAIT_CONST = 1;

}

interface SomeInterface
{

}
