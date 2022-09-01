<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;
use const COUNT_RECURSIVE;

class CountFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['sizeof', 'count'], true);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): Type
	{
		if (count($functionCall->getArgs()) < 1) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		if (count($functionCall->getArgs()) > 1) {
			$mode = $scope->getType($functionCall->getArgs()[1]->value);
			if ($mode->isSuperTypeOf(new ConstantIntegerType(COUNT_RECURSIVE))->yes()) {
				return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
			}
		}

		$argType = $scope->getType($functionCall->getArgs()[0]->value);
		$constantArrays = $scope->getType($functionCall->getArgs()[0]->value)->getConstantArrays();
		if (count($constantArrays) === 0) {
			if ($argType->isIterableAtLeastOnce()->yes()) {
				return IntegerRangeType::fromInterval(1, null);
			}

			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}
		$countTypes = [];
		foreach ($constantArrays as $array) {
			$countTypes[] = $array->count();
		}

		return TypeCombinator::union(...$countTypes);
	}

}
