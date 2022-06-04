<?php declare(strict_types = 1);

namespace Bug1396;

use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use function PHPStan\Testing\assertType;

class TypeSpecifier2
{
	/**
	 * @api
	 */
	public function specifyTypesInCondition(
		ConstantScalarType $constantType
	): SpecifiedTypes
	{
		if ($constantType->getValue() === null) {
			return new SpecifiedTypes();
		}

		if (
			$constantType instanceof ConstantStringType
		) {
			assertType('string', $constantType->getValue());
		}
	}
}
