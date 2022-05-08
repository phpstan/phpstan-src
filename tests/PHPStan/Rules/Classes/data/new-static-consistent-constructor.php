<?php

namespace NewStaticConsistentConstructor;

/** @phpstan-consistent-constructor  */
class ParentClass
{
	public function __construct()
	{
	}

	public function doFoo(): void
	{
		$foo = new static();
	}
}

class Child extends ParentClass
{
	public function doBar(): void
	{
		$bar = new static();
	}
}

