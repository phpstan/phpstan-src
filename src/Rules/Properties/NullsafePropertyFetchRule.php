<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Node\Expr\NullsafePropertyFetch>
 */
#[RegisteredRule(level: 4)]
final class NullsafePropertyFetchRule implements Rule
{

	public function __construct()
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\NullsafePropertyFetch::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$calledOnType = $scope->getScopeType($node->var);
		if (!$calledOnType->isNull()->no()) {
			return [];
		}

		if ($scope->isUndefinedExpressionAllowed($node)) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf('Using nullsafe property access on non-nullable type %s. Use -> instead.', $calledOnType->describe(VerbosityLevel::typeOnly())))
				->identifier('nullsafe.neverNull')
				->build(),
		];
	}

}
