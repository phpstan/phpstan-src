<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;

class ArrayMapFunctionReturnTypeExtension implements \PHPStan\Type\DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_map';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if (count($functionCall->getArgs()) < 2) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$valueType = new MixedType();
		$callableType = $scope->getType($functionCall->getArgs()[0]->value);
		if ($callableType->isCallable()->yes()) {
			$valueType = new NeverType();
			foreach ($callableType->getCallableParametersAcceptors($scope) as $parametersAcceptor) {
				$valueType = TypeCombinator::union($valueType, $parametersAcceptor->getReturnType());
			}
		}

		$mappedArrayType = new ArrayType(
			new MixedType(),
			$valueType
		);
		$arrayType = $scope->getType($functionCall->getArgs()[1]->value);
		$constantArrays = TypeUtils::getConstantArrays($arrayType);

		if (!isset($functionCall->getArgs()[2])) {
			if (count($constantArrays) > 0) {
				$arrayTypes = [];
				foreach ($constantArrays as $constantArray) {
					$returnedArrayBuilder = ConstantArrayTypeBuilder::createEmpty();
					foreach ($constantArray->getKeyTypes() as $keyType) {
						$returnedArrayBuilder->setOffsetValueType(
							$keyType,
							$valueType
						);
					}
					$arrayTypes[] = $returnedArrayBuilder->getArray();
				}

				$mappedArrayType = TypeCombinator::union(...$arrayTypes);
			} elseif ($arrayType->isArray()->yes()) {
				$mappedArrayType = TypeCombinator::intersect(new ArrayType(
					$arrayType->getIterableKeyType(),
					$valueType
				), ...TypeUtils::getAccessoryTypes($arrayType));
			}
		} else {
			$mappedArrayType = TypeCombinator::intersect(new ArrayType(
				new IntegerType(),
				$valueType
			), ...TypeUtils::getAccessoryTypes($arrayType));
		}

		if ($arrayType->isIterableAtLeastOnce()->yes()) {
			$mappedArrayType = TypeCombinator::intersect($mappedArrayType, new NonEmptyArrayType());
		}

		return $mappedArrayType;
	}

}
