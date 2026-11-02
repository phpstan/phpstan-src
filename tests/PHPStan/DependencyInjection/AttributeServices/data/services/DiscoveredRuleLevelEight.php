<?php declare(strict_types = 1);

namespace AttributeServicesFixtures;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Node\Expr\New_>
 */
#[RegisteredRule(level: 8)]
final class DiscoveredRuleLevelEight implements Rule
{

	public function getNodeType(): string
	{
		return Node\Expr\New_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return [];
	}

}
