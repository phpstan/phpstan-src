<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function strtolower;

class ArrayValuesFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return strtolower($functionReflection->getName()) === 'array_values';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$arrayArg = $functionCall->getArgs()[0]->value ?? null;
		if ($arrayArg !== null) {
			$valueType = $scope->getType($arrayArg);
			if ($valueType->isArray()->yes()) {
				if ($valueType instanceof ConstantArrayType) {
					return $valueType->getValuesArray();
				}

				$array = TypeCombinator::intersect(new ArrayType(new IntegerType(), $valueType->getIterableValueType()), new AccessoryArrayListType());
				if ($valueType->isIterableAtLeastOnce()->yes()) {
					$array = TypeCombinator::intersect($array, new NonEmptyArrayType());
				}
				return $array;
			}
		}

		return TypeCombinator::intersect(
			new ArrayType(new IntegerType(), new MixedType()),
			new AccessoryArrayListType(),
		);
	}

}
