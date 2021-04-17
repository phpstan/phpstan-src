<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\Reflection\ConstantReflection;

interface AlwaysUsedClassConstantsExtension
{

	public function isAlwaysUsed(ConstantReflection $constant): bool;

}
