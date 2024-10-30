<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class ImmediatelyInvokedClosureVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'isImmediatelyInvokedClosure';

	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Expr\Closure) {
			$node->name->setAttribute(self::ATTRIBUTE_NAME, true);
		}

		return null;
	}

}
