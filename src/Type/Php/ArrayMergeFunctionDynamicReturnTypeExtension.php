<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;

class ArrayMergeFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_merge';
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

			$keyTypes[] = $argType->getIterableKeyType()->generalize(GeneralizePrecision::moreSpecific());
			$valueTypes[] = $argType->getIterableValueType();

			if (!$argType->isIterableAtLeastOnce()->yes()) {
				continue;
			}

			$nonEmpty = true;
		}

		$keyType = TypeCombinator::union(...$keyTypes);
		if ($keyType instanceof NeverType && $keyType->isExplicit()) {
			return new ConstantArrayType([], []);
		}

		$arrayType = new ArrayType(
			$keyType,
			TypeCombinator::union(...$valueTypes),
		);

		if ($nonEmpty) {
			$arrayType = TypeCombinator::intersect($arrayType, new NonEmptyArrayType());
		}

		return $arrayType;
	}

}
