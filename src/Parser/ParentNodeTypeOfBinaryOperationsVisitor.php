<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\NodeVisitorAbstract;
use function array_pop;
use function count;
use function get_class;

final class ParentNodeTypeOfBinaryOperationsVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'parentNodeTypeOfBinaryOperation';

	/** @var array<int, class-string<Node>> */
	private array $typeStack = [];

	public function beforeTraverse(array $nodes): ?array
	{
		$this->typeStack = [];
		return null;
	}

	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof BinaryOp && count($this->typeStack) > 0) {
			$node->setAttribute(self::ATTRIBUTE_NAME, array_pop($this->typeStack));
		}
		$this->typeStack[] = get_class($node);

		return null;
	}

}
