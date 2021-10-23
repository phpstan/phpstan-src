<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\FloatType;
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
			return $defaultReturnType;
		}

		$firstArgType = $scope->getType($functionCall->getArgs()[0]->value);
		if ($firstArgType instanceof ArrayType) {
			return new ConstantBooleanType(false);
		}

		return $defaultReturnType;
	}

}
