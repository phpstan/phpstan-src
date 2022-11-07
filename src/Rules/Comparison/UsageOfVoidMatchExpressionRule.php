<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Expr\Match_>
 */
class UsageOfVoidMatchExpressionRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Expr\Match_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$matchResultType = $scope->getType($node);
		if (
			$matchResultType->isVoid()->yes()
			&& !$scope->isInFirstLevelStatement()
		) {
			return [RuleErrorBuilder::message('Result of match expression (void) is used.')->build()];
		}

		return [];
	}

}
