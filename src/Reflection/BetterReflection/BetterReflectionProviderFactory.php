<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection;

use PHPStan\BetterReflection\Reflector\ClassReflector;
use PHPStan\BetterReflection\Reflector\ConstantReflector;
use PHPStan\BetterReflection\Reflector\FunctionReflector;

interface BetterReflectionProviderFactory
{

	public function create(
		FunctionReflector $functionReflector,
		ClassReflector $classReflector,
		ConstantReflector $constantReflector
	): BetterReflectionProvider;

}
