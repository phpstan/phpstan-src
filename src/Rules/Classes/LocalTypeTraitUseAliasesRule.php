<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InTraitNode;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<InTraitNode>
 */
final class LocalTypeTraitUseAliasesRule implements Rule
{

	public function __construct(private LocalTypeAliasesCheck $check)
	{
	}

	public function getNodeType(): string
	{
		return InTraitNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return $this->check->checkInTraitUseContext(
			$node->getTraitReflection(),
			$node->getImplementingClassReflection(),
			$node->getOriginalNode(),
		);
	}

}
