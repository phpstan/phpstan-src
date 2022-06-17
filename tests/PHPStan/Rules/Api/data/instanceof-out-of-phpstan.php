<?php

namespace App\TestInstanceof;

use PHPStan\Reflection\BetterReflection\SourceLocator\AutoloadSourceLocator;
use PHPStan\Reflection\ClassReflection;

class Foo
{

	public function doFoo(object $o): void
	{
		if ($o instanceof ClassReflection) {

		}

		if ($o instanceof AutoloadSourceLocator) {

		}
	}

}
