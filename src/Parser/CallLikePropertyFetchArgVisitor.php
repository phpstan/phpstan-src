<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class CallLikePropertyFetchArgVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'invokingCallLike';

	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name && !$node->isFirstClassCallable()) {
			$args = $node->getArgs();

			foreach ($args as $arg) {
				if (!($arg->value instanceof Node\Expr\PropertyFetch)) {
					continue;
				}

				$arg->value->setAttribute(self::ATTRIBUTE_NAME, $node);
			}
		}

		return null;
	}

}
