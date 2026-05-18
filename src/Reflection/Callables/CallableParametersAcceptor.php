<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Callables;

use PHPStan\Node\InvalidateExprNode;
use PHPStan\Reflection\Assertions;
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
 * @api-do-not-implement
 */
interface CallableParametersAcceptor extends ParametersAcceptor
{

	/** @return SimpleThrowPoint[] */
	public function getThrowPoints(): array;

	public function isPure(): TrinaryLogic;

	public function acceptsNamedArguments(): TrinaryLogic;

	/** @return SimpleImpurePoint[] */
	public function getImpurePoints(): array;

	/**
	 * Tracks when calling a closure invalidates cached type information
	 * for variables it captures by reference.
	 *
	 * @return InvalidateExprNode[]
	 */
	public function getInvalidateExpressions(): array;

	/** @return string[] */
	public function getUsedVariables(): array;

	/**
	 * Whether the callable is marked with the `#[\NoDiscard]` attribute.
	 * On PHP 8.5+ if the return value is unused at runtime, a warning is emitted.
	 * PHPStan reports this during analysis regardless of PHP version.
	 */
	public function mustUseReturnValue(): TrinaryLogic;

	public function getAsserts(): Assertions;

	public function isStaticClosure(): TrinaryLogic;

}
