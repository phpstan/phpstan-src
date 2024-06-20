<?php

namespace PHPStan\Tests;

use ParameterOutTests\FooClass;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\MethodParameterOutTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

class ParamOutMethodExtension implements MethodParameterOutTypeExtension {


	public function isMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
	{
		return
			$methodReflection->getDeclaringClass()->getName() === FooClass::class
			&& $methodReflection->getName() === 'callWithOut'
			&& $parameter->getName() === 'outParam'
		;
	}

	public function getParameterOutTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, ParameterReflection $parameter, Scope $scope): ?Type
	{
		return new IntegerType();
	}
}
