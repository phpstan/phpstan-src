<?php

namespace Bug5086;

use stdClass;
use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(): ?object
	{

	}

	public function doBar(): void
	{
		/** @var stdClass $obj */
		if (!($obj = $this->doFoo())) {
			return;
		}

		assertType(stdClass::class, $obj);
	}

}
