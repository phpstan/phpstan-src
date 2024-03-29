<?php

namespace ParamOutDefault;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param-out ($flags is 1 ? array<string> : array<int>) $out
	 */
	public function doFoo(&$out, $flags = 1): void
	{

	}

	public function doBar(): void
	{
		$this->doFoo($a);
		assertType('array<string>', $a);

		$this->doFoo($b, 1);
		assertType('array<string>', $b);

		$this->doFoo($c, 2);
		assertType('array<int>', $c);
	}

}
