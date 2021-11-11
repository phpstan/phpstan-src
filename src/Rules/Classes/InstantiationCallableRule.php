<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InstantiationCallableNode;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements \PHPStan\Rules\Rule<InstantiationCallableNode>
 */
class InstantiationCallableRule implements \PHPStan\Rules\Rule
{

	public function getNodeType(): string
	{
		return InstantiationCallableNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return [
			RuleErrorBuilder::message('Cannot create callable from the new operator.')->nonIgnorable()->build(),
		];
	}

}
