<?php declare(strict_types = 1);

namespace PHPStan\Reflection\RequireExtension;

use PHPStan\Analyser\OutOfClassScope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

#[AutowiredService]
final class RequireExtendsPropertiesClassReflectionExtension
{

	/** @deprecated Use hasInstanceProperty or hasStaticProperty */
	public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
	{
		return $this->findProperty(
			$classReflection,
			$classReflection,
			$propertyName,
			static fn (Type $type, string $propertyName): TrinaryLogic => $type->hasProperty($propertyName),
			static fn (Type $type, string $propertyName, Type $fetchedOnType): ExtendedPropertyReflection => $type->getUnresolvedPropertyPrototype($propertyName, new OutOfClassScope())->withFechedOnType($fetchedOnType)->getTransformedProperty(),
		) !== null;
	}

	/** @deprecated Use getInstanceProperty or getStaticProperty */
	public function getProperty(ClassReflection $classReflection, string $propertyName): ExtendedPropertyReflection
	{
		$property = $this->findProperty(
			$classReflection,
			$classReflection,
			$propertyName,
			static fn (Type $type, string $propertyName): TrinaryLogic => $type->hasProperty($propertyName),
			static fn (Type $type, string $propertyName, Type $fetchedOnType): ExtendedPropertyReflection => $type->getUnresolvedPropertyPrototype($propertyName, new OutOfClassScope())->withFechedOnType($fetchedOnType)->getTransformedProperty(),
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
			$classReflection,
			$propertyName,
			static fn (Type $type, string $propertyName): TrinaryLogic => $type->hasInstanceProperty($propertyName),
			static fn (Type $type, string $propertyName, Type $fetchedOnType): ExtendedPropertyReflection => $type->getUnresolvedInstancePropertyPrototype($propertyName, new OutOfClassScope())->withFechedOnType($fetchedOnType)->getTransformedProperty(),
		) !== null;
	}

	public function getInstanceProperty(ClassReflection $classReflection, string $propertyName): ExtendedPropertyReflection
	{
		$property = $this->findProperty(
			$classReflection,
			$classReflection,
			$propertyName,
			static fn (Type $type, string $propertyName): TrinaryLogic => $type->hasInstanceProperty($propertyName),
			static fn (Type $type, string $propertyName, Type $fetchedOnType): ExtendedPropertyReflection => $type->getUnresolvedInstancePropertyPrototype($propertyName, new OutOfClassScope())->withFechedOnType($fetchedOnType)->getTransformedProperty(),
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
			$classReflection,
			$propertyName,
			static fn (Type $type, string $propertyName): TrinaryLogic => $type->hasStaticProperty($propertyName),
			static fn (Type $type, string $propertyName, Type $fetchedOnType): ExtendedPropertyReflection => $type->getUnresolvedStaticPropertyPrototype($propertyName, new OutOfClassScope())->withFechedOnType($fetchedOnType)->getTransformedProperty(),
		) !== null;
	}

	public function getStaticProperty(ClassReflection $classReflection, string $propertyName): ExtendedPropertyReflection
	{
		$property = $this->findProperty(
			$classReflection,
			$classReflection,
			$propertyName,
			static fn (Type $type, string $propertyName): TrinaryLogic => $type->hasStaticProperty($propertyName),
			static fn (Type $type, string $propertyName, Type $fetchedOnType): ExtendedPropertyReflection => $type->getUnresolvedStaticPropertyPrototype($propertyName, new OutOfClassScope())->withFechedOnType($fetchedOnType)->getTransformedProperty(),
		);
		if ($property === null) {
			throw new ShouldNotHappenException();
		}

		return $property;
	}

	/**
	 * @param callable(Type, string): TrinaryLogic               $propertyHasser
	 * @param callable(Type, string, Type): ExtendedPropertyReflection $propertyGetter
	 */
	private function findProperty(
		ClassReflection $originalClassReflection,
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

			// map static to static(interface)&Base so that it gets resolved against the type the property is fetched on
			return $propertyGetter($type, $propertyName, TypeCombinator::intersect(new StaticType($originalClassReflection), $type));
		}

		$interfaces = $classReflection->getInterfaces();
		foreach ($interfaces as $interface) {
			$property = $this->findProperty($originalClassReflection, $interface, $propertyName, $propertyHasser, $propertyGetter);
			if ($property !== null) {
				return $property;
			}
		}

		return null;
	}

}
