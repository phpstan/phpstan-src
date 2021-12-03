<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection;

use PHPStan\BetterReflection\Reflector\Reflector;

interface BetterReflectionProviderFactory
{

	public function create(
		Reflector $reflector
	): BetterReflectionProvider;

}
