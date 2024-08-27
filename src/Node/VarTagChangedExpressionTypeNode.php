<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;
use PHPStan\PhpDoc\Tag\VarTag;

final class VarTagChangedExpressionTypeNode extends NodeAbstract implements VirtualNode
{

	public function __construct(private VarTag $varTag, private Expr $expr)
	{
		parent::__construct($expr->getAttributes());
	}

	public function getVarTag(): VarTag
	{
		return $this->varTag;
	}

	public function getExpr(): Expr
	{
		return $this->expr;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_VarTagChangedExpressionType';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
