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

}
