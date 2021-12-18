<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use function count;
use function in_array;

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
		Scope $scope,
	): Type
	{
		$args = $functionCall->getArgs();
		if (count($args) === 1) {
			$argType = $scope->getType($args[0]->value);
			if ($argType->isArray()->yes()) {
				$accessoryTypes = [];
				if ($argType->isIterableAtLeastOnce()->yes() && $argType->getIterableValueType()->isNonEmptyString()->yes()) {
					$accessoryTypes[] = new AccessoryNonEmptyStringType();
				}
				if ($argType->getIterableValueType()->isLiteralString()->yes()) {
					$accessoryTypes[] = new AccessoryLiteralStringType();
				}

				if (count($accessoryTypes) > 0) {
					$accessoryTypes[] = new StringType();
					return new IntersectionType($accessoryTypes);
				}

				return new StringType();
			}
		}

		if (count($args) !== 2) {
			return new StringType();
		}

		$separatorType = $scope->getType($args[0]->value);
		$arrayType = $scope->getType($args[1]->value);
		$accessoryTypes = [];
		if ($arrayType->isIterableAtLeastOnce()->yes()) {
			if ($arrayType->getIterableValueType()->isNonEmptyString()->yes() || $separatorType->isNonEmptyString()->yes()) {
				$accessoryTypes[] = new AccessoryNonEmptyStringType();
			}
		}

		if ($arrayType->getIterableValueType()->isLiteralString()->yes() && $separatorType->isLiteralString()->yes()) {
			$accessoryTypes[] = new AccessoryLiteralStringType();
		}

		if (count($accessoryTypes) > 0) {
			$accessoryTypes[] = new StringType();
			return new IntersectionType($accessoryTypes);
		}

		return new StringType();
	}

}
