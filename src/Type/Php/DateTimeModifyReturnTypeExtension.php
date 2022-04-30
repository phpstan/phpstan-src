<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use DateTime;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;

class DateTimeModifyReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return DateTime::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'modify';
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
	{
		$defaultReturnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

		$valueType = $scope->getType($methodCall->getArgs()[0]->value);
		$constantStrings = TypeUtils::getConstantStrings($valueType);

		$hasFalse = false;
		$hasDateTime = false;

		foreach ($constantStrings as $constantString) {
			if ((new DateTime())->modify($constantString->getValue()) === false) {
				$hasFalse = true;
			} else {
				$hasDateTime = true;
			}

			$valueType = TypeCombinator::remove($valueType, $constantString);
		}

		if (!$valueType instanceof NeverType) {
			return $defaultReturnType;
		}

		if ($hasFalse && !$hasDateTime) {
			return new ConstantBooleanType(false);
		}
		if ($hasDateTime && !$hasFalse) {
			return new StaticType($methodReflection->getDeclaringClass());
		}

		return $defaultReturnType;
	}

}
