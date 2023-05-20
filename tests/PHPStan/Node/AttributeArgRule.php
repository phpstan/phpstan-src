<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

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

	public function processNode(Node $node, Scope $scope): array
	{
		return [
			RuleErrorBuilder::message(self::ERROR_MESSAGE)
				->identifier('tests.attributeArg')
				->build(),
		];
	}

}
