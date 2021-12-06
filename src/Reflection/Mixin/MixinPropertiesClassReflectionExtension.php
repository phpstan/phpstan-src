<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Mixin;

use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\TypeUtils;
use function array_intersect;
use function count;

class MixinPropertiesClassReflectionExtension implements PropertiesClassReflectionExtension
{

	/** @var string[] */
	private array $mixinExcludeClasses;

	/**
	 * @param string[] $mixinExcludeClasses
	 */
	public function __construct(array $mixinExcludeClasses)
	{
		$this->mixinExcludeClasses = $mixinExcludeClasses;
	}

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
		$mixinTypes = $classReflection->getResolvedMixinTypes();
		foreach ($mixinTypes as $type) {
			if (count(array_intersect(TypeUtils::getDirectClassNames($type), $this->mixinExcludeClasses)) > 0) {
				continue;
			}

			if (!$type->hasProperty($propertyName)->yes()) {
				continue;
			}

			return $type->getProperty($propertyName, new OutOfClassScope());
		}

		foreach ($classReflection->getParents() as $parentClass) {
			$property = $this->findProperty($parentClass, $propertyName);
			if ($property === null) {
				continue;
			}

			return $property;
		}

		return null;
	}

}
