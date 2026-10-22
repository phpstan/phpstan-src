<?php declare(strict_types = 1);

namespace PHPStan\Rules\Keywords;

use PhpParser\Node;
use PhpParser\Node\Stmt\Goto_;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Parser\GotoLabelVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements Rule<Goto_>
 */
#[RegisteredRule(level: 0)]
final class GotoUndefinedLabelRule implements Rule
{

	public function getNodeType(): string
	{
		return Goto_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->getAttribute(GotoLabelVisitor::GOTO_LABEL_UNDEFINED_ATTRIBUTE) !== true) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				"Goto to undefined label '%s'.",
				$node->name->toString(),
			))
				->nonIgnorable()
				->identifier('goto.labelUndefined')
				->build(),
		];
	}

}
