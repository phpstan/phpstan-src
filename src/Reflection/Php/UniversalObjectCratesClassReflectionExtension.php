<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\MixedType;

class UniversalObjectCratesClassReflectionExtension
	implements \PHPStan\Reflection\PropertiesClassReflectionExtension
{

	/** @var string[] */
	private array $classes;

	private ReflectionProvider $reflectionProvider;

	/**
	 * @param string[] $classes
	 */
	public function __construct(ReflectionProvider $reflectionProvider, array $classes)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->classes = $classes;
	}

	public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
	{
		return self::isUniversalObjectCrate(
			$this->reflectionProvider,
			$this->classes,
			$classReflection
		);
	}

	/**
	 * @param string[] $classes
	 */
	public static function isUniversalObjectCrate(
		ReflectionProvider $reflectionProvider,
		array $classes,
		ClassReflection $classReflection
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
		if ($classReflection->hasNativeMethod('__get')) {
			$readableType = ParametersAcceptorSelector::selectSingle($classReflection->getNativeMethod('__get')->getVariants())->getReturnType();
		} else {
			$readableType = new MixedType();
		}

		if ($classReflection->hasNativeMethod('__set')) {
			$writableType = ParametersAcceptorSelector::selectSingle($classReflection->getNativeMethod('__set')->getVariants())->getParameters()[1]->getType();
		} else {
			$writableType = new MixedType();
		}

		return new UniversalObjectCrateProperty($classReflection, $readableType, $writableType);
	}

}
