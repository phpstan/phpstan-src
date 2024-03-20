<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;

/** @api */
class InvalidateExprNode extends NodeAbstract implements VirtualNode
{

	public function __construct(private Expr $expr)
	{
		parent::__construct($expr->getAttributes());
	}

	public function getExpr(): Expr
	{
		return $this->expr;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_InvalidateExpr';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
