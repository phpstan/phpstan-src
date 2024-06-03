<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
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
		$args = $functionCall->getArgs();
		if (count($args) < 1) {
			return null;
		}

		$valueType = $scope->getType($args[0]->value);
		if (!$valueType->isArray()->yes()) {
			return null;
		}

		$offsetType = isset($args[1]) ? $scope->getType($args[1]->value) : null;
		$limitType = isset($args[2]) ? $scope->getType($args[2]->value) : null;

		$constantArrays = $valueType->getConstantArrays();
		if (count($constantArrays) > 0) {
			$preserveKeysType = isset($args[3]) ? $scope->getType($args[3]->value) : null;

			$offset = $offsetType instanceof ConstantIntegerType ? $offsetType->getValue() : 0;
			$limit = $limitType instanceof ConstantIntegerType ? $limitType->getValue() : null;
			$preserveKeys = $preserveKeysType !== null && $preserveKeysType->isTrue()->yes();

			$results = [];
			foreach ($constantArrays as $constantArray) {
				$results[] = $constantArray->slice($offset, $limit, $preserveKeys);
			}

			return TypeCombinator::union(...$results);
		}

		if ($valueType->isIterableAtLeastOnce()->yes()) {
			$optionalOffsetsType = TypeCombinator::union($valueType, new ConstantArrayType([], []));

			$zero = new ConstantIntegerType(0);
			if (
				($offsetType === null || $zero->isSuperTypeOf($offsetType)->yes())
				&& ($limitType === null || IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($limitType)->yes())
			) {
				return TypeCombinator::intersect($optionalOffsetsType, new NonEmptyArrayType());
			}

			return $optionalOffsetsType;
		}

		return $valueType;
	}

}
