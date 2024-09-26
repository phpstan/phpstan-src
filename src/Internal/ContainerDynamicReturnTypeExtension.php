<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;

final class ContainerDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return Container::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return in_array($methodReflection->getName(), [
			'getByType',
		], true);
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
	{
		if (count($methodCall->getArgs()) === 0) {
			return ParametersAcceptorSelector::selectFromArgs(
				$scope,
				$methodCall->getArgs(),
				$methodReflection->getVariants(),
			)->getReturnType();
		}
		$argType = $scope->getType($methodCall->getArgs()[0]->value);
		if (!$argType instanceof ConstantStringType) {
			return ParametersAcceptorSelector::selectFromArgs(
				$scope,
				$methodCall->getArgs(),
				$methodReflection->getVariants(),
			)->getReturnType();
		}

		$type = new ObjectType($argType->getValue());
		if ($methodReflection->getName() === 'getByType' && count($methodCall->getArgs()) >= 2) {
			$argType = $scope->getType($methodCall->getArgs()[1]->value);
			if ($argType->isTrue()->yes()) {
				$type = TypeCombinator::addNull($type);
			}
		}

		return $type;
	}

}
