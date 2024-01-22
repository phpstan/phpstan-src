<?php // lint >= 8.0

namespace CallStaticMethodMixed;

class Foo
{

	/**
	 * @param mixed $explicit
	 */
	public function doFoo(
		$implicit,
		$explicit
	): void
	{
		$implicit::foo();
		$explicit::foo();
	}

	/**
	 * @template T
	 * @param T $t
	 */
	public function doBar($t): void
	{
		$t::foo();
	}

}

class Bar
{

	/**
	 * @param mixed $explicit
	 */
	public function doFoo(
		$implicit,
		$explicit
	): void
	{
		self::doBar($implicit);
		self::doBar($explicit);

		self::acceptImplicitMixed($implicit);
		self::acceptImplicitMixed($explicit);

		self::acceptExplicitMixed($implicit);
		self::acceptExplicitMixed($explicit);

		self::acceptVariadicArguments(...$implicit);
		self::acceptVariadicArguments(...$explicit);
	}

	public static function doBar(int $i): void
	{

	}

	public static function acceptImplicitMixed($mixed): void
	{

	}

	public static function acceptExplicitMixed(mixed $mixed): void
	{

	}

	public static function acceptVariadicArguments(mixed... $args): void
	{

	}

	/**
	 * @template T
	 * @param T $t
	 */
	public function doLorem($t): void
	{
		self::doBar($t);
		self::acceptImplicitMixed($t);
		self::acceptExplicitMixed($t);
		self::acceptVariadicArguments(...$t);
	}

}

class CallableMixed
{

	/**
	 * @param callable(mixed): void $cb
	 */
	public static function callAcceptsExplicitMixed(callable $cb): void
	{

	}

	/**
	 * @param callable(int): void $cb
	 */
	public static function callAcceptsInt(callable $cb): void
	{

	}

	/**
	 * @param callable(): mixed $cb
	 */
	public static function callReturnsExplicitMixed(callable $cb): void
	{

	}

	public static function callReturnsImplicitMixed(callable $cb): void
	{

	}

	/**
	 * @param callable(): int $cb
	 */
	public static function callReturnsInt(callable $cb): void
	{

	}

	public static function doLorem(int $i, mixed $explicitMixed, $implicitMixed): void
	{
		$acceptsInt = function (int $i): void {

		};
		self::callAcceptsExplicitMixed($acceptsInt);
		self::callAcceptsInt($acceptsInt);

		$acceptsExplicitMixed = function (mixed $m): void {

		};
		self::callAcceptsExplicitMixed($acceptsExplicitMixed);
		self::callAcceptsInt($acceptsExplicitMixed);

		$acceptsImplicitMixed = function ($m): void {

		};
		self::callAcceptsExplicitMixed($acceptsImplicitMixed);
		self::callAcceptsInt($acceptsImplicitMixed);

		$returnsInt = function () use ($i): int {
			return $i;
		};
		self::callReturnsExplicitMixed($returnsInt);
		self::callReturnsImplicitMixed($returnsInt);
		self::callReturnsInt($returnsInt);

		$returnsExplicitMixed = function () use ($explicitMixed): mixed {
			return $explicitMixed;
		};
		self::callReturnsExplicitMixed($returnsExplicitMixed);
		self::callReturnsImplicitMixed($returnsExplicitMixed);
		self::callReturnsInt($returnsExplicitMixed);

		$returnsImplicitMixed = function () use ($implicitMixed): mixed {
			return $implicitMixed;
		};
		self::callReturnsExplicitMixed($returnsImplicitMixed);
		self::callReturnsImplicitMixed($returnsImplicitMixed);
		self::callReturnsInt($returnsImplicitMixed);
	}

}
