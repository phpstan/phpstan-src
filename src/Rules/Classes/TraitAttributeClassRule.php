<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Stmt\Trait_>
 */
class TraitAttributeClassRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Stmt\Trait_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		foreach ($node->attrGroups as $attrGroup) {
			foreach ($attrGroup->attrs as $attr) {
				$name = $attr->name->toLowerString();
				if ($name === 'attribute') {
					return [
						RuleErrorBuilder::message('Trait cannot be an Attribute class.')
							->identifier('attribute.trait')
							->build(),
					];
				}
			}
		}

		return [];
	}

}
