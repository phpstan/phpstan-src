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
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;

#[AutowiredService]
final class DeprecationProvider
{

	/**
	 * @param ExtensionsCollection<ClassDeprecationExtension> $classDeprecationExtensions
	 * @param ExtensionsCollection<ClassConstantDeprecationExtension> $classConstantDeprecationExtensions
	 * @param ExtensionsCollection<ConstantDeprecationExtension> $constantDeprecationExtensions
	 * @param ExtensionsCollection<EnumCaseDeprecationExtension> $enumCaseDeprecationExtensions
	 * @param ExtensionsCollection<FunctionDeprecationExtension> $functionDeprecationExtensions
	 * @param ExtensionsCollection<MethodDeprecationExtension> $methodDeprecationExtensions
	 * @param ExtensionsCollection<PropertyDeprecationExtension> $propertyDeprecationExtensions
	 */
	public function __construct(
		#[AutowiredExtensions(of: ClassDeprecationExtension::class)]
		private ExtensionsCollection $classDeprecationExtensions,
		#[AutowiredExtensions(of: ClassConstantDeprecationExtension::class)]
		private ExtensionsCollection $classConstantDeprecationExtensions,
		#[AutowiredExtensions(of: ConstantDeprecationExtension::class)]
		private ExtensionsCollection $constantDeprecationExtensions,
		#[AutowiredExtensions(of: EnumCaseDeprecationExtension::class)]
		private ExtensionsCollection $enumCaseDeprecationExtensions,
		#[AutowiredExtensions(of: FunctionDeprecationExtension::class)]
		private ExtensionsCollection $functionDeprecationExtensions,
		#[AutowiredExtensions(of: MethodDeprecationExtension::class)]
		private ExtensionsCollection $methodDeprecationExtensions,
		#[AutowiredExtensions(of: PropertyDeprecationExtension::class)]
		private ExtensionsCollection $propertyDeprecationExtensions,
	)
	{
	}

	public function getPropertyDeprecation(ReflectionProperty $reflectionProperty): ?Deprecation
	{
		foreach ($this->propertyDeprecationExtensions->getAll() as $extension) {
			$deprecation = $extension->getPropertyDeprecation($reflectionProperty);
			if ($deprecation !== null) {
				return $deprecation;
			}
		}

		return null;
	}

	public function getMethodDeprecation(ReflectionMethod $methodReflection): ?Deprecation
	{
		foreach ($this->methodDeprecationExtensions->getAll() as $extension) {
			$deprecation = $extension->getMethodDeprecation($methodReflection);
			if ($deprecation !== null) {
				return $deprecation;
			}
		}

		return null;
	}

	public function getClassConstantDeprecation(ReflectionClassConstant $reflectionConstant): ?Deprecation
	{
		foreach ($this->classConstantDeprecationExtensions->getAll() as $extension) {
			$deprecation = $extension->getClassConstantDeprecation($reflectionConstant);
			if ($deprecation !== null) {
				return $deprecation;
			}
		}

		return null;
	}

	public function getClassDeprecation(ReflectionClass|ReflectionEnum $reflection): ?Deprecation
	{
		foreach ($this->classDeprecationExtensions->getAll() as $extension) {
			$deprecation = $extension->getClassDeprecation($reflection);
			if ($deprecation !== null) {
				return $deprecation;
			}
		}

		return null;
	}

	public function getFunctionDeprecation(ReflectionFunction $reflectionFunction): ?Deprecation
	{
		foreach ($this->functionDeprecationExtensions->getAll() as $extension) {
			$deprecation = $extension->getFunctionDeprecation($reflectionFunction);
			if ($deprecation !== null) {
				return $deprecation;
			}
		}

		return null;
	}

	public function getConstantDeprecation(ReflectionConstant $constantReflection): ?Deprecation
	{
		foreach ($this->constantDeprecationExtensions->getAll() as $extension) {
			$deprecation = $extension->getConstantDeprecation($constantReflection);
			if ($deprecation !== null) {
				return $deprecation;
			}
		}

		return null;
	}

	public function getEnumCaseDeprecation(ReflectionEnumUnitCase|ReflectionEnumBackedCase $enumCaseReflection): ?Deprecation
	{
		foreach ($this->enumCaseDeprecationExtensions->getAll() as $extension) {
			$deprecation = $extension->getEnumCaseDeprecation($enumCaseReflection);
			if ($deprecation !== null) {
				return $deprecation;
			}
		}

		return null;
	}

}
