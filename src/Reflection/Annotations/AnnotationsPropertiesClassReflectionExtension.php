<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Annotations;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\Generic\TemplateTypeHelper;

class AnnotationsPropertiesClassReflectionExtension implements PropertiesClassReflectionExtension
{

	/** @var \PHPStan\Reflection\PropertyReflection[][] */
	private array $properties = [];

	public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
	{
		if (!isset($this->properties[$classReflection->getCacheKey()])) {
			$this->properties[$classReflection->getCacheKey()] = $this->createProperties($classReflection, $classReflection);
		}

		return isset($this->properties[$classReflection->getCacheKey()][$propertyName]);
	}

	public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
	{
		return $this->properties[$classReflection->getCacheKey()][$propertyName];
	}

	/**
	 * @param \PHPStan\Reflection\ClassReflection $classReflection
	 * @param \PHPStan\Reflection\ClassReflection $declaringClass
	 * @return \PHPStan\Reflection\PropertyReflection[]
	 */
	private function createProperties(
		ClassReflection $classReflection,
		ClassReflection $declaringClass
	): array
	{
		$properties = [];
		foreach ($classReflection->getTraits() as $traitClass) {
			$properties += $this->createProperties($traitClass, $classReflection);
		}
		foreach ($classReflection->getParents() as $parentClass) {
			$properties += $this->createProperties($parentClass, $parentClass);
			foreach ($parentClass->getTraits() as $traitClass) {
				$properties += $this->createProperties($traitClass, $parentClass);
			}
		}

		foreach ($classReflection->getInterfaces() as $interfaceClass) {
			$properties += $this->createProperties($interfaceClass, $interfaceClass);
		}

		$fileName = $classReflection->getFileName();
		if ($fileName === false) {
			return $properties;
		}

		$docComment = $classReflection->getNativeReflection()->getDocComment();
		if ($docComment === false) {
			return $properties;
		}

		$propertyTags = $classReflection->getPropertyTags();
		foreach ($propertyTags as $propertyName => $propertyTag) {
			$properties[$propertyName] = new AnnotationPropertyReflection(
				$declaringClass,
				TemplateTypeHelper::resolveTemplateTypes(
					$propertyTag->getType(),
					$classReflection->getActiveTemplateTypeMap()
				),
				$propertyTag->isReadable(),
				$propertyTag->isWritable()
			);
		}

		return $properties;
	}

}
