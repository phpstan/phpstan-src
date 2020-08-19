<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

/**
 * @implements Rule<Node\Stmt\Echo_>
 */
class NodeConnectingRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Stmt\Echo_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return [
			sprintf(
				'Parent: %s, previous: %s, next: %s',
				get_class($node->getAttribute('parent')),
				get_class($node->getAttribute('previous')),
				get_class($node->getAttribute('next'))
			),
		];
	}

}
