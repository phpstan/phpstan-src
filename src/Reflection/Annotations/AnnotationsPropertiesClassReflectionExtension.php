<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Annotations;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\NeverType;

final class AnnotationsPropertiesClassReflectionExtension implements PropertiesClassReflectionExtension
{

	/** @var PropertyReflection[][] */
	private array $properties = [];

	public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
	{
		if (!isset($this->properties[$classReflection->getCacheKey()][$propertyName])) {
			$property = $this->findClassReflectionWithProperty($classReflection, $classReflection, $propertyName);
			if ($property === null) {
				return false;
			}
			$this->properties[$classReflection->getCacheKey()][$propertyName] = $property;
		}

		return isset($this->properties[$classReflection->getCacheKey()][$propertyName]);
	}

	public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
	{
		return $this->properties[$classReflection->getCacheKey()][$propertyName];
	}

	private function findClassReflectionWithProperty(
		ClassReflection $classReflection,
		ClassReflection $declaringClass,
		string $propertyName,
	): ?PropertyReflection
	{
		$propertyTags = $classReflection->getPropertyTags();
		if (isset($propertyTags[$propertyName])) {
			$propertyTag = $propertyTags[$propertyName];

			$isReadable = $propertyTags[$propertyName]->isReadable();
			$isWritable = $propertyTags[$propertyName]->isWritable();
			if ($classReflection->hasNativeProperty($propertyName)) {
				$nativeProperty = $classReflection->getNativeProperty($propertyName);
				$isReadable = $isReadable || $nativeProperty->isReadable();
				$isWritable = $isWritable || $nativeProperty->isWritable();
			}

			return new AnnotationPropertyReflection(
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
		while ($parentClass !== null) {
			$methodWithDeclaringClass = $this->findClassReflectionWithProperty($parentClass, $parentClass, $propertyName);
			if ($methodWithDeclaringClass !== null) {
				return $methodWithDeclaringClass;
			}

			foreach ($parentClass->getTraits() as $traitClass) {
				$parentTraitMethodWithDeclaringClass = $this->findClassReflectionWithProperty($traitClass, $parentClass, $propertyName);
				if ($parentTraitMethodWithDeclaringClass === null) {
					continue;
				}

				return $parentTraitMethodWithDeclaringClass;
			}

			$parentClass = $parentClass->getParentClass();
		}

		foreach ($classReflection->getInterfaces() as $interfaceClass) {
			$methodWithDeclaringClass = $this->findClassReflectionWithProperty($interfaceClass, $interfaceClass, $propertyName);
			if ($methodWithDeclaringClass === null) {
				continue;
			}

			return $methodWithDeclaringClass;
		}

		return null;
	}

}
