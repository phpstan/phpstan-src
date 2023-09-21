<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\Type\Constant\ConstantBooleanType;

final class LooseComparisonHelper
{

	public static function compareConstantScalars(ConstantScalarType $leftType, ConstantScalarType $rightType, PhpVersion $phpVersion): BooleanType
	{
		if ($phpVersion->castsNumbersToStringsOnLooseComparison()) {
			$isNumber = new UnionType([
				new IntegerType(),
				new FloatType(),
			]);

			if ($leftType->isString()->yes() && $leftType->isNumericString()->no() && $isNumber->isSuperTypeOf($rightType)->yes()) {
				$stringValue = (string) $rightType->getValue();
				return new ConstantBooleanType($stringValue === $leftType->getValue());
			}
			if ($rightType->isString()->yes() && $rightType->isNumericString()->no() && $isNumber->isSuperTypeOf($leftType)->yes()) {
				$stringValue = (string) $leftType->getValue();
				return new ConstantBooleanType($stringValue === $rightType->getValue());
			}
		} else {
			if ($leftType->isString()->yes() && $leftType->isNumericString()->no() && $rightType->isFloat()->yes()) {
				$numericPart = (float) $leftType->getValue();
				return new ConstantBooleanType($numericPart === $rightType->getValue());
			}
			if ($rightType->isString()->yes() && $rightType->isNumericString()->no() && $leftType->isFloat()->yes()) {
				$numericPart = (float) $rightType->getValue();
				return new ConstantBooleanType($numericPart === $leftType->getValue());
			}
			if ($leftType->isString()->yes() && $leftType->isNumericString()->no() && $rightType->isInteger()->yes()) {
				$numericPart = (int) $leftType->getValue();
				return new ConstantBooleanType($numericPart === $rightType->getValue());
			}
			if ($rightType->isString()->yes() && $rightType->isNumericString()->no() && $leftType->isInteger()->yes()) {
				$numericPart = (int) $rightType->getValue();
				return new ConstantBooleanType($numericPart === $leftType->getValue());
			}
		}

		// @phpstan-ignore equal.notAllowed
		return new ConstantBooleanType($leftType->getValue() == $rightType->getValue()); // phpcs:ignore
	}

}
