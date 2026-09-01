<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Type\Type;
use function count;
use function is_int;
use function is_string;

/**
 * The statically known array offset a dimension expression selects.
 *
 * @internal
 */
final class VariableWriteOffset
{

	/**
	 * @return int|string|null
	 */
	public static function fromType(Type $dimType)
	{
		$values = $dimType->toArrayKey()->getConstantScalarValues();
		if (count($values) !== 1) {
			return null;
		}
		$value = $values[0];
		if (is_int($value) || is_string($value)) {
			return $value;
		}

		return null;
	}

}
