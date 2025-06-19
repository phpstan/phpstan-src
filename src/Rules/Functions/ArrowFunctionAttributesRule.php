<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use Attribute;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\InArrowFunctionNode;
use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<InArrowFunctionNode>
 */
#[RegisteredRule(level: 0)]
final class ArrowFunctionAttributesRule implements Rule
{

	public function __construct(private AttributesCheck $attributesCheck)
	{
	}

	public function getNodeType(): string
	{
		return InArrowFunctionNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return $this->attributesCheck->check(
			$scope,
			$node->getOriginalNode()->attrGroups,
			Attribute::TARGET_FUNCTION,
			'function',
		);
	}

}
