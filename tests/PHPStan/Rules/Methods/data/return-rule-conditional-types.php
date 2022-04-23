<?php

namespace ReturnRuleConditionalTypes;

class Foo
{

	/**
	 * @param mixed $p
	 * @return ($p is int ? int : string)
	 */
	public function doFoo($p)
	{
		if (rand(0, 1)) {
			return new \stdClass();
		}

		return 3;
	}

}

class Bar extends Foo
{

	public function doFoo($p)
	{
		if (rand(0, 1)) {
			return new \stdClass();
		}

		return 3;
	}

}

class Bar2 extends Foo
{

	public function doFoo($x)
	{
		if (rand(0, 1)) {
			return new \stdClass();
		}

		return 3;
	}

}
