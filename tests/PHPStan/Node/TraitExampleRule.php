<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Node\Stmt\Return_>
 */
class TraitExampleRule implements Rule
{

	public const ERROR_MESSAGE = 'Found return node';

	public function getNodeType(): string
	{
		return Node\Stmt\Return_::class;
	}

	/**
	 * @param Node\Stmt\Return_ $node
	 * @return string[]
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		return [self::ERROR_MESSAGE];
	}

}
