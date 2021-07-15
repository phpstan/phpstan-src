<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
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

		if ($inputType->isNonEmptyString()->yes()) {
			return new IntersectionType([
				new StringType(),
				new AccessoryNonEmptyStringType(),
			]);
		}

		if (IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($lengthType)->yes()) {
			return new IntersectionType([
				new StringType(),
				new AccessoryNonEmptyStringType(),
			]);
		}

		return new StringType();
	}

}
