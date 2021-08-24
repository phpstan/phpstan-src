<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;

class StrPadFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'str_pad';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope
	): \PHPStan\Type\Type
	{
		$args = $functionCall->args;
		if (count($args) < 2) {
			return new StringType();
		}

		$inputType = $scope->getType($args[0]->value);
		$lengthType = $scope->getType($args[1]->value);

		$accessoryTypes = [];
		if ($inputType->isNonEmptyString()->yes() || IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($lengthType)->yes()) {
			$accessoryTypes[] = new AccessoryNonEmptyStringType();
		}

		if ($inputType->isLiteralString()->yes()) {
			if (count($args) < 3) {
				$accessoryTypes[] = new AccessoryLiteralStringType();
			} else {
				$padStringType = $scope->getType($args[2]->value);
				if ($padStringType->isLiteralString()->yes()) {
					$accessoryTypes[] = new AccessoryLiteralStringType();
				}
			}
		}

		if (count($accessoryTypes) > 0) {
			return new IntersectionType([new StringType(), ...$accessoryTypes]);
		}

		return new StringType();
	}

}
