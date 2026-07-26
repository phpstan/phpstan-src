<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Node\VirtualNode;
use PHPStan\ShouldNotHappenException;

final class ReplacingNodeVisitor extends NodeVisitorAbstract
{

	private bool $found = false;

	/**
	 * @param callable(Node): Node $newNodeCallable
	 */
	public function __construct(private Node $originalNode, private $newNodeCallable)
	{
	}

	/**
	 * The replacement happens in leaveNode() so that the callable receives a node
	 * whose children have already been cloned by CloningVisitor. That makes it
	 * possible to run both visitors in a single traversal.
	 */
	#[Override]
	public function leaveNode(Node $node): ?Node
	{
		if ($this->found) {
			return null;
		}

		$origNode = $node->getAttribute('origNode');
		if ($origNode !== $this->originalNode) {
			return null;
		}

		$this->found = true;

		$callable = $this->newNodeCallable;
		$newNode = $callable($node);
		if ($newNode instanceof VirtualNode) {
			throw new ShouldNotHappenException('Cannot print VirtualNode.');
		}

		return $newNode;
	}

	public function isFound(): bool
	{
		return $this->found;
	}

}
