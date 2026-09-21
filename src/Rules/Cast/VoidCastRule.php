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

	public function getNodeType(): string
	{
		return Node\Expr\Cast\Void_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errors = [];
		if (!$scope->getPhpVersion()->supportsVoidCast()->yes()) {
			$errors[] = RuleErrorBuilder::message('The (void) cast is supported only on PHP 8.5 and later.')
				->identifier('cast.voidNotSupported')
				->nonIgnorable()
				->build();
		}

		if ($scope->isInFirstLevelStatement()) {
			return $errors;
		}

		$errors[] = RuleErrorBuilder::message('The (void) cast cannot be used within an expression.')
			->identifier('cast.void')
			->nonIgnorable()
			->build();

		return $errors;
	}

}
