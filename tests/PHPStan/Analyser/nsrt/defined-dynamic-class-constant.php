<?php declare(strict_types = 1);

namespace DefinedDynamicClassConstant;

use function PHPStan\Testing\assertType;

final class WithConstant
{

	const FOO = 1;

}

class WithoutConstant
{

}

class StringableWithoutConstant
{

	public function __toString(): string
	{
		return WithConstant::class;
	}

}

class Foo
{

	/**
	 * @param class-string<WithoutConstant> $class
	 */
	public function concat(string $class): void
	{
		if (defined($class . '::FOO')) {
			assertType('mixed', $class::FOO);
		}
	}

	/**
	 * @param class-string<WithoutConstant> $class
	 */
	public function interpolated(string $class): void
	{
		if (defined("$class::FOO")) {
			assertType('mixed', $class::FOO);
		}
		if (defined("{$class}::FOO")) {
			assertType('mixed', $class::FOO);
		}
	}

	public function plainString(string $class): void
	{
		if (defined($class . '::FOO')) {
			assertType('mixed', $class::FOO);
		}
	}

	/**
	 * @param class-string<WithConstant> $class
	 */
	public function existingConstantKeepsItsType(string $class): void
	{
		assertType('1', $class::FOO);
		if (defined($class . '::FOO')) {
			assertType('1', $class::FOO);
		}
	}

	/**
	 * @param StringableWithoutConstant|class-string<WithoutConstant> $class
	 */
	public function maybeString($class): void
	{
		// the concatenation casts an object to string, $class::FOO reads its class,
		// so only an expression that is always a string is narrowed
		if (defined($class . '::FOO')) {
			assertType('*ERROR*', $class::FOO);
		}
	}

}
