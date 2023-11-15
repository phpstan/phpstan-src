<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/** @implements Rule<Node\Scalar\MagicConst\Class_> */
class MagicClassConstantRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Scalar\MagicConst\Class_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($scope->isInClass()) {
			return [];
		}

		return [
			RuleErrorBuilder::message(
				sprintf('Magic constant %s cannot be used outside a class.', $node->getName()),
			)->build(),
		];
	}

}
