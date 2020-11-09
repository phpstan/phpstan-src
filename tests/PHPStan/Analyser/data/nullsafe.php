<?php

namespace Nullsafe;

use function PHPStan\Analyser\assertType;

class Foo
{

	public function doFoo(?\Exception $e)
	{
		assertType('string|null', $e?->getMessage());
		assertType('Exception|null', $e);

		assertType('Throwable|null', $e?->getPrevious());
		assertType('string|null', $e?->getPrevious()?->getMessage());

		$e?->getMessage(assertType('Exception', $e));
	}

}
