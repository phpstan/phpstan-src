<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class ArrayShiftFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_shift';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (!isset($functionCall->getArgs()[0])) {
			return null;
		}

		$argType = $scope->getType($functionCall->getArgs()[0]->value);
		$iterableAtLeastOnce = $argType->isIterableAtLeastOnce();
		if ($iterableAtLeastOnce->no()) {
			return new NullType();
		}

		$itemType = $argType->getFirstIterableValueType();
		if ($iterableAtLeastOnce->yes()) {
			return $itemType;
		}

		return TypeCombinator::union($itemType, new NullType());
	}

}
