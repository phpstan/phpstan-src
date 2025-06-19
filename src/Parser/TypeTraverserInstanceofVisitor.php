<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;

#[AutowiredService]
final class TypeTraverserInstanceofVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'insideTypeTraverserMap';

	private int $depth = 0;

	#[Override]
	public function beforeTraverse(array $nodes): ?array
	{
		$this->depth = 0;
		return null;
	}

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Expr\Instanceof_ && $this->depth > 0) {
			$node->setAttribute(self::ATTRIBUTE_NAME, true);
			return null;
		}

		if (
			$node instanceof Node\Expr\StaticCall
			&& $node->class instanceof Node\Name
			&& $node->class->toLowerString() === 'phpstan\\type\\typetraverser'
			&& $node->name instanceof Node\Identifier
			&& $node->name->toLowerString() === 'map'
		) {
			$this->depth++;
		}

		return null;
	}

	#[Override]
	public function leaveNode(Node $node): ?Node
	{
		if (
			$node instanceof Node\Expr\StaticCall
			&& $node->class instanceof Node\Name
			&& $node->class->toLowerString() === 'phpstan\\type\\typetraverser'
			&& $node->name instanceof Node\Identifier
			&& $node->name->toLowerString() === 'map'
		) {
			$this->depth--;
		}

		return null;
	}

}
