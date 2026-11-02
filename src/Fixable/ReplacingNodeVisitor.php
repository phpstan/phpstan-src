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
		if (!$origNode instanceof Node) {
			return null;
		}

		if (!$this->callbacks->contains($origNode)) {
			// a statement is invalid without items, remove it when a fix removed all of them
			if (self::getItems($node) === [] && self::getItems($origNode) !== []) {
				return NodeVisitor::REMOVE_NODE;
			}

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

	/**
	 * @return array<Node>|null
	 */
	private static function getItems(Node $node): ?array
	{
		if ($node instanceof Node\Stmt\ClassConst || $node instanceof Node\Stmt\Const_) {
			return $node->consts;
		}
		if ($node instanceof Node\Stmt\Property) {
			return $node->props;
		}
		if ($node instanceof Node\Stmt\Use_ || $node instanceof Node\Stmt\GroupUse) {
			return $node->uses;
		}
		if ($node instanceof Node\Stmt\Global_ || $node instanceof Node\Stmt\Static_ || $node instanceof Node\Stmt\Unset_) {
			return $node->vars;
		}
		if ($node instanceof Node\Stmt\Echo_) {
			return $node->exprs;
		}

		return null;
	}

}
