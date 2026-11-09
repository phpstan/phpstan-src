<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use function array_pop;
use function get_class;

/**
 * Clones only the nodes that a fix can change: the fixed nodes with all their
 * children, and the parents of the fixed nodes. Each other node stays the
 * original object and gets itself as its origNode attribute, so that
 * PhpPrinter can copy its original code without printing its children.
 *
 * Call restoreOriginalNodes() after printing, because the original nodes
 * belong to the analysed AST.
 */
final class PathCloningVisitor extends NodeVisitorAbstract
{

	/** @var list<array{int, int}> */
	private array $targetRanges;

	/** @var array<string, true> */
	private array $targetKeys = [];

	private int $insideTarget = 0;

	/** @var list<bool> */
	private array $enteredTargets = [];

	/** @var list<Node> */
	private array $sharedNodes = [];

	/**
	 * @param non-empty-list<Node> $targets
	 */
	public function __construct(array $targets)
	{
		$ranges = [];
		foreach ($targets as $target) {
			$ranges[] = [$target->getStartFilePos(), $target->getEndFilePos()];
			$this->targetKeys[self::getKey($target)] = true;
		}
		$this->targetRanges = $ranges;
	}

	/**
	 * @param non-empty-list<Node> $targets
	 */
	public static function canFind(array $targets): bool
	{
		foreach ($targets as $target) {
			if ($target->getStartFilePos() < 0 || $target->getEndFilePos() < 0) {
				return false;
			}
		}

		return true;
	}

	#[Override]
	public function enterNode(Node $node): Node|int
	{
		if ($this->insideTarget > 0) {
			$this->enteredTargets[] = false;
			return $this->cloneNode($node);
		}

		if (!$this->containsTarget($node)) {
			$this->enteredTargets[] = false;
			$node->setAttribute('origNode', $node);
			$this->sharedNodes[] = $node;
			return NodeVisitor::DONT_TRAVERSE_CHILDREN;
		}

		$isTarget = isset($this->targetKeys[self::getKey($node)]);
		$this->enteredTargets[] = $isTarget;
		if ($isTarget) {
			$this->insideTarget++;
		}

		return $this->cloneNode($node);
	}

	#[Override]
	public function leaveNode(Node $node): ?Node
	{
		if (array_pop($this->enteredTargets) === true) {
			$this->insideTarget--;
		}

		return null;
	}

	/**
	 * Removes the origNode attribute from the original nodes again.
	 */
	public function restoreOriginalNodes(): void
	{
		foreach ($this->sharedNodes as $node) {
			$attributes = $node->getAttributes();
			unset($attributes['origNode']);
			$node->setAttributes($attributes);
		}
		$this->sharedNodes = [];
	}

	private function containsTarget(Node $node): bool
	{
		$start = $node->getStartFilePos();
		$end = $node->getEndFilePos();
		if ($start < 0 || $end < 0) {
			return true;
		}

		foreach ($this->targetRanges as [$targetStart, $targetEnd]) {
			if ($start <= $targetStart && $targetEnd <= $end) {
				return true;
			}
		}

		return false;
	}

	private function cloneNode(Node $node): Node
	{
		$clone = clone $node;
		$clone->setAttribute('origNode', $node);

		return $clone;
	}

	private static function getKey(Node $node): string
	{
		return get_class($node) . ':' . $node->getStartFilePos() . ':' . $node->getEndFilePos();
	}

}
