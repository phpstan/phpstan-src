<?php declare(strict_types = 1);

namespace PHPStan\Type;

/** @api */
interface ConstantScalarType extends Type
{

	/**
	 * @return int|float|string|bool|null
	 */
	public function getValue();

}
