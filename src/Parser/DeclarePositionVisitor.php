<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use function str_starts_with;

final class DeclarePositionVisitor extends NodeVisitorAbstract
{

	private bool $isFirstStatement = true;

	public const ATTRIBUTE_NAME = 'isFirstStatement';

	public function beforeTraverse(array $nodes): ?array
	{
		$this->isFirstStatement = true;
		return null;
	}

	public function enterNode(Node $node): ?Node
	{
		// ignore shebang
		if (
			$this->isFirstStatement
			&& $node instanceof Node\Stmt\InlineHTML
			&& str_starts_with($node->value, '#!')
		) {
			return null;
		}

		if ($node instanceof Node\Stmt) {
			if ($node instanceof Node\Stmt\Declare_) {
				$node->setAttribute(self::ATTRIBUTE_NAME, $this->isFirstStatement);
			}

			$this->isFirstStatement = false;
		}

		return null;
	}

}
