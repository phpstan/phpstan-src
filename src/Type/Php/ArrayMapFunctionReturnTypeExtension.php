<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

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
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function array_slice;
use function count;

class ArrayMapFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
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

		$arrayType = $scope->getType($functionCall->getArgs()[1]->value);

		if ($singleArrayArgument) {
			if ($callableIsNull) {
				return $arrayType;
			}
			$constantArrays = $arrayType->getConstantArrays();
			if (count($constantArrays) > 0) {
				$arrayTypes = [];
				foreach ($constantArrays as $constantArray) {
					$returnedArrayBuilder = ConstantArrayTypeBuilder::createEmpty();
					foreach ($constantArray->getKeyTypes() as $i => $keyType) {
						$offsetValueType = $constantArray->getOffsetValueType($keyType);

						$valueTypes = [new NeverType()];
						foreach ($callableType->getCallableParametersAcceptors($scope) as $parametersAcceptor) {
							$parametersAcceptor = ParametersAcceptorSelector::selectFromTypes(
								[$offsetValueType],
								[$parametersAcceptor],
								false,
							);
							$valueTypes[] = $parametersAcceptor->getReturnType();
						}

						$returnedArrayBuilder->setOffsetValueType(
							$keyType,
							TypeCombinator::union(...$valueTypes),
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
			} elseif ($arrayType->isArray()->yes()) {
				$mappedArrayType = TypeCombinator::intersect(new ArrayType(
					$arrayType->getIterableKeyType(),
					$this->resolveValueType($scope, $callableType, $callableIsNull, $functionCall),
				), ...TypeUtils::getAccessoryTypes($arrayType));
			} else {
				$mappedArrayType = new ArrayType(
					new MixedType(),
					$this->resolveValueType($scope, $callableType, $callableIsNull, $functionCall),
				);
			}
		} else {
			$mappedArrayType = TypeCombinator::intersect(new ArrayType(
				new IntegerType(),
				$this->resolveValueType($scope, $callableType, $callableIsNull, $functionCall),
			), ...TypeUtils::getAccessoryTypes($arrayType));
		}

		if ($arrayType->isIterableAtLeastOnce()->yes()) {
			$mappedArrayType = TypeCombinator::intersect($mappedArrayType, new NonEmptyArrayType());
		}

		return $mappedArrayType;
	}

	private function resolveValueType(
		Scope $scope,
		Type $callableType,
		bool $callableIsNull,
		FuncCall $functionCall,
	): Type
	{
		if ($callableType->isCallable()->yes()) {
			$argTypes = [];

			foreach (array_slice($functionCall->getArgs(), 1) as $arrayArg) {
				$argTypes[] = $scope->getType($arrayArg->value)->getIterableValueType();
			}

			$valueTypes = [new NeverType()];
			foreach ($callableType->getCallableParametersAcceptors($scope) as $parametersAcceptor) {
				$parametersAcceptor = ParametersAcceptorSelector::selectFromTypes(
					$argTypes,
					[$parametersAcceptor],
					false,
				);
				$valueTypes[] = $parametersAcceptor->getReturnType();
			}
			return TypeCombinator::union(...$valueTypes);
		} elseif ($callableIsNull) {
			$arrayBuilder = ConstantArrayTypeBuilder::createEmpty();
			foreach (array_slice($functionCall->getArgs(), 1) as $index => $arg) {
				$arrayBuilder->setOffsetValueType(
					new ConstantIntegerType($index),
					$scope->getType($arg->value)->getIterableValueType(),
				);
			}
			return $arrayBuilder->getArray();
		}

		return new MixedType();
	}

}
