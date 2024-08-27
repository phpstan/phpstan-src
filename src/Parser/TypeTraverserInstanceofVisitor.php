<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class TypeTraverserInstanceofVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'insideTypeTraverserMap';

	private int $depth = 0;

	public function beforeTraverse(array $nodes): ?array
	{
		$this->depth = 0;
		return null;
	}

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
