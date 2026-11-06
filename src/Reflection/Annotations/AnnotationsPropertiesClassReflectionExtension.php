<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Annotations;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\NeverType;
use function array_key_exists;

// autoTag: false - wired explicitly in ClassReflectionExtensionRegistry, must not be tagged
#[AutowiredService(autoTag: false)]
final class AnnotationsPropertiesClassReflectionExtension implements PropertiesClassReflectionExtension
{

	/** @var array<string, array<string, ExtendedPropertyReflection|null>> */
	private array $properties = [];

	public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
	{
		return $this->findProperty($classReflection, $propertyName) !== null;
	}

	public function getProperty(ClassReflection $classReflection, string $propertyName): ExtendedPropertyReflection
	{
		$property = $this->findProperty($classReflection, $propertyName);
		if ($property === null) {
			throw new ShouldNotHappenException();
		}

		return $property;
	}

	/**
	 * Remembers misses too: the lookup recurses into the parent and every
	 * interface, which reach the same ancestors many times over.
	 */
	private function findProperty(ClassReflection $classReflection, string $propertyName): ?ExtendedPropertyReflection
	{
		$cacheKey = $classReflection->getCacheKey();
		if (!array_key_exists($propertyName, $this->properties[$cacheKey] ?? [])) {
			$this->properties[$cacheKey][$propertyName] = $this->findClassReflectionWithProperty($classReflection, $classReflection, $propertyName);
		}

		return $this->properties[$cacheKey][$propertyName];
	}

	private function findClassReflectionWithProperty(
		ClassReflection $classReflection,
		ClassReflection $declaringClass,
		string $propertyName,
	): ?ExtendedPropertyReflection
	{
		$propertyTags = $classReflection->getPropertyTags();
		if (isset($propertyTags[$propertyName])) {
			$propertyTag = $propertyTags[$propertyName];

			$isReadable = $propertyTags[$propertyName]->isReadable();
			$isWritable = $propertyTags[$propertyName]->isWritable();
			if ($classReflection->hasNativeProperty($propertyName)) {
				$nativeProperty = $classReflection->getNativeProperty($propertyName);
				if (!$nativeProperty->isPrivate() && !$nativeProperty->isStatic()) {
					$isReadable = $isReadable || $nativeProperty->isReadable();
					$isWritable = $isWritable || $nativeProperty->isWritable();
				}
			}

			return new AnnotationPropertyReflection(
				$propertyName,
				$declaringClass,
				TemplateTypeHelper::resolveTemplateTypes(
					$propertyTag->getReadableType() ?? new NeverType(),
					$classReflection->getActiveTemplateTypeMap(),
					$classReflection->getCallSiteVarianceMap(),
					TemplateTypeVariance::createCovariant(),
				),
				TemplateTypeHelper::resolveTemplateTypes(
					$propertyTag->getWritableType() ?? new NeverType(),
					$classReflection->getActiveTemplateTypeMap(),
					$classReflection->getCallSiteVarianceMap(),
					TemplateTypeVariance::createContravariant(),
				),
				$isReadable,
				$isWritable,
			);
		}

		foreach ($classReflection->getTraits() as $traitClass) {
			$methodWithDeclaringClass = $this->findClassReflectionWithProperty($traitClass, $classReflection, $propertyName);
			if ($methodWithDeclaringClass === null) {
				continue;
			}

			return $methodWithDeclaringClass;
		}

		$parentClass = $classReflection->getParentClass();
		if ($parentClass !== null) {
			$methodWithDeclaringClass = $this->findProperty($parentClass, $propertyName);
			if ($methodWithDeclaringClass !== null) {
				return $methodWithDeclaringClass;
			}
		}

		foreach ($classReflection->getInterfaces() as $interfaceClass) {
			$methodWithDeclaringClass = $this->findProperty($interfaceClass, $propertyName);
			if ($methodWithDeclaringClass === null) {
				continue;
			}

			return $methodWithDeclaringClass;
		}

		return null;
	}

}
