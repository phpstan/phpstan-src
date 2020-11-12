<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\ArrayDimFetch>
 */
class OffsetAccessWithoutDimForReadingRule implements \PHPStan\Rules\Rule
{

	public function getNodeType(): string
	{
		return \PhpParser\Node\Expr\ArrayDimFetch::class;
	}

	public function processNode(\PhpParser\Node $node, Scope $scope): array
	{
		if ($scope->isInExpressionAssign($node)) {
			return [];
		}

		if ($node->dim !== null) {
			return [];
		}

		return [
			RuleErrorBuilder::message('Cannot use [] for reading.')->nonIgnorable()->build(),
		];
	}

}
