<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;

#[AutowiredService]
final class ImmediatelyInvokedClosureVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'isImmediatelyInvokedClosure';

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Expr\Closure) {
			$node->name->setAttribute(self::ATTRIBUTE_NAME, true);
		}

		return null;
	}

}
