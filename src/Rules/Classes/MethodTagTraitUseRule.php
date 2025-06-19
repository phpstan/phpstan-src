<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\InTraitNode;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<InTraitNode>
 */
#[RegisteredRule(level: 2)]
final class MethodTagTraitUseRule implements Rule
{

	public function __construct(private MethodTagCheck $check)
	{
	}

	public function getNodeType(): string
	{
		return InTraitNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return $this->check->checkInTraitUseContext(
			$scope,
			$node->getTraitReflection(),
			$node->getImplementingClassReflection(),
			$node->getOriginalNode(),
		);
	}

}
