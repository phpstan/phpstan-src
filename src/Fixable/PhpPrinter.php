<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use Override;
use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard;
use function array_last;
use function count;
use function rtrim;

final class PhpPrinter extends Standard
{

	public const TAB_WIDTH = 4;
	public const FUNC_ARGS_TRAILING_COMMA_ATTRIBUTE = 'trailing_comma';

	/**
	 * @param Node[] $nodes
	 */
	#[Override]
	protected function pCommaSeparated(array $nodes): string
	{
		$result = parent::pCommaSeparated($nodes);
		if (count($nodes) === 0) {
			return $result;
		}
		$last = array_last($nodes);

		$trailingComma = $last->getAttribute(self::FUNC_ARGS_TRAILING_COMMA_ATTRIBUTE);
		if ($trailingComma === false) {
			$result = rtrim($result, ',');
		}

		return $result;
	}

	/**
	 * A node that is its own origNode was not cloned, so a fix did not change it
	 * (see PathCloningVisitor). Its original code is copied as it is, without
	 * printing its children one by one.
	 */
	#[Override]
	protected function p(
		Node $node,
		int $precedence = self::MAX_PRECEDENCE,
		int $lhsPrecedence = self::MAX_PRECEDENCE,
		bool $parentFormatPreserved = false,
	): string
	{
		if (
			$parentFormatPreserved
			&& $this->origTokens !== null
			&& $node->getAttribute('origNode') === $node
		) {
			$startPos = $node->getStartTokenPos();
			$endPos = $node->getEndTokenPos();
			if ($startPos >= 0 && $endPos >= 0) {
				$indentAdjustment = $this->indentLevel - $this->origTokens->getIndentationBefore($startPos);

				return $this->origTokens->getTokenCode($startPos, $endPos + 1, $indentAdjustment);
			}
		}

		return parent::p($node, $precedence, $lhsPrecedence, $parentFormatPreserved);
	}

}
