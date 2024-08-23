<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Node\AnonymousClassNode;
use function count;

final class AnonymousClassVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_LINE_INDEX = 'anonymousClassLineIndex';

	/** @var array<int, non-empty-list<AnonymousClassNode>> */
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

		$node = AnonymousClassNode::createFromClassNode($node);
		$node->setAttribute('anonymousClass', true); // We keep this for backward compatibility
		$this->nodesPerLine[$node->getStartLine()][] = $node;

		return $node;
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
