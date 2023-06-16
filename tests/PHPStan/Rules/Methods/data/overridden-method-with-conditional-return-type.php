<?php

namespace OverriddenMethodWithConditionalReturnType;

class Foo
{

	/**
	 * @return ($p is int ? int : string)
	 */
	public function doFoo($p)
	{

	}

}

class Bar extends Foo
{

	/**
	 * @return ($p is int ? int : string)
	 */
	public function doFoo($p)
	{

	}

}

class Bar2 extends Foo
{

	/**
	 * @return ($p is int ? \stdClass : string)
	 */
	public function doFoo($p)
	{

	}

}
