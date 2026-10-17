<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\UnreachableStatementNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Turbo\ShadowedByTurboExtension;

/**
 * @implements Rule<UnreachableStatementNode>
 */
#[RegisteredRule(level: 4)]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/UnreachableStatementRule.cpp')]
final class UnreachableStatementRule implements Rule
{

	public function getNodeType(): string
	{
		return UnreachableStatementNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return [
			RuleErrorBuilder::message('Unreachable statement - code above always terminates.')
				->identifier('deadCode.unreachable')
				->build(),
		];
	}

}
