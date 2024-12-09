<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function array_map;
use function array_reduce;
use function array_slice;
use function count;

final class ArrayMapFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_map';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$numArgs = count($functionCall->getArgs());
		if ($numArgs < 2) {
			return null;
		}

		$singleArrayArgument = !isset($functionCall->getArgs()[2]);
		$callableType = $scope->getType($functionCall->getArgs()[0]->value);
		$callableIsNull = $callableType->isNull()->yes();

		$callableParametersAcceptors = null;

		if ($callableType->isCallable()->yes()) {
			$callableParametersAcceptors = $callableType->getCallableParametersAcceptors($scope);
			$valueType = ParametersAcceptorSelector::selectFromTypes(
				array_map(
					static fn (Node\Arg $arg) => $scope->getType($arg->value)->getIterableValueType(),
					array_slice($functionCall->getArgs(), 1),
				),
				$callableParametersAcceptors,
				false,
			)->getReturnType();
		} elseif ($callableIsNull) {
			$arrayBuilder = ConstantArrayTypeBuilder::createEmpty();
			$argTypes = [];
			$areAllSameSize = true;
			$expectedSize = null;
			foreach (array_slice($functionCall->getArgs(), 1) as $index => $arg) {
				$argTypes[$index] = $argType = $scope->getType($arg->value);
				if (!$areAllSameSize || $numArgs === 2) {
					continue;
				}

				$arraySizes = $argType->getArraySize()->getConstantScalarValues();
				if ($arraySizes === []) {
					$areAllSameSize = false;
					continue;
				}

				foreach ($arraySizes as $size) {
					$expectedSize ??= $size;
					if ($expectedSize === $size) {
						continue;
					}

					$areAllSameSize = false;
					continue 2;
				}
			}

			if (!$areAllSameSize) {
				$firstArr = $functionCall->getArgs()[1]->value;
				$identities = [];
				foreach (array_slice($functionCall->getArgs(), 2) as $arg) {
					$identities[] = new Node\Expr\BinaryOp\Identical($firstArr, $arg->value);
				}

				$and = array_reduce(
					$identities,
					static fn (Node\Expr $a, Node\Expr $b) => new Node\Expr\BinaryOp\BooleanAnd($a, $b),
					new Node\Expr\ConstFetch(new Node\Name('true')),
				);
				$areAllSameSize = $scope->getType($and)->isTrue()->yes();
			}

			$addNull = !$areAllSameSize;

			foreach ($argTypes as $index => $argType) {
				$offsetValueType = $argType->getIterableValueType();
				if ($addNull) {
					$offsetValueType = TypeCombinator::addNull($offsetValueType);
				}

				$arrayBuilder->setOffsetValueType(
					new ConstantIntegerType($index),
					$offsetValueType,
				);
			}
			$valueType = $arrayBuilder->getArray();
		} else {
			$valueType = new MixedType();
		}

		$arrayType = $scope->getType($functionCall->getArgs()[1]->value);

		if ($singleArrayArgument) {
			if ($callableIsNull) {
				return $arrayType;
			}
			$constantArrays = $arrayType->getConstantArrays();
			if (count($constantArrays) > 0) {
				$arrayTypes = [];
				$totalCount = TypeCombinator::countConstantArrayValueTypes($constantArrays) * TypeCombinator::countConstantArrayValueTypes([$valueType]);
				if ($totalCount < ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT) {
					foreach ($constantArrays as $constantArray) {
						$returnedArrayBuilder = ConstantArrayTypeBuilder::createEmpty();
						$valueTypes = $constantArray->getValueTypes();
						foreach ($constantArray->getKeyTypes() as $i => $keyType) {
							$returnedArrayBuilder->setOffsetValueType(
								$keyType,
								$callableParametersAcceptors !== null
									? ParametersAcceptorSelector::selectFromTypes(
										[$valueTypes[$i]],
										$callableParametersAcceptors,
										false,
									)->getReturnType()
									: $valueType,
								$constantArray->isOptionalKey($i),
							);
						}
						$returnedArray = $returnedArrayBuilder->getArray();
						if ($constantArray->isList()->yes()) {
							$returnedArray = AccessoryArrayListType::intersectWith($returnedArray);
						}
						$arrayTypes[] = $returnedArray;
					}

					$mappedArrayType = TypeCombinator::union(...$arrayTypes);
				} else {
					$mappedArrayType = TypeCombinator::intersect(new ArrayType(
						$arrayType->getIterableKeyType(),
						$valueType,
					), ...TypeUtils::getAccessoryTypes($arrayType));
				}
			} elseif ($arrayType->isArray()->yes()) {
				$mappedArrayType = TypeCombinator::intersect(new ArrayType(
					$arrayType->getIterableKeyType(),
					$valueType,
				), ...TypeUtils::getAccessoryTypes($arrayType));
			} else {
				$mappedArrayType = new ArrayType(
					new MixedType(),
					$valueType,
				);
			}
		} else {
			$mappedArrayType = AccessoryArrayListType::intersectWith(
				TypeCombinator::intersect(new ArrayType(
					new IntegerType(),
					$valueType,
				), ...TypeUtils::getAccessoryTypes($arrayType)),
			);
		}

		if ($arrayType->isIterableAtLeastOnce()->yes()) {
			$mappedArrayType = TypeCombinator::intersect($mappedArrayType, new NonEmptyArrayType());
		}

		return $mappedArrayType;
	}

}
