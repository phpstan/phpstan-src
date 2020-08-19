<?php declare(strict_types = 1);

namespace PHPStan\NodeVisitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class StatementOrderVisitor extends NodeVisitorAbstract
{

	/** @var int[] */
	private array $orderStack = [];

	private int $depth = 0;

	/**
	 * @param Node[] $nodes $nodes
	 * @return null
	 */
	public function beforeTraverse(array $nodes)
	{
		$this->orderStack = [0];
		$this->depth = 0;

		return null;
	}

	/**
	 * @param Node $node
	 * @return null
	 */
	public function enterNode(Node $node)
	{
		if (!$node instanceof Node\Stmt) {
			return null;
		}

		$order = $this->orderStack[count($this->orderStack) - 1];
		$node->setAttribute('statementOrder', $order);
		$node->setAttribute('statementDepth', $this->depth);

		$this->orderStack[count($this->orderStack) - 1] = $order + 1;
		$this->orderStack[] = 0;
		$this->depth++;

		return null;
	}

	/**
	 * @param Node $node
	 * @return null
	 */
	public function leaveNode(Node $node)
	{
		if (!$node instanceof Node\Stmt) {
			return null;
		}

		array_pop($this->orderStack);
		$this->depth--;

		return null;
	}

}
