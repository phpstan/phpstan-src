<?php declare(strict_types = 1);

namespace AttributeServicesFixtures;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Conditional;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Node\Expr\New_>
 */
#[Conditional(
	attribute: ['olvlvl\ComposerAttributeCollector\NewExpression' => [RegisteredRule::class, ['level' => 3]]],
	if: ['%reportUnmatchedIgnoredErrors%', 'not(%reportUnmatchedIgnoredErrors%)'],
)]
final class DiscoveredConditionalRuleNotMet implements Rule
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
