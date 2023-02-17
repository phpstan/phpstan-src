<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;

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

		// @phpstan-ignore-next-line
		return new ConstantBooleanType($leftType->getValue() == $rightType->getValue()); // phpcs:ignore
	}

	public static function compareZero(Type $left, Type $right, PhpVersion $phpVersion): ?ConstantBooleanType
	{
		$zero = new UnionType([
			new ConstantIntegerType(0),
			new ConstantFloatType(0.0),
		]);

		$isNumber = new UnionType([
			new IntegerType(),
			new FloatType(),
		]);

		if (
			$left->isString()->yes() && $left->isNumericString()->no()
			&& $isNumber->isSuperTypeOf($right)->yes()
		) {
			if ($zero->isSuperTypeOf($right)->yes()) {
				if (!$phpVersion->castsNumbersToStringsOnLooseComparison()) {
					return new ConstantBooleanType(true);
				}
				return new ConstantBooleanType(false);
			}
			if ($phpVersion->castsNumbersToStringsOnLooseComparison()) {
				return new ConstantBooleanType(false);
			}
		}

		if (
			$right->isString()->yes() && $right->isNumericString()->no()
			&& $isNumber->isSuperTypeOf($left)->yes()
		) {
			if ($zero->isSuperTypeOf($left)->yes()) {
				if (!$phpVersion->castsNumbersToStringsOnLooseComparison()) {
					return new ConstantBooleanType(true);
				}
				return new ConstantBooleanType(false);
			}
			if ($phpVersion->castsNumbersToStringsOnLooseComparison()) {
				return new ConstantBooleanType(false);
			}
		}

		return null;
	}

}
