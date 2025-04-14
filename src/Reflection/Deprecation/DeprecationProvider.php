<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Deprecation;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClassConstant;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumBackedCase;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumUnitCase;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\DependencyInjection\Container;

final class DeprecationProvider
{

	/** @var array<PropertyDeprecationExtension> $propertyDeprecationExtensions */
	private array $propertyDeprecationExtensions;

	/** @var array<MethodDeprecationExtension> $methodDeprecationExtensions */
	private array $methodDeprecationExtensions;

	/** @var array<ClassConstantDeprecationExtension> $classConstantDeprecationExtensions */
	private array $classConstantDeprecationExtensions;

	/** @var array<ClassDeprecationExtension> $classDeprecationExtensions */
	private array $classDeprecationExtensions;

	/** @var array<FunctionDeprecationExtension> $functionDeprecationExtensions */
	private array $functionDeprecationExtensions;

	/** @var array<ConstantDeprecationExtension> $constantDeprecationExtensions */
	private array $constantDeprecationExtensions;

	/** @var array<EnumCaseDeprecationExtension> $enumCaseDeprecationExtensions */
	private array $enumCaseDeprecationExtensions;

	public function __construct(
		Container $container,
	)
	{
		$this->propertyDeprecationExtensions = $container->getServicesByTag('phpstan.propertyDeprecationExtension');
		$this->methodDeprecationExtensions = $container->getServicesByTag('phpstan.methodDeprecationExtension');
		$this->classConstantDeprecationExtensions = $container->getServicesByTag('phpstan.classConstantDeprecationExtension');
		$this->classDeprecationExtensions = $container->getServicesByTag('phpstan.classDeprecationExtension');
		$this->functionDeprecationExtensions = $container->getServicesByTag('phpstan.functionDeprecationExtension');
		$this->constantDeprecationExtensions = $container->getServicesByTag('phpstan.constantDeprecationExtension');
		$this->enumCaseDeprecationExtensions = $container->getServicesByTag('phpstan.enumCaseDeprecationExtension');
	}

	public function getPropertyDeprecation(ReflectionProperty $reflectionProperty): ?Deprecation
	{
		foreach ($this->propertyDeprecationExtensions as $extension) {
			$deprecation = $extension->getPropertyDeprecation($reflectionProperty);
			if ($deprecation !== null) {
				return $deprecation;
			}
		}

		return null;
	}

	public function getMethodDeprecation(ReflectionMethod $methodReflection): ?Deprecation
	{
		foreach ($this->methodDeprecationExtensions as $extension) {
			$deprecation = $extension->getMethodDeprecation($methodReflection);
			if ($deprecation !== null) {
				return $deprecation;
			}
		}

		return null;
	}

	public function getClassConstantDeprecation(ReflectionClassConstant $reflectionConstant): ?Deprecation
	{
		foreach ($this->classConstantDeprecationExtensions as $extension) {
			$deprecation = $extension->getClassConstantDeprecation($reflectionConstant);
			if ($deprecation !== null) {
				return $deprecation;
			}
		}

		return null;
	}

	public function isClassDeprecated(ReflectionClass|ReflectionEnum $reflection): ?Deprecation
	{
		foreach ($this->classDeprecationExtensions as $extension) {
			$deprecation = $extension->getClassDeprecation($reflection);
			if ($deprecation !== null) {
				return $deprecation;
			}
		}

		return null;
	}

	public function getFunctionDeprecation(ReflectionFunction $reflectionFunction): ?Deprecation
	{
		foreach ($this->functionDeprecationExtensions as $extension) {
			$deprecation = $extension->getFunctionDeprecation($reflectionFunction);
			if ($deprecation !== null) {
				return $deprecation;
			}
		}

		return null;
	}

	public function getConstantDeprecation(ReflectionConstant $constantReflection): ?Deprecation
	{
		foreach ($this->constantDeprecationExtensions as $extension) {
			$deprecation = $extension->getConstantDeprecation($constantReflection);
			if ($deprecation !== null) {
				return $deprecation;
			}
		}

		return null;
	}

	public function getEnumCaseDeprecation(ReflectionEnumUnitCase|ReflectionEnumBackedCase $enumCaseReflection): ?Deprecation
	{
		foreach ($this->enumCaseDeprecationExtensions as $extension) {
			$deprecation = $extension->getEnumCaseDeprecation($enumCaseReflection);
			if ($deprecation !== null) {
				return $deprecation;
			}
		}

		return null;
	}

}
