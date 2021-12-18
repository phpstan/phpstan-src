<?php declare(strict_types = 1);

namespace PHPStan\Type;

/** @api */
interface ConstantScalarType extends ConstantType
{

	public function getValue(): int|float|string|bool|null;

}
