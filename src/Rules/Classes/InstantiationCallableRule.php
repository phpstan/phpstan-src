<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InstantiationCallableNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<InstantiationCallableNode>
 */
final class InstantiationCallableRule implements Rule
{

	public function getNodeType(): string
	{
		return InstantiationCallableNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return [
			RuleErrorBuilder::message('Cannot create callable from the new operator.')
				->identifier('callable.notSupported')
				->nonIgnorable()
				->build(),
		];
	}

}
