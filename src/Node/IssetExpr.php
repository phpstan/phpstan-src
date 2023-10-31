<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PHPStan\TrinaryLogic;

class IssetExpr extends Expr implements VirtualNode
{

	public function __construct(
		private Expr $expr,
		private TrinaryLogic $certainty,
	)
	{
		parent::__construct([]);
	}

	public function getExpr(): Expr
	{
		return $this->expr;
	}

	public function getCertainty(): TrinaryLogic
	{
		return $this->certainty;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_IssetExpr';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
