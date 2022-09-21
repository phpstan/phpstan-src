<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use function count;

class ClosureArgVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'closureCallArgs';

	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Expr\Closure) {
			$args = $node->getArgs();

			if (count($args) > 0) {
				$node->name->setAttribute(self::ATTRIBUTE_NAME, $args);
			}
		}
		return null;
	}

}
