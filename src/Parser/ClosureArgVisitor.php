<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use function count;

final class ClosureArgVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'closureCallArgs';

	public function enterNode(Node $node): ?Node
	{
		if (!$node instanceof Node\Expr\FuncCall) {
			return null;
		}

		if ($node->isFirstClassCallable()) {
			return null;
		}

		if ($node->name instanceof Node\Expr\Assign && $node->name->expr instanceof Node\Expr\Closure) {
			$closure = $node->name->expr;
		} elseif ($node->name instanceof Node\Expr\Closure) {
			$closure = $node->name;
		} else {
			return null;
		}

		$args = $node->getArgs();

		if (count($args) > 0) {
			$closure->setAttribute(self::ATTRIBUTE_NAME, $args);
		}

		return null;
	}

}
