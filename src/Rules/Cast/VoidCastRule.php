<?php declare(strict_types = 1);

namespace PHPStan\Rules\Cast;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Expr\Cast\Void_>
 */
#[RegisteredRule(level: 0)]
final class VoidCastRule implements Rule
{

	public function __construct()
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\Cast\Void_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($scope->isInFirstLevelStatement()) {
			return [];
		}

		return [
			RuleErrorBuilder::message('The (void) cast cannot be used within an expression.')
				->identifier('cast.void')
				->nonIgnorable()
				->build(),
		];
	}

}
