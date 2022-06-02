<?php

namespace TraitStubs;

use function PHPStan\Testing\assertType;

trait TraitA
{
	public $notTypedFoo;

	/**
	 * @param int $int
	 *
	 * @return int
	 */
	public function doFoo($int)
	{
		return rand($int, $int + 100);
	}
}

class Bar
{
	use TraitA;
}

assertType('string', (new Bar)->doFoo(5));
assertType('int', (new Bar)->notTypedFoo);
