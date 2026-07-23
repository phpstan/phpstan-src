<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ClassReflection;

/**
 * A Type that represents an object with a known class name.
 *
 * Implemented by ObjectType, StaticType, ThisType, EnumCaseObjectType, ClosureType,
 * and GenericObjectType. Provides access to the class name and its ClassReflection.
 *
 * This interface is used when code needs to work with any object type that has a
 * specific class — for example, Scope::resolveTypeByName() returns TypeWithClassName
 * because the resolved type always has a known class.
 *
 * Note: Do not use `instanceof TypeWithClassName` to check if a type is an object.
 * Use `$type->getObjectClassNames()` or `$type->isObject()` instead, which correctly
 * handles union types and intersection types.
 *
 * @api
 * @api-do-not-implement
 */
#[InstanceofDeprecated(insteadUse: 'Type::getObjectClassNames() or Type::getObjectClassReflections()')]
interface TypeWithClassName extends Type
{

	public function getClassName(): string;

	/**
	 * Returns this type projected onto an ancestor class, preserving generic type arguments.
	 */
	public function getAncestorWithClassName(string $className): ?self;

	public function getClassReflection(): ?ClassReflection;

}
