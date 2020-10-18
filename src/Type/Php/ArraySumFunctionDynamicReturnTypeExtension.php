<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\FloatType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

class ArraySumFunctionDynamicReturnTypeExtension implements \PHPStan\Type\DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_sum';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$argType = $scope->getType($functionCall->args[0]->value);
		$keyType = $argType->getIterableValueType();

		if ($keyType instanceof UnionType) {
			$floatType = new FloatType();

			if ($keyType->accepts($floatType, true)->yes()) {
				$keyType = $floatType;
			}
		}

		return $keyType;
	}

}
