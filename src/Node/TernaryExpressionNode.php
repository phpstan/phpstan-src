<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Turbo\ReferencedByTurboExtension;

/**
 * Emitted by TernaryHandler once the condition and both branches are processed,
 * so rules read their impure points and throw points from the carried
 * ExpressionResults instead of working out the purity of the branches again.
 *
 * @internal
 */
#[ReferencedByTurboExtension(key: 'ternaryExpressionNode')]
final class TernaryExpressionNode extends NodeAbstract implements VirtualNode
{

	public function __construct(
		private Expr\Ternary $originalExpr,
		private ExpressionResult $condResult,
		private ?ExpressionResult $ifResult,
		private ExpressionResult $elseResult,
	)
	{
		parent::__construct($originalExpr->getAttributes());
	}

	public function getOriginalExpr(): Expr\Ternary
	{
		return $this->originalExpr;
	}

	public function getCondResult(): ExpressionResult
	{
		return $this->condResult;
	}

	/** Null for the short ternary `?:`. */
	public function getIfResult(): ?ExpressionResult
	{
		return $this->ifResult;
	}

	public function getElseResult(): ExpressionResult
	{
		return $this->elseResult;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_TernaryExpressionNode';
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
