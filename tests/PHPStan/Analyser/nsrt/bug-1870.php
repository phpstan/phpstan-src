<?php

namespace Bug1870;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(): void
	{
		static $i = 0;
		$i++;
		assertType('int<1, max>', $i);
	}

	public function doBar(): void
	{
		static $i = 0;
		assertType('int<1, max>', ++$i);
	}

}
