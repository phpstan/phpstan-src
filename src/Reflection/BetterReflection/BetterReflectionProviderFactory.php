<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection;

use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\ConstantReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;

interface BetterReflectionProviderFactory
{

	public function create(
		FunctionReflector $functionReflector,
		ClassReflector $classReflector,
		ConstantReflector $constantReflector
	): BetterReflectionProvider;

}
