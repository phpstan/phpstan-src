<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use function array_pop;
use function count;

final class ParentConnectingVisitor extends NodeVisitorAbstract
{

	/** @var Node[] */
	private array $stack = [];

	public function beforeTraverse(array $nodes)
	{
		$this->stack = [];

		return null;
	}

	public function enterNode(Node $node)
	{
		if (count($this->stack) > 0) {
			$node->setAttribute('parent', $this->stack[count($this->stack) - 1]);
		}

		$this->stack[] = $node;

		return null;
	}

	public function leaveNode(Node $node)
	{
		array_pop($this->stack);

		return null;
	}

}
