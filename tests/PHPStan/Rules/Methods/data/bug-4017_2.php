<?php

namespace Bug4017_2;

class Foo
{

}

/**
 * @template T
 */
class Bar
{

	/**
	 * @param T $a
	 */
	public function doFoo($a)
	{

	}

}

/**
 * @extends Bar<Foo>
 */
class Baz extends Bar
{

	/**
	 * @param Foo $a
	 */
	public function doFoo($a)
	{

	}

}

/**
 * @extends Bar<\stdClass>
 */
class Lorem extends Bar
{

	/**
	 * @param Foo $a
	 */
	public function doFoo($a)
	{

	}

}
