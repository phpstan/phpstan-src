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

	/** @var ?array<PropertyDeprecationExtension> $propertyDeprecationExtensions */
	private ?array $propertyDeprecationExtensions = null;

	/** @var ?array<MethodDeprecationExtension> $methodDeprecationExtensions */
	private ?array $methodDeprecationExtensions = null;

	/** @var ?array<ClassConstantDeprecationExtension> $classConstantDeprecationExtensions */
	private ?array $classConstantDeprecationExtensions = null;

	/** @var ?array<ClassDeprecationExtension> $classDeprecationExtensions */
	private ?array $classDeprecationExtensions = null;

	/** @var ?array<FunctionDeprecationExtension> $functionDeprecationExtensions */
	private ?array $functionDeprecationExtensions = null;

	/** @var ?array<ConstantDeprecationExtension> $constantDeprecationExtensions */
	private ?array $constantDeprecationExtensions = null;

	/** @var ?array<EnumCaseDeprecationExtension> $enumCaseDeprecationExtensions */
	private ?array $enumCaseDeprecationExtensions = null;

	public function __construct(
		private Container $container,
	)
	{
	}

	public function getPropertyDeprecation(ReflectionProperty $reflectionProperty): ?Deprecation
	{
		$this->propertyDeprecationExtensions ??= $this->container->getServicesByTag(PropertyDeprecationExtension::PROPERTY_EXTENSION_TAG);

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
		$this->methodDeprecationExtensions ??= $this->container->getServicesByTag(MethodDeprecationExtension::METHOD_EXTENSION_TAG);

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
		$this->classConstantDeprecationExtensions ??= $this->container->getServicesByTag(ClassConstantDeprecationExtension::CLASS_CONSTANT_EXTENSION_TAG);

		foreach ($this->classConstantDeprecationExtensions as $extension) {
			$deprecation = $extension->getClassConstantDeprecation($reflectionConstant);
			if ($deprecation !== null) {
				return $deprecation;
			}
		}

		return null;
	}

	public function getClassDeprecation(ReflectionClass|ReflectionEnum $reflection): ?Deprecation
	{
		$this->classDeprecationExtensions ??= $this->container->getServicesByTag(ClassDeprecationExtension::CLASS_EXTENSION_TAG);

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
		$this->functionDeprecationExtensions ??= $this->container->getServicesByTag(FunctionDeprecationExtension::FUNCTION_EXTENSION_TAG);

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
		$this->constantDeprecationExtensions ??= $this->container->getServicesByTag(ConstantDeprecationExtension::CONSTANT_EXTENSION_TAG);

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
		$this->enumCaseDeprecationExtensions ??= $this->container->getServicesByTag(EnumCaseDeprecationExtension::ENUM_CASE_EXTENSION_TAG);

		foreach ($this->enumCaseDeprecationExtensions as $extension) {
			$deprecation = $extension->getEnumCaseDeprecation($enumCaseReflection);
			if ($deprecation !== null) {
				return $deprecation;
			}
		}

		return null;
	}

}
