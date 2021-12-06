<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use function count;
use function get_class;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_nan;
use function is_object;
use function is_string;

/** @api */
class ConstantTypeHelper
{

	/**
	 * @param mixed $value
	 */
	public static function getTypeFromValue($value): Type
	{
		if (is_int($value)) {
			return new ConstantIntegerType($value);
		} elseif (is_float($value)) {
			if (is_nan($value)) {
				return new MixedType();
			}
			return new ConstantFloatType($value);
		} elseif (is_bool($value)) {
			return new ConstantBooleanType($value);
		} elseif ($value === null) {
			return new NullType();
		} elseif (is_string($value)) {
			return new ConstantStringType($value);
		} elseif (is_array($value)) {
			$arrayBuilder = ConstantArrayTypeBuilder::createEmpty();
			if (count($value) > ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT) {
				$arrayBuilder->degradeToGeneralArray();
			}
			foreach ($value as $k => $v) {
				$arrayBuilder->setOffsetValueType(self::getTypeFromValue($k), self::getTypeFromValue($v));
			}
			return $arrayBuilder->getArray();
		} elseif (is_object($value)) {
			return new ObjectType(get_class($value));
		}

		return new MixedType();
	}

}
