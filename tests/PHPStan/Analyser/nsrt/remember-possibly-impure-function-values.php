<?php

namespace RememberPossiblyImpureFunctionValues;

use function PHPStan\Testing\assertType;

class Foo
{
	private static int $counter = 0;

	/** @phpstan-pure */
	public function pure(): int
	{
		return 1;
	}

	public function maybePure(): int
	{
		return 1;
	}

	/** @phpstan-impure */
	public function impure(): int
	{
		return rand(0, 1);
	}

	public static function getCounter(): int
	{
		return self::$counter;
	}

	public function test(): void
	{
		if ($this->pure() === 1) {
			assertType('1', $this->pure());
		}

		if ($this->maybePure() === 1) {
			assertType('1', $this->maybePure());
		}

		if ($this->impure() === 1) {
			assertType('int', $this->impure());
		}
	}

	public function testStatic(): void
	{
		if (self::getCounter() === 1) {
			$this->pure();
			assertType('1', self::getCounter());
		}

		if (self::getCounter() === 1) {
			$this->maybePure();
			assertType('1', self::getCounter());
		}

		if (self::getCounter() === 1) {
			$this->impure();
			assertType('int', self::getCounter());
		}
	}

}

class FooStatic
{

	/** @phpstan-pure */
	public static function pure(): int
	{
		return 1;
	}

	public static function maybePure(): int
	{
		return 1;
	}

	/** @phpstan-impure */
	public static function impure(): int
	{
		return rand(0, 1);
	}

	public function test(): void
	{
		if (self::pure() === 1) {
			assertType('1', self::pure());
		}

		if (self::maybePure() === 1) {
			assertType('1', self::maybePure());
		}

		if (self::impure() === 1) {
			assertType('int', self::impure());
		}
	}

}

/** @phpstan-pure */
function pure(): int
{
	return 1;
}

function maybePure(): int
{
	return 1;
}

/** @phpstan-impure */
function impure(): int
{
	return rand(0, 1);
}

function test(): void
{
	if (pure() === 1) {
		assertType('1', pure());
	}

	if (maybePure() === 1) {
		assertType('1', maybePure());
	}

	if (impure() === 1) {
		assertType('int', impure());
	}
}
