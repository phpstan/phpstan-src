<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_map;
use function count;

final class ArrayFindFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_find';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (count($functionCall->getArgs()) < 2) {
			return null;
		}

		$arrayType = $scope->getType($functionCall->getArgs()[0]->value);
		if (count($arrayType->getArrays()) < 1) {
			return null;
		}

		$resultTypes = $scope->getType(new FuncCall(new Name('\array_filter'), $functionCall->getArgs()));
		$resultType = TypeCombinator::union(...array_map(fn ($type) => $type->getIterableValueType(), $resultTypes->getArrays()));

		return $resultTypes->isIterableAtLeastOnce()->yes() ? $resultType : TypeCombinator::addNull($resultType);
	}

}
