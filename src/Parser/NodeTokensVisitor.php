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
			if ($myStart === null || $myEnd === null) {
				return null;
			}
			[$immediatePredecessor, $immediateSuccessor] = $this->getImmediates($parent, $myStart, $myEnd);
			if ($immediatePredecessor !== null) {
				$tokensBefore = [];
				for ($i = $immediatePredecessor->getAttribute('endTokenPos') + 1; $i < $myStart; $i++) {
					$tokensBefore[] = $this->tokens[$i];
				}
				$node->setAttribute('tokensBefore', $tokensBefore);
			}

			if ($immediateSuccessor !== null) {
				$tokensAfter = [];
				for ($i = $myEnd + 1; $i < $immediateSuccessor->getAttribute('startTokenPos'); $i++) {
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
	 * @return array{Node|null, Node|null}
	 */
	private function getImmediates(Node $parent, int $myStart, int $myEnd): array
	{
		$immediatePredecessor = null;
		$immediateSuccessor = null;
		foreach ($parent->getAttribute('children', []) as $parentChild) {
			$childEnd = $parentChild->getAttribute('endTokenPos');
			if ($childEnd < $myStart) {
				$immediatePredecessor = $parentChild;
				continue;
			}

			$childStart = $parentChild->getAttribute('startTokenPos');
			if ($childStart > $myEnd) {
				$immediateSuccessor = $parentChild;
				break;
			}
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
