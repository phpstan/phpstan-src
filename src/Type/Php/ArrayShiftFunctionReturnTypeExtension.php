<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function count;

class ArrayShiftFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_shift';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if (!isset($functionCall->getArgs()[0])) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$argType = $scope->getType($functionCall->getArgs()[0]->value);
		$iterableAtLeastOnce = $argType->isIterableAtLeastOnce();
		if ($iterableAtLeastOnce->no()) {
			return new NullType();
		}

		$constantArrays = TypeUtils::getOldConstantArrays($argType);
		if (count($constantArrays) > 0) {
			$valueTypes = [];
			foreach ($constantArrays as $constantArray) {
				$iterableAtLeastOnce = $constantArray->isIterableAtLeastOnce();
				if (!$iterableAtLeastOnce->yes()) {
					$valueTypes[] = new NullType();
				}
				if ($iterableAtLeastOnce->no()) {
					continue;
				}

				$valueTypes[] = $constantArray->getFirstValueType();
			}

			return TypeCombinator::union(...$valueTypes);
		}

		$itemType = $argType->getIterableValueType();
		if ($iterableAtLeastOnce->yes()) {
			return $itemType;
		}

		return TypeCombinator::union($itemType, new NullType());
	}

}
