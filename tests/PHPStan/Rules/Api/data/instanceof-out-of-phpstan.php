<?php

namespace App\TestInstanceof;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\BetterReflection\SourceLocator\AutoloadSourceLocator;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

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

class Baz
{

	public function doFoo(Type $type): void
	{
		if ($type instanceof ObjectType) {

		}
	}

	public function doBar(Scope $scope): void
	{
		$function = $scope->getFunction();
		if ($function instanceof MethodReflection) {

		}
	}

}
