<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;

final class ArraySumFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_sum';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if (!isset($functionCall->args[0])) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$arrayType = $scope->getType($functionCall->args[0]->value);
		$itemType = $arrayType->getIterableValueType();

		if ($arrayType->isIterableAtLeastOnce()->no()) {
			return new ConstantIntegerType(0);
		}

		$intUnionFloat = new UnionType([new IntegerType(), new FloatType()]);

		if ($arrayType->isIterableAtLeastOnce()->yes()) {
			if ($intUnionFloat->isSuperTypeOf($itemType)->yes()) {
				return $itemType;
			}

			return $intUnionFloat;
		}

		if ($intUnionFloat->isSuperTypeOf($itemType)->yes()) {
			return TypeCombinator::union(new ConstantIntegerType(0), $itemType);
		}

		return TypeCombinator::union(new ConstantIntegerType(0), $intUnionFloat);
	}

}
