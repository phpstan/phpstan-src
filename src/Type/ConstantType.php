<?php declare(strict_types = 1);

namespace PHPStan\Type;

/** @api */
interface ConstantType extends Type
{

	public function generalize(GeneralizePrecision $precision): Type;

}
