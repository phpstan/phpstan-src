<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\Reflection\ParametersAcceptor;
use function spl_object_id;

/**
 * Result of NodeScopeResolver::processArgs(): the scope/throw/impure state after
 * processing all arguments (wrapped ExpressionResult) plus the ParametersAcceptor
 * resolved from the arg types gathered on the arg-to-arg evolving scope. The
 * resolved acceptor is type-driven (selectFromTypes) so its generics are resolved
 * against the actual argument types - callers wire it into the call expression's
 * stored return type. Null when the call had no variants (dynamic callee).
 */
final class ArgsResult
{

	/**
	 * @param array<int, ExpressionResult> $argResults keyed by spl_object_id of each argument's value expression
	 */
	public function __construct(
		private ExpressionResult $expressionResult,
		private ?ParametersAcceptor $resolvedParametersAcceptor,
		private array $argResults = [],
	)
	{
	}

	/**
	 * The already-processed ExpressionResult of a call argument's value expression,
	 * so callers read its type via the result instead of re-asking the scope.
	 */
	public function getArgResult(Expr $argValue): ?ExpressionResult
	{
		return $this->argResults[spl_object_id($argValue)] ?? null;
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
