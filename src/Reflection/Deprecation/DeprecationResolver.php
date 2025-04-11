<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Deprecation;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClassConstant;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;

final class DeprecationResolver
{

	/**
	 * @param list<PropertyDeprecationProvider> $propertyDeprecationProviders
	 * @param list<MethodDeprecationProvider> $methodDeprecationProviders
	 * @param list<ClassConstantDeprecationProvider> $classConstantDeprecationProviders
	 * @param list<ClassDeprecationProvider> $classDeprecationProviders
	 * @param list<FunctionDeprecationProvider> $functionDeprecationProviders
	 * @param list<ConstantDeprecationProvider> $constantDeprecationProviders
	 */
	public function __construct(
		private array $propertyDeprecationProviders,
		private array $methodDeprecationProviders,
		private array $classConstantDeprecationProviders,
		private array $classDeprecationProviders,
		private array $functionDeprecationProviders,
		private array $constantDeprecationProviders,
	)
	{
	}

	public function getPropertyDeprecation(ReflectionProperty $reflectionProperty): ?Deprecation
	{
		foreach ($this->propertyDeprecationProviders as $provider) {
			$deprecation = $provider->getPropertyDeprecation($reflectionProperty);
			if ($deprecation !== null) {
				return $deprecation;
			}
		}

		return null;
	}

	public function getMethodDeprecation(ReflectionMethod $methodReflection): ?Deprecation
	{
		foreach ($this->methodDeprecationProviders as $provider) {
			$deprecation = $provider->getMethodDeprecation($methodReflection);
			if ($deprecation !== null) {
				return $deprecation;
			}
		}

		return null;
	}

	public function getClassConstantDeprecation(ReflectionClassConstant $reflectionConstant): ?Deprecation
	{
		foreach ($this->classConstantDeprecationProviders as $provider) {
			$deprecation = $provider->getClassConstantDeprecation($reflectionConstant);
			if ($deprecation !== null) {
				return $deprecation;
			}
		}

		return null;
	}

	public function isClassDeprecated(ReflectionClass|ReflectionEnum $reflection): ?Deprecation
	{
		foreach ($this->classDeprecationProviders as $provider) {
			$deprecation = $provider->getClassDeprecation($reflection);
			if ($deprecation !== null) {
				return $deprecation;
			}
		}

		return null;
	}

	public function getFunctionDeprecation(ReflectionFunction $reflectionFunction): ?Deprecation
	{
		foreach ($this->functionDeprecationProviders as $provider) {
			$deprecation = $provider->getFunctionDeprecation($reflectionFunction);
			if ($deprecation !== null) {
				return $deprecation;
			}
		}

		return null;
	}

	public function getConstantDeprecation(ReflectionConstant $constantReflection): ?Deprecation
	{
		foreach ($this->constantDeprecationProviders as $provider) {
			$deprecation = $provider->getConstantDeprecation($constantReflection);
			if ($deprecation !== null) {
				return $deprecation;
			}
		}

		return null;
	}

}
