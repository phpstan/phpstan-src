<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;
use function count;

#[AutowiredService]
final class ClosureBindArgVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'closureBindArg';

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if (
			$node instanceof Node\Expr\StaticCall
			&& $node->class instanceof Node\Name
			&& $node->class->toLowerString() === 'closure'
			&& $node->name instanceof Identifier
			&& $node->name->toLowerString() === 'bind'
			&& !$node->isFirstClassCallable()
		) {
			$args = $node->getArgs();
			if (count($args) > 1) {
				$args[0]->setAttribute(self::ATTRIBUTE_NAME, true);
			}
		}
		return null;
	}

}
