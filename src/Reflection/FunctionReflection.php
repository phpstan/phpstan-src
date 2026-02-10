<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

/**
 * Reflection for a standalone function (not a class method).
 *
 * Represents both built-in PHP functions and user-defined functions. Like methods,
 * functions can have multiple "variants" (overloaded signatures) — particularly
 * common for built-in functions where the return type depends on argument types.
 *
 * Extension developers encounter this interface when implementing
 * DynamicFunctionReturnTypeExtension or FunctionTypeSpecifyingExtension.
 *
 * Functions referenced in Scope::getFunctionCallStack() may be either
 * FunctionReflection or MethodReflection.
 *
 * @api
 */
interface FunctionReflection
{

	/** Returns the fully qualified function name. */
	public function getName(): string;

	/** Returns the file path where this function is defined, or null for built-ins. */
	public function getFileName(): ?string;

	/**
	 * Returns the function's parameter/return type signatures (one or more variants).
	 *
	 * @return list<ExtendedParametersAcceptor>
	 */
	public function getVariants(): array;

	/**
	 * Shortcut for functions with exactly one variant.
	 *
	 * @internal
	 */
	public function getOnlyVariant(): ExtendedParametersAcceptor;

	/**
	 * Returns alternative signatures used when the function is called with named arguments.
	 *
	 * Returns null if the named argument variants are the same as regular variants.
	 *
	 * @return list<ExtendedParametersAcceptor>|null
	 */
	public function getNamedArgumentsVariants(): ?array;

	/** Whether this function accepts named arguments (PHP 8.0+). */
	public function acceptsNamedArguments(): TrinaryLogic;

	/** Whether this function is marked as deprecated. */
	public function isDeprecated(): TrinaryLogic;

	/** Returns the deprecation message, or null if not deprecated. */
	public function getDeprecatedDescription(): ?string;

	/** Whether this function is marked as @internal. */
	public function isInternal(): TrinaryLogic;

	/**
	 * Returns the type of exceptions this function throws, or null if unknown.
	 *
	 * Comes from @throws PHPDoc tag.
	 */
	public function getThrowType(): ?Type;

	/**
	 * Whether this function has side effects (is impure).
	 *
	 * @see MethodReflection::hasSideEffects() for semantics.
	 */
	public function hasSideEffects(): TrinaryLogic;

	/** Whether this is a PHP built-in function (not defined in userland code). */
	public function isBuiltin(): bool;

	/**
	 * Returns type assertions declared via @phpstan-assert annotations.
	 */
	public function getAsserts(): Assertions;

	/**
	 * Returns the raw PHPDoc comment, or null if none exists.
	 */
	public function getDocComment(): ?string;

	/** Whether this function returns by reference (&). */
	public function returnsByReference(): TrinaryLogic;

	/**
	 * Whether this function has a @phpstan-pure or @phpstan-impure annotation.
	 *
	 * In most cases hasSideEffects() is more practical as it also accounts
	 * for void return type (functions returning void are always impure).
	 */
	public function isPure(): TrinaryLogic;

	/**
	 * Returns PHP attributes on this function.
	 *
	 * @return list<AttributeReflection>
	 */
	public function getAttributes(): array;

	/**
	 * Whether this function has the #[\NoDiscard] attribute.
	 *
	 * On PHP 8.5+ if the return value is unused at runtime, a warning is emitted.
	 * PHPStan reports this during analysis regardless of PHP version.
	 */
	public function mustUseReturnValue(): TrinaryLogic;

}
