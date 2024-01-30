<?php

namespace Bug10509;

/** @template T */
interface One
{

}

/** @template T */
interface Two
{

}

/**
 * @template T of One&Two
 */
class Foo
{

	/**
	 * @return T<int>
	 */
	public function doFoo()
	{

	}

}
