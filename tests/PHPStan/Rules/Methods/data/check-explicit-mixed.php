<?php

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

/**
 * @template T
 */
class Baz{
	/** @var T */
	private $t;

	/**
	 * @param T $t
	 */
	public function acceptsT($t): void
	{

	}

	public function doFoo(): void
	{
		$this->acceptsT($this->t);
	}
}
