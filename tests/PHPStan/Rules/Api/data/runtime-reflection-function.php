<?php

namespace RuntimeReflectionFunction;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;

class Foo
{

	public function doFoo(object $o): void
	{
		if (is_a($o, \stdClass::class)) {

		}
	}

}

class Bar implements DynamicMethodReturnTypeExtension
{

	public function getClass(): string
	{
		// TODO: Implement getClass() method.
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		// TODO: Implement isMethodSupported() method.
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
	{
		// TODO: Implement getTypeFromMethodCall() method.
	}

	public function doFoo(object $o): void
	{
		if (is_a($o, \stdClass::class)) {

		}
		if (is_subclass_of($o, \stdClass::class)) {

		}
		$p = class_parents($o);
		$i = class_implements($o);
		$t = class_uses($o);
		$d = get_declared_classes();
	}

}
