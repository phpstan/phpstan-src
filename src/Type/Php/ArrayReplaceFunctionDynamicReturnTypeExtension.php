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
	private const MAX_SIZE_USE_CONSTANT_ARRAY = 100;

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_replace';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if (!isset($functionCall->getArgs()[0])) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$argumentTypes = [];
		foreach ($functionCall->getArgs() as $arg) {
			$argType = $scope->getType($arg->value);
			if ($arg->unpack) {
				$iterableValueType = $argType->getIterableValueType();
				if ($iterableValueType instanceof UnionType) {
					foreach ($iterableValueType->getTypes() as $innerType) {
						$argumentTypes[] = $innerType;
					}
				} else {
					$argumentTypes[] = $iterableValueType;
				}
				continue;
			}

			$argumentTypes[] = $argType;
		}

		$keyTypes = [];
		$valueTypes = [];
		$nonEmpty = false;
		foreach ($argumentTypes as $argType) {

			$arrays = TypeUtils::getConstantArrays($argType);
			$arraysCount = count($arrays);
			if ($arraysCount > 0 && $arraysCount <= self::MAX_SIZE_USE_CONSTANT_ARRAY) {
				foreach ($arrays as $constantArray) {
					foreach ($constantArray->getKeyTypes() as $i => $keyType) {
						$keyTypes[$keyType->getValue()] = $keyType;
						$valueTypes[$keyType->getValue()] = $constantArray->getValueTypes()[$i];
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

		$arrayType = new ArrayType(
			TypeCombinator::union(...array_values($keyTypes)),
			TypeCombinator::union(...array_values($valueTypes))
		);

		if ($nonEmpty) {
			$arrayType = TypeCombinator::intersect($arrayType, new NonEmptyArrayType());
		}

		return $arrayType;
	}

}
