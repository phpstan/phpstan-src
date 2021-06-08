<?php

namespace Bug3753;

class Foo
{
}

class Bar
{

}

class Lorem
{

	/**
	 * @param array<int, Foo&Bar> $foo
	 */
	public function doFoo(array $foo): void
	{

	}

	/**
	 * @param array<float, Bar> $bars
	 */
	public function doBar(array $bars): void
	{

	}

}
