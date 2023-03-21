<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Annotations;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
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
		$propertyReadTags = $classReflection->getPropertyReadTags();
		$propertyWriteTags = $classReflection->getPropertyWriteTags();
		if (isset($propertyReadTags[$propertyName]) || isset($propertyWriteTags[$propertyName])) {
			$readableType = !isset($propertyReadTags[$propertyName]) ? null : TemplateTypeHelper::resolveTemplateTypes(
				$propertyReadTags[$propertyName]->getType(),
				$classReflection->getActiveTemplateTypeMap(),
			);
			$writableType = !isset($propertyWriteTags[$propertyName]) ? null : TemplateTypeHelper::resolveTemplateTypes(
				$propertyWriteTags[$propertyName]->getType(),
				$classReflection->getActiveTemplateTypeMap(),
			);

			return new AnnotationPropertyReflection(
				$declaringClass,
				$readableType,
				$writableType,
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
