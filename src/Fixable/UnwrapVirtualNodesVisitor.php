<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Node\Expr\AlwaysRememberedExpr;

final class UnwrapVirtualNodesVisitor extends NodeVisitorAbstract
{

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if (!$node instanceof Node\Expr\Match_) {
			return null;
		}

		if (!$node->cond instanceof AlwaysRememberedExpr) {
			return null;
		}

		$node->cond = $node->cond->expr;

		return $node;
	}

}
