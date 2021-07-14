<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;

class ImplodeFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), [
			'implode',
			'join',
		], true);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope
	): \PHPStan\Type\Type
	{
		$args = $functionCall->args;
		if (count($args) === 1) {
			$argType = $scope->getType($args[0]->value);
			if ($argType->isArray()->yes()) {
				if ($argType->isIterableAtLeastOnce()->yes() && $argType->getIterableValueType()->isNonEmptyString()->yes()) {
					return new IntersectionType([
						new StringType(),
						new AccessoryNonEmptyStringType(),
					]);
				}

				return new StringType();
			}
		}

		if (count($args) !== 2) {
			return new StringType();
		}

		$separatorType = $scope->getType($args[0]->value);
		$arrayType = $scope->getType($args[1]->value);
		if ($arrayType->isIterableAtLeastOnce()->yes()) {
			if ($arrayType->getIterableValueType()->isNonEmptyString()->yes()) {
				return new IntersectionType([
					new StringType(),
					new AccessoryNonEmptyStringType(),
				]);
			}
			if ($separatorType->isNonEmptyString()->yes()) {
				return new IntersectionType([
					new StringType(),
					new AccessoryNonEmptyStringType(),
				]);
			}
		}

		return new StringType();
	}

}
