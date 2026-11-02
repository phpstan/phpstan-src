<?php declare(strict_types = 1);

namespace AttributeServicesFixtures;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\DependencyInjection\RegisteredCollector;

/**
 * @implements Collector<Node\Expr\New_, string>
 */
#[RegisteredCollector(level: 3)]
final class DiscoveredCollector implements Collector
{

	public function getNodeType(): string
	{
		return Node\Expr\New_::class;
	}

	public function processNode(Node $node, Scope $scope): ?string
	{
		return null;
	}

}
