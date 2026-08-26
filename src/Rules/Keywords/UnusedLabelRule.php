<?php declare(strict_types = 1);

namespace PHPStan\Rules\Keywords;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Parser\GotoLabelVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\Label>
 */
#[RegisteredRule(level: 4, enabledBy: '%featureToggles.unusedLabel%')]
final class UnusedLabelRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Stmt\Label::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->getAttribute(GotoLabelVisitor::LABEL_IS_USED_ATTRIBUTE) !== false) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				"Label '%s' is unused.",
				$node->name->toString(),
			))
				->identifier('label.unused')
				->build(),
		];
	}

}
