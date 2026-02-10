<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Type\Type;

/**
 * Lazy property reflection that defers template type resolution.
 *
 * When accessing a property on a generic type, the property's types need to be
 * transformed by substituting template type parameters with their concrete arguments.
 * This interface allows that resolution to be deferred and configured:
 *
 * - getNakedProperty() returns the property as declared (before template substitution)
 * - getTransformedProperty() returns the property with templates resolved
 * - doNotResolveTemplateTypeMapToBounds() prevents falling back to template bounds
 *   when concrete types are unknown (used during type inference)
 * - withFechedOnType() sets the type the property is being accessed on
 *
 * This is the return type of Type::getUnresolvedPropertyPrototype(),
 * Type::getUnresolvedInstancePropertyPrototype(), and
 * Type::getUnresolvedStaticPropertyPrototype().
 */
interface UnresolvedPropertyPrototypeReflection
{

	public function doNotResolveTemplateTypeMapToBounds(): self;

	public function getNakedProperty(): ExtendedPropertyReflection;

	/**
	 * Returns the property reflection with template types substituted from the
	 * fetched-on type's generic arguments.
	 */
	public function getTransformedProperty(): ExtendedPropertyReflection;

	public function withFechedOnType(Type $type): self;

}
