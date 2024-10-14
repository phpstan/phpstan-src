<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\Type\Constant\ConstantBooleanType;

final class LooseComparisonHelper
{

	/**
	 * @deprecated Use getConstantScalarValuesForComparison instead
	 */
	public static function compareConstantScalars(ConstantScalarType $leftType, ConstantScalarType $rightType, PhpVersion $phpVersion): BooleanType
	{
		[$leftValue, $rightValue] = self::getConstantScalarValuesForComparison($leftType, $rightType, $phpVersion);

		// @phpstan-ignore equal.notAllowed
		return new ConstantBooleanType($leftValue == $rightValue); // phpcs:ignore
	}

	/**
	 * @return array{scalar|null, scalar|null}
	 */
	public static function getConstantScalarValuesForComparison(ConstantScalarType $leftType, ConstantScalarType $rightType, PhpVersion $phpVersion): array
	{
		if ($phpVersion->castsNumbersToStringsOnLooseComparison()) {
			$isNumber = new UnionType([
				new IntegerType(),
				new FloatType(),
			]);

			if ($leftType->isString()->yes() && $leftType->isNumericString()->no() && $isNumber->isSuperTypeOf($rightType)->yes()) {
				return [$leftType->getValue(), (string) $rightType->getValue()];
			}
			if ($rightType->isString()->yes() && $rightType->isNumericString()->no() && $isNumber->isSuperTypeOf($leftType)->yes()) {
				return [(string) $leftType->getValue(), $rightType->getValue()];
			}
		} else {
			if ($leftType->isString()->yes() && $leftType->isNumericString()->no() && $rightType->isFloat()->yes()) {
				return [(float) $leftType->getValue(), $rightType->getValue()];
			}
			if ($rightType->isString()->yes() && $rightType->isNumericString()->no() && $leftType->isFloat()->yes()) {
				return [$leftType->getValue(), (float) $rightType->getValue()];
			}
			if ($leftType->isString()->yes() && $leftType->isNumericString()->no() && $rightType->isInteger()->yes()) {
				return [(int) $leftType->getValue(), $rightType->getValue()];
			}
			if ($rightType->isString()->yes() && $rightType->isNumericString()->no() && $leftType->isInteger()->yes()) {
				return [$leftType->getValue(), (int) $rightType->getValue()];
			}
		}

		return [$leftType->getValue(), $rightType->getValue()];
	}

}
