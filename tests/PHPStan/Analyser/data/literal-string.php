<?php

namespace LiteralString;

use function PHPStan\Testing\assertType;

class Foo
{

	/** @param literal-string $s */
	public function doFoo($s)
	{
		assertType('string', $s);
	}

}
