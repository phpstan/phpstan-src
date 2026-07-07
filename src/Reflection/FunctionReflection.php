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
 * @api-do-not-implement
 */
interface FunctionReflection
{

	public function getName(): string;

	public function getFileName(): ?string;

	/** @return list<ExtendedParametersAcceptor> */
	public function getVariants(): array;

	/** @internal */
	public function getOnlyVariant(): ExtendedParametersAcceptor;

	/**
	 * Returns alternative signatures used when the function is called with named arguments.
	 * Returns null if the named argument variants are the same as regular variants.
	 *
	 * @return list<ExtendedParametersAcceptor>|null
	 */
	public function getNamedArgumentsVariants(): ?array;

	public function acceptsNamedArguments(): TrinaryLogic;

	public function isDeprecated(): TrinaryLogic;

	public function getDeprecatedDescription(): ?string;

	public function isInternal(): TrinaryLogic;

	public function getThrowType(): ?Type;

	public function hasSideEffects(): TrinaryLogic;

	public function isBuiltin(): bool;

	public function getAsserts(): Assertions;

	public function getDocComment(): ?string;

	public function returnsByReference(): TrinaryLogic;

	/**
	 * In most cases hasSideEffects() is more practical as it also accounts
	 * for void return type (functions returning void are always impure).
	 */
	public function isPure(): TrinaryLogic;

	/**
	 * @return array<string, TrinaryLogic>
	 */
	public function getPureUnlessCallableIsImpureParameters(): array;

	/** @return list<AttributeReflection> */
	public function getAttributes(): array;

	/**
	 * On PHP 8.5+ if the return value is unused at runtime, a warning is emitted.
	 * PHPStan reports this during analysis regardless of PHP version.
	 */
	public function mustUseReturnValue(): TrinaryLogic;

}
