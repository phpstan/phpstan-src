<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Callables;

use PHPStan\Node\InvalidateExprNode;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\TrinaryLogic;

/**
 * A ParametersAcceptor for callable types (closures, first-class callables).
 *
 * Extends ParametersAcceptor with information about side effects, exceptions,
 * and other runtime behavior of callable values. This is what PHPStan knows
 * about a closure or callable when it's passed as a parameter or stored in a variable.
 *
 * Implemented by ClosureType and used as the return type of
 * Type::getCallableParametersAcceptors().
 *
 * Provides:
 * - Throw points (what exceptions the callable may throw)
 * - Impure points (what side effects the callable may have)
 * - Purity information
 * - Variables captured from outer scope (used variables)
 * - Expressions that are invalidated by calling this callable
 *
 * @api
 */
interface CallableParametersAcceptor extends ParametersAcceptor
{

	/**
	 * Returns the points where this callable may throw exceptions.
	 *
	 * @return SimpleThrowPoint[]
	 */
	public function getThrowPoints(): array;

	/** Whether this callable is known to be pure (no side effects). */
	public function isPure(): TrinaryLogic;

	/** Whether this callable accepts named arguments. */
	public function acceptsNamedArguments(): TrinaryLogic;

	/**
	 * Returns the points where this callable may have side effects.
	 *
	 * @return SimpleImpurePoint[]
	 */
	public function getImpurePoints(): array;

	/**
	 * Returns expressions that become invalid after this callable is invoked.
	 *
	 * Used to track when calling a closure invalidates cached type information
	 * for variables it captures by reference.
	 *
	 * @return InvalidateExprNode[]
	 */
	public function getInvalidateExpressions(): array;

	/**
	 * Returns the names of outer-scope variables captured by this callable.
	 *
	 * Relevant for `use ($var)` in closures.
	 *
	 * @return string[]
	 */
	public function getUsedVariables(): array;

	/**
	 * Whether this callable has the #[\NoDiscard] attribute.
	 *
	 * On PHP 8.5+ if the return value is unused at runtime, a warning is emitted.
	 * PHPStan reports this during analysis regardless of PHP version.
	 */
	public function mustUseReturnValue(): TrinaryLogic;

}
