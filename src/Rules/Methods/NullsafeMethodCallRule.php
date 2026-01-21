<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Node\Expr\NullsafeMethodCall>
 */
#[RegisteredRule(level: 4)]
final class NullsafeMethodCallRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Expr\NullsafeMethodCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$calledOnType = $scope->getScopeType($node->var);
		if (!$calledOnType->isNull()->no()) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf('Using nullsafe method call on non-nullable type %s. Use -> instead.', $calledOnType->describe(VerbosityLevel::typeOnly())))
				->identifier('nullsafe.neverNull')
				->build(),
		];
	}

}
