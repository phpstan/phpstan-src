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

		if ($arrayType->isIterableAtLeastOnce()->yes()) {
			return TypeCombinator::intersect($itemType, new UnionType([new IntegerType(), new FloatType()]));
		}

		return TypeCombinator::union(
			new ConstantIntegerType(0),
			TypeCombinator::intersect($itemType, new UnionType([new IntegerType(), new FloatType()]))
		);
	}

}
