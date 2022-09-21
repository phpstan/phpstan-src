<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use function array_pop;
use function count;
use function get_class;

final class ParentStmtTypesVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'parentStmtTypes';

	/** @var array<int, class-string<Node\Stmt|Node\Expr\Closure>> */
	private array $typeStack = [];

	public function beforeTraverse(array $nodes): ?array
	{
		$this->typeStack = [];
		return null;
	}

	public function enterNode(Node $node): ?Node
	{
		if (!$node instanceof Node\Stmt && !$node instanceof Node\Expr\Closure) {
			return null;
		}

		if (count($this->typeStack) > 0) {
			$node->setAttribute(self::ATTRIBUTE_NAME, $this->typeStack);
		}
		$this->typeStack[] = get_class($node);

		return null;
	}

	public function leaveNode(Node $node): ?Node
	{
		if (!$node instanceof Node\Stmt && !$node instanceof Node\Expr\Closure) {
			return null;
		}

		array_pop($this->typeStack);

		return null;
	}

}
