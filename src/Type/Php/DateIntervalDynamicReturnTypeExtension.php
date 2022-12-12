<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use DateInterval;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;

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

	public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
	{
		$dateTimeString = TypeUtils::getConstantStrings($scope->getType($methodCall->getArgs()[0]->value));

		if (!($dateTimeString instanceof ConstantStringType)) {
			return null;
		}

		$isValid = DateInterval::createFromDateString($dateTimeString->getValue()) !== false;

		return $isValid ? new ObjectType(DateInterval::class) : new ConstantBooleanType(false);
	}

}
