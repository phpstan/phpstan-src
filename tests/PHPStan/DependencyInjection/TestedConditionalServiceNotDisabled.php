<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Node>
 */
class TestedConditionalServiceNotDisabled implements Rule
{

	public function getNodeType(): string
	{
		return Node::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return [];
	}

}
