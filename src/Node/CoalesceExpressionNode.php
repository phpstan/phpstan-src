<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr;
use PHPStan\Analyser\ExpressionResult;

/**
 * Emitted by CoalesceHandler (for ??) and AssignOpHandler (for ??=) once the left
 * side is processed, so NullCoalesceRule reads the already-computed
 * IssetabilityResolution (via the carried ExpressionResult) instead of re-walking
 * the chain on demand. The operator description distinguishes ?? from ??=.
 *
 * @internal
 */
final class CoalesceExpressionNode extends Expr implements VirtualNode
{

	public function __construct(
		private Expr $originalExpr,
		private ExpressionResult $subjectResult,
		private string $operatorDescription,
	)
	{
		parent::__construct($originalExpr->getAttributes());
	}

	public function getOriginalExpr(): Expr
	{
		return $this->originalExpr;
	}

	public function getSubjectResult(): ExpressionResult
	{
		return $this->subjectResult;
	}

	public function getOperatorDescription(): string
	{
		return $this->operatorDescription;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_CoalesceExpressionNode';
	}

	/**
	 * @return string[]
	 */
	#[Override]
	public function getSubNodeNames(): array
	{
		return [];
	}

}
