<?php

namespace DynamicMethodReturnGetSingleConditional;

use function PHPStan\Testing\assertType;

abstract class Foo
{
	/**
	 * @return ($input is 1 ? true : false)
	 */
	abstract public function get(int $input): mixed;

	public function doFoo(): void
	{
		assertType('bool', $this->get(0));
		assertType('bool', $this->get(1));
		assertType('bool', $this->get(2));
	}
}
