<?php declare(strict_types = 1);

namespace PHPStan\Reflection\RequireExtension;

use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\ShouldNotHappenException;

final class RequireExtendsPropertiesClassReflectionExtension implements PropertiesClassReflectionExtension
{

	public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
	{
		return $this->findProperty($classReflection, $propertyName) !== null;
	}

	public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
	{
		$property = $this->findProperty($classReflection, $propertyName);
		if ($property === null) {
			throw new ShouldNotHappenException();
		}

		return $property;
	}

	private function findProperty(ClassReflection $classReflection, string $propertyName): ?PropertyReflection
	{
		if (!$classReflection->isInterface()) {
			return null;
		}

		$requireExtendsTags = $classReflection->getRequireExtendsTags();
		foreach ($requireExtendsTags as $requireExtendsTag) {
			$type = $requireExtendsTag->getType();

			if (!$type->hasProperty($propertyName)->yes()) {
				continue;
			}

			return $type->getProperty($propertyName, new OutOfClassScope());
		}

		$interfaces = $classReflection->getInterfaces();
		foreach ($interfaces as $interface) {
			$property = $this->findProperty($interface, $propertyName);
			if ($property !== null) {
				return $property;
			}
		}

		return null;
	}

}
