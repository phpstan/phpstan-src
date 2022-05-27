<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Node\Arg>
 */
class AttributeArgRule implements Rule
{

	public const ERROR_MESSAGE = 'Found Arg';

	public function getNodeType(): string
	{
		return Node\Arg::class;
	}

	/**
	 * @param Node\Arg $node
	 * @return string[]
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		return [self::ERROR_MESSAGE];
	}

}
