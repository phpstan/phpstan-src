<?php

namespace Bug1870;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(): void
	{
		static $i = 0;
		$i++;
		assertType('(float|int)', $i);
	}

	public function doBar(): void
	{
		static $i = 0;
		assertType('(float|int)', ++$i);
	}

}
