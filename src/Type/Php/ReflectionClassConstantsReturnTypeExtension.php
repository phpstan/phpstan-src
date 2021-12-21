<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;
use ReflectionClass;
use function count;

class ReflectionClassConstantsReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return ReflectionClass::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'getConstant';
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
	{
		if (count($methodCall->getArgs()) < 1) {
			return $this->getDefaultReturnType($scope, $methodCall, $methodReflection);
		}

		$objectType = $scope->getType($methodCall->var);
		if (!$objectType instanceof GenericObjectType) {
			return $this->getDefaultReturnType($scope, $methodCall, $methodReflection);
		}

		$classReflection = $objectType->getClassReflection();
		if ($classReflection === null) {
			return $this->getDefaultReturnType($scope, $methodCall, $methodReflection);
		}

		$argType = $scope->getType($methodCall->getArgs()[0]->value);
		if (!$argType instanceof ConstantStringType) {
			return $this->getDefaultReturnType($scope, $methodCall, $methodReflection);
		}

		if (!$classReflection->hasConstant($argType->getValue())) {
			return new ConstantBooleanType(false);
		}

		$constantReflection = $classReflection->getConstant($argType->getValue());
		return $constantReflection->getValueType();
	}

	private function getDefaultReturnType(Scope $scope, MethodCall $methodCall, MethodReflection $methodReflection): Type
	{
		return ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$methodCall->getArgs(),
			$methodReflection->getVariants(),
		)->getReturnType();
	}

}
