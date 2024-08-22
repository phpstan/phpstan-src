<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use function count;

final class AnonymousClassVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_ANONYMOUS_CLASS = 'anonymousClass';
	public const ATTRIBUTE_LINE_INDEX = 'anonymousClassLineIndex';

	/** @var array<int, non-empty-list<Node\Stmt\Class_>> */
	private array $nodesPerLine = [];

	public function beforeTraverse(array $nodes): ?array
	{
		$this->nodesPerLine = [];
		return null;
	}

	public function enterNode(Node $node): ?Node
	{
		if (!$node instanceof Node\Stmt\Class_ || !$node->isAnonymous()) {
			return null;
		}

		$node->setAttribute(self::ATTRIBUTE_ANONYMOUS_CLASS, true);
		$this->nodesPerLine[$node->getStartLine()][] = $node;

		return null;
	}

	public function afterTraverse(array $nodes): ?array
	{
		foreach ($this->nodesPerLine as $nodesOnLine) {
			if (count($nodesOnLine) === 1) {
				continue;
			}
			for ($i = 0; $i < count($nodesOnLine); $i++) {
				$nodesOnLine[$i]->setAttribute(self::ATTRIBUTE_LINE_INDEX, $i + 1);
			}
		}

		$this->nodesPerLine = [];
		return null;
	}

}
