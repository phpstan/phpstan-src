<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Node\VirtualNode;
use PHPStan\ShouldNotHappenException;
use function array_values;
use function spl_object_id;

final class BatchReplacingNodeVisitor extends NodeVisitorAbstract
{

	/** @var array<int, callable(Node): Node> */
	private array $fixesByOriginalNodeId = [];

	/** @var array<int, Node> */
	private array $appliedOriginalNodes = [];

	/**
	 * @param iterable<array{node: Node, callable: callable(Node): Node}> $fixes
	 */
	public function __construct(iterable $fixes)
	{
		foreach ($fixes as $fix) {
			$id = spl_object_id($fix['node']);
			if (isset($this->fixesByOriginalNodeId[$id])) {
				continue;
			}
			$this->fixesByOriginalNodeId[$id] = $fix['callable'];
		}
	}

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		$origNode = $node->getAttribute('origNode');
		if ($origNode === null) {
			return null;
		}
		$id = spl_object_id($origNode);
		if (!isset($this->fixesByOriginalNodeId[$id])) {
			return null;
		}

		if (isset($this->appliedOriginalNodes[$id])) {
			return null;
		}
		$this->appliedOriginalNodes[$id] = $origNode;

		$callable = $this->fixesByOriginalNodeId[$id];
		$newNode = $callable($node);
		if ($newNode instanceof VirtualNode) {
			throw new ShouldNotHappenException('Cannot print VirtualNode.');
		}

		return $newNode;
	}

	/**
	 * @return list<Node>
	 */
	public function getAppliedOriginalNodes(): array
	{
		return array_values($this->appliedOriginalNodes);
	}

}
