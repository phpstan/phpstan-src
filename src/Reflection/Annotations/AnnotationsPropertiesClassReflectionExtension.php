<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Annotations;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Generic\TemplateTypeHelper;

class AnnotationsPropertiesClassReflectionExtension implements PropertiesClassReflectionExtension
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
			$defaultType = $propertyTag->getReadableType() ?? $propertyTag->getWritableType();
			if ($defaultType === null) {
				throw new ShouldNotHappenException();
			}

			return new AnnotationPropertyReflection(
				$declaringClass,
				TemplateTypeHelper::resolveTemplateTypes(
					$propertyTag->getReadableType() ?? $defaultType,
					$classReflection->getActiveTemplateTypeMap(),
				),
				TemplateTypeHelper::resolveTemplateTypes(
					$propertyTag->getWritableType() ?? $defaultType,
					$classReflection->getActiveTemplateTypeMap(),
				),
				$propertyTags[$propertyName]->isReadable(),
				$propertyTags[$propertyName]->isWritable(),
			);
		}

		foreach ($classReflection->getTraits() as $traitClass) {
			$methodWithDeclaringClass = $this->findClassReflectionWithProperty($traitClass, $classReflection, $propertyName);
			if ($methodWithDeclaringClass === null) {
				continue;
			}

			return $methodWithDeclaringClass;
		}

		foreach ($classReflection->getParents() as $parentClass) {
			$methodWithDeclaringClass = $this->findClassReflectionWithProperty($parentClass, $parentClass, $propertyName);
			if ($methodWithDeclaringClass === null) {
				foreach ($parentClass->getTraits() as $traitClass) {
					$parentTraitMethodWithDeclaringClass = $this->findClassReflectionWithProperty($traitClass, $parentClass, $propertyName);
					if ($parentTraitMethodWithDeclaringClass === null) {
						continue;
					}

					return $parentTraitMethodWithDeclaringClass;
				}
				continue;
			}

			return $methodWithDeclaringClass;
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
