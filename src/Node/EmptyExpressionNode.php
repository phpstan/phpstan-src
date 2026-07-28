<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Empty_;
use PHPStan\Analyser\ExpressionResult;

/**
 * Emitted by EmptyHandler once the empty() subject is processed, so EmptyRule
 * reads the already-computed IssetabilityResolution (via the carried
 * ExpressionResult) instead of re-walking the chain on demand.
 *
 * @internal
 */
final class EmptyExpressionNode extends Expr implements VirtualNode
{

	public function __construct(Empty_ $originalNode, private ExpressionResult $exprResult)
	{
		parent::__construct($originalNode->getAttributes());
	}

	public function getExprResult(): ExpressionResult
	{
		return $this->exprResult;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_EmptyExpressionNode';
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
