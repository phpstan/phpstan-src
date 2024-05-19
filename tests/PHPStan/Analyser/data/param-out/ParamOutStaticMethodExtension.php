<?php

namespace PHPStan\Tests;

use DynamicParameterOutTests\FooClass;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\BooleanType;
use PHPStan\Type\DynamicMethodParameterOutTypeExtension;
use PHPStan\Type\DynamicStaticMethodParameterOutTypeExtension;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

class ParamOutStaticMethodExtension implements DynamicStaticMethodParameterOutTypeExtension {

	public function isStaticMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
	{
		return
			$methodReflection->getDeclaringClass()->getName() === FooClass::class
			&& $methodReflection->getName() === 'staticCallWithOut'
			&& $parameter->getName() === 'outParam'
		;
	}

	public function getParameterOutTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, ParameterReflection $parameter, Scope $scope): ?Type
	{
		return new BooleanType();
	}
}
