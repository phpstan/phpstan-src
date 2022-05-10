<?php declare(strict_types = 1);

namespace PHPStan\Node\Expr;

use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;

class MixedTypeExpr extends Expr implements VirtualNode
{

	public function getType(): string
	{
		return 'PHPStan_Node_MixedTypeExpr';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
