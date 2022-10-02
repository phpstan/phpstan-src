<?php // lint >= 8.0

namespace DynamicMethodThrowTypeExtensionNamedArgs;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodThrowTypeExtension;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use PHPStan\Type\Type;

class MethodThrowTypeExtension implements DynamicMethodThrowTypeExtension
{

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getDeclaringClass()->getName() === Foo::class && $methodReflection->getName() === 'throwOrNot';
	}

	public function getThrowTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
	{
		if (count($methodCall->args) < 1) {
			return $methodReflection->getThrowType();
		}

		$argType = $scope->getType($methodCall->args[0]->value);
		if ((new ConstantBooleanType(true))->isSuperTypeOf($argType)->yes()) {
			return $methodReflection->getThrowType();
		}

		return null;
	}

}

class StaticMethodThrowTypeExtension implements DynamicStaticMethodThrowTypeExtension
{

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getDeclaringClass()->getName() === Foo::class && $methodReflection->getName() === 'staticThrowOrNot';
	}

	public function getThrowTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
	{
		if (count($methodCall->args) < 1) {
			return $methodReflection->getThrowType();
		}

		$argType = $scope->getType($methodCall->args[0]->value);
		if ((new ConstantBooleanType(true))->isSuperTypeOf($argType)->yes()) {
			return $methodReflection->getThrowType();
		}

		return null;
	}

}
