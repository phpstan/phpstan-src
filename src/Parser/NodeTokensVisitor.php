<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\Node\Expr\Ternary;
use PhpParser\NodeVisitorAbstract;

class NodeTokensVisitor extends NodeVisitorAbstract
{

	/** @var mixed[] */
	private array $tokens;

	/**
	 * @param mixed[] $tokens
	 */
	public function __construct(array $tokens)
	{
		$this->tokens = $tokens;
	}

	/**
	 * @param Node $node
	 * @return null
	 */
	public function enterNode(Node $node)
	{
		$parent = $node->getAttribute('parent');
		if (
			$node instanceof Ternary
			&& $parent instanceof Ternary
		) {
			$myStart = $node->getAttribute('startTokenPos');
			$myEnd = $node->getAttribute('endTokenPos');
			[$immediatePredecessor, $immediateSuccessor] = $this->getImmediates($parent, $myStart, $myEnd);
			if ($immediatePredecessor !== null) {
				$tokensBefore = [];
				for ($i = $immediatePredecessor; $i < $myStart; $i++) {
					$tokensBefore[] = $this->tokens[$i];
				}
				$node->setAttribute('tokensBefore', $tokensBefore);
			}

			if ($immediateSuccessor !== null) {
				$tokensAfter = [];
				for ($i = $myEnd + 1; $i <= $immediateSuccessor; $i++) {
					$tokensAfter[] = $this->tokens[$i];
				}
				$node->setAttribute('tokensAfter', $tokensAfter);
			}
		}

		return null;
	}

	/**
	 * @param Node $parent
	 * @param int $myStart
	 * @param int $myEnd
	 * @return array{int|null, int|null}
	 */
	private function getImmediates(Node $parent, int $myStart, int $myEnd): array
	{
		$immediatePredecessor = null;
		$immediateSuccessor = null;

		/** @var NodeList $parentChildList */
		$parentChildList = $parent->getAttribute('children');
		while ($parentChildList !== null) {
			$parentChild = $parentChildList->getNode();

			/** @var int $childEnd */
			$childEnd = $parentChild->getAttribute('endTokenPos');
			if ($childEnd < $myStart) {
				$immediatePredecessor = $childEnd + 1;
				$parentChildList = $parentChildList->getNext();
				continue;
			}

			/** @var int $childStart */
			$childStart = $parentChild->getAttribute('startTokenPos');
			if ($childStart > $myEnd) {
				$immediateSuccessor = $childStart - 1;
				break;
			}

			if ($childStart + 1 < $myStart && $childEnd > $myEnd) {
				return [$childStart + 1, $childEnd];
			}

			$parentChildList = $parentChildList->getNext();
		}

		if ($immediatePredecessor === null || $immediateSuccessor === null) {
			$parentParent = $parent->getAttribute('parent');
			if ($parentParent !== null) {
				[$parentPredecessor, $parentSucessor] = $this->getImmediates($parentParent, $myStart, $myEnd);

				if ($immediatePredecessor === null) {
					$immediatePredecessor = $parentPredecessor;
				}
				if ($immediateSuccessor === null) {
					$immediateSuccessor = $parentSucessor;
				}
			}
		}

		return [$immediatePredecessor, $immediateSuccessor];
	}

}
