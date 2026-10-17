<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NeverType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function abs;
use function count;
use function floor;
use function is_finite;
use function is_float;
use function is_numeric;
use function is_string;
use function max;
use function min;

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

		$phpVersions = $scope->getPhpVersion();
		$hasStricterRange = $phpVersions->hasStricterRangeFunction();
		$throwsValueError = $phpVersions->throwsValueErrorForInternalFunctions();

		$constantReturnTypes = [];
		$constantCombinations = 0;
		$throwingCombinations = 0;
		$hasSkippedCombination = false;
		$hasUnknownCombination = false;

		$startConstants = $startType->getConstantScalarTypes();
		foreach ($startConstants as $startConstant) {
			if (!$startConstant instanceof ConstantIntegerType && !$startConstant instanceof ConstantFloatType && !$startConstant instanceof ConstantStringType) {
				$hasSkippedCombination = true;
				continue;
			}

			$endConstants = $endType->getConstantScalarTypes();
			foreach ($endConstants as $endConstant) {
				if (!$endConstant instanceof ConstantIntegerType && !$endConstant instanceof ConstantFloatType && !$endConstant instanceof ConstantStringType) {
					$hasSkippedCombination = true;
					continue;
				}

				$stepConstants = $stepType->getConstantScalarTypes();
				foreach ($stepConstants as $stepConstant) {
					if (!$stepConstant instanceof ConstantIntegerType && !$stepConstant instanceof ConstantFloatType) {
						$hasSkippedCombination = true;
						continue;
					}

					$constantCombinations++;

					// range() would allocate every item before the length could be checked
					$rangeLength = self::getRangeLength($startConstant->getValue(), $endConstant->getValue(), $stepConstant->getValue());
					if ($rangeLength !== null && $rangeLength > ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT) {
						// without calling range() nothing rejects a negative step on an increasing range, which PHP 8.3 does
						if (RangeFunctionArgumentsHelper::isNegativeStepOnIncreasingRange($startConstant->getValue(), $endConstant->getValue(), $stepConstant->getValue())) {
							if ($hasStricterRange->yes()) {
								$throwingCombinations++;
								continue;
							}
							if ($hasStricterRange->maybe()) {
								$hasUnknownCombination = true;
								continue;
							}
						}

						$constantReturnTypes[] = self::getLongRangeType($hasStricterRange, $startConstant, $endConstant, $stepConstant, $stepType, null);
						continue;
					}

					$rangeValues = RangeFunctionArgumentsHelper::callRange($startConstant->getValue(), $endConstant->getValue(), $stepConstant->getValue());
					if ($rangeValues === false) {
						$fails = RangeFunctionArgumentsHelper::rejects($hasStricterRange, $startConstant->getValue(), $endConstant->getValue(), $stepConstant->getValue(), true);
						if (!$fails->yes()) {
							// the analysed PHP version might accept the step, but only the runtime's verdict is known
							$hasUnknownCombination = true;
						} elseif ($throwsValueError->yes()) {
							$throwingCombinations++;
						} else {
							$constantReturnTypes[] = new ConstantBooleanType(false);
						}
						continue;
					}

					if (count($rangeValues) > self::RANGE_LENGTH_THRESHOLD) {
						$constantReturnTypes[] = self::getLongRangeType($hasStricterRange, $startConstant, $endConstant, $stepConstant, $stepType, $rangeValues);
						continue;
					}

					$arrayBuilder = ConstantArrayTypeBuilder::createEmpty();
					foreach ($rangeValues as $value) {
						$arrayBuilder->setOffsetValueType(null, $scope->getTypeFromValue($value));
					}

					$constantReturnTypes[] = $arrayBuilder->getArray();
				}
			}
		}

		if (!$hasUnknownCombination && !$hasSkippedCombination) {
			if (count($constantReturnTypes) > 0) {
				return TypeCombinator::union(...$constantReturnTypes);
			}

			// nothing is returned when every combination of the constant arguments throws
			if (
				$constantCombinations > 0
				&& $throwingCombinations === $constantCombinations
				&& $startType->isConstantScalarValue()->yes()
				&& $endType->isConstantScalarValue()->yes()
				&& $stepType->isConstantScalarValue()->yes()
			) {
				return new NeverType();
			}
		}

		// the general type covers the combinations that could not be decided, the rest keep their own types
		return TypeCombinator::union(self::getGeneralType($startType, $endType, $stepType), ...$constantReturnTypes);
	}

	private static function getGeneralType(Type $startType, Type $endType, Type $stepType): Type
	{
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
	private static function getRangeLength(int|float|string $start, int|float|string $end, int|float $step): ?float
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

	/**
	 * @param non-empty-list<int|float|string>|null $rangeValues null when range() was not called
	 */
	private static function getLongRangeType(
		TrinaryLogic $hasStricterRange,
		ConstantIntegerType|ConstantFloatType|ConstantStringType $startConstant,
		ConstantIntegerType|ConstantFloatType|ConstantStringType $endConstant,
		ConstantIntegerType|ConstantFloatType $stepConstant,
		Type $stepType,
		?array $rangeValues,
	): Type
	{
		$type = self::getLongRangeItemsType($startConstant, $endConstant, $stepConstant, $stepType, $rangeValues);
		if (
			$hasStricterRange->yes()
			|| (
				!$startConstant instanceof ConstantFloatType
				&& !$endConstant instanceof ConstantFloatType
				&& !$stepConstant instanceof ConstantFloatType
			)
		) {
			return $type;
		}

		// before PHP 8.3 a float argument produced floats even when none of the arguments had a fractional
		// part, and even for two strings, of which only a numeric one kept its value
		$floatListType = self::getNonEmptyListOfType(new FloatType());
		if ($hasStricterRange->no()) {
			return $floatListType;
		}

		return TypeCombinator::union($type, $floatListType);
	}

	/**
	 * The type of the items the runtime returned. Without them it follows PHP 8.3 for numeric
	 * boundaries and generalizes the arguments for a string one.
	 *
	 * @param non-empty-list<int|float|string>|null $rangeValues
	 */
	private static function getLongRangeItemsType(
		ConstantIntegerType|ConstantFloatType|ConstantStringType $startConstant,
		ConstantIntegerType|ConstantFloatType|ConstantStringType $endConstant,
		ConstantIntegerType|ConstantFloatType $stepConstant,
		Type $stepType,
		?array $rangeValues,
	): Type
	{
		$floatListType = self::getNonEmptyListOfType(new FloatType());

		if ($rangeValues !== null) {
			// range() only ever returns values of a single type
			$firstValue = $rangeValues[0];
			$lastValue = $rangeValues[count($rangeValues) - 1];

			if (is_string($firstValue) || is_string($lastValue)) {
				// a character range consists of single bytes taken from constant boundaries
				return self::getNonEmptyListOfType(TypeCombinator::intersect(
					new StringType(),
					new AccessoryNonEmptyStringType(),
					new AccessoryLiteralStringType(),
				));
			}

			if (is_float($firstValue) || is_float($lastValue)) {
				return $floatListType;
			}

			$bounds = $startConstant instanceof ConstantIntegerType && $endConstant instanceof ConstantIntegerType
				? [$startConstant->getValue(), $endConstant->getValue()]
				: [$firstValue, $lastValue];
		} elseif ($startConstant instanceof ConstantFloatType || $endConstant instanceof ConstantFloatType) {
			return $floatListType;
		} elseif (!$startConstant instanceof ConstantIntegerType || !$endConstant instanceof ConstantIntegerType) {
			return self::getNonEmptyListOfType(
				TypeCombinator::union(
					$startConstant->generalize(GeneralizePrecision::moreSpecific()),
					$endConstant->generalize(GeneralizePrecision::moreSpecific()),
					$stepType->generalize(GeneralizePrecision::moreSpecific()),
				),
			);
		} elseif (floor($stepConstant->getValue()) !== (float) $stepConstant->getValue()) {
			// a step with a fractional part produces floats
			return $floatListType;
		} else {
			$bounds = [$startConstant->getValue(), $endConstant->getValue()];
		}

		// the sequence is monotonic, so the first and the last value are its bounds
		return self::getNonEmptyListOfType(IntegerRangeType::fromInterval(min($bounds), max($bounds)));
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
