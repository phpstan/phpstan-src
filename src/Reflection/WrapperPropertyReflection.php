<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

interface WrapperPropertyReflection extends ExtendedPropertyReflection
{

	public function getOriginalReflection(): ExtendedPropertyReflection;

}
