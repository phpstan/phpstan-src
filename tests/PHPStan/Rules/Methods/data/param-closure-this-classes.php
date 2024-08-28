<?php

namespace ParamClosureThisClasses;

trait FooTrait
{

}

class Foo
{

}

class Bar
{

	/**
	 * @param-closure-this Nonexistent $a
	 * @param-closure-this FooTrait $b
	 * @param-closure-this fOO $c
	 */
	public function doFoo(
		callable $a,
		callable $b,
		callable $c
	): void
	{

	}

}
