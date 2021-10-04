<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class CleaningVisitor extends NodeVisitorAbstract
{

	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Stmt\Function_) {
			$node->stmts = [];
			return $node;
		}

		if ($node instanceof Node\Stmt\ClassMethod && $node->stmts !== null) {
			$node->stmts = [];
			return $node;
		}

		if ($node instanceof Node\Expr\Closure) {
			$node->stmts = [];
			return $node;
		}

		return null;
	}

}
