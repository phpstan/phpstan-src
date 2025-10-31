<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;

#[AutowiredService]
final class ArrayFirstLastDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['array_first', 'array_last'], true);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();

		if (count($args) < 1) {
			return null;
		}

		$argType = $scope->getType($args[0]->value);
		$iterableAtLeastOnce = $argType->isIterableAtLeastOnce();

		if ($iterableAtLeastOnce->no()) {
			return new NullType();
		}

		$valueType = $argType->getIterableValueType();

		if ($iterableAtLeastOnce->yes()) {
			return $valueType;
		}

		return TypeCombinator::union($valueType, new NullType());
	}

}
