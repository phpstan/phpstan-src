<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<PropertyAssignNode>
 */
final class AccessStaticPropertiesInAssignRule implements Rule
{

	public function __construct(private AccessStaticPropertiesRule $accessStaticPropertiesRule)
	{
	}

	public function getNodeType(): string
	{
		return PropertyAssignNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->getPropertyFetch() instanceof Node\Expr\StaticPropertyFetch) {
			return [];
		}

		if ($node->isAssignOp()) {
			return [];
		}

		return $this->accessStaticPropertiesRule->processNode($node->getPropertyFetch(), $scope);
	}

}
