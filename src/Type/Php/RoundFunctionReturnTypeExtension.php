<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;

class RoundFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array(
			$functionReflection->getName(),
			[
				'round',
				'ceil',
				'floor',
			],
			true
		);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$defaultReturnType = new FloatType();
		if (count($functionCall->getArgs()) < 1) {
			if (PHP_VERSION_ID >= 80000) {
				return new NeverType(true);
			} else {
				return new NullType();
			}
		}

		$firstArgType = $scope->getType($functionCall->getArgs()[0]->value);

		if (PHP_VERSION_ID >= 80000) {
			if (!($firstArgType instanceof IntegerType) && !($firstArgType instanceof FloatType)) {
				return new NeverType(true);
			}
		}

		if ($firstArgType->isArray()->yes()) {
			return new ConstantBooleanType(false);
		}

		return $defaultReturnType;
	}

}
