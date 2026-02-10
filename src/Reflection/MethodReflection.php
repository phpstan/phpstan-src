<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

/**
 * Reflection for a class method.
 *
 * This is the interface extension developers should implement when creating custom
 * MethodsClassReflectionExtension implementations for magic methods (__call, etc.).
 *
 * Methods can have multiple "variants" (overloaded signatures) — for example,
 * built-in functions like `array_map` have different signatures depending on
 * the number of arguments. Each variant is a ParametersAcceptor.
 *
 * For additional method metadata (assertions, purity, named arguments, attributes),
 * see ExtendedMethodReflection which extends this interface.
 *
 * @api
 */
interface MethodReflection extends ClassMemberReflection
{

	/** Returns the method name. */
	public function getName(): string;

	/**
	 * Returns the prototype (original declaration) of this method.
	 *
	 * For methods that override a parent method, this returns the parent's
	 * method reflection. For methods with no parent, returns itself.
	 */
	public function getPrototype(): ClassMemberReflection;

	/**
	 * Returns the method's parameter/return type signatures (one or more variants).
	 *
	 * Most methods have a single variant. Built-in PHP functions with overloaded
	 * signatures (e.g. different return types based on argument count) have multiple.
	 *
	 * @return list<ParametersAcceptor>
	 */
	public function getVariants(): array;

	/** Whether this method is marked as deprecated. */
	public function isDeprecated(): TrinaryLogic;

	/** Returns the deprecation message, or null if not deprecated. */
	public function getDeprecatedDescription(): ?string;

	/** Whether this method is final. */
	public function isFinal(): TrinaryLogic;

	/** Whether this method is marked as @internal. */
	public function isInternal(): TrinaryLogic;

	/**
	 * Returns the type of exceptions this method throws, or null if unknown.
	 *
	 * Comes from @throws PHPDoc tag.
	 */
	public function getThrowType(): ?Type;

	/**
	 * Whether this method has side effects (is impure).
	 *
	 * Returns Yes for methods known to be impure, No for pure methods,
	 * and Maybe when purity cannot be determined. Void methods are always
	 * considered impure since they must do something to be useful.
	 */
	public function hasSideEffects(): TrinaryLogic;

}
