<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

class ServiceLocatorDynamicReturnTypeExtension implements \PHPStan\Type\DynamicMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return \Nette\DI\Container::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return in_array($methodReflection->getName(), [
			'getByType',
			'createInstance',
		], true);
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
	{
		if (count($methodCall->getArgs()) === 0) {
			return ParametersAcceptorSelector::selectFromArgs($scope, $methodCall->getArgs(), $methodReflection->getVariants())->getReturnType();
		}
		$argType = $scope->getType($methodCall->getArgs()[0]->value);
		if ($argType instanceof ConstantStringType) {
			$type = new ObjectType($argType->getValue());
		} elseif ($argType instanceof GenericClassStringType) {
			$type = $argType->getGenericType();
		} elseif ($argType instanceof ClassStringType) {
			$type = new ObjectWithoutClassType();
		} else {
			return ParametersAcceptorSelector::selectFromArgs($scope, $methodCall->getArgs(), $methodReflection->getVariants())->getReturnType();
		}

		if ($methodReflection->getName() === 'getByType' && count($methodCall->getArgs()) >= 2) {
			$argType = $scope->getType($methodCall->getArgs()[1]->value);
			if ($argType instanceof ConstantBooleanType && $argType->getValue()) {
				$type = TypeCombinator::addNull($type);
			}
		}

		return $type;
	}

}
