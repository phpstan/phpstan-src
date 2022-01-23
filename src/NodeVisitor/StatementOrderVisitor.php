<?php declare(strict_types = 1);

namespace PHPStan\NodeVisitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use function array_pop;
use function count;

class StatementOrderVisitor extends NodeVisitorAbstract
{

	/** @var int[] */
	private array $orderStack = [];

	/** @var int[] */
	private array $expressionOrderStack = [];

	private int $depth = 0;

	private int $expressionDepth = 0;

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
	 * @return null
	 */
	public function enterNode(Node $node)
	{
		$order = $this->orderStack[count($this->orderStack) - 1];
		$node->setAttribute('statementOrder', $order);
		$node->setAttribute('statementDepth', $this->depth);

		if (
			($node instanceof Node\Expr || $node instanceof Node\Arg)
			&& count($this->expressionOrderStack) > 0
		) {
			$expressionOrder = $this->expressionOrderStack[count($this->expressionOrderStack) - 1];
			$node->setAttribute('expressionOrder', $expressionOrder);
			$node->setAttribute('expressionDepth', $this->expressionDepth);
			$this->expressionOrderStack[count($this->expressionOrderStack) - 1] = $expressionOrder + 1;
			$this->expressionOrderStack[] = 0;
			$this->expressionDepth++;
		}

		if (!$node instanceof Node\Stmt) {
			return null;
		}

		$this->orderStack[count($this->orderStack) - 1] = $order + 1;
		$this->orderStack[] = 0;
		$this->depth++;

		$this->expressionOrderStack = [0];
		$this->expressionDepth = 0;

		return null;
	}

	/**
	 * @return null
	 */
	public function leaveNode(Node $node)
	{
		if ($node instanceof Node\Expr) {
			array_pop($this->expressionOrderStack);
			$this->expressionDepth--;
		}
		if (!$node instanceof Node\Stmt) {
			return null;
		}

		array_pop($this->orderStack);
		$this->depth--;

		return null;
	}

}
