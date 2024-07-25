<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Stmt\Foreach_>
 */
final class DeadForeachRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Stmt\Foreach_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$iterableType = $scope->getType($node->expr);
		if ($iterableType->isIterable()->no()) {
			return [];
		}

		if (!$iterableType->isIterableAtLeastOnce()->no()) {
			return [];
		}

		return [
			RuleErrorBuilder::message('Empty array passed to foreach.')
				->identifier('foreach.emptyArray')
				->build(),
		];
	}

}
