<?php declare(strict_types = 1);

namespace PHPStan\Reflection\RequireExtension;

use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

final class RequireExtendsPropertiesClassReflectionExtension
{

	/** @deprecated Use hasInstanceProperty or hasStaticProperty */
	public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
	{
		return $this->findProperty(
			$classReflection,
			$propertyName,
			static fn (Type $type, string $propertyName): TrinaryLogic => $type->hasProperty($propertyName),
			static fn (Type $type, string $propertyName): ExtendedPropertyReflection => $type->getProperty($propertyName, new OutOfClassScope()),
		) !== null;
	}

	/** @deprecated Use getInstanceProperty or getStaticProperty */
	public function getProperty(ClassReflection $classReflection, string $propertyName): ExtendedPropertyReflection
	{
		$property = $this->findProperty(
			$classReflection,
			$propertyName,
			static fn (Type $type, string $propertyName): TrinaryLogic => $type->hasProperty($propertyName),
			static fn (Type $type, string $propertyName): ExtendedPropertyReflection => $type->getProperty($propertyName, new OutOfClassScope()),
		);
		if ($property === null) {
			throw new ShouldNotHappenException();
		}

		return $property;
	}

	public function hasInstanceProperty(ClassReflection $classReflection, string $propertyName): bool
	{
		return $this->findProperty(
			$classReflection,
			$propertyName,
			static fn (Type $type, string $propertyName): TrinaryLogic => $type->hasInstanceProperty($propertyName),
			static fn (Type $type, string $propertyName): ExtendedPropertyReflection => $type->getInstanceProperty($propertyName, new OutOfClassScope()),
		) !== null;
	}

	public function getInstanceProperty(ClassReflection $classReflection, string $propertyName): ExtendedPropertyReflection
	{
		$property = $this->findProperty(
			$classReflection,
			$propertyName,
			static fn (Type $type, string $propertyName): TrinaryLogic => $type->hasInstanceProperty($propertyName),
			static fn (Type $type, string $propertyName): ExtendedPropertyReflection => $type->getInstanceProperty($propertyName, new OutOfClassScope()),
		);
		if ($property === null) {
			throw new ShouldNotHappenException();
		}

		return $property;
	}

	public function hasStaticProperty(ClassReflection $classReflection, string $propertyName): bool
	{
		return $this->findProperty(
			$classReflection,
			$propertyName,
			static fn (Type $type, string $propertyName): TrinaryLogic => $type->hasStaticProperty($propertyName),
			static fn (Type $type, string $propertyName): ExtendedPropertyReflection => $type->getStaticProperty($propertyName, new OutOfClassScope()),
		) !== null;
	}

	public function getStaticProperty(ClassReflection $classReflection, string $propertyName): ExtendedPropertyReflection
	{
		$property = $this->findProperty(
			$classReflection,
			$propertyName,
			static fn (Type $type, string $propertyName): TrinaryLogic => $type->hasStaticProperty($propertyName),
			static fn (Type $type, string $propertyName): ExtendedPropertyReflection => $type->getStaticProperty($propertyName, new OutOfClassScope()),
		);
		if ($property === null) {
			throw new ShouldNotHappenException();
		}

		return $property;
	}

	/**
	 * @param callable(Type, string): TrinaryLogic               $propertyHasser
	 * @param callable(Type, string): ExtendedPropertyReflection $propertyGetter
	 */
	private function findProperty(
		ClassReflection $classReflection,
		string $propertyName,
		callable $propertyHasser,
		callable $propertyGetter,
	): ?ExtendedPropertyReflection
	{
		if (!$classReflection->isInterface()) {
			return null;
		}

		$requireExtendsTags = $classReflection->getRequireExtendsTags();
		foreach ($requireExtendsTags as $requireExtendsTag) {
			$type = $requireExtendsTag->getType();

			if (!$propertyHasser($type, $propertyName)->yes()) {
				continue;
			}

			return $propertyGetter($type, $propertyName);
		}

		$interfaces = $classReflection->getInterfaces();
		foreach ($interfaces as $interface) {
			$property = $this->findProperty($interface, $propertyName, $propertyHasser, $propertyGetter);
			if ($property !== null) {
				return $property;
			}
		}

		return null;
	}

}
