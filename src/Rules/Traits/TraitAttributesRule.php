<?php declare(strict_types = 1);

namespace PHPStan\Rules\Traits;

use Attribute;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InTraitNode;
use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;

/**
 * @implements Rule<InTraitNode>
 */
final class TraitAttributesRule implements Rule
{

	public function __construct(
		private AttributesCheck $attributesCheck,
	)
	{
	}

	public function getNodeType(): string
	{
		return InTraitNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$originalNode = $node->getOriginalNode();
		$errors = $this->attributesCheck->check(
			$scope,
			$originalNode->attrGroups,
			Attribute::TARGET_CLASS,
			'class',
		);

		if (count($node->getTraitReflection()->getNativeReflection()->getAttributes('AllowDynamicProperties')) > 0) {
			$errors[] = RuleErrorBuilder::message('Attribute class AllowDynamicProperties cannot be used with trait.')
				->identifier('trait.allowDynamicProperties')
				->nonIgnorable()
				->build();
		}

		return $errors;
	}

}
