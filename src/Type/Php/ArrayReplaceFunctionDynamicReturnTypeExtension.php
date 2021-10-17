<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;

class ArrayReplaceFunctionDynamicReturnTypeExtension implements \PHPStan\Type\DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_replace';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if (!isset($functionCall->getArgs()[0])) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$keyTypes = [];
		$valueTypes = [];
		$nonEmpty = false;
		foreach ($functionCall->getArgs() as $arg) {
			$argType = $scope->getType($arg->value);
			if ($arg->unpack) {
				$argType = $argType->getIterableValueType();
				if ($argType instanceof UnionType) {
					foreach ($argType->getTypes() as $innerType) {
						$argType = $innerType;
					}
				}
			}

			$keyTypes[] = TypeUtils::generalizeType($argType->getIterableKeyType(), GeneralizePrecision::moreSpecific());
			$valueTypes[] = $argType->getIterableValueType();

			if (!$argType->isIterableAtLeastOnce()->yes()) {
				continue;
			}

			$nonEmpty = true;
		}

		$arrayType = new ArrayType(
			TypeCombinator::union(...$keyTypes),
			TypeCombinator::union(...$valueTypes)
		);

		if ($nonEmpty) {
			$arrayType = TypeCombinator::intersect($arrayType, new NonEmptyArrayType());
		}

		return $arrayType;
	}

}
