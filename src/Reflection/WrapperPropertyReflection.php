<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

interface WrapperPropertyReflection extends PropertyReflection
{

	public function getOriginalReflection(): PropertyReflection;

}
