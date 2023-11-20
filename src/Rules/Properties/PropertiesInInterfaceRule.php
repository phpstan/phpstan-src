<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<ClassPropertyNode>
 */
class PropertiesInInterfaceRule implements Rule
{

	public function getNodeType(): string
	{
		return ClassPropertyNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->getClassReflection()->isInterface()) {
			return [];
		}

		return [
			RuleErrorBuilder::message('Interfaces may not include properties.')
				->nonIgnorable()
				->identifier('property.inInterface')
				->build(),
		];
	}

}
