<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
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
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use ValueError;
use function abs;
use function count;
use function floor;
use function is_array;
use function is_finite;
use function is_numeric;
use function is_string;
use function max;
use function min;
use function range;

#[AutowiredService]
final class RangeFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	private const RANGE_LENGTH_THRESHOLD = 50;

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'range';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) < 2) {
			return null;
		}

		$startType = $scope->getType($args[0]->value);
		$endType = $scope->getType($args[1]->value);
		$stepType = count($args) >= 3 ? $scope->getType($args[2]->value) : new ConstantIntegerType(1);

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

					// range() would allocate every item before the length could be checked
					$rangeLength = self::getRangeLength($startConstant->getValue(), $endConstant->getValue(), $stepConstant->getValue());
					if ($rangeLength !== null && $rangeLength > ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT) {
						return self::getLongRangeType($startConstant, $endConstant, $stepConstant, $stepType);
					}

					try {
						$rangeValues = @range($startConstant->getValue(), $endConstant->getValue(), $stepConstant->getValue());
					} catch (ValueError) {
						continue;
					}

					// @phpstan-ignore function.alreadyNarrowedType
					if (!is_array($rangeValues)) {
						continue;
					}

					if (count($rangeValues) > self::RANGE_LENGTH_THRESHOLD) {
						return self::getLongRangeType($startConstant, $endConstant, $stepConstant, $stepType);
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
				return self::getNonEmptyListOfType($argType);
			}
			return self::getNonEmptyListOfType(new IntegerType());
		}

		if ($argType->isFloat()->yes()) {
			return self::getNonEmptyListOfType(new FloatType());
		}

		$numberType = new UnionType([new IntegerType(), new FloatType()]);
		$isNumber = $numberType->isSuperTypeOf($argType)->yes();
		$isNumericString = $argType->isNumericString()->yes();
		if ($isNumber || $isNumericString) {
			return self::getNonEmptyListOfType($numberType);
		}

		if ($argType->isString()->yes()) {
			return self::getNonEmptyListOfType(new StringType());
		}

		return self::getNonEmptyListOfType(
			new BenevolentUnionType([
				new IntegerType(),
				new FloatType(),
				new StringType(),
			]),
		);
	}

	/**
	 * The number of items range() creates for numeric arguments, or null when
	 * only calling it tells - for a character range, which has at most 256
	 * items, and for a zero, infinite or NAN argument, for which it throws.
	 */
	public static function getRangeLength(int|float|string $start, int|float|string $end, int|float $step): ?float
	{
		if (is_string($start) && is_string($end) && !is_numeric($start) && !is_numeric($end)) {
			return null;
		}

		// a non-numeric string next to a number is 0
		$startNumber = is_numeric($start) ? (float) $start : 0.0;
		$endNumber = is_numeric($end) ? (float) $end : 0.0;
		$stepNumber = abs((float) $step);
		if ($stepNumber === 0.0) {
			return null;
		}

		$length = abs($endNumber - $startNumber) / $stepNumber;
		if (!is_finite($length)) {
			return null;
		}

		return floor($length) + 1;
	}

	private static function getLongRangeType(
		ConstantIntegerType|ConstantFloatType|ConstantStringType $startConstant,
		ConstantIntegerType|ConstantFloatType|ConstantStringType $endConstant,
		ConstantIntegerType|ConstantFloatType $stepConstant,
		Type $stepType,
	): Type
	{
		if (
			$startConstant instanceof ConstantIntegerType
			&& $endConstant instanceof ConstantIntegerType
			&& $stepConstant instanceof ConstantIntegerType
		) {
			return self::getNonEmptyListOfType(
				IntegerRangeType::fromInterval(
					min($startConstant->getValue(), $endConstant->getValue()),
					max($startConstant->getValue(), $endConstant->getValue()),
				),
			);
		}

		if ($stepType->isFloat()->yes()) {
			return self::getNonEmptyListOfType(new FloatType());
		}

		return self::getNonEmptyListOfType(
			TypeCombinator::union(
				$startConstant->generalize(GeneralizePrecision::moreSpecific()),
				$endConstant->generalize(GeneralizePrecision::moreSpecific()),
				$stepType->generalize(GeneralizePrecision::moreSpecific()),
			),
		);
	}

	private static function getNonEmptyListOfType(Type $type): IntersectionType
	{
		return new IntersectionType([
			new ArrayType(
				IntegerRangeType::createAllGreaterThanOrEqualTo(0),
				$type,
			),
			new NonEmptyArrayType(),
			new AccessoryArrayListType(),
		]);
	}

}
