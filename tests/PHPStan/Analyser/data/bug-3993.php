<?php

namespace Bug3993;

use function PHPStan\Analyser\assertType;

class Foo
{

	public function doFoo($arguments)
	{
		if (!isset($arguments) || count($arguments) === 0) {
			return;
		}

		assertType('mixed~null', $arguments);

		array_shift($arguments);

		assertType('mixed~null', $arguments);
		assertType('int<0, max>', count($arguments));
	}

}
