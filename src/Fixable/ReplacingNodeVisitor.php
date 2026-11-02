<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Node\VirtualNode;
use PHPStan\ShouldNotHappenException;
use function in_array;
use function is_array;

final class ReplacingNodeVisitor extends NodeVisitorAbstract
{

	private bool $found = false;

	private bool $originalNodeInArray = false;

	/**
	 * @param callable(Node): (Node|NodeVisitor::REMOVE_NODE) $newNodeCallable
	 */
	public function __construct(private Node $originalNode, private $newNodeCallable)
	{
	}

	/**
	 * CloningVisitor has just cloned $node, its subnodes are still the original nodes.
	 * Only a node in an array can be removed.
	 */
	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if ($this->found || $this->originalNodeInArray) {
			return null;
		}

		foreach ($node->getSubNodeNames() as $subNodeName) {
			$subNode = $node->$subNodeName;
			if (is_array($subNode) && in_array($this->originalNode, $subNode, true)) {
				$this->originalNodeInArray = true;
				break;
			}
		}

		return null;
	}

	/**
	 * The replacement happens in leaveNode() so that the callable receives a node
	 * whose children have already been cloned by CloningVisitor. That makes it
	 * possible to run both visitors in a single traversal.
	 */
	#[Override]
	public function leaveNode(Node $node): Node|int|null
	{
		if ($this->found) {
			return null;
		}

		$origNode = $node->getAttribute('origNode');
		if ($origNode !== $this->originalNode) {
			return null;
		}

		$callable = $this->newNodeCallable;
		$newNode = $callable($node);
		if ($newNode === NodeVisitor::REMOVE_NODE) {
			if (!$this->originalNodeInArray) {
				return null;
			}

			$this->found = true;

			return NodeVisitor::REMOVE_NODE;
		}

		if ($newNode instanceof VirtualNode) {
			throw new ShouldNotHappenException('Cannot print VirtualNode.');
		}

		$this->found = true;

		return $newNode;
	}

	public function isFound(): bool
	{
		return $this->found;
	}

}
