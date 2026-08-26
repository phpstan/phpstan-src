<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\AutowiredServices;

use PhpParser\Node;
use PhpParser\Node\Stmt\Echo_;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\DependencyInjection\RegisteredCollector;

/**
 * @implements Collector<Echo_, string>
 */
#[RegisteredCollector(level: 0)]
final class TestedDiscoveredCollector implements Collector
{

	public function getNodeType(): string
	{
		return Echo_::class;
	}

	public function processNode(Node $node, Scope $scope)
	{
		return null;
	}

}
