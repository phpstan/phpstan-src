<?php // lint >= 8.0

namespace CheckExplicitMixedMethodCall;

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
		$implicit->foo();
		$explicit->foo();
	}

	/**
	 * @template T
	 * @param T $t
	 */
	public function doBar($t): void
	{
		$t->foo();
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
		$this->doBar($implicit);
		$this->doBar($explicit);

		$this->doBaz($implicit);
		$this->doBaz($explicit);
	}

	public function doBar(int $i): void
	{

	}

	public function doBaz($mixed): void
	{

	}

	/**
	 * @template T
	 * @param T $t
	 */
	public function doLorem($t): void
	{
		$this->doBar($t);
		$this->doBaz($t);
	}

}

class TemplateMixed
{

	/**
	 * @template T
	 * @param T $t
	 */
	public function doFoo($t): void
	{
		$this->doBar($t);
	}

	/**
	 * @param mixed $mixed
	 */
	public function doBar($mixed): void
	{
		$this->doFoo($mixed);
	}

}

class CallableMixed
{

	/**
	 * @param callable(mixed): void $cb
	 */
	public function doFoo(callable $cb): void
	{

	}

	/**
	 * @param callable(int): void $cb
	 */
	public function doBar(callable $cb): void
	{

	}

	/**
	 * @param callable(): mixed $cb
	 */
	public function doFoo2(callable $cb): void
	{

	}

	/**
	 * @param callable(): int $cb
	 */
	public function doBar2(callable $cb): void
	{

	}

	public function doLorem(int $i, mixed $m): void
	{
		$acceptsInt = function (int $i): void {

		};
		$this->doFoo($acceptsInt);
		$this->doBar($acceptsInt);

		$acceptsMixed = function (mixed $m): void {

		};
		$this->doFoo($acceptsMixed);
		$this->doBar($acceptsMixed);

		$returnsInt = function () use ($i): int {
			return $i;
		};
		$this->doFoo2($returnsInt);
		$this->doBar2($returnsInt);

		$returnsMixed = function () use ($m): mixed {
			return $m;
		};
		$this->doFoo2($returnsMixed);
		$this->doBar2($returnsMixed);
	}

}
