<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\MatchExpressionNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;

/**
 * This rule exists solely as a regression test for the match expression
 * callback scope fix. It reports the type of the match condition as seen
 * from the scope passed to the MatchExpressionNode callback.
 *
 * Without the fix, exhaustive match expressions pass the merged arm body
 * scope to the callback, which contains narrowed types from arm conditions
 * instead of the original match condition type.
 *
 * @implements Rule<MatchExpressionNode>
 */
final class MatchCallbackScopeRegressionRule implements Rule
{

	public function getNodeType(): string
	{
		return MatchExpressionNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return [
			RuleErrorBuilder::message(
				$scope->getType($node->getCondition())->describe(VerbosityLevel::precise()),
			)->identifier('test.matchCallbackScope')->build(),
		];
	}

}
