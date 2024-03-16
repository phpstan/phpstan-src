<?php

namespace PureMethod;

class Foo
{

	/**
	 * @phpstan-pure
	 */
	public function doFoo(&$p)
	{
		echo 'test';
	}

	/**
	 * @phpstan-pure
	 */
	public function doFoo2(): void
	{
		die;
	}

	/**
	 * @phpstan-pure
	 */
	public function doFoo3(object $obj)
	{
		$obj->foo = 'test';
	}

	public function voidMethod(): void
	{

	}

	/**
	 * @phpstan-impure
	 */
	public function impureVoidMethod(): void
	{
		echo '';
	}

	public function returningMethod(): int
	{

	}

	/**
	 * @phpstan-pure
	 */
	public function pureReturningMethod(): int
	{

	}

	/**
	 * @phpstan-impure
	 */
	public function impureReturningMethod(): int
	{
		echo '';
	}

	/**
	 * @phpstan-pure
	 */
	public function doFoo4()
	{
		$this->voidMethod();
		$this->impureVoidMethod();
		$this->returningMethod();
		$this->pureReturningMethod();
		$this->impureReturningMethod();
		$this->unknownMethod();
	}

	/**
	 * @phpstan-pure
	 */
	public function doFoo5()
	{
		self::voidMethod();
		self::impureVoidMethod();
		self::returningMethod();
		self::pureReturningMethod();
		self::impureReturningMethod();
		self::unknownMethod();
	}


}

class PureConstructor
{

	/**
	 * @phpstan-pure
	 */
	public function __construct()
	{

	}

}

class ImpureConstructor
{

	/**
	 * @phpstan-impure
	 */
	public function __construct()
	{
		echo '';
	}

}

class PossiblyImpureConstructor
{

	public function __construct()
	{

	}

}

class TestConstructors
{

	/**
	 * @phpstan-pure
	 */
	public function doFoo(string $s)
	{
		new PureConstructor();
		new ImpureConstructor();
		new PossiblyImpureConstructor();
		new $s();
	}

}

class ActuallyPure
{

	/**
	 * @phpstan-impure
	 */
	public function doFoo()
	{

	}

}
