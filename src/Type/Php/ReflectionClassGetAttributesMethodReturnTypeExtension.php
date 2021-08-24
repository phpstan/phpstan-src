<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

class ReflectionClassGetAttributesMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return \ReflectionClass::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'getAttributes';
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
	{
		if (count($methodCall->args) === 0) {
			return $this->getDefaultReturnType($scope, $methodCall, $methodReflection);
		}
		$argType = $scope->getType($methodCall->args[0]->value);

		if ($argType instanceof ConstantStringType) {
			$classType = new ObjectType($argType->getValue());
		} elseif ($argType instanceof GenericClassStringType) {
			$classType = $argType->getGenericType();
		} else {
			return $this->getDefaultReturnType($scope, $methodCall, $methodReflection);
		}

		return new ArrayType(new MixedType(), new GenericObjectType(\ReflectionAttribute::class, [$classType]));
	}

	private function getDefaultReturnType(Scope $scope, MethodCall $methodCall, MethodReflection $methodReflection): Type
	{
		return ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$methodCall->args,
			$methodReflection->getVariants()
		)->getReturnType();
	}

}
