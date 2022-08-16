<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use function count;

class ArrowFunctionArgVisitor extends NodeVisitorAbstract
{

	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Expr\ArrowFunction) {
			$args = $node->getArgs();

			if (count($args) > 0) {
				$node->name->setAttribute('arrowFunctionCallArgs', $args);
			}
		}
		return null;
	}

}
