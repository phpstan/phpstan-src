<?php

namespace Bug4017_3;

class Foo
{

}

class Bar
{

	/**
	 * @template T of Foo
	 * @param T $a
	 */
	public function doFoo($a)
	{

	}

}

class Baz extends Bar
{

	/**
	 * @template T of Foo
	 * @param T $a
	 */
	public function doFoo($a)
	{

	}

}

class Lorem extends Bar
{

	/**
	 * @template T of \stdClass
	 * @param T $a
	 */
	public function doFoo($a)
	{

	}

}
