<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FloatType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;

class RangeFunctionReturnTypeExtension implements \PHPStan\Type\DynamicFunctionReturnTypeExtension
{

	private const RANGE_LENGTH_THRESHOLD = 50;

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'range';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if (count($functionCall->args) < 2) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$startType = $scope->getType($functionCall->args[0]->value);
		$endType = $scope->getType($functionCall->args[1]->value);
		$stepType = count($functionCall->args) >= 3 ? $scope->getType($functionCall->args[2]->value) : new ConstantIntegerType(1);

		$constantReturnTypes = [];

		$startConstants = TypeUtils::getConstantScalars($startType);
		foreach ($startConstants as $startConstant) {
			if (!$startConstant instanceof ConstantIntegerType && !$startConstant instanceof ConstantFloatType && !$startConstant instanceof ConstantStringType) {
				continue;
			}

			$endConstants = TypeUtils::getConstantScalars($endType);
			foreach ($endConstants as $endConstant) {
				if (!$endConstant instanceof ConstantIntegerType && !$endConstant instanceof ConstantFloatType && !$endConstant instanceof ConstantStringType) {
					continue;
				}

				$stepConstants = TypeUtils::getConstantScalars($stepType);
				foreach ($stepConstants as $stepConstant) {
					if (!$stepConstant instanceof ConstantIntegerType && !$stepConstant instanceof ConstantFloatType) {
						continue;
					}

					$rangeValues = range($startConstant->getValue(), $endConstant->getValue(), $stepConstant->getValue());
					if (count($rangeValues) > self::RANGE_LENGTH_THRESHOLD) {
						return new IntersectionType([
							new ArrayType(
								new IntegerType(),
								TypeCombinator::union(
									$startConstant->generalize(GeneralizePrecision::moreSpecific()),
									$endConstant->generalize(GeneralizePrecision::moreSpecific())
								)
							),
							new NonEmptyArrayType(),
						]);
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
		$isInteger = (new IntegerType())->isSuperTypeOf($argType)->yes();
		$isStepInteger = (new IntegerType())->isSuperTypeOf($stepType)->yes();

		if ($isInteger && $isStepInteger) {
			return new ArrayType(new IntegerType(), new IntegerType());
		}

		$isFloat = (new FloatType())->isSuperTypeOf($argType)->yes();
		if ($isFloat) {
			return new ArrayType(new IntegerType(), new FloatType());
		}

		$numberType = new UnionType([new IntegerType(), new FloatType()]);
		$isNumber = $numberType->isSuperTypeOf($argType)->yes();
		if ($isNumber) {
			return new ArrayType(new IntegerType(), $numberType);
		}

		$isString = (new StringType())->isSuperTypeOf($argType)->yes();
		if ($isString) {
			return new ArrayType(new IntegerType(), new StringType());
		}

		return new ArrayType(new IntegerType(), new BenevolentUnionType([new IntegerType(), new FloatType(), new StringType()]));
	}

}
