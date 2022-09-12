<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ArrayFilterArgVisitor extends NodeVisitorAbstract
{

	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name) {
			$functionName = $node->name->toLowerString();
			if ($functionName === 'array_filter') {
				$args = $node->getArgs();
				if (isset($args[0])) {
					$args[0]->setAttribute('isArrayFilterArg', true);
				}
			}
		}
		return null;
	}

}
