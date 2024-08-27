<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
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
		if (count($functionCall->getArgs()) < 2) {
			return null;
		}

		$singleArrayArgument = !isset($functionCall->getArgs()[2]);
		$callableType = $scope->getType($functionCall->getArgs()[0]->value);
		$callableIsNull = $callableType->isNull()->yes();
		$callback = null;

		if ($callableType->isCallable()->yes()) {
			$valueTypes = [new NeverType()];
			foreach ($callableType->getCallableParametersAcceptors($scope) as $parametersAcceptor) {
				$valueTypes[] = $parametersAcceptor->getReturnType();
			}
			$valueType = TypeCombinator::union(...$valueTypes);
			$callback = $functionCall->getArgs()[0]->value;
		} elseif ($callableIsNull) {
			$arrayBuilder = ConstantArrayTypeBuilder::createEmpty();
			foreach (array_slice($functionCall->getArgs(), 1) as $index => $arg) {
				$arrayBuilder->setOffsetValueType(
					new ConstantIntegerType($index),
					$scope->getType($arg->value)->getIterableValueType(),
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
						foreach ($constantArray->getKeyTypes() as $i => $keyType) {
							$returnedArrayBuilder->setOffsetValueType(
								$keyType,
								$callback === null
									? $valueType
									: $scope->getType(new FuncCall($callback, [
										new Arg(new TypeExpr($constantArray->getValueTypes()[$i])),
									])),
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
			$mappedArrayType = TypeCombinator::intersect(new ArrayType(
				new IntegerType(),
				$valueType,
			), ...TypeUtils::getAccessoryTypes($arrayType));
		}

		if ($arrayType->isIterableAtLeastOnce()->yes()) {
			$mappedArrayType = TypeCombinator::intersect($mappedArrayType, new NonEmptyArrayType());
		}

		return $mappedArrayType;
	}

}
