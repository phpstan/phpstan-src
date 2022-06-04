<?php declare(strict_types = 1);

namespace Bug1396;

use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function doFoo(
		ConstantScalarType $constantType
	): SpecifiedTypes
	{
		assertType('bool|float|int|string|null', $constantType->getValue());

		if ($constantType->getValue() === null) {
			return new SpecifiedTypes();
		}

		assertType('bool|float|int|string', $constantType->getValue());

		if (
			$constantType instanceof ConstantStringType
		) {
			assertType('string', $constantType->getValue());
		}
		assertType('bool|float|int|string', $constantType->getValue());
	}
}
