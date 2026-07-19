<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PHPStan\Turbo\ShadowedByTurboExtension;
use function is_array;

/**
 * Recursive AST queries over php-parser node graphs.
 */
#[ShadowedByTurboExtension(
	turboClass: 'PHPStanTurbo\NodeScanner',
	implementation: __DIR__ . '/../../turbo-ext/src/NodeScanner.cpp',
)]
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
