<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;

class UnionTypeHelper
{

	/**
	 * @param \PHPStan\Type\Type[] $types
	 * @return string[]
	 */
	public static function getReferencedClasses(array $types): array
	{
		$subTypeClasses = [];
		foreach ($types as $type) {
			$subTypeClasses[] = $type->getReferencedClasses();
		}

		return array_merge(...$subTypeClasses);
	}

	/**
	 * @param \PHPStan\Type\Type[] $types
	 * @return \PHPStan\Type\Type[]
	 */
	public static function sortTypes(array $types): array
	{
		usort($types, static function (Type $a, Type $b): int {
			if ($a instanceof NullType) {
				return 1;
			} elseif ($b instanceof NullType) {
				return -1;
			}

			if ($a instanceof AccessoryType) {
				if ($b instanceof AccessoryType) {
					return strcasecmp($a->describe(VerbosityLevel::value()), $b->describe(VerbosityLevel::value()));
				}

				return 1;
			}
			if ($b instanceof AccessoryType) {
				return -1;
			}

			$aIsBool = $a instanceof ConstantBooleanType;
			$bIsBool = $b instanceof ConstantBooleanType;
			if ($aIsBool && !$bIsBool) {
				return 1;
			} elseif ($bIsBool && !$aIsBool) {
				return -1;
			}
			if ($a instanceof ConstantScalarType && !$b instanceof ConstantScalarType) {
				return -1;
			} elseif (!$a instanceof ConstantScalarType && $b instanceof ConstantScalarType) {
				return 1;
			}

			if (
				(
					$a instanceof ConstantIntegerType
					|| $a instanceof ConstantFloatType
				)
				&& (
					$b instanceof ConstantIntegerType
					|| $b instanceof ConstantFloatType
				)
			) {
				$cmp = $a->getValue() <=> $b->getValue();
				if ($cmp !== 0) {
					return $cmp;
				}
				if ($a instanceof ConstantIntegerType && $b instanceof ConstantFloatType) {
					return -1;
				}
				if ($b instanceof ConstantIntegerType && $a instanceof ConstantFloatType) {
					return 1;
				}
				return 0;
			}

			if ($a instanceof IntegerRangeType && $b instanceof IntegerRangeType) {
				return $a->getMin() <=> $b->getMin();
			}

			if ($a instanceof ConstantStringType && $b instanceof ConstantStringType) {
				return strcasecmp($a->getValue(), $b->getValue());
			}

			if ($a instanceof ConstantArrayType && $b instanceof ConstantArrayType) {
				if ($a->isEmpty()) {
					if ($b->isEmpty()) {
						return 0;
					}

					return -1;
				} elseif ($b->isEmpty()) {
					return 1;
				}

				return strcasecmp($a->describe(VerbosityLevel::value()), $b->describe(VerbosityLevel::value()));
			}

			return strcasecmp($a->describe(VerbosityLevel::typeOnly()), $b->describe(VerbosityLevel::typeOnly()));
		});
		return $types;
	}

}
