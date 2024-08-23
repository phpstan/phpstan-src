<?php

namespace MissingMethodSelfOutType;

/**
 * @template T
 */
class Foo
{

	/**
	 * @phpstan-self-out self<array>
	 */
	public function doFoo(): void
	{

	}

	/**
	 * @phpstan-self-out self
	 */
	public function doFoo2(): void
	{

	}

	/**
	 * @phpstan-self-out Foo<int>&callable
	 */
	public function doFoo3(): void
	{

	}

}
