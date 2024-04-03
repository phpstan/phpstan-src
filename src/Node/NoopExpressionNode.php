<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;

class NoopExpressionNode extends NodeAbstract implements VirtualNode
{

	public function __construct(private Expr $originalExpr)
	{
		parent::__construct($this->originalExpr->getAttributes());
	}

	public function getOriginalExpr(): Expr
	{
		return $this->originalExpr;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_NoopExpressionNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
