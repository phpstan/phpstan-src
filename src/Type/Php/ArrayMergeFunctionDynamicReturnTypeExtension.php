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
use PHPStan\Type\UnionType;

class ArrayMergeFunctionDynamicReturnTypeExtension implements \PHPStan\Type\DynamicFunctionReturnTypeExtension
{

	private const MAX_ARGUMENT_TYPES = 5;
	private const MAX_CONSTANT_ARRAY_TYPES = 10;
	private const MAX_CONSTANT_ARRAY_TYPE_KEYS = 10;

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_merge';
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

		$constantData = $this->getConstantData($argumentTypes);
		$constantArrays = $constantData['constantArrays'];
		$isIterableAtLeastOnceArrays = $constantData['isIterableAtLeastOnceArrays'];
		$useConstantArrays = $constantData['useConstantArrays'];
		$useReturnedArrayBuilder = $constantData['useReturnedArrayBuilder'];

		$keyTypes = [];
		$valueTypes = [];
		$returnedArrayBuilder = ConstantArrayTypeBuilder::createEmpty();
		$returnedArrayBuilderFilled = false;
		$nonEmpty = false;
		foreach ($argumentTypes as $argKey => $argType) {
			if ($useConstantArrays === true) {
				if ($useReturnedArrayBuilder === true) {
					foreach ($constantArrays[$argKey] as $constantArray) {
						foreach ($constantArray->getKeyTypes() as $i => $keyType) {
							$returnedArrayBuilderFilled = true;

							$returnedArrayBuilder->setOffsetValueType(
								is_numeric($keyType->getValue()) ? null : $keyType,
								$constantArray->getValueTypes()[$i]
							);
						}
					}
				} else {
					foreach ($constantArrays[$argKey] as $constantArray) {
						foreach ($constantArray->getKeyTypes() as $i => $keyType) {
							if (is_numeric($keyType->getValue())) {
								$keyTypes[] = $keyType;
								$valueTypes[] = $constantArray->getValueTypes()[$i];
							} else {
								$keyTypes[$keyType->getValue()] = $keyType;
								$valueTypes[$keyType->getValue()] = $constantArray->getValueTypes()[$i];
							}
						}
					}
				}
			} else {
				$keyTypes[] = TypeUtils::generalizeType($argType->getIterableKeyType(), GeneralizePrecision::moreSpecific());
				$valueTypes[] = $argType->getIterableValueType();
			}

			if (
				(isset($isIterableAtLeastOnceArrays[$argKey]) && !$isIterableAtLeastOnceArrays[$argKey])
				||
				!$argType->isIterableAtLeastOnce()->yes()
			) {
				continue;
			}

			$nonEmpty = true;
		}

		if (count($keyTypes) > 0) {
			$arrayType = new ArrayType(
				TypeCombinator::union(...array_values($keyTypes)),
				TypeCombinator::union(...array_values($valueTypes))
			);

			if ($returnedArrayBuilderFilled) {
				$arrayType = TypeCombinator::union($returnedArrayBuilder->getArray(), $arrayType);
			}
		} elseif ($returnedArrayBuilderFilled) {
			$arrayType = $returnedArrayBuilder->getArray();
		} else {
			$arrayType = new ArrayType(
				TypeCombinator::union(...array_values($keyTypes)),
				TypeCombinator::union(...array_values($valueTypes))
			);
		}

		if ($nonEmpty) {
			$arrayType = TypeCombinator::intersect($arrayType, new NonEmptyArrayType());
		}

		return $arrayType;
	}

	/**
	 * @param Type[] $argumentTypes
	 * @return array{
	 *     constantArrays: array<int, \PHPStan\Type\Constant\ConstantArrayType[]>,
	 *     isIterableAtLeastOnceArrays: bool[],
	 *     useReturnedArrayBuilder: ?bool,
	 *     useConstantArrays: ?bool
	 * }
	 */
	private function getConstantData(array $argumentTypes): array
	{
		$isIterableAtLeastOnceArrays = [];
		$constantArrays = [];
		$useReturnedArrayBuilder = null;
		$useConstantArrays = null;

		// because of performance issues with "ConstantArrays" we do not collect / merge all arrays

		if (count($argumentTypes) <= self::MAX_ARGUMENT_TYPES) {
			foreach ($argumentTypes as $argKey => $argType) {
				$isIterableAtLeastOnceArrays[$argKey] = $argType->isIterableAtLeastOnce()->yes();
				$constantArrays[$argKey] = TypeUtils::getConstantArrays($argType);

				$constantArraysCount = count($constantArrays[$argKey]);
				if ($useConstantArrays !== false && $constantArraysCount > 0) {
					$useConstantArrays = true;
				} else {
					$useConstantArrays = false;
				}
				if ($constantArraysCount > self::MAX_CONSTANT_ARRAY_TYPES) {
					$useConstantArrays = false;

					break;
				}
			}

			foreach ($argumentTypes as $argKey => $argType) { // phpcs:ignore
				foreach ($constantArrays[$argKey] ?? [] as $constantArray) {
					$constantArraysKeyCount = count($constantArray->getKeyTypes());
					if ($constantArraysKeyCount > 0) {
						$useReturnedArrayBuilder = true;
					}
					if ($constantArraysKeyCount > self::MAX_CONSTANT_ARRAY_TYPE_KEYS) {
						$useReturnedArrayBuilder = false;

						break 2;
					}
				}
			}
		}

		return [
			'constantArrays' => $constantArrays,
			'isIterableAtLeastOnceArrays' => $isIterableAtLeastOnceArrays,
			'useReturnedArrayBuilder' => $useReturnedArrayBuilder,
			'useConstantArrays' => $useConstantArrays,
		];
	}

}
