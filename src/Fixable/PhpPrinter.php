<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use Override;
use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard;
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
		$last = $nodes[count($nodes) - 1];

		$trailingComma = $last->getAttribute(self::FUNC_ARGS_TRAILING_COMMA_ATTRIBUTE);
		if ($trailingComma === false) {
			$result = rtrim($result, ',');
		}

		return $result;
	}

}
