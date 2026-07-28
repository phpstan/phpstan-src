<?php

namespace PathConstantsTestTrait;

use function PHPStan\Testing\assertType;

trait PathConstantsTrait
{

	public function doFoo(): void
	{
		assertType('\'path-constants-trait\'', substr(__DIR__, -20));
		assertType('\'PathConstantsTrait.php\'', substr(__FILE__, -22));
	}

}
