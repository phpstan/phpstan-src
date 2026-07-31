<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Node\InvalidateExprNode;

final class ProcessArrowFunctionResult
{

	/**
	 * @param ThrowPoint[] $closureTypeThrowPoints public throw points for building the arrow function type
	 * @param ImpurePoint[] $closureTypeImpurePoints already merged in getClosureType() order (property-assign impure points first, then expression result impure points)
	 * @param InvalidateExprNode[] $invalidateExpressions
	 */
	public function __construct(
		private ExpressionResult $expressionResult,
		private MutatingScope $arrowFunctionScope,
		private array $closureTypeThrowPoints,
		private array $closureTypeImpurePoints,
		private array $invalidateExpressions,
	)
	{
	}

	public function getExpressionResult(): ExpressionResult
	{
		return $this->expressionResult;
	}

	public function getArrowFunctionScope(): MutatingScope
	{
		return $this->arrowFunctionScope;
	}

	/**
	 * @return ThrowPoint[]
	 */
	public function getClosureTypeThrowPoints(): array
	{
		return $this->closureTypeThrowPoints;
	}

	/**
	 * @return ImpurePoint[]
	 */
	public function getClosureTypeImpurePoints(): array
	{
		return $this->closureTypeImpurePoints;
	}

	/**
	 * @return InvalidateExprNode[]
	 */
	public function getInvalidateExpressions(): array
	{
		return $this->invalidateExpressions;
	}

}
