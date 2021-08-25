<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;

class ArrayMergeFunctionDynamicReturnTypeExtension implements \PHPStan\Type\DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_merge';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if (!isset($functionCall->args[0])) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$keyTypes = [];
		$valueTypes = [];
		$returnedArrayBuilder = ConstantArrayTypeBuilder::createEmpty();
		$returnedArrayBuilderFilled = false;
		$nonEmpty = false;
		foreach ($functionCall->args as $arg) {
			$argType = $scope->getType($arg->value);

			if ($arg->unpack) {
				$argType = $argType->getIterableValueType();
			}

			$arrays = TypeUtils::getConstantArrays($argType);
			if (count($arrays) > 0) {
				foreach ($arrays as $constantArray) {
					foreach ($constantArray->getKeyTypes() as $i => $keyType) {
						$returnedArrayBuilderFilled = true;

						$returnedArrayBuilder->setOffsetValueType(
							is_numeric($keyType->getValue()) ? null : $keyType,
							$constantArray->getValueTypes()[$i]
						);
					}
				}

			} else {
				$keyTypes[] = TypeUtils::generalizeType($argType->getIterableKeyType(), GeneralizePrecision::moreSpecific());
				$valueTypes[] = $argType->getIterableValueType();
			}

			if (!$argType->isIterableAtLeastOnce()->yes()) {
				continue;
			}

			$nonEmpty = true;
		}

		if (count($keyTypes) > 0) {
			$arrayType = new ArrayType(
				TypeCombinator::union(...$keyTypes),
				TypeCombinator::union(...$valueTypes)
			);

			if ($returnedArrayBuilderFilled) {
				$arrayType = TypeCombinator::union($returnedArrayBuilder->getArray(), $arrayType);
			}
		} elseif ($returnedArrayBuilderFilled) {
			$arrayType = $returnedArrayBuilder->getArray();
		} else {
			$arrayType = new ArrayType(
				TypeCombinator::union(...$keyTypes),
				TypeCombinator::union(...$valueTypes)
			);
		}

		if ($nonEmpty) {
			$arrayType = TypeCombinator::intersect($arrayType, new NonEmptyArrayType());
		}

		return $arrayType;
	}

}
