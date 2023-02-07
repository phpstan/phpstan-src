<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use function array_keys;
use function count;

final class ComparisonHelper
{

	public static function looseCompareConstantScalars(ConstantScalarType $leftType, ConstantScalarType $rightType, PhpVersion $phpVersion): BooleanType
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

	/**
	 * @param callable(Type, Type): BooleanType $valueComparisonCallback
	 */
	public static function compareConstantArrayType(ConstantArrayType $leftType, ConstantArrayType $rightType, callable $valueComparisonCallback): BooleanType
	{
		$leftKeyTypes = $leftType->getKeyTypes();
		$rightKeyTypes = $rightType->getKeyTypes();
		$leftValueTypes = $leftType->getValueTypes();
		$rightValueTypes = $rightType->getValueTypes();

		$resultType = new ConstantBooleanType(true);

		foreach ($leftKeyTypes as $i => $leftKeyType) {
			$leftOptional = $leftType->isOptionalKey($i);
			if ($leftOptional) {
				$resultType = new BooleanType();
			}

			if (count($rightKeyTypes) === 0) {
				if (!$leftOptional) {
					return new ConstantBooleanType(false);
				}
				continue;
			}

			$found = false;
			foreach ($rightKeyTypes as $j => $rightKeyType) {
				unset($rightKeyTypes[$j]);

				if ($leftKeyType->equals($rightKeyType)) {
					$found = true;
					break;
				} elseif (!$rightType->isOptionalKey($j)) {
					return new ConstantBooleanType(false);
				}
			}

			if (!$found) {
				if (!$leftOptional) {
					return new ConstantBooleanType(false);
				}
				continue;
			}

			if (!isset($j)) {
				throw new ShouldNotHappenException();
			}

			$rightOptional = $rightType->isOptionalKey($j);
			if ($rightOptional) {
				$resultType = new BooleanType();
				if ($leftOptional) {
					continue;
				}
			}

			$leftIdenticalToRight = $valueComparisonCallback($leftValueTypes[$i], $rightValueTypes[$j]);
			if ($leftIdenticalToRight->isFalse()->yes()) {
				return new ConstantBooleanType(false);
			}
			$resultType = TypeCombinator::union($resultType, $leftIdenticalToRight);
		}

		foreach (array_keys($rightKeyTypes) as $j) {
			if (!$rightType->isOptionalKey($j)) {
				return new ConstantBooleanType(false);
			}
			$resultType = new BooleanType();
		}

		return $resultType->toBoolean();
	}

}
