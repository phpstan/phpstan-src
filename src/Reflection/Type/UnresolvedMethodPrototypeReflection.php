<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Type\Type;

/**
 * Lazy method reflection that defers template type resolution.
 *
 * When calling a method on a generic type, the method's parameter and return types
 * need to be transformed by substituting template type parameters with their concrete
 * arguments. This interface allows that resolution to be deferred and configured:
 *
 * - getNakedMethod() returns the method as declared (before template substitution)
 * - getTransformedMethod() returns the method with templates resolved
 * - doNotResolveTemplateTypeMapToBounds() prevents falling back to template bounds
 *   when concrete types are unknown (used during type inference)
 * - withCalledOnType() sets the type the method is being called on
 *
 * This is the return type of Type::getUnresolvedMethodPrototype().
 */
interface UnresolvedMethodPrototypeReflection
{

	public function doNotResolveTemplateTypeMapToBounds(): self;

	public function getNakedMethod(): ExtendedMethodReflection;

	/**
	 * Returns the method reflection with template types substituted from the
	 * called-on type's generic arguments.
	 */
	public function getTransformedMethod(): ExtendedMethodReflection;

	public function withCalledOnType(Type $type): self;

}
