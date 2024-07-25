<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Expr\Match_>
 */
final class UsageOfVoidMatchExpressionRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Expr\Match_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->isInFirstLevelStatement()) {
			$matchResultType = $scope->getKeepVoidType($node);
			if ($matchResultType->isVoid()->yes()) {
				return [RuleErrorBuilder::message('Result of match expression (void) is used.')->identifier('match.void')->build()];
			}
		}

		return [];
	}

}
