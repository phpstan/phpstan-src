<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use function count;

/**
 * Transforms `if` / `else if` into the equivalent flat `if` / `elseif`
 */
final class TransformElseIfToFlatElseIfVisitor extends NodeVisitorAbstract
{

	public function enterNode(Node $node): ?Node
	{
		if (
			!$node instanceof Node\Stmt\If_
			|| !$node->else instanceof Node\Stmt\Else_
			|| count($node->else->stmts) !== 1
			|| !$node->else->stmts[0] instanceof Node\Stmt\If_
			|| $node->else->stmts[0]->else !== null
		) {
			return null;
		}

		$node->elseifs[] = new Node\Stmt\ElseIf_($node->else->stmts[0]->cond, $node->else->stmts[0]->stmts);
		$node->else = null;

		return $node;
	}

}
