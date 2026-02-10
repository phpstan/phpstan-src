<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

/**
 * Extended method reflection with additional metadata beyond MethodReflection.
 *
 * This interface exists to allow PHPStan to add new method query methods in minor
 * versions without breaking existing MethodsClassReflectionExtension implementations.
 * Extension developers should implement MethodReflection, not this interface — PHPStan
 * wraps MethodReflection implementations to provide ExtendedMethodReflection.
 *
 * Provides access to:
 * - Extended parameter signatures (ExtendedParametersAcceptor with PHPDoc/native types)
 * - Named argument variants (different signatures when using named arguments)
 * - Type assertions (@phpstan-assert annotations)
 * - Self-out types (@phpstan-self-out for fluent interfaces)
 * - Purity information (@phpstan-pure/@phpstan-impure)
 * - PHP attributes (including #[\NoDiscard])
 * - Resolved PHPDoc block
 *
 * This is the return type of Type::getMethod() and Scope::getMethodReflection().
 *
 * @api
 */
interface ExtendedMethodReflection extends MethodReflection
{

	/**
	 * Returns extended parameter/return type signatures with PHPDoc and native types.
	 *
	 * @return list<ExtendedParametersAcceptor>
	 */
	public function getVariants(): array;

	/**
	 * Shortcut for methods with exactly one variant.
	 *
	 * @internal
	 */
	public function getOnlyVariant(): ExtendedParametersAcceptor;

	/**
	 * Returns alternative signatures used when the method is called with named arguments.
	 *
	 * Some built-in functions have different behavior with named arguments.
	 * Returns null if the named argument variants are the same as regular variants.
	 *
	 * @return list<ExtendedParametersAcceptor>|null
	 */
	public function getNamedArgumentsVariants(): ?array;

	/** Whether this method accepts named arguments (PHP 8.0+). */
	public function acceptsNamedArguments(): TrinaryLogic;

	/**
	 * Returns type assertions declared via @phpstan-assert annotations.
	 *
	 * These narrow parameter or property types after the method call,
	 * similar to how is_string() narrows to string.
	 */
	public function getAsserts(): Assertions;

	/**
	 * Returns the @phpstan-self-out type, if declared.
	 *
	 * Used for fluent interfaces where calling a method changes the generic
	 * type parameters of $this (e.g. a builder pattern).
	 */
	public function getSelfOutType(): ?Type;

	/** Whether this method returns by reference (&). */
	public function returnsByReference(): TrinaryLogic;

	/** Whether this method has the `final` keyword explicitly. */
	public function isFinalByKeyword(): TrinaryLogic;

	/** Whether this method is abstract. */
	public function isAbstract(): TrinaryLogic|bool;

	/** Whether this method is a PHP built-in (not defined in userland code). */
	public function isBuiltin(): TrinaryLogic|bool;

	/**
	 * Whether this method has a @phpstan-pure or @phpstan-impure annotation.
	 *
	 * In most cases hasSideEffects() is more practical as it also accounts
	 * for void return type (methods returning void are always impure).
	 */
	public function isPure(): TrinaryLogic;

	/**
	 * Returns PHP attributes on this method.
	 *
	 * @return list<AttributeReflection>
	 */
	public function getAttributes(): array;

	/**
	 * Whether this method has the #[\NoDiscard] attribute.
	 *
	 * On PHP 8.5+ if the return value is unused at runtime, a warning is emitted.
	 * PHPStan reports this during analysis regardless of PHP version.
	 */
	public function mustUseReturnValue(): TrinaryLogic;

	/**
	 * Returns the resolved PHPDoc block for this method, or null if none exists.
	 */
	public function getResolvedPhpDoc(): ?ResolvedPhpDocBlock;

}
