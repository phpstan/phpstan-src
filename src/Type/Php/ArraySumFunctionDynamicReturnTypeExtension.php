<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function array_filter;
use function array_sum;
use function count;
use function is_float;
use function is_int;

final class ArraySumFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_sum';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if (!isset($functionCall->getArgs()[0])) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$arrayType = $scope->getType($functionCall->getArgs()[0]->value);
		$itemType = $arrayType->getIterableValueType();

		if ($arrayType->isIterableAtLeastOnce()->no()) {
			return new ConstantIntegerType(0);
		}

		$constantArray = $arrayType->getConstantArrays()[0] ?? null;

		if ($constantArray !== null) {
			$nonScalarTypes = [];
			$scalarValues = [];

			$types = $constantArray->getValueTypes();

			foreach ($types as $type) {
				$scalar = $type->getConstantScalarValues();
				if (count($scalar) === 1) {
					$scalarValues[] = $scalar[0];
				} else {
					$nonScalarTypes[] = $type;
				}
			}

			if (count($nonScalarTypes) === 0) {
				$sum = array_sum($scalarValues);
				if (is_int($sum)) {
					$newItemType = new ConstantIntegerType($sum);
				} else {
					$newItemType = new ConstantFloatType($sum);
				}
			} else {
				$newItemType = count(array_filter($types, static fn ($type) => $type->isFloat()->yes())) !== 0
					? new FloatType()
					: $itemType->generalize(GeneralizePrecision::lessSpecific());
			}
		} else {
			$newItemType = new NeverType();

			if ($itemType instanceof UnionType) {
				$types = $itemType->getTypes();
			} else {
				$types = [$itemType];
			}

			$positive = false;
			$negative = false;

			foreach ($types as $type) {
				$constant = $type->getConstantScalarValues()[0] ?? null;
				if ($constant !== null) {
					if (is_float($constant)) {
						$nextType = new FloatType();
					} elseif (is_int($constant)) {
						if ($constant > 0) {
							$nextType = IntegerRangeType::fromInterval($negative ? 0 : 1, null);
							$positive = true;
						} elseif ($constant < 0) {
							$nextType = IntegerRangeType::fromInterval(null, $positive ? 0 : -1);
							$negative = true;
						} else {
							$nextType = new ConstantIntegerType(0);
						}
					} else {
						$nextType = $type;
					}
				} else {
					$nextType = $type;
				}

				$newItemType = TypeCombinator::union($newItemType, $nextType);
			}

			$nonEmptyArrayType = new NonEmptyArrayType();

			if (!$nonEmptyArrayType->isSuperTypeOf($arrayType)->yes()) {
				$newItemType = TypeCombinator::union($newItemType, new ConstantIntegerType(0));
			}
		}

		$intUnionFloat = new UnionType([new IntegerType(), new FloatType()]);

		if ($intUnionFloat->isSuperTypeOf($newItemType)->yes()) {
			return $newItemType;
		}
		return $intUnionFloat;
	}

}
