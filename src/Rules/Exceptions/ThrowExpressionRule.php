<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Parser\StandaloneThrowExprVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Expr\Throw_>
 */
#[RegisteredRule(level: 0)]
final class ThrowExpressionRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Expr\Throw_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($scope->getPhpVersion()->supportsThrowExpression()->yes()) {
			return [];
		}

		if ($node->getAttribute(StandaloneThrowExprVisitor::ATTRIBUTE_NAME) === true) {
			return [];
		}

		return [
			RuleErrorBuilder::message('Throw expression is supported only on PHP 8.0 and later.')->nonIgnorable()
				->identifier('throw.notSupported')
				->build(),
		];
	}

}
