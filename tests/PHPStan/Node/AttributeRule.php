<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

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

	public function processNode(Node $node, Scope $scope): array
	{
		return [
			RuleErrorBuilder::message(self::ERROR_MESSAGE)
				->identifier('tests.attribute')
				->build(),
		];
	}

}
