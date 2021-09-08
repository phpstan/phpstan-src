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
