<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use DateInterval;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;

class DateIntervalDynamicReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
	public function getClass(): string
	{
		return DateInterval::class;
	}

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'createFromDateString';
	}

	public function getTypeFromStaticMethodCall( MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope ): ?\PHPStan\Type\Type
	{
		$defaultReturnType = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$methodCall->getArgs(),
			$methodReflection->getVariants(),
		)->getReturnType();

		$dateTimeString = $scope->getType($methodCall->getArgs()[0]->value);

		if (!($dateTimeString instanceof ConstantStringType)) {
			return $defaultReturnType;
		}

		$isValid = DateInterval::createFromDateString($dateTimeString->getValue()) !== false;

		return $isValid ? new ObjectType(DateInterval::class) : new ConstantBooleanType(false);
	}
}
