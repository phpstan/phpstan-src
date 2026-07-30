<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Reflection\ParametersAcceptor;

/**
 * Result of NodeScopeResolver::processArgs(): the scope/throw/impure state after
 * processing all arguments (wrapped ExpressionResult) plus the ParametersAcceptor
 * resolved from the arg types gathered on the arg-to-arg evolving scope. The
 * resolved acceptor is type-driven (selectFromTypes) so its generics are resolved
 * against the actual argument types - callers wire it into the call's return
 * type. Null when the call had no variants (dynamic callee).
 */
final class ArgsResult
{

	public function __construct(
		private ExpressionResult $expressionResult,
		private ?ParametersAcceptor $resolvedParametersAcceptor,
	)
	{
	}

	public function getScope(): MutatingScope
	{
		return $this->expressionResult->getScope();
	}

	public function hasYield(): bool
	{
		return $this->expressionResult->hasYield();
	}

	public function isAlwaysTerminating(): bool
	{
		return $this->expressionResult->isAlwaysTerminating();
	}

	/**
	 * @return InternalThrowPoint[]
	 */
	public function getThrowPoints(): array
	{
		return $this->expressionResult->getThrowPoints();
	}

	/**
	 * @return ImpurePoint[]
	 */
	public function getImpurePoints(): array
	{
		return $this->expressionResult->getImpurePoints();
	}

	public function getResolvedParametersAcceptor(): ?ParametersAcceptor
	{
		return $this->resolvedParametersAcceptor;
	}

}
