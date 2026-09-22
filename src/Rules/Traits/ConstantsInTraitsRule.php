<?php declare(strict_types = 1);

namespace PHPStan\Rules\Traits;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Stmt\ClassConst>
 */
#[RegisteredRule(level: 0)]
final class ConstantsInTraitsRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Stmt\ClassConst::class;
	}

	/**
	 * @param Node\Stmt\ClassConst $node
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		if ($scope->getPhpVersion()->supportsConstantsInTraits()->yes()) {
			return [];
		}

		if (!$scope->isInTrait()) {
			return [];
		}

		return [
			RuleErrorBuilder::message(
				'Constant is declared inside a trait but is only supported on PHP 8.2 and later.',
			)->identifier('classConstant.inTrait')->nonIgnorable()->build(),
		];
	}

}
