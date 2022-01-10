<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Mixin;

use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use function array_intersect;
use function count;

class MixinPropertiesClassReflectionExtension implements PropertiesClassReflectionExtension
{

	/** @var array<string, array<string, true>> */
	private array $inProcess = [];

	/**
	 * @param string[] $mixinExcludeClasses
	 */
	public function __construct(private array $mixinExcludeClasses)
	{
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
			if (array_intersect(TypeUtils::getDirectClassNames($type), $this->mixinExcludeClasses) !== []) {
				continue;
			}

			$typeDescription = $type->describe(VerbosityLevel::typeOnly());
			if (isset($this->inProcess[$typeDescription][$propertyName])) {
				continue;
			}

			$this->inProcess[$typeDescription][$propertyName] = true;

			if (!$type->hasProperty($propertyName)->yes()) {
				unset($this->inProcess[$typeDescription][$propertyName]);
				continue;
			}

			$property = $type->getProperty($propertyName, new OutOfClassScope());
			unset($this->inProcess[$typeDescription][$propertyName]);

			return $property;
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
