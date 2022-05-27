<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Node\Attribute>
 */
class AttributeRule implements Rule
{

	public const ERROR_MESSAGE = 'Found Attribute';

	public function getNodeType(): string
	{
		return Node\Attribute::class;
	}

	/**
	 * @param Node\Attribute $node
	 * @return string[]
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		return [self::ERROR_MESSAGE];
	}

}
