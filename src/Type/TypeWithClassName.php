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
 */
interface TypeWithClassName extends Type
{

	/**
	 * Returns the fully qualified class name (without leading backslash).
	 */
	public function getClassName(): string;

	/**
	 * Walks the type's class hierarchy to find an ancestor matching the given class name.
	 *
	 * Returns a TypeWithClassName representing the type projected onto that ancestor,
	 * or null if the class is not in the hierarchy. Preserves generic type arguments
	 * when walking through the hierarchy.
	 */
	public function getAncestorWithClassName(string $className): ?self;

	/**
	 * Returns the ClassReflection for this type's class, or null if the class
	 * cannot be reflected (e.g. the class doesn't exist).
	 */
	public function getClassReflection(): ?ClassReflection;

}
