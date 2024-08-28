<?php

namespace ParamOutClassesMethods;

trait FooTrait
{

}

class Foo
{

}

class Bar
{

	/**
	 * @param-out Nonexistent $p
	 * @param-out FooTrait $q
	 * @param-out fOO $r
	 */
	public function doFoo(&$p, &$q, $r): void
	{

	}


}
