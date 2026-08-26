<?php declare(strict_types = 1); // lint >= 8.1

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

}
