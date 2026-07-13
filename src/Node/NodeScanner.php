<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use function is_array;

/**
 * Recursive AST queries over php-parser node graphs.
 *
 * When the phpstan_turbo extension is loaded, this class is shadowed by a stub
 * extending the extension's native implementation.
 */
final class NodeScanner
{

	public static function nodeIsOrContainsYield(Node $node): bool
	{
		if ($node instanceof Node\Expr\Yield_) {
			return true;
		}

		if ($node instanceof Node\Expr\YieldFrom) {
			return true;
		}

		foreach ($node->getSubNodeNames() as $nodeName) {
			$nodeProperty = $node->$nodeName;

			if ($nodeProperty instanceof Node && self::nodeIsOrContainsYield($nodeProperty)) {
				return true;
			}

			if (!is_array($nodeProperty)) {
				continue;
			}

			foreach ($nodeProperty as $nodePropertyArrayItem) {
				if ($nodePropertyArrayItem instanceof Node && self::nodeIsOrContainsYield($nodePropertyArrayItem)) {
					return true;
				}
			}
		}

		return false;
	}

}
