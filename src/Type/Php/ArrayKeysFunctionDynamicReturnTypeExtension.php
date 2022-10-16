<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function strtolower;

class ArrayKeysFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return strtolower($functionReflection->getName()) === 'array_keys';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$arrayArg = $functionCall->getArgs()[0]->value ?? null;
		if ($arrayArg !== null) {
			$valueType = $scope->getType($arrayArg);

			$array = $valueType->getKeysArray();
			if ($valueType->isArray()->yes() && $valueType->isIterableAtLeastOnce()->yes()) {
				$array = TypeCombinator::intersect($array, new NonEmptyArrayType());
			}
			return $array;
		}

		return AccessoryArrayListType::intersectWith(new ArrayType(
			new IntegerType(),
			new UnionType([new StringType(), new IntegerType()]),
		));
	}

}
