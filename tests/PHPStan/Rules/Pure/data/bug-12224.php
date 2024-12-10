<?php declare(strict_types=1);

namespace PHPStan\Rules\Pure\data;

/**
 * @phpstan-pure
 * @throws \Exception
 */
function pureWithThrows(): void
{
	throw new \Exception();
}

/**
 * @phpstan-pure
 * @throws void
 */
function pureWithThrowsVoid(): void
{
}

/**
 * @phpstan-pure
 * @phpstan-assert int $a
 */
function pureWithAssert(mixed $a): void
{
	if (!is_int($a)) {
		throw new \Exception();
	}
}

class A {
	/**
	 * @phpstan-pure
	 * @throws \Exception
	 */
	public function pureWithThrows(): void
	{
		throw new \Exception();
	}

	/**
	 * @phpstan-pure
	 * @throws void
	 */
	public function pureWithThrowsVoid(): void
	{
	}

	/**
	 * @phpstan-pure
	 * @phpstan-assert int $a
	 */
	public function pureWithAssert(mixed $a): void
	{
		if (!is_int($a)) {
			throw new \Exception();
		}
	}
}

class B {
	/**
	 * @phpstan-pure
	 * @throws \Exception
	 */
	public static function pureWithThrows(): void
	{
		throw new \Exception();
	}

	/**
	 * @phpstan-pure
	 * @throws \Exception
	 */
	public static function pureWithThrowsVoid(): void
	{
	}

	/**
	 * @phpstan-pure
	 * @phpstan-assert int $a
	 */
	public static function pureWithAssert(mixed $a): void
	{
		if (!is_int($a)) {
			throw new \Exception();
		}
	}
}
