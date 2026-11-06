<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Node\VirtualNode;
use PHPStan\Rules\NodeFix;
use PHPStan\ShouldNotHappenException;
use function array_key_exists;
use function count;
use function get_class;
use function is_array;
use function spl_object_id;

/**
 * Applies the NodeFixes of one error to the nodes of one file. Runs after CloningVisitor in the same traversal.
 *
 * Nodes are matched by their class and position in the file, not by identity, so a rule can take them
 * from any parse of the same file contents. A CollectedDataNode rule parses files itself,
 * and the parser cache does not keep the parsed nodes of every file until the fixes are made.
 */
final class ReplacingNodeVisitor extends NodeVisitorAbstract
{

	/** @var array<string, list<callable(Node): (Node|NodeVisitor::REMOVE_NODE)>> */
	private array $callbacks = [];

	/** @var array<string, true> nodes in an array, those can be removed */
	private array $removable = [];

	private int $appliedCount = 0;

	private bool $failed = false;

	/** @var list<Node> */
	private array $fixedNodes = [];

	/**
	 * @param non-empty-list<NodeFix> $fixes
	 */
	public function __construct(array $fixes)
	{
		foreach ($fixes as $fix) {
			// a node without a position cannot be found, it stays in $callbacks so that the fix is not complete
			$this->callbacks[self::getKey($fix->node) ?? '#' . spl_object_id($fix->node)][] = $fix->callback;
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
				if (!$item instanceof Node) {
					continue;
				}

				$key = self::getKey($item);
				if ($key === null || !array_key_exists($key, $this->callbacks)) {
					continue;
				}

				$this->removable[$key] = true;
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

		$key = self::getKey($origNode);
		if ($key === null || !array_key_exists($key, $this->callbacks)) {
			// a statement is invalid without items, remove it when a fix removed all of them
			if (self::getItems($node) === [] && self::getItems($origNode) !== []) {
				return NodeVisitor::REMOVE_NODE;
			}

			return null;
		}

		$this->appliedCount++;
		$newNode = $node;
		foreach ($this->callbacks[$key] as $callback) {
			$newNode = $callback($newNode);
			if ($newNode === NodeVisitor::REMOVE_NODE) {
				if (!array_key_exists($key, $this->removable)) {
					$this->failed = true;
					return null;
				}

				return NodeVisitor::REMOVE_NODE;
			}

			if ($newNode instanceof VirtualNode) {
				throw new ShouldNotHappenException('Cannot print VirtualNode.');
			}
		}

		$this->fixedNodes[] = $newNode;

		return $newNode;
	}

	/**
	 * Nodes returned by the fixes. A fix changes only these nodes and their children.
	 *
	 * @return list<Node>
	 */
	public function getFixedNodes(): array
	{
		return $this->fixedNodes;
	}

	/**
	 * Whether every node was found and fixed.
	 */
	public function isComplete(): bool
	{
		return !$this->failed && $this->appliedCount === count($this->callbacks);
	}

	private static function getKey(Node $node): ?string
	{
		$start = $node->getStartFilePos();
		if ($start < 0) {
			return null;
		}

		return get_class($node) . ':' . $start . ':' . $node->getEndFilePos();
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
