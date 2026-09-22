<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Constant\ConstantBooleanType;
use function is_float;
use function is_nan;

#[ReferencedByTurboExtension(key: 'looseComparisonHelper')]
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
				if (self::isNan($rightType->getValue())) {
					return new ConstantBooleanType(false);
				}

				$stringValue = (string) $rightType->getValue();
				return new ConstantBooleanType($stringValue === $leftType->getValue());
			}
			if ($rightType->isString()->yes() && $rightType->isNumericString()->no() && $isNumber->isSuperTypeOf($leftType)->yes()) {
				if (self::isNan($leftType->getValue())) {
					return new ConstantBooleanType(false);
				}

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

	/**
	 * NAN is never cast to a string: it compares equal to nothing, not even to
	 * 'NAN' (and casting it warns since PHP 8.5).
	 */
	private static function isNan(bool|float|int|string|null $value): bool
	{
		return is_float($value) && is_nan($value);
	}

}
