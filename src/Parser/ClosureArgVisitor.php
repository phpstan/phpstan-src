<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use function count;

class ClosureArgVisitor extends NodeVisitorAbstract
{

	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Expr\Closure) {
			$args = $node->getArgs();

			if (count($args) > 0) {
				$node->name->setAttribute('closureCallArgs', $args);
			}
		}
		return null;
	}

}
