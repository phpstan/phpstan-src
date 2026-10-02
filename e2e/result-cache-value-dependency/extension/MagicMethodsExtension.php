<?php declare(strict_types = 1);

namespace ResultCacheE2EValueDependency;

use PHPStan\Analyser\DeclarationDependencyTracker;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;

/**
 * Magic::greet() returns the "greeting" parameter - an int when it's numeric. A class reflection
 * extension gets no Scope, and what it declares is reused by every file using the class, so it
 * tracks the parameter on the class.
 */
final class MagicMethodsExtension implements MethodsClassReflectionExtension
{

	public function __construct(private DeclarationDependencyTracker $declarationDependencyTracker)
	{
	}

	public function hasMethod(ClassReflection $classReflection, string $methodName): bool
	{
		return $classReflection->getName() === Magic::class && $methodName === 'greet';
	}

	public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
	{
		$this->declarationDependencyTracker->trackValueDependency($classReflection, ParameterValueExtension::class, 'greeting');
		$greeting = Container::getParameter('greeting') ?? '';

		return new MagicMethodReflection(
			$classReflection,
			$methodName,
			is_numeric($greeting) ? new ConstantIntegerType((int) $greeting) : new ConstantStringType($greeting),
		);
	}

}
