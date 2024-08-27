<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Node\InvalidateExprNode;

final class ProcessClosureResult
{

	/**
	 * @param ThrowPoint[] $throwPoints
	 * @param ImpurePoint[] $impurePoints
	 * @param InvalidateExprNode[] $invalidateExpressions
	 */
	public function __construct(
		private MutatingScope $scope,
		private array $throwPoints,
		private array $impurePoints,
		private array $invalidateExpressions,
	)
	{
	}

	public function getScope(): MutatingScope
	{
		return $this->scope;
	}

	/**
	 * @return ThrowPoint[]
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
