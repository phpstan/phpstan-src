<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Node\VirtualNode;
use PHPStan\ShouldNotHappenException;
use function array_values;
use function count;
use function spl_object_id;

final class BatchReplacingNodeVisitor extends NodeVisitorAbstract
{

	/** @var array<int, callable(Node): Node> */
	private array $fixesByOriginalNodeId = [];

	/** @var array<int, array<string, list<string|null>>> */
	private array $clustersByOriginalNodeId = [];

	/** @var array<int, Node> */
	private array $appliedOriginalNodes = [];

	/**
	 * @param iterable<array{node: Node, callable: callable(Node): Node, consumerClass?: string|null}> $fixes
	 */
	public function __construct(iterable $fixes)
	{
		$printer = new PhpPrinter();
		foreach ($fixes as $fix) {
			$id = spl_object_id($fix['node']);
			$consumerClass = $fix['consumerClass'] ?? null;

			$newNode = ($fix['callable'])($fix['node']);
			if ($newNode instanceof VirtualNode) {
				throw new ShouldNotHappenException('Cannot print VirtualNode.');
			}
			$printed = self::printNode($printer, $newNode);

			$this->clustersByOriginalNodeId[$id][$printed][] = $consumerClass;

			if (count($this->clustersByOriginalNodeId[$id]) === 1) {
				$this->fixesByOriginalNodeId[$id] = $fix['callable'];
				continue;
			}

			unset($this->fixesByOriginalNodeId[$id]);
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

	/**
	 * @return array<int, array<string, list<string|null>>>
	 */
	public function getConflictClustersByOriginalNodeId(): array
	{
		$conflicts = [];
		foreach ($this->clustersByOriginalNodeId as $id => $clusters) {
			if (count($clusters) < 2) {
				continue;
			}
			$conflicts[$id] = $clusters;
		}

		return $conflicts;
	}

	private static function printNode(PhpPrinter $printer, Node $node): string
	{
		if ($node instanceof Node\Stmt) {
			return $printer->prettyPrint([$node]);
		}
		if ($node instanceof Node\Expr) {
			return $printer->prettyPrintExpr($node);
		}
		return $printer->printSingleNode($node);
	}

}
