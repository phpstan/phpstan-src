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

class Bar
{

	public function doBar(ClassReflection $classReflection, object $o): void
	{
		if ($classReflection instanceof ClassReflection) { // yes - do not report

		}

		if ($classReflection instanceof ClassReflection) { // no - do not report

		}

		if ($o instanceof ClassReflection) { // maybe - report

		}
	}

}
