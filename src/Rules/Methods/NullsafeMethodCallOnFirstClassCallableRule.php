<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\NullsafeMethodCallOnFirstClassCallableNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<NullsafeMethodCallOnFirstClassCallableNode>
 */
#[RegisteredRule(level: 0)]
final class NullsafeMethodCallOnFirstClassCallableRule implements Rule
{

	public function getNodeType(): string
	{
		return NullsafeMethodCallOnFirstClassCallableNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return [
			RuleErrorBuilder::message('Cannot combine nullsafe operator with Closure creation.')
				->nonIgnorable()
				->identifier('nullsafe.firstClassCallable')
				->build(),
		];
	}

}
