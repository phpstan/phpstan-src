<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Node\VirtualNode;
use PHPStan\Rules\NodeFix;
use PHPStan\ShouldNotHappenException;
use SplObjectStorage;
use function count;
use function is_array;

/**
 * Applies the NodeFixes of one error to the nodes of one file. Runs after CloningVisitor in the same traversal.
 */
final class ReplacingNodeVisitor extends NodeVisitorAbstract
{

	/** @var SplObjectStorage<Node, list<callable(Node): (Node|NodeVisitor::REMOVE_NODE)>> */
	private SplObjectStorage $callbacks;

	/** @var SplObjectStorage<Node, true> original nodes in an array, those can be removed */
	private SplObjectStorage $removable;

	private int $appliedCount = 0;

	private bool $failed = false;

	/**
	 * @param non-empty-list<NodeFix> $fixes
	 */
	public function __construct(array $fixes)
	{
		$this->callbacks = new SplObjectStorage();
		$this->removable = new SplObjectStorage();
		foreach ($fixes as $fix) {
			$callbacks = $this->callbacks->contains($fix->node) ? $this->callbacks[$fix->node] : [];
			$callbacks[] = $fix->callback;
			$this->callbacks[$fix->node] = $callbacks;
		}
	}

	/**
	 * CloningVisitor has just cloned $node, its subnodes are still the original nodes.
	 */
	#[Override]
	public function enterNode(Node $node): ?Node
	{
		foreach ($node->getSubNodeNames() as $subNodeName) {
			$subNode = $node->$subNodeName;
			if (!is_array($subNode)) {
				continue;
			}

			foreach ($subNode as $item) {
				if (!$item instanceof Node || !$this->callbacks->contains($item)) {
					continue;
				}

				$this->removable[$item] = true;
			}
		}

		return null;
	}

	/**
	 * The replacement happens in leaveNode() so that the callable receives a node
	 * whose children have already been cloned and fixed.
	 */
	#[Override]
	public function leaveNode(Node $node): Node|int|null
	{
		$origNode = $node->getAttribute('origNode');
		if (!$origNode instanceof Node || !$this->callbacks->contains($origNode)) {
			return null;
		}

		$this->appliedCount++;
		$newNode = $node;
		foreach ($this->callbacks[$origNode] as $callback) {
			$newNode = $callback($newNode);
			if ($newNode === NodeVisitor::REMOVE_NODE) {
				if (!$this->removable->contains($origNode)) {
					$this->failed = true;
					return null;
				}

				return NodeVisitor::REMOVE_NODE;
			}

			if ($newNode instanceof VirtualNode) {
				throw new ShouldNotHappenException('Cannot print VirtualNode.');
			}
		}

		return $newNode;
	}

	/**
	 * Whether every node was found and fixed.
	 */
	public function isComplete(): bool
	{
		return !$this->failed && $this->appliedCount === count($this->callbacks);
	}

}
