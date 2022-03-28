<?php declare(strict_types = 1);

namespace PHPStan\Node\Expr;

use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;

class GetIterableKeyTypeExpr extends Expr implements VirtualNode
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
		return 'PHPStan_Node_GetIterableKeyTypeExpr';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
