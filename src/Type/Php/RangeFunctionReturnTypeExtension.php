<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use ValueError;
use function count;
use function is_array;
use function range;

final class RangeFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	private const RANGE_LENGTH_THRESHOLD = 50;

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'range';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (count($functionCall->getArgs()) < 2) {
			return null;
		}

		$startType = $scope->getType($functionCall->getArgs()[0]->value);
		$endType = $scope->getType($functionCall->getArgs()[1]->value);
		$stepType = count($functionCall->getArgs()) >= 3 ? $scope->getType($functionCall->getArgs()[2]->value) : new ConstantIntegerType(1);

		$constantReturnTypes = [];

		$startConstants = $startType->getConstantScalarTypes();
		foreach ($startConstants as $startConstant) {
			if (!$startConstant instanceof ConstantIntegerType && !$startConstant instanceof ConstantFloatType && !$startConstant instanceof ConstantStringType) {
				continue;
			}

			$endConstants = $endType->getConstantScalarTypes();
			foreach ($endConstants as $endConstant) {
				if (!$endConstant instanceof ConstantIntegerType && !$endConstant instanceof ConstantFloatType && !$endConstant instanceof ConstantStringType) {
					continue;
				}

				$stepConstants = $stepType->getConstantScalarTypes();
				foreach ($stepConstants as $stepConstant) {
					if (!$stepConstant instanceof ConstantIntegerType && !$stepConstant instanceof ConstantFloatType) {
						continue;
					}

					try {
						$rangeValues = range($startConstant->getValue(), $endConstant->getValue(), $stepConstant->getValue());
					} catch (ValueError) {
						continue;
					}

					// @phpstan-ignore function.alreadyNarrowedType
					if (!is_array($rangeValues)) {
						continue;
					}

					if (count($rangeValues) > self::RANGE_LENGTH_THRESHOLD) {
						if ($startConstant instanceof ConstantIntegerType && $endConstant instanceof ConstantIntegerType) {
							if ($startConstant->getValue() > $endConstant->getValue()) {
								$tmp = $startConstant;
								$startConstant = $endConstant;
								$endConstant = $tmp;
							}
							return AccessoryArrayListType::intersectWith(TypeCombinator::intersect(
								new ArrayType(
									new IntegerType(),
									IntegerRangeType::fromInterval($startConstant->getValue(), $endConstant->getValue()),
								),
								new NonEmptyArrayType(),
							));
						}

						return AccessoryArrayListType::intersectWith(TypeCombinator::intersect(
							new ArrayType(
								new IntegerType(),
								TypeCombinator::union(
									$startConstant->generalize(GeneralizePrecision::moreSpecific()),
									$endConstant->generalize(GeneralizePrecision::moreSpecific()),
								),
							),
							new NonEmptyArrayType(),
						));
					}
					$arrayBuilder = ConstantArrayTypeBuilder::createEmpty();
					foreach ($rangeValues as $value) {
						$arrayBuilder->setOffsetValueType(null, $scope->getTypeFromValue($value));
					}

					$constantReturnTypes[] = $arrayBuilder->getArray();
				}
			}
		}

		if (count($constantReturnTypes) > 0) {
			return TypeCombinator::union(...$constantReturnTypes);
		}

		$argType = TypeCombinator::union($startType, $endType);
		$isInteger = $argType->isInteger()->yes();
		$isStepInteger = $stepType->isInteger()->yes();

		if ($isInteger && $isStepInteger) {
			if ($argType instanceof IntegerRangeType) {
				return AccessoryArrayListType::intersectWith(new ArrayType(new IntegerType(), $argType));
			}
			return AccessoryArrayListType::intersectWith(new ArrayType(new IntegerType(), new IntegerType()));
		}

		if ($argType->isFloat()->yes()) {
			return AccessoryArrayListType::intersectWith(new ArrayType(new IntegerType(), new FloatType()));
		}

		$numberType = new UnionType([new IntegerType(), new FloatType()]);
		$isNumber = $numberType->isSuperTypeOf($argType)->yes();
		$isNumericString = $argType->isNumericString()->yes();
		if ($isNumber || $isNumericString) {
			return AccessoryArrayListType::intersectWith(new ArrayType(new IntegerType(), $numberType));
		}

		if ($argType->isString()->yes()) {
			return AccessoryArrayListType::intersectWith(new ArrayType(new IntegerType(), new StringType()));
		}

		return AccessoryArrayListType::intersectWith(new ArrayType(
			new IntegerType(),
			new BenevolentUnionType([new IntegerType(), new FloatType(), new StringType()]),
		));
	}

}
