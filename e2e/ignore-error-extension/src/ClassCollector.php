<?php

declare(strict_types = 1);

namespace App;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * @implements Collector<Node\Stmt\Class_, array{string, int}>
 */
final class ClassCollector implements Collector
{
	public function getNodeType(): string
	{
		return Node\Stmt\Class_::class;
	}

	public function processNode(Node $node, Scope $scope) : ?array
	{
		if ($node->name === null) {
			return null;
		}

		return [$node->name->name, $node->getStartLine()];
	}
}
