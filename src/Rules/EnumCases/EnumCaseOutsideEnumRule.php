<?php declare(strict_types = 1);

namespace PHPStan\Rules\EnumCases;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Stmt\EnumCase>
 */
#[RegisteredRule(level: 0)]
final class EnumCaseOutsideEnumRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Stmt\EnumCase::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($scope->isInTrait()) {
			return [
				RuleErrorBuilder::message('Enum case can only be used in enums.')
					->nonIgnorable()
					->identifier('enum.caseOutsideOfEnum')
					->build(),
			];
		}

		if (!$scope->isInClass()) {
			return [];
		}

		$classReflection = $scope->getClassReflection();
		if ($classReflection->isEnum()) {
			return [];
		}

		return [
			RuleErrorBuilder::message('Enum case can only be used in enums.')
				->nonIgnorable()
				->identifier('enum.caseOutsideOfEnum')
				->build(),
		];
	}

}
