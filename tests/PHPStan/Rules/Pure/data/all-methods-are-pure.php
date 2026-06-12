<?php // lint >= 8.0

namespace AllMethodsArePure;

/**
 * @phpstan-all-methods-pure
 */
final class Foo
{
	function test(): int
	{
		return 1;
	}

	function testVoid(): void
	{
	}

	/**
	 * @phpstan-pure
	 */
	function pure(): int
	{
		return 1;
	}

	/**
	 * @phpstan-pure
	 */
	function pureVoid(): void
	{
	}

	/**
	 * @phpstan-impure
	 */
	function impure(): int
	{
		return 1;
	}

	/**
	 * @phpstan-impure
	 */
	function impureVoid(): void
	{
	}
}

/**
 * @phpstan-all-methods-impure
 */
final class Bar
{
	function test(): int
	{
		return 1;
	}

	function testVoid(): void
	{
	}

	/**
	 * @phpstan-pure
	 */
	function pure(): int
	{
		return 1;
	}

	/**
	 * @phpstan-pure
	 */
	function pureVoid(): void
	{
	}

	/**
	 * @phpstan-impure
	 */
	function impure(): int
	{
		return 1;
	}

	/**
	 * @phpstan-impure
	 */
	function impureVoid(): void
	{
	}
}

class Test
{
	/**
	 * @phpstan-impure
	 */
	public static function impure(): void
	{
	}

	/**
	 * @phpstan-pure
	 * @throws void
	 */
	public static function pure(): int
	{
		return 1;
	}
}

/**
 * @phpstan-all-methods-pure
 */
final class SideEffectPure
{
	public function nothingWithImpure(): int {
		Test::impure();
	}
	public function nothingWithPure(): int {
		Test::pure();
	}
	/** @phpstan-impure */
	public function impureWithImpure(): int {
		Test::impure();
	}
	/** @phpstan-impure */
	public function impureWithPure(): int {
		Test::pure();
	}
}

/**
 * @phpstan-all-methods-impure
 */
final class SideEffectImpure
{
	public function nothingWithImpure(): int {
		Test::impure();
	}
	public function nothingWithPure(): int {
		Test::pure();
	}
	/** @phpstan-pure */
	public function pureWithImpure(): int {
		Test::impure();
	}
	/** @phpstan-pure */
	public function pureWithPure(): int {
		Test::pure();
	}
}

/**
 * @phpstan-all-methods-pure
 */
final class FooWithMixed
{
	public function testMixed(): mixed
	{
		return $this->impureMethod();
	}

	/**
	 * @return mixed
	 */
	public function testMixed2()
	{
		return $this->impureMethod();
	}

	/**
	 * @phpstan-impure
	 */
	public function impureMethod(): mixed {
		return time();
	}
}
