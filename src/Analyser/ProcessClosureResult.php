<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\ClosureUse;
use PHPStan\Node\InvalidateExprNode;

final class ProcessClosureResult
{

	/**
	 * @param InternalThrowPoint[] $throwPoints
	 * @param ImpurePoint[] $impurePoints
	 * @param InvalidateExprNode[] $invalidateExpressions
	 * @param ClosureUse[] $byRefUses
	 */
	public function __construct(
		private MutatingScope $scope,
		private array $throwPoints,
		private array $impurePoints,
		private array $invalidateExpressions,
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

}
