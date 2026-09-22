<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_slice;
use function count;

#[AutowiredService]
final class ArrayIntersectKeyFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_intersect_key';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) === 0) {
			return null;
		}

		$argTypes = [];
		foreach ($args as $arg) {
			$argType = $scope->getType($arg->value);
			if ($arg->unpack) {
				$argTypes[] = $argType->getIterableValueType();
				continue;
			}

			$argTypes[] = $argType;
		}

		$firstArrayType = $argTypes[0];
		$otherArraysType = TypeCombinator::union(...array_slice($argTypes, 1));
		$onlyOneArrayGiven = count($argTypes) === 1;

		if ($firstArrayType->isArray()->no() || (!$onlyOneArrayGiven && $otherArraysType->isArray()->no())) {
			if ($scope->getPhpVersion()->arrayFunctionsReturnNullWithNonArray()->no()) {
				return new NeverType();
			}

			return new NullType();
		}

		if ($onlyOneArrayGiven) {
			return $firstArrayType;
		}

		return $firstArrayType->intersectKeyArray($otherArraysType);
	}

}
