<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;

class ArraySliceFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_slice';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (count($functionCall->getArgs()) < 1) {
			return null;
		}

		$valueType = $scope->getType($functionCall->getArgs()[0]->value);
		if (!$valueType->isArray()->yes()) {
			return null;
		}

		$offsetType = isset($functionCall->getArgs()[1]) ? $scope->getType($functionCall->getArgs()[1]->value) : null;
		$offset = $offsetType instanceof ConstantIntegerType ? $offsetType->getValue() : 0;

		$constantArrays = $valueType->getConstantArrays();
		if (count($constantArrays) > 0) {
			$limitType = isset($functionCall->getArgs()[2]) ? $scope->getType($functionCall->getArgs()[2]->value) : null;
			$limit = $limitType instanceof ConstantIntegerType ? $limitType->getValue() : null;

			$preserveKeysType = isset($functionCall->getArgs()[3]) ? $scope->getType($functionCall->getArgs()[3]->value) : null;
			$preserveKeys = $preserveKeysType !== null && $preserveKeysType->isTrue()->yes();

			$results = [];
			foreach ($constantArrays as $constantArray) {
				$results[] = $constantArray->slice($offset, $limit, $preserveKeys);
			}

			return TypeCombinator::union(...$results);
		}

		if ($valueType->isIterableAtLeastOnce()->yes()) {
			$valueType = $valueType->toArray();
			if ((new NonEmptyArrayType())->isSuperTypeOf($valueType)->yes()) {
				$arrays = $valueType->getArrays();
				if (count($arrays) === 1) {
					$newArrayType = $arrays[0];
					return $valueType->isList()->yes() ? AccessoryArrayListType::intersectWith($newArrayType) : $newArrayType;
				}
			}
		}

		return $valueType;
	}

}
