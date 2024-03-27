<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\NodeVisitorAbstract;

class ClosureBindToVarVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'closureBindToVar';

	public function enterNode(Node $node): ?Node
	{
		if (
			$node instanceof Node\Expr\MethodCall
			&& $node->name instanceof Identifier
			&& $node->name->toLowerString() === 'bindto'
			&& !$node->isFirstClassCallable()
		) {
			$args = $node->getArgs();
			if (isset($args[0])) {
				$args[0]->setAttribute(self::ATTRIBUTE_NAME, $node->var);
			}
		}
		return null;
	}

}
