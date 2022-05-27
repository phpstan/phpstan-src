<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Node\AttributeGroup>
 */
class AttributeGroupRule implements Rule
{

	public const ERROR_MESSAGE = 'Found AttributeGroup';

	public function getNodeType(): string
	{
		return Node\AttributeGroup::class;
	}

	/**
	 * @param Node\AttributeGroup $node
	 * @return string[]
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		return [self::ERROR_MESSAGE];
	}

}
