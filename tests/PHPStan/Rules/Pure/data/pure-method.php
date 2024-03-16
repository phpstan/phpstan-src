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


}
