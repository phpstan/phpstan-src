<?php declare(strict_types = 1);

namespace PHPStan\Analyser\CollectedDataReuse;

use PhpParser\Node;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * @implements Collector<Node, string>
 */
final class FirstCollector implements Collector
{

	public function getNodeType(): string
	{
		return Node::class;
	}

	public function processNode(Node $node, Scope&DependencyTracker $scope)
	{
		return null;
	}

}
