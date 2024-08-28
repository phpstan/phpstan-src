<?php

namespace SelfOutClasses;

trait FooTrait
{

}

class Foo
{

	/**
	 * @phpstan-self-out Nonexistent
	 */
	public function doFoo(): void
	{

	}

	/**
	 * @phpstan-self-out FooTrait
	 */
	public function doBar(): void
	{

	}

	/**
	 * @phpstan-self-out fOO
	 */
	public function doBaz(): void
	{

	}

}
