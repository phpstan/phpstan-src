<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\MixedType;

final class UniversalObjectCratesClassReflectionExtension
	implements PropertiesClassReflectionExtension
{

	/**
	 * @param string[] $classes
	 */
	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private array $classes,
		private AnnotationsPropertiesClassReflectionExtension $annotationClassReflection,
	)
	{
	}

	public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
	{
		return self::isUniversalObjectCrate(
			$this->reflectionProvider,
			$this->classes,
			$classReflection,
		);
	}

	/**
	 * @param string[] $classes
	 */
	public static function isUniversalObjectCrate(
		ReflectionProvider $reflectionProvider,
		array $classes,
		ClassReflection $classReflection,
	): bool
	{
		foreach ($classes as $className) {
			if (!$reflectionProvider->hasClass($className)) {
				continue;
			}

			if (
				$classReflection->getName() === $className
				|| $classReflection->isSubclassOf($className)
			) {
				return true;
			}
		}

		return false;
	}

	public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
	{
		if ($this->annotationClassReflection->hasProperty($classReflection, $propertyName)) {
			return $this->annotationClassReflection->getProperty($classReflection, $propertyName);
		}

		if ($classReflection->hasNativeMethod('__get')) {
			$readableType = $classReflection->getNativeMethod('__get')->getOnlyVariant()->getReturnType();
		} else {
			$readableType = new MixedType();
		}

		if ($classReflection->hasNativeMethod('__set')) {
			$writableType = $classReflection->getNativeMethod('__set')->getOnlyVariant()->getParameters()[1]->getType();
		} else {
			$writableType = new MixedType();
		}

		return new UniversalObjectCrateProperty($classReflection, $readableType, $writableType);
	}

}
