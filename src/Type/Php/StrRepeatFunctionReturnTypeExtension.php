<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;

class StrRepeatFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'str_repeat';
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
		$multiplierType = $scope->getType($args[1]->value);

		if ((new ConstantIntegerType(0))->isSuperTypeOf($multiplierType)->yes()) {
			return new ConstantStringType('');
		}

		if ($inputType->isNonEmptyString()->yes()) {
			if (IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($multiplierType)->yes()) {
				return new IntersectionType([
					new StringType(),
					new AccessoryNonEmptyStringType(),
				]);
			}
		}

		return new StringType();
	}

}
