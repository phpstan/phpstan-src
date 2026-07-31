<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\ClosureUse;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Expr\YieldFrom;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Node\ExecutionEndNode;
use PHPStan\Node\InvalidateExprNode;

final class ProcessClosureResult
{

	/**
	 * @param InternalThrowPoint[] $throwPoints
	 * @param ImpurePoint[] $impurePoints
	 * @param InvalidateExprNode[] $invalidateExpressions
	 * @param list<array{Return_, Scope}> $gatheredReturnStatements
	 * @param list<array{Yield_|YieldFrom, Scope}> $gatheredYieldStatements
	 * @param list<ExecutionEndNode> $executionEnds
	 * @param ImpurePoint[] $closureTypeImpurePoints already merged in getClosureType() order (property-assign impure points first, then statement result impure points)
	 * @param ClosureUse[] $byRefUses
	 */
	public function __construct(
		private MutatingScope $scope,
		private array $throwPoints,
		private array $impurePoints,
		private array $invalidateExpressions,
		private array $gatheredReturnStatements,
		private array $gatheredYieldStatements,
		private array $executionEnds,
		private array $closureTypeImpurePoints,
		private ?MutatingScope $byRefClosureResultScope = null,
		private array $byRefUses = [],
	)
	{
	}

	public function getScope(): MutatingScope
	{
		return $this->scope;
	}

	public function applyByRefUseScope(MutatingScope $scope): MutatingScope
	{
		if ($this->byRefClosureResultScope === null) {
			return $scope;
		}

		return $scope->processClosureScope($this->byRefClosureResultScope, null, $this->byRefUses);
	}

	/**
	 * @return InternalThrowPoint[]
	 */
	public function getThrowPoints(): array
	{
		return $this->throwPoints;
	}

	/**
	 * @return ImpurePoint[]
	 */
	public function getImpurePoints(): array
	{
		return $this->impurePoints;
	}

	/**
	 * @return InvalidateExprNode[]
	 */
	public function getInvalidateExpressions(): array
	{
		return $this->invalidateExpressions;
	}

	/**
	 * @return list<array{Return_, Scope}>
	 */
	public function getGatheredReturnStatements(): array
	{
		return $this->gatheredReturnStatements;
	}

	/**
	 * @return list<array{Yield_|YieldFrom, Scope}>
	 */
	public function getGatheredYieldStatements(): array
	{
		return $this->gatheredYieldStatements;
	}

	/**
	 * @return list<ExecutionEndNode>
	 */
	public function getExecutionEnds(): array
	{
		return $this->executionEnds;
	}

	/**
	 * The closure body's impure points already merged in getClosureType() order
	 * (property-assign impure points first, then statement result impure points),
	 * ready to feed ClosureTypeResolver::buildClosureTypeForClosure().
	 *
	 * @return ImpurePoint[]
	 */
	public function getClosureTypeImpurePoints(): array
	{
		return $this->closureTypeImpurePoints;
	}

}
