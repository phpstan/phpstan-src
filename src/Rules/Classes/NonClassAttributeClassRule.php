<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Stmt\ClassLike>
 */
class NonClassAttributeClassRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Stmt\ClassLike::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node instanceof Node\Stmt\Class_) {
			return [];
		}

		foreach ($node->attrGroups as $attrGroup) {
			foreach ($attrGroup->attrs as $attr) {
				$name = $attr->name->toLowerString();
				if ($name === 'attribute') {
					return [
						RuleErrorBuilder::message(
							sprintf(
								'%s cannot be an Attribute class.',
								$node instanceof Node\Stmt\Trait_ ? 'Trait' : 'Interface'
							)
						)->line($attr->getLine())->build(),
					];
				}
			}
		}

		return [];
	}

}
