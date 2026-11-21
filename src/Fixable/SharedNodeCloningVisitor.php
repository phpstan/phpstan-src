<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use function array_pop;

/**
 * Clones every node of an AST that PathCloningVisitor produced, so that later
 * visitors can change nodes in place without changing the analysed AST.
 *
 * A clone keeps the origNode of the node it copies. In a subtree that
 * PathCloningVisitor shared, the nodes have no origNode yet, so each clone
 * gets the original node as its origNode, the same as CloningVisitor does.
 */
final class SharedNodeCloningVisitor extends NodeVisitorAbstract
{

	private int $insideShared = 0;

	/** @var list<bool> */
	private array $enteredShared = [];

	#[Override]
	public function enterNode(Node $node): Node
	{
		$origNode = $node->getAttribute('origNode');
		$isShared = $origNode === $node;
		$this->enteredShared[] = $isShared;
		if ($isShared) {
			$this->insideShared++;
		}

		$clone = clone $node;
		if ($this->insideShared > 0) {
			$clone->setAttribute('origNode', $node);
		}

		return $clone;
	}

	#[Override]
	public function leaveNode(Node $node): ?Node
	{
		if (array_pop($this->enteredShared) === true) {
			$this->insideShared--;
		}

		return null;
	}

}
